Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "DEPLOYMENT A GITHUB" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

cd "c:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Proyecto"

# Verificar Git
Write-Host "`nVerificando Git..." -ForegroundColor Yellow
git --version

# Configurar Git
Write-Host "`nConfigurando Git..." -ForegroundColor Yellow
$userName = Read-Host "Tu nombre"
$userEmail = Read-Host "Tu email (de GitHub)"

git config --global user.name "$userName"
git config --global user.email "$userEmail"

# Inicializar repo
Write-Host "`nInicializando repositorio..." -ForegroundColor Yellow
git init

# Agregar remote
Write-Host "`nConectando con GitHub..." -ForegroundColor Yellow
git remote add origin https://github.com/urielmcgutierrez001-glitch/Sistema_Archivos_TAM.git 2>$null
git remote set-url origin https://github.com/urielmcgutierrez001-glitch/Sistema_Archivos_TAM.git

# Agregar archivos
Write-Host "`nAgregando archivos..." -ForegroundColor Yellow
git add .

# Commit
Write-Host "`nCreando commit..." -ForegroundColor Yellow
git commit -m "Sistema TAMEP completo con Clever Cloud config"

# Push
Write-Host "`nSubiendo a GitHub..." -ForegroundColor Yellow
git branch -M main
git push -u origin main --force

Write-Host "`n=====================================" -ForegroundColor Green
Write-Host "DEPLOYMENT COMPLETADO" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
