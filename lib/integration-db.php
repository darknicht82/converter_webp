<?php
/**
 * SQLite Integration Database helpers for WordPress module.
 *
 * Se encarga de inicializar el esquema necesario para:
 *  - Tokens de clientes
 *  - Métricas y costos de conversiones
 *  - Registro detallado de eventos de conversión
 */

if (!defined('INTEGRATION_DB_PATH')) {
    throw new RuntimeException('INTEGRATION_DB_PATH no está definido. Carga config.php primero.');
}

/**
 * Obtiene una instancia compartida de PDO para la base SQLite de integración.
 *
 * @return PDO
 */
function getIntegrationPdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databasePath = INTEGRATION_DB_PATH;

    $directory = dirname($databasePath);
    if (!is_dir($directory)) {
        @mkdir($directory, 0755, true);
    }

    $isNew = !file_exists($databasePath);

    try {
        $pdo = new PDO('sqlite:' . $databasePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON;');

        if ($isNew) {
            logIntegrationEvent('INFO', 'Base de datos de integración creada', [
                'path' => $databasePath
            ]);
        }
    } catch (PDOException $e) {
        logIntegrationEvent('ERROR', 'No se pudo conectar con la base de datos de integración', [
            'error' => $e->getMessage()
        ]);
        throw new RuntimeException('Error al inicializar la base de datos de integración.');
    }

    return $pdo;
}

/**
 * Crea las tablas necesarias para tokens y métricas si no existen.
 *
 * - integration_clients: listado de tokens/clientes autorizados
 * - conversion_metrics: acumulados de consumo por periodo/token
 * - conversion_events: auditoría por conversión individual
 *
 * @return void
 */
function initializeIntegrationDatabase(): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo = getIntegrationPdo();

    try {
        $pdo->beginTransaction();

        // Tabla principal de clientes/tokens
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS integration_clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_name TEXT NOT NULL,
    contact_email TEXT,
    api_token TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'active',
    monthly_quota INTEGER DEFAULT NULL,
    cost_per_image REAL DEFAULT 0,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    last_used_at TEXT
);
SQL
        );

        // Tabla de métricas agregadas
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS conversion_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    period_key TEXT NOT NULL,
    images_processed INTEGER NOT NULL DEFAULT 0,
    total_source_bytes INTEGER NOT NULL DEFAULT 0,
    total_converted_bytes INTEGER NOT NULL DEFAULT 0,
    total_cost REAL NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE(client_id, period_key),
    FOREIGN KEY(client_id) REFERENCES integration_clients(id) ON DELETE CASCADE
);
SQL
        );

        // Tabla de auditoría por evento
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS conversion_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    api_token TEXT,
    source_filename TEXT,
    webp_filename TEXT,
    source_bytes INTEGER DEFAULT 0,
    converted_bytes INTEGER DEFAULT 0,
    cost REAL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(client_id) REFERENCES integration_clients(id) ON DELETE SET NULL
);
SQL
        );

        // Tabla de usuarios
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS integration_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT,
    full_name TEXT,
    status TEXT NOT NULL DEFAULT 'active',
    role TEXT NOT NULL DEFAULT 'manager',
    avatar_url TEXT,
    phone TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    last_login_at TEXT
);
SQL
        );

        // Proveedores OAuth
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS integration_user_providers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    provider TEXT NOT NULL,
    provider_user_id TEXT NOT NULL,
    email TEXT,
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE(provider, provider_user_id),
    FOREIGN KEY(user_id) REFERENCES integration_users(id) ON DELETE CASCADE
);
SQL
        );

        // Sesiones / tokens de acceso
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS integration_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_token TEXT NOT NULL UNIQUE,
    refresh_token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    refresh_expires_at TEXT NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    revoked_at TEXT,
    FOREIGN KEY(user_id) REFERENCES integration_users(id) ON DELETE CASCADE
);
SQL
        );

        // Relación usuario-cliente
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS integration_user_clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    role TEXT NOT NULL DEFAULT 'owner',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    revoked_at TEXT,
    UNIQUE(user_id, client_id),
    FOREIGN KEY(user_id) REFERENCES integration_users(id) ON DELETE CASCADE,
    FOREIGN KEY(client_id) REFERENCES integration_clients(id) ON DELETE CASCADE
);
SQL
        );

        // Tokens de API emitidos por usuario
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS integration_api_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    label TEXT,
    scopes TEXT,
    expires_at TEXT,
    last_used_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    revoked_at TEXT,
    UNIQUE(user_id, client_id, label),
    FOREIGN KEY(user_id) REFERENCES integration_users(id) ON DELETE CASCADE,
    FOREIGN KEY(client_id) REFERENCES integration_clients(id) ON DELETE CASCADE
);
SQL
        );

        // Tabla de logs de conversión individual
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS conversion_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    original_size INTEGER NOT NULL,
    webp_size INTEGER NOT NULL,
    savings_bytes INTEGER NOT NULL,
    savings_percent REAL NOT NULL,
    cost REAL NOT NULL DEFAULT 0.00,
    status TEXT NOT NULL DEFAULT 'success',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(client_id) REFERENCES integration_clients(id) ON DELETE CASCADE
);
SQL
        );

        // Índices auxiliares
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_clients_status ON integration_clients(status);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_metrics_period ON conversion_metrics(period_key);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_events_created_at ON conversion_events(created_at);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_status ON integration_users(status);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sessions_user ON integration_sessions(user_id);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_clients_user ON integration_user_clients(user_id);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_api_tokens_user ON integration_api_tokens(user_id);");

        $pdo->commit();
        $initialized = true;

        logIntegrationEvent('INFO', 'Esquema de base de datos de integración verificado', []);
    } catch (PDOException $e) {
        $pdo->rollBack();
        logIntegrationEvent('ERROR', 'Error al preparar el esquema de integración', [
            'error' => $e->getMessage()
        ]);
        throw new RuntimeException('No fue posible preparar las tablas de integración.');
    }
}

/**
 * Actualiza el campo updated_at de un registro utilizando un trigger simple.
 *
 * Garantiza que el timestamp se mantenga consistente sin necesidad de lógica manual.
 *
 * @return void
 */
function ensureIntegrationTriggers(): void
{
    $pdo = getIntegrationPdo();

    try {
        $pdo->exec(<<<SQL
CREATE TRIGGER IF NOT EXISTS trg_clients_updated_at
AFTER UPDATE ON integration_clients
BEGIN
    UPDATE integration_clients
    SET updated_at = datetime('now')
    WHERE id = NEW.id;
END;
SQL
        );

        $pdo->exec(<<<SQL
CREATE TRIGGER IF NOT EXISTS trg_metrics_updated_at
AFTER UPDATE ON conversion_metrics
BEGIN
    UPDATE conversion_metrics
    SET updated_at = datetime('now')
    WHERE id = NEW.id;
END;
SQL
        );

        $pdo->exec(<<<SQL
CREATE TRIGGER IF NOT EXISTS trg_users_updated_at
AFTER UPDATE ON integration_users
BEGIN
    UPDATE integration_users
    SET updated_at = datetime('now')
    WHERE id = NEW.id;
END;
SQL
        );
    } catch (PDOException $e) {
        logIntegrationEvent('ERROR', 'Error al crear triggers de integración', [
            'error' => $e->getMessage()
        ]);
        throw new RuntimeException('No se pudo crear los triggers de integración.');
    }
}

/**
 * Obtiene un cliente activo por token.
 *
 * @param string $token
 * @return array|null
 */
function findIntegrationClientByToken(string $token): ?array
{
    $pdo = getIntegrationPdo();

    $stmt = $pdo->prepare('SELECT * FROM integration_clients WHERE api_token = :token LIMIT 1');
    $stmt->execute([':token' => $token]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    return $client ?: null;
}

/**
 * Actualiza la fecha de último uso de un cliente.
 *
 * @param int $clientId
 * @return void
 */
function touchIntegrationClientUsage(int $clientId): void
{
    $pdo = getIntegrationPdo();

    $stmt = $pdo->prepare('UPDATE integration_clients SET last_used_at = datetime("now") WHERE id = :id');
    $stmt->execute([':id' => $clientId]);
}

/**
 * Registra una conversión individual y actualiza métricas agregadas.
 *
 * @param array $client
 * @param array $payload
 * @return bool
 */
function recordIntegrationConversion(array $client, array $payload): bool
{
    $pdo = getIntegrationPdo();

    $clientId = (int)($client['id'] ?? 0);
    if ($clientId <= 0) {
        return false;
    }

    $apiToken = $payload['api_token'] ?? ($client['api_token'] ?? null);
    $sourceFilename = $payload['source_filename'] ?? null;
    $webpFilename = $payload['webp_filename'] ?? null;
    $sourceBytes = max(0, (int)($payload['source_bytes'] ?? 0));
    $convertedBytes = max(0, (int)($payload['converted_bytes'] ?? 0));
    $costOverride = isset($payload['cost']) ? (float)$payload['cost'] : null;

    $periodKey = $payload['period_key'] ?? date('Y-m');
    $costPerImage = (float)($client['cost_per_image'] ?? 0);
    $cost = $costOverride !== null ? max(0, $costOverride) : max(0, $costPerImage);

    try {
        $pdo->beginTransaction();

        $insertEvent = $pdo->prepare('INSERT INTO conversion_events (client_id, api_token, source_filename, webp_filename, source_bytes, converted_bytes, cost, created_at) VALUES (:client_id, :api_token, :source_filename, :webp_filename, :source_bytes, :converted_bytes, :cost, datetime("now"))');
        $insertEvent->execute([
            ':client_id' => $clientId,
            ':api_token' => $apiToken,
            ':source_filename' => $sourceFilename,
            ':webp_filename' => $webpFilename,
            ':source_bytes' => $sourceBytes,
            ':converted_bytes' => $convertedBytes,
            ':cost' => $cost
        ]);

        $upsertMetrics = $pdo->prepare(
            'INSERT INTO conversion_metrics (client_id, period_key, images_processed, total_source_bytes, total_converted_bytes, total_cost, created_at, updated_at) VALUES (:client_id, :period_key, :images_processed, :total_source_bytes, :total_converted_bytes, :total_cost, datetime("now"), datetime("now"))
            ON CONFLICT(client_id, period_key) DO UPDATE SET
                images_processed = images_processed + excluded.images_processed,
                total_source_bytes = total_source_bytes + excluded.total_source_bytes,
                total_converted_bytes = total_converted_bytes + excluded.total_converted_bytes,
                total_cost = total_cost + excluded.total_cost'
        );

        $upsertMetrics->execute([
            ':client_id' => $clientId,
            ':period_key' => $periodKey,
            ':images_processed' => 1,
            ':total_source_bytes' => $sourceBytes,
            ':total_converted_bytes' => $convertedBytes,
            ':total_cost' => $cost
        ]);

        // Also log individual conversion for detailed tracking
        logConversion(
            $clientId,
            $sourceFilename ?? 'unknown',
            $sourceBytes,
            $convertedBytes,
            $cost,
            'success'
        );

        touchIntegrationClientUsage($clientId);

        $pdo->commit();

        logIntegrationEvent('INFO', 'Conversión registrada', [
            'client_id' => $clientId,
            'source' => $sourceFilename,
            'destination' => $webpFilename,
            'source_bytes' => $sourceBytes,
            'converted_bytes' => $convertedBytes,
            'cost' => $cost
        ]);

        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        logIntegrationEvent('ERROR', 'No se pudo registrar la conversión', [
            'client_id' => $clientId,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

function generateIntegrationToken(int $length = 48): string
{
    $length = max(16, $length);
    $bytes = random_bytes((int)ceil($length / 2));
    return substr(bin2hex($bytes), 0, $length);
}

function hashIntegrationPassword(string $password): string
{
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyIntegrationPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function findIntegrationUserByEmail(string $email): ?array
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('SELECT * FROM integration_users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($user) {
        $user['role'] = $user['role'] ?? 'manager';
    }
    return $user ?: null;
}

function findIntegrationUserById(int $id): ?array
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('SELECT * FROM integration_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($user) {
        $user['role'] = $user['role'] ?? 'manager';
    }
    return $user ?: null;
}

function createIntegrationUser(array $data): array
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('INSERT INTO integration_users (email, password_hash, full_name, status, role, avatar_url, phone, created_at, updated_at) VALUES (:email, :password_hash, :full_name, :status, :role, :avatar_url, :phone, datetime("now"), datetime("now"))');

    $plainPassword = $data['password'] ?? null;
    $passwordHash = $plainPassword !== null ? hashIntegrationPassword($plainPassword) : null;

    $stmt->execute([
        ':email' => strtolower(trim($data['email'])),
        ':password_hash' => $passwordHash,
        ':full_name' => $data['full_name'] ?? null,
        ':status' => $data['status'] ?? 'active',
        ':role' => $data['role'] ?? 'manager',
        ':avatar_url' => $data['avatar_url'] ?? null,
        ':phone' => $data['phone'] ?? null
    ]);

    $id = (int)$pdo->lastInsertId();
    $user = findIntegrationUserById($id);

    logIntegrationEvent('INFO', 'Usuario de integración creado', [
        'user_id' => $id,
        'email' => $user['email'] ?? null
    ]);

    return $user;
}

function assignIntegrationUserToClient(int $userId, int $clientId, string $role = 'owner'): void
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO integration_user_clients (user_id, client_id, role, created_at) VALUES (:user_id, :client_id, :role, datetime("now"))');
    $stmt->execute([
        ':user_id' => $userId,
        ':client_id' => $clientId,
        ':role' => $role
    ]);
}

function updateIntegrationUserLogin(int $userId): void
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('UPDATE integration_users SET last_login_at = datetime("now"), updated_at = datetime("now") WHERE id = :id');
    $stmt->execute([':id' => $userId]);
}

function linkIntegrationUserProvider(int $userId, string $provider, string $providerUserId, array $tokens = []): void
{
    $pdo = getIntegrationPdo();

    $stmt = $pdo->prepare('INSERT INTO integration_user_providers (user_id, provider, provider_user_id, email, access_token, refresh_token, token_expires_at, created_at)
        VALUES (:user_id, :provider, :provider_user_id, :email, :access_token, :refresh_token, :token_expires_at, datetime("now"))
        ON CONFLICT(provider, provider_user_id) DO UPDATE SET
            user_id = excluded.user_id,
            email = excluded.email,
            access_token = excluded.access_token,
            refresh_token = excluded.refresh_token,
            token_expires_at = excluded.token_expires_at');

    $stmt->execute([
        ':user_id' => $userId,
        ':provider' => strtolower($provider),
        ':provider_user_id' => $providerUserId,
        ':email' => $tokens['email'] ?? null,
        ':access_token' => $tokens['access_token'] ?? null,
        ':refresh_token' => $tokens['refresh_token'] ?? null,
        ':token_expires_at' => $tokens['token_expires_at'] ?? null
    ]);
}

function findIntegrationUserByProvider(string $provider, string $providerUserId): ?array
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('SELECT u.*
        FROM integration_user_providers p
        INNER JOIN integration_users u ON u.id = p.user_id
        WHERE p.provider = :provider AND p.provider_user_id = :provider_user_id
        LIMIT 1');
    $stmt->execute([
        ':provider' => strtolower($provider),
        ':provider_user_id' => $providerUserId
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($user) {
        $user['role'] = $user['role'] ?? 'manager';
    }
    return $user ?: null;
}

function getIntegrationClientById(int $id): ?array
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('SELECT * FROM integration_clients WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($client) {
        $client['monthly_quota'] = $client['monthly_quota'] !== null ? (int)$client['monthly_quota'] : null;
        $client['cost_per_image'] = (float)($client['cost_per_image'] ?? 0);
    }

    return $client ?: null;
}

function createIntegrationSession(int $userId, string $sessionToken, string $refreshToken, string $expiresAt, string $refreshExpiresAt, ?string $ip = null, ?string $userAgent = null): void
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('INSERT INTO integration_sessions (user_id, session_token, refresh_token, expires_at, refresh_expires_at, ip_address, user_agent, created_at) VALUES (:user_id, :session_token, :refresh_token, :expires_at, :refresh_expires_at, :ip_address, :user_agent, datetime("now"))');
    $stmt->execute([
        ':user_id' => $userId,
        ':session_token' => hash('sha256', $sessionToken),
        ':refresh_token' => hash('sha256', $refreshToken),
        ':expires_at' => $expiresAt,
        ':refresh_expires_at' => $refreshExpiresAt,
        ':ip_address' => $ip,
        ':user_agent' => $userAgent
    ]);
}

function findIntegrationSessionByToken(string $token, bool $isRefresh = false): ?array
{
    $pdo = getIntegrationPdo();
    $column = $isRefresh ? 'refresh_token' : 'session_token';
    $stmt = $pdo->prepare("SELECT * FROM integration_sessions WHERE {$column} = :token AND revoked_at IS NULL LIMIT 1");
    $stmt->execute([':token' => hash('sha256', $token)]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function revokeIntegrationSession(string $token, bool $isRefresh = false): void
{
    $pdo = getIntegrationPdo();
    $column = $isRefresh ? 'refresh_token' : 'session_token';
    $stmt = $pdo->prepare("UPDATE integration_sessions SET revoked_at = datetime('now') WHERE {$column} = :token");
    $stmt->execute([':token' => hash('sha256', $token)]);
}

function cleanupExpiredSessions(): void
{
    $pdo = getIntegrationPdo();
    $pdo->prepare('DELETE FROM integration_sessions WHERE (expires_at <= datetime("now") AND refresh_expires_at <= datetime("now")) OR revoked_at IS NOT NULL')->execute();
}

function createIntegrationApiToken(int $userId, int $clientId, string $label, array $scopes = [], ?string $expiresAt = null): array
{
    $pdo = getIntegrationPdo();
    $plainToken = generateIntegrationToken(48);
    $hash = hash('sha256', $plainToken);

    $stmt = $pdo->prepare('INSERT INTO integration_api_tokens (user_id, client_id, token_hash, label, scopes, expires_at, created_at)
        VALUES (:user_id, :client_id, :token_hash, :label, :scopes, :expires_at, datetime("now"))');
    $stmt->execute([
        ':user_id' => $userId,
        ':client_id' => $clientId,
        ':token_hash' => $hash,
        ':label' => $label,
        ':scopes' => json_encode($scopes),
        ':expires_at' => $expiresAt
    ]);

    logIntegrationEvent('INFO', 'Token API emitido', [
        'user_id' => $userId,
        'client_id' => $clientId,
        'label' => $label
    ]);

    return [
        'token' => $plainToken,
        'label' => $label,
        'expires_at' => $expiresAt
    ];
}

function findIntegrationApiToken(string $token): ?array
{
    $pdo = getIntegrationPdo();
    $hash = hash('sha256', $token);
    $stmt = $pdo->prepare('SELECT * FROM integration_api_tokens WHERE token_hash = :hash AND revoked_at IS NULL LIMIT 1');
    $stmt->execute([':hash' => $hash]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function revokeIntegrationApiToken(int $id): void
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('UPDATE integration_api_tokens SET revoked_at = datetime("now") WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function getIntegrationClientsForUser(int $userId): array
{
    $pdo = getIntegrationPdo();
    $stmt = $pdo->prepare('
        SELECT c.*, uc.role AS user_role, uc.created_at AS assigned_at
        FROM integration_user_clients uc
        INNER JOIN integration_clients c ON c.id = uc.client_id
        WHERE uc.user_id = :user_id AND uc.revoked_at IS NULL
        ORDER BY c.created_at DESC
    ');
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createIntegrationClient(array $data): ?array
{
    $pdo = getIntegrationPdo();

    $token = $data['api_token'] ?? generateIntegrationToken();
    $status = $data['status'] ?? 'active';
    $allowed = ['active', 'paused', 'revoked'];
    if (!in_array($status, $allowed, true)) {
        $status = 'active';
    }

    $stmt = $pdo->prepare('INSERT INTO integration_clients (client_name, contact_email, api_token, status, monthly_quota, cost_per_image, notes, created_at, updated_at) VALUES (:client_name, :contact_email, :api_token, :status, :monthly_quota, :cost_per_image, :notes, datetime("now"), datetime("now"))');
    $stmt->execute([
        ':client_name' => $data['client_name'],
        ':contact_email' => $data['contact_email'] ?? null,
        ':api_token' => $token,
        ':status' => $status,
        ':monthly_quota' => $data['monthly_quota'],
        ':cost_per_image' => $data['cost_per_image'],
        ':notes' => $data['notes'] ?? null
    ]);

    $id = (int)$pdo->lastInsertId();
    $client = getIntegrationClientById($id);

    logIntegrationEvent('INFO', 'Cliente WordPress creado', [
        'client_id' => $id,
        'client_name' => $data['client_name'],
        'status' => $status
    ]);

    return $client;
}

function updateIntegrationClient(int $id, array $data): bool
{
    $pdo = getIntegrationPdo();

    $status = $data['status'] ?? 'active';
    $allowed = ['active', 'paused', 'revoked'];
    if (!in_array($status, $allowed, true)) {
        $status = 'active';
    }

    $stmt = $pdo->prepare('UPDATE integration_clients SET client_name = :client_name, contact_email = :contact_email, status = :status, monthly_quota = :monthly_quota, cost_per_image = :cost_per_image, notes = :notes WHERE id = :id');
    $result = $stmt->execute([
        ':client_name' => $data['client_name'],
        ':contact_email' => $data['contact_email'] ?? null,
        ':status' => $status,
        ':monthly_quota' => $data['monthly_quota'],
        ':cost_per_image' => $data['cost_per_image'],
        ':notes' => $data['notes'] ?? null,
        ':id' => $id
    ]);

    if ($result) {
        logIntegrationEvent('INFO', 'Cliente WordPress actualizado', [
            'client_id' => $id,
            'status' => $status
        ]);
    }

    return $result;
}

function regenerateIntegrationClientToken(int $id, int $length = 48): ?string
{
    $pdo = getIntegrationPdo();
    $token = generateIntegrationToken($length);

    $stmt = $pdo->prepare('UPDATE integration_clients SET api_token = :token, updated_at = datetime("now") WHERE id = :id');
    $success = $stmt->execute([':token' => $token, ':id' => $id]);

    if ($success) {
        logIntegrationEvent('INFO', 'Token de cliente regenerado', [
            'client_id' => $id,
            'token_suffix' => substr($token, -6)
        ]);
        return $token;
    }

    return null;
}

/**
 * Obtiene métricas agregadas globales.
 *
 * @return array<string, mixed>
 */
function fetchIntegrationStats(): array
{
    $pdo = getIntegrationPdo();

    $stats = [
        'total_clients' => 0,
        'active_clients' => 0,
        'paused_clients' => 0,
        'total_conversions' => 0,
        'total_cost' => 0.0,
        'total_source_mb' => 0.0,
        'total_converted_mb' => 0.0
    ];

    $clientsStmt = $pdo->query('SELECT COUNT(*) AS total, SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN status != "active" THEN 1 ELSE 0 END) AS inactive FROM integration_clients');
    if ($clientsRow = $clientsStmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['total_clients'] = (int)($clientsRow['total'] ?? 0);
        $stats['active_clients'] = (int)($clientsRow['active'] ?? 0);
        $stats['paused_clients'] = (int)($clientsRow['inactive'] ?? 0);
    }

    $metricsStmt = $pdo->query('SELECT SUM(images_processed) AS conversions, SUM(total_cost) AS cost, SUM(total_source_bytes) AS source_bytes, SUM(total_converted_bytes) AS converted_bytes FROM conversion_metrics');
    if ($metricsRow = $metricsStmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['total_conversions'] = (int)($metricsRow['conversions'] ?? 0);
        $stats['total_cost'] = (float)($metricsRow['cost'] ?? 0);
        $stats['total_source_mb'] = round(((int)($metricsRow['source_bytes'] ?? 0)) / (1024 * 1024), 2);
        $stats['total_converted_mb'] = round(((int)($metricsRow['converted_bytes'] ?? 0)) / (1024 * 1024), 2);
    }

    return $stats;
}

/**
 * Recupera clientes junto a métricas acumuladas.
 *
 * @return array<int, array<string, mixed>>
 */
function fetchIntegrationClientsWithMetrics(): array
{
    $pdo = getIntegrationPdo();

    $stmt = $pdo->query('
        SELECT c.*, IFNULL(m.images_processed, 0) AS images_processed, IFNULL(m.total_cost, 0) AS total_cost, IFNULL(m.total_source_bytes, 0) AS total_source_bytes, IFNULL(m.total_converted_bytes, 0) AS total_converted_bytes
        FROM integration_clients c
        LEFT JOIN (
            SELECT client_id,
                   SUM(images_processed) AS images_processed,
                   SUM(total_cost) AS total_cost,
                   SUM(total_source_bytes) AS total_source_bytes,
                   SUM(total_converted_bytes) AS total_converted_bytes
            FROM conversion_metrics
            GROUP BY client_id
        ) m ON m.client_id = c.id
        ORDER BY c.created_at DESC
    ');

    $clients = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['images_processed'] = (int)($row['images_processed'] ?? 0);
        $row['total_cost'] = (float)($row['total_cost'] ?? 0);
        $row['total_source_mb'] = round(((int)($row['total_source_bytes'] ?? 0)) / (1024 * 1024), 2);
        $row['total_converted_mb'] = round(((int)($row['total_converted_bytes'] ?? 0)) / (1024 * 1024), 2);
        $clients[] = $row;
    }

    return $clients;
}

/**
 * Obtiene eventos recientes de conversión.
 *
 * @param int $limit
 * @return array<int, array<string, mixed>>
 */
function fetchRecentIntegrationEvents(int $limit = 10): array
{
    $pdo = getIntegrationPdo();

    $stmt = $pdo->prepare('
        SELECT e.*, c.client_name
        FROM conversion_events e
        LEFT JOIN integration_clients c ON c.id = e.client_id
        ORDER BY e.created_at DESC
        LIMIT :limit
    ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['source_bytes'] = (int)($row['source_bytes'] ?? 0);
        $row['converted_bytes'] = (int)($row['converted_bytes'] ?? 0);
        $row['cost'] = (float)($row['cost'] ?? 0);
        $events[] = $row;
    }

    return $events;
}

/**
 * Obtiene eventos de conversión paginados.
 *
 * @param int $page Página actual (1-indexed)
 * @param int $perPage Elementos por página
 * @return array{events: array, total: int, pages: int}
 */
function fetchIntegrationEventsPaginated(int $page = 1, int $perPage = 20): array
{
    $pdo = getIntegrationPdo();
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    // Obtener total
    $countStmt = $pdo->query('SELECT COUNT(*) FROM conversion_events');
    $total = (int)$countStmt->fetchColumn();
    $pages = ceil($total / $perPage);

    // Obtener eventos
    $stmt = $pdo->prepare('
        SELECT e.*, c.client_name
        FROM conversion_events e
        LEFT JOIN integration_clients c ON c.id = e.client_id
        ORDER BY e.created_at DESC
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['source_bytes'] = (int)($row['source_bytes'] ?? 0);
        $row['converted_bytes'] = (int)($row['converted_bytes'] ?? 0);
        $row['cost'] = (float)($row['cost'] ?? 0);
        $events[] = $row;
    }

    return [
        'events' => $events,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page
    ];
}


