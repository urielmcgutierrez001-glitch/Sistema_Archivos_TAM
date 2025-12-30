# Script para desplegar a Clever Cloud con archivos críticos

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "DEPLOYMENT A CLEVER CLOUD" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

cd "c:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Proyecto"

# Verificar que los archivos existen
Write-Host "`n📋 Verificando archivos críticos..." -ForegroundColor Yellow

$archivos = @(
    ".clevercloud/php.json",
    "composer.json",
    "config/database.php",
    "src/Middleware/AuthMiddleware.php",
    ".gitignore"
)

foreach ($archivo in $archivos) {
    if (Test-Path $archivo) {
        Write-Host "  ✅ $archivo" -ForegroundColor Green
    }
    else {
        Write-Host "  ❌ $archivo (FALTA)" -ForegroundColor Red
    }
}

# Añadir archivos a Git
Write-Host "`n📦 Añadiendo archivos a Git..." -ForegroundColor Yellow
git add .clevercloud/
git add composer.json
git add config/database.php
git add src/Middleware/AuthMiddleware.php
git add .gitignore

# Mostrar status
Write-Host "`n📊 Status de Git:" -ForegroundColor Cyan
git status --short

# Confirmar commit
Write-Host "`n⚠️  ¿Hacer commit y push? (S/N)" -ForegroundColor Yellow
$confirm = Read-Host

if ($confirm -eq "S" -or $confirm -eq "s") {
    Write-Host "`n💾 Haciendo commit..." -ForegroundColor Cyan
    git commit -m "Fix: Add Clever Cloud config and fix redirect for production deployment"
    
    Write-Host "`n🚀 Pushing a origin..." -ForegroundColor Cyan
    git push origin main
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`n✅ ¡Deployment iniciado!" -ForegroundColor Green
        Write-Host "`n⏳ Clever Cloud desplegará en ~2-3 minutos" -ForegroundColor Yellow
        Write-Host "   Verifica en: https://console.clever-cloud.com" -ForegroundColor Gray
    }
    else {
        Write-Host "`n❌ Error en push" -ForegroundColor Red
    }
}
else {
    Write-Host "`n❌ Deployment cancelado" -ForegroundColor Red
}
