<?php
require_once __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'register':
            if ($method !== 'POST') {
                jsonError('Método no permitido', 405);
            }
            handleRegister();
            break;

        case 'login':
            if ($method !== 'POST') {
                jsonError('Método no permitido', 405);
            }
            handleLogin();
            break;

        case 'refresh':
            if ($method !== 'POST') {
                jsonError('Método no permitido', 405);
            }
            handleRefresh();
            break;

        case 'logout':
            if ($method !== 'POST') {
                jsonError('Método no permitido', 405);
            }
            handleLogout();
            break;

        case 'providers':
            jsonResponse([
                'success' => true,
                'providers' => [
                    'google' => [
                        'enabled' => getenv('GOOGLE_CLIENT_ID') !== false,
                    ],
                    'facebook' => [
                        'enabled' => getenv('FACEBOOK_CLIENT_ID') !== false,
                    ],
                ],
            ]);
            break;

        case 'oauth_start':
            handleOauthStart();
            break;

        case 'oauth_callback':
            handleOauthCallback();
            break;

        default:
            jsonError('Acción no reconocida', 400);
    }
} catch (Throwable $e) {
    logIntegrationEvent('ERROR', 'Auth endpoint error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    jsonError('Error interno del servidor', 500, ['exception' => $e->getMessage()]);
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        jsonError('JSON inválido', 400);
    }
    return $data;
}

function sanitizeEmail(string $email): string
{
    return strtolower(trim($email));
}

function handleRegister(): void
{
    $data = getJsonInput();

    $email = isset($data['email']) ? sanitizeEmail($data['email']) : null;
    $password = $data['password'] ?? null;
    $fullName = $data['full_name'] ?? null;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Correo electrónico inválido', 422);
    }

    if (!$password || strlen($password) < 8) {
        jsonError('La contraseña debe tener al menos 8 caracteres', 422);
    }

    if (findIntegrationUserByEmail($email)) {
        jsonError('Ya existe un usuario con ese correo', 409);
    }

    $user = createIntegrationUser([
        'email' => $email,
        'password' => $password,
        'full_name' => $fullName,
        'status' => 'active',
        'role' => 'manager',
    ]);

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
        ],
    ], 201);
}

function handleLogin(): void
{
    $data = getJsonInput();

    $email = isset($data['email']) ? sanitizeEmail($data['email']) : null;
    $password = $data['password'] ?? null;

    if (!$email || !$password) {
        jsonError('Credenciales incompletas', 422);
    }

    $user = findIntegrationUserByEmail($email);
    if (!$user) {
        jsonError('Usuario o contraseña inválidos', 401);
    }

    if (($user['status'] ?? 'active') !== 'active') {
        jsonError('La cuenta está deshabilitada. Contacta soporte.', 403);
    }

    if (empty($user['password_hash']) || !verifyIntegrationPassword($password, $user['password_hash'])) {
        jsonError('Usuario o contraseña inválidos', 401);
    }

    list($accessToken, $refreshToken, $expiresIn) = issueSessionTokens((int)$user['id']);
    updateIntegrationUserLogin((int)$user['id']);

    jsonResponse([
        'success' => true,
        'token_type' => 'Bearer',
        'access_token' => $accessToken,
        'expires_in' => $expiresIn,
        'refresh_token' => $refreshToken,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
        ],
    ]);
}

function handleRefresh(): void
{
    $data = getJsonInput();
    $refreshToken = $data['refresh_token'] ?? null;

    if (!$refreshToken) {
        jsonError('Falta refresh_token', 422);
    }

    $session = findIntegrationSessionByToken($refreshToken, true);
    if (!$session) {
        jsonError('Refresh token inválido', 401);
    }

    if (!empty($session['refresh_expires_at']) && strtotime($session['refresh_expires_at']) <= time()) {
        revokeIntegrationSession($refreshToken, true);
        jsonError('Refresh token expirado', 401);
    }

    $user = findIntegrationUserById((int)$session['user_id']);
    if (!$user || ($user['status'] ?? 'active') !== 'active') {
        revokeIntegrationSession($refreshToken, true);
        jsonError('Usuario no disponible', 403);
    }

    list($accessToken, $newRefreshToken, $expiresIn) = issueSessionTokens((int)$session['user_id'], $refreshToken);

    jsonResponse([
        'success' => true,
        'token_type' => 'Bearer',
        'access_token' => $accessToken,
        'expires_in' => $expiresIn,
        'refresh_token' => $newRefreshToken,
    ]);
}

function handleLogout(): void
{
    $data = getJsonInput();
    $accessToken = $data['access_token'] ?? null;
    $refreshToken = $data['refresh_token'] ?? null;

    if (!$accessToken && !$refreshToken) {
        jsonError('Debe proporcionar access_token o refresh_token', 422);
    }

    if ($accessToken) {
        revokeIntegrationSession($accessToken, false);
    }
    if ($refreshToken) {
        revokeIntegrationSession($refreshToken, true);
    }

    jsonResponse(['success' => true]);
}

function issueSessionTokens(int $userId, ?string $previousRefreshToken = null): array
{
    $accessToken = generateIntegrationToken(64);
    $refreshToken = generateIntegrationToken(64);

    $accessTtl = (int)(getenv('AUTH_ACCESS_TTL') ?: 3600);
    $refreshTtl = (int)(getenv('AUTH_REFRESH_TTL') ?: 60 * 60 * 24 * 30);

    $expiresAt = gmdate('c', time() + $accessTtl);
    $refreshExpiresAt = gmdate('c', time() + $refreshTtl);

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    if ($previousRefreshToken) {
        // Rotar sesión existente
        $pdo = getIntegrationPdo();
        $stmt = $pdo->prepare('UPDATE integration_sessions SET session_token = :session_token, refresh_token = :refresh_token, expires_at = :expires_at, refresh_expires_at = :refresh_expires_at, ip_address = :ip, user_agent = :agent WHERE refresh_token = :previous_refresh');
        $stmt->execute([
            ':session_token' => hash('sha256', $accessToken),
            ':refresh_token' => hash('sha256', $refreshToken),
            ':expires_at' => $expiresAt,
            ':refresh_expires_at' => $refreshExpiresAt,
            ':ip' => $ip,
            ':agent' => $agent,
            ':previous_refresh' => hash('sha256', $previousRefreshToken),
        ]);
    } else {
        createIntegrationSession($userId, $accessToken, $refreshToken, $expiresAt, $refreshExpiresAt, $ip, $agent);
    }

    return [$accessToken, $refreshToken, $accessTtl];
}

function handleOauthStart(): void
{
    $provider = strtolower($_GET['provider'] ?? '');
    if (!in_array($provider, ['google', 'facebook'], true)) {
        jsonError('Proveedor inválido', 400);
    }

    jsonResponse([
        'success' => false,
        'message' => 'OAuth aún no implementado. Configura las credenciales y callback.',
        'provider' => $provider,
    ], 501);
}

function handleOauthCallback(): void
{
    jsonResponse([
        'success' => false,
        'message' => 'Callback OAuth aún no implementado.',
    ], 501);
}

