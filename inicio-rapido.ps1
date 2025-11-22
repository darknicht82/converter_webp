# Script de Inicio Rápido - WebP Converter
# Ejecutar con: .\inicio-rapido.ps1

param(
    [Parameter(Position=0)]
    [ValidateSet('mamp', 'docker', 'test', 'stop')]
    [string]$Modo = 'mamp'
)

Write-Host "`n╔════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   WebP Converter - Inicio Rápido      ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════╝`n" -ForegroundColor Cyan

switch ($Modo) {
    'mamp' {
        Write-Host "🖥️  Modo MAMP/XAMPP" -ForegroundColor Green
        Write-Host "───────────────────────────────────────`n" -ForegroundColor Gray
        
        Write-Host "✓ Archivos PHP listos en:" -ForegroundColor White
        Write-Host "  $(Get-Location)`n" -ForegroundColor Gray
        
        Write-Host "✓ Accede desde el navegador:" -ForegroundColor White
        Write-Host "  http://localhost/webp/index.php" -ForegroundColor Cyan
        Write-Host "  http://localhost/webp/api.php?action=health`n" -ForegroundColor Cyan
        
        Write-Host "✓ Coloca imágenes en:" -ForegroundColor White
        Write-Host "  .\upload\`n" -ForegroundColor Gray
        
        # Intentar abrir en navegador
        try {
            Start-Process "http://localhost/webp/index.php"
            Write-Host "✓ Abriendo navegador..." -ForegroundColor Green
        } catch {
            Write-Host "⚠ No se pudo abrir el navegador automáticamente" -ForegroundColor Yellow
        }
    }
    
    'docker' {
        Write-Host "🐳 Modo Docker" -ForegroundColor Green
        Write-Host "───────────────────────────────────────`n" -ForegroundColor Gray
        
        # Verificar si Docker está instalado
        try {
            $dockerVersion = docker --version
            Write-Host "✓ Docker detectado: $dockerVersion`n" -ForegroundColor Green
        } catch {
            Write-Host "✗ ERROR: Docker no está instalado o no está en PATH" -ForegroundColor Red
            Write-Host "  Descarga Docker Desktop: https://www.docker.com/products/docker-desktop`n" -ForegroundColor Yellow
            exit 1
        }
        
        Write-Host "Iniciando contenedor..." -ForegroundColor Yellow
        docker-compose up -d
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "`n✓ Contenedor iniciado exitosamente`n" -ForegroundColor Green
            
            Write-Host "✓ Accede desde:" -ForegroundColor White
            Write-Host "  Interfaz: http://localhost:8080" -ForegroundColor Cyan
            Write-Host "  API:      http://localhost:8080/api.php`n" -ForegroundColor Cyan
            
            Write-Host "✓ Ver logs:" -ForegroundColor White
            Write-Host "  docker-compose logs -f`n" -ForegroundColor Gray
            
            Write-Host "✓ Detener:" -ForegroundColor White
            Write-Host "  docker-compose down`n" -ForegroundColor Gray
            
            # Esperar 3 segundos y hacer health check
            Write-Host "Verificando servicio..." -ForegroundColor Yellow
            Start-Sleep -Seconds 3
            
            try {
                $health = Invoke-RestMethod -Uri "http://localhost:8080/api.php?action=health"
                if ($health.success) {
                    Write-Host "✓ Servicio verificado: ONLINE`n" -ForegroundColor Green
                }
            } catch {
                Write-Host "⚠ Servicio iniciando... (espera 10 segundos y recarga)`n" -ForegroundColor Yellow
            }
            
            # Abrir navegador
            try {
                Start-Process "http://localhost:8080"
                Write-Host "✓ Abriendo navegador..." -ForegroundColor Green
            } catch {}
        } else {
            Write-Host "`n✗ ERROR al iniciar contenedor" -ForegroundColor Red
            Write-Host "  Revisa los logs: docker-compose logs`n" -ForegroundColor Yellow
        }
    }
    
    'test' {
        Write-Host "🧪 Modo Test" -ForegroundColor Green
        Write-Host "───────────────────────────────────────`n" -ForegroundColor Gray
        
        Write-Host "Ejecutando test suite...`n" -ForegroundColor Yellow
        
        # Ejecutar script de pruebas
        if (Test-Path ".\test-api.ps1") {
            .\test-api.ps1
        } else {
            Write-Host "✗ ERROR: No se encontró test-api.ps1" -ForegroundColor Red
        }
    }
    
    'stop' {
        Write-Host "🛑 Deteniendo servicios Docker" -ForegroundColor Yellow
        Write-Host "───────────────────────────────────────`n" -ForegroundColor Gray
        
        docker-compose down
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "`n✓ Servicios detenidos`n" -ForegroundColor Green
        } else {
            Write-Host "`n✗ ERROR al detener servicios`n" -ForegroundColor Red
        }
    }
}

Write-Host "───────────────────────────────────────" -ForegroundColor Gray
Write-Host "Para ayuda completa: cat README.md" -ForegroundColor Gray
Write-Host "───────────────────────────────────────`n" -ForegroundColor Gray

