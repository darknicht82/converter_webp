# --------------------------------------------------------------
#  run-tests.ps1  –  Suite de pruebas para la API WebP
# --------------------------------------------------------------

# Configuración
$baseUrl = "http://localhost:9191/api.php"
$token = "e07ae44b27cb5e7904f0ce1c846e28b6ecd668d1dce325d2"
$backupDir = "C:\backup\tests_before"
$sampleImage = "C:\MAMP\htdocs\webp\media\sample.jpg"
$sqliteDb = "C:\MAMP\htdocs\webp\database\webp_integration.sqlite"

# Crear carpeta de salida
if (-Not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
}
Write-Host "`n[+] Carpeta de resultados: $backupDir`n"

# Caso 1 – Upload
Write-Host "`n[1] Upload (multipart)…"
if (Test-Path $sampleImage) {
    $result = & curl.exe -X POST "$baseUrl" -H "X-API-Token: $token" -F "image=@$sampleImage" -F "quality=80" -F "output_name=test_upload" -s
    $result | Out-File -Encoding utf8 "$backupDir\case1_upload.json"
    Write-Host "   → Guardado en case1_upload.json"
}
else {
    Write-Warning "   → Archivo sample.jpg no encontrado"
}

# Caso 2 – URL
Write-Host "`n[2] URL…"
$json2 = '{"url":"https://picsum.photos/seed/picsum/800/600.jpg","quality":80,"output_name":"test_url"}'
$result2 = & curl.exe -X POST "$baseUrl" -H "X-API-Token: $token" -H "Content-Type: application/json" -d $json2 -s
$result2 | Out-File -Encoding utf8 "$backupDir\case2_url.json"
Write-Host "   → Guardado en case2_url.json"

# Caso 3 – Health
Write-Host "`n[3] Health…"
$result3 = & curl.exe -X GET "$baseUrl?action=health" -H "X-API-Token: $token" -s
$result3 | Out-File -Encoding utf8 "$backupDir\case3_health.json"
Write-Host "   → Guardado en case3_health.json"

# Caso 4 – Logs
Write-Host "`n[4] Logs (último archivo)…"
$logFiles = Get-ChildItem "C:\MAMP\htdocs\webp\media\logs\app-*.log" -ErrorAction SilentlyContinue
if ($logFiles) {
    $logFile = $logFiles | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    Copy-Item $logFile.FullName "$backupDir\case4_latest_log.log" -ErrorAction SilentlyContinue
    Write-Host "   → Copiado $($logFile.Name)"
}
else {
    Write-Warning "   → No se encontraron logs"
}

# Caso 5 – Exportar tabla conversion_events
Write-Host "`n[5] Exportando tabla conversion_events a CSV…"
if (Test-Path $sqliteDb) {
    $csvPath = "$backupDir\conversion_events_pre.csv"
    $sqlCmd = @"
.headers on
.mode csv
.output "$csvPath"
SELECT * FROM conversion_events;
.quit
"@
    $sqlCmd | & sqlite3.exe $sqliteDb 2>&1 | Out-Null
    if (Test-Path $csvPath) {
        $rowCount = (Get-Content $csvPath | Measure-Object -Line).Lines - 1
        Write-Host "   → CSV guardado ($rowCount filas)"
    }
    else {
        Write-Warning "   → Error al exportar CSV"
    }
}
else {
    Write-Warning "   → Base de datos no encontrada en: $sqliteDb"
}

# Caso 6 – Update URL
Write-Host "`n[6] Update URL…"
$json6 = '{"action":"update_url","filename":"test_upload.webp","webp_url":"http://localhost/opuntia/wp-content/uploads/2025/11/test_upload.webp"}'
$json6 | Out-File -Encoding ascii "temp_case6.json"
$result6 = & curl.exe -X POST "$baseUrl" -H "X-API-Token: $token" -H "Content-Type: application/json" -d "@temp_case6.json" -s
Remove-Item "temp_case6.json"
$result6 | Out-File -Encoding utf8 "$backupDir\case6_update_url.json"
Write-Host "   → Guardado en case6_update_url.json"

Write-Host "`n=== FIN DE LA SUITE DE PRUEBAS ===`n"
Write-Host "Resultados guardados en: $backupDir`n"
