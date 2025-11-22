# Script de Prueba para WebP Converter API
# Ejecutar con: .\test-api.ps1

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   WebP Converter - Test Suite" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$baseUrl = "http://localhost:8080"
$apiUrl = "$baseUrl/api.php"

# Test 1: Health Check
Write-Host "[TEST 1] Health Check..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$apiUrl?action=health" -Method Get
    if ($response.success) {
        Write-Host "✓ EXITO: Servicio online" -ForegroundColor Green
        Write-Host "  Environment: $($response.environment)" -ForegroundColor Gray
        Write-Host "  Version: $($response.version)" -ForegroundColor Gray
    } else {
        Write-Host "✗ FALLO: Respuesta inesperada" -ForegroundColor Red
    }
} catch {
    Write-Host "✗ ERROR: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "  ¿Está el servicio corriendo?" -ForegroundColor Yellow
    Write-Host "  MAMP: http://localhost/webp/api.php" -ForegroundColor Yellow
    Write-Host "  Docker: docker-compose up -d" -ForegroundColor Yellow
}

Write-Host ""

# Test 2: Listar archivos
Write-Host "[TEST 2] Listar archivos source..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$apiUrl?action=list&type=source" -Method Get
    Write-Host "✓ EXITO: Encontrados $($response.count) archivos" -ForegroundColor Green
    
    if ($response.count -gt 0) {
        Write-Host "  Primeros archivos:" -ForegroundColor Gray
        $response.files | Select-Object -First 3 | ForEach-Object {
            Write-Host "    - $($_.filename) ($($_.size_formatted))" -ForegroundColor Gray
        }
    }
} catch {
    Write-Host "✗ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 3: Convertir desde URL de prueba
Write-Host "[TEST 3] Convertir desde URL (Picsum)..." -ForegroundColor Yellow
try {
    $body = @{
        url = "https://picsum.photos/800/600"
        quality = 85
        output_name = "test_picsum_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    } | ConvertTo-Json

    $response = Invoke-RestMethod -Uri $apiUrl -Method Post `
        -ContentType "application/json" -Body $body
    
    if ($response.success) {
        Write-Host "✓ EXITO: Imagen convertida" -ForegroundColor Green
        Write-Host "  Archivo: $($response.data.filename)" -ForegroundColor Gray
        Write-Host "  Tamaño: $([math]::Round($response.data.size/1024, 2)) KB" -ForegroundColor Gray
        Write-Host "  URL: $($response.data.url)" -ForegroundColor Gray
    }
} catch {
    Write-Host "✗ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 4: Convertir archivo existente (si hay alguno)
Write-Host "[TEST 4] Convertir archivo existente..." -ForegroundColor Yellow
try {
    $listResponse = Invoke-RestMethod -Uri "$apiUrl?action=list&type=source" -Method Get
    
    if ($listResponse.count -gt 0) {
        $firstFile = $listResponse.files[0].filename
        
        $body = @{
            filename = $firstFile
            quality = 80
            output_name = "test_existing_$(Get-Date -Format 'HHmmss')"
        } | ConvertTo-Json

        $response = Invoke-RestMethod -Uri $apiUrl -Method Post `
            -ContentType "application/json" -Body $body
        
        if ($response.success) {
            Write-Host "✓ EXITO: Convertido '$firstFile'" -ForegroundColor Green
            Write-Host "  Ahorro: $($response.data.savings)" -ForegroundColor Gray
        }
    } else {
        Write-Host "⊘ SALTADO: No hay archivos en upload/" -ForegroundColor Yellow
    }
} catch {
    Write-Host "✗ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Tests Completados" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "Para más tests, consulta README.md" -ForegroundColor Gray
Write-Host "Logs disponibles en: logs/app-$(Get-Date -Format 'yyyy-MM-dd').log`n" -ForegroundColor Gray

