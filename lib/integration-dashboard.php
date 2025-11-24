<?php
/**
 * Dashboard helper functions for webp-wordpress
 * These functions provide aggregated data for the admin panel
 */

function fetchIntegrationStats(): array
{
    $pdo = getIntegrationPdo();
    
    // Total clients
    $stmtClients = $pdo->query("SELECT COUNT(*) as total FROM integration_clients WHERE status = 'active'");
    $totalClients = (int)$stmtClients->fetchColumn();
    
    // Total conversions
    $stmtConversions = $pdo->query("SELECT COUNT(*) as total FROM conversion_logs");
    $totalConversions = (int)$stmtConversions->fetchColumn();
    
    // Total bytes processed
    $stmtBytes = $pdo->query("SELECT COALESCE(SUM(original_size), 0) as total FROM conversion_logs");
    $totalBytes = (int)$stmtBytes->fetchColumn();
    
    // Total cost
    $stmtCost = $pdo->query("SELECT COALESCE(SUM(cost), 0) as total FROM conversion_logs");
    $totalCost = (float)$stmtCost->fetchColumn();
    
    // Calculate MB values and savings
    $stmtConverted = $pdo->query("SELECT COALESCE(SUM(webp_size), 0) as total FROM conversion_logs");
    $totalConvertedBytes = (int)$stmtConverted->fetchColumn();
    
    $totalSourceMB = $totalBytes / (1024 * 1024);
    $totalConvertedMB = $totalConvertedBytes / (1024 * 1024);
    $totalSavingsMB = $totalSourceMB - $totalConvertedMB;
    
    return [
        'total_clients' => $totalClients,
        'active_clients' => $totalClients,
        'total_conversions' => $totalConversions,
        'total_bytes' => $totalBytes,
        'total_cost' => $totalCost,
        'total_savings' => $totalSavingsMB,
        'total_source_mb' => $totalSourceMB,
        'total_converted_mb' => $totalConvertedMB,
        'total_savings_mb' => $totalSavingsMB
    ];
}

function fetchIntegrationClientsWithMetrics(): array
{
    $pdo = getIntegrationPdo();
    
    $sql = "
        SELECT 
            c.id,
            c.client_name,
            c.contact_email,
            c.api_token,
            c.status,
            c.monthly_quota,
            c.cost_per_image,
            c.created_at,
            c.last_used_at,
            COALESCE(COUNT(e.id), 0) as total_conversions,
            COALESCE(COUNT(e.id), 0) as images_processed,
            COALESCE(SUM(e.cost), 0) as total_cost
        FROM integration_clients c
        LEFT JOIN conversion_logs e ON c.id = e.client_id
        GROUP BY c.id, c.client_name, c.contact_email, c.api_token, c.status, 
                 c.monthly_quota, c.cost_per_image, c.created_at, c.last_used_at
        ORDER BY c.id DESC
    ";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchRecentIntegrationEvents(int $limit = 10): array
{
    $pdo = getIntegrationPdo();
    
    $sql = "
        SELECT 
            e.*,
            c.client_name
        FROM conversion_logs e
        LEFT JOIN integration_clients c ON e.client_id = c.id
        ORDER BY e.created_at DESC
        LIMIT :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchIntegrationEventsPaginated(int $page = 1, int $perPage = 50): array
{
    $pdo = getIntegrationPdo();
    $offset = ($page - 1) * $perPage;
    
    // Get total count
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM conversion_logs");
    $total = (int)$totalStmt->fetchColumn();
    $totalPages = ceil($total / $perPage);
    
    // Get paginated events
    $sql = "
        SELECT 
            e.*,
            c.client_name
        FROM conversion_logs e
        LEFT JOIN integration_clients c ON e.client_id = c.id
        ORDER BY e.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return [
        'events' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'pages' => $totalPages,
        'current_page' => $page,
        'per_page' => $perPage
    ];
}

function createIntegrationClient(array $data): ?array
{
    $pdo = getIntegrationPdo();
    $now = getSqlTimestampFunction();
    
    // Generate unique token
    $token = bin2hex(random_bytes(24));
    
    $sql = "INSERT INTO integration_clients 
            (client_name, contact_email, api_token, status, monthly_quota, cost_per_image, notes, created_at, updated_at)
            VALUES (:client_name, :contact_email, :token, :status, :quota, :cost, :notes, $now, $now)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_name' => $data['client_name'],
        ':contact_email' => $data['contact_email'] ?? null,
        ':token' => $token,
        ':status' => $data['status'] ?? 'active',
        ':quota' => $data['monthly_quota'] ?? null,
        ':cost' => $data['cost_per_image'] ?? 0,
        ':notes' => $data['notes'] ?? null
    ]);
    
    $id = (int)$pdo->lastInsertId();
    return getIntegrationClientById($id);
}

function updateIntegrationClient(int $clientId, array $data): bool
{
    $pdo = getIntegrationPdo();
    $now = getSqlTimestampFunction();
    
    $sql = "UPDATE integration_clients 
            SET client_name = :client_name,
            contact_email = :contact_email,
            status = :status,
            monthly_quota = :quota,
            cost_per_image = :cost,
            notes = :notes,
            updated_at = $now
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':id' => $clientId,
        ':client_name' => $data['client_name'],
        ':contact_email' => $data['contact_email'] ?? null,
        ':status' => $data['status'] ?? 'active',
        ':quota' => $data['monthly_quota'] ?? null,
        ':cost' => $data['cost_per_image'] ?? 0,
        ':notes' => $data['notes'] ?? null
    ]);
}

function regenerateIntegrationClientToken(int $clientId): ?string
{
    $pdo = getIntegrationPdo();
    $now = getSqlTimestampFunction();
    
    $newToken = bin2hex(random_bytes(24));
    
    $stmt = $pdo->prepare("UPDATE integration_clients SET api_token = :token, updated_at = $now WHERE id = :id");
    $result = $stmt->execute([':token' => $newToken, ':id' => $clientId]);
    
    return $result ? $newToken : null;
}

if (!function_exists('logConversion')) {
    function logConversion(int $clientId, string $filename, int $originalSize, int $webpSize, float $cost, string $status = 'success'): bool
    {
        $pdo = getIntegrationPdo();
        $now = getSqlTimestampFunction();
        
        $savingsBytes = $originalSize - $webpSize;
        $savingsPercent = $originalSize > 0 ? ($savingsBytes / $originalSize) * 100 : 0;
        
        $sql = "INSERT INTO conversion_logs 
                (client_id, filename, original_size, webp_size, savings_bytes, savings_percent, cost, status, created_at)
                VALUES (:client_id, :filename, :original_size, :webp_size, :savings_bytes, :savings_percent, :cost, :status, $now)";
        
        try {
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':client_id' => $clientId,
                ':filename' => $filename,
                ':original_size' => $originalSize,
                ':webp_size' => $webpSize,
                ':savings_bytes' => $savingsBytes,
                ':savings_percent' => $savingsPercent,
                ':cost' => $cost,
                ':status' => $status
            ]);
        } catch (PDOException $e) {
            error_log("Log Conversion Error: " . $e->getMessage());
            return false;
        }
    }
}
