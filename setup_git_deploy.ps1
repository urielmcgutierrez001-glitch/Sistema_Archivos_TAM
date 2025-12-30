# ========================================
# SCRIPT DE CONFIGURACIÓN Y DEPLOYMENT
# Sistema TAMEP - GitHub y Clever Cloud
# ========================================

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "CONFIGURACIÓN DE GIT Y DEPLOYMENT" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

# Navegar al directorio del proyecto
cd "c:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Proyecto"

# ========================================
# PASO 1: Verificar Git
# ========================================
Write-Host "`n📋 PASO 1: Verificando Git..." -ForegroundColor Yellow

try {
    $gitVersion = git --version 2>&1
    Write-Host "  ✅ Git instalado: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Git NO detectado" -ForegroundColor Red
    Write-Host "     Por favor cierra y reabre PowerShell, luego ejecuta este script nuevamente" -ForegroundColor Yellow
    exit 1
}

# ========================================
# PASO 2: Configurar Git
# ========================================
Write-Host "`n📋 PASO 2: Configurando Git..." -ForegroundColor Yellow

$userName = Read-Host "Ingresa tu nombre (ej: Juan Pérez)"
$userEmail = Read-Host "Ingresa tu email (debe ser el mismo de GitHub)"

git config --global user.name "$userName"
git config --global user.email "$userEmail"

Write-Host "  ✅ Configuración guardada" -ForegroundColor Green

# ========================================
# PASO 3: Inicializar Repositorio
# ========================================
Write-Host "`n📋 PASO 3: Inicializando repositorio..." -ForegroundColor Yellow

# Verificar si ya existe .git
if (Test-Path ".git") {
    Write-Host "  ℹ️  Repositorio ya inicializado" -ForegroundColor Gray
} else {
    git init
    Write-Host "  ✅ Repositorio inicializado" -ForegroundColor Green
}

# ========================================
# PASO 4: Agregar Remote de GitHub
# ========================================
Write-Host "`n📋 PASO 4: Conectando con GitHub..." -ForegroundColor Yellow

$repoUrl = "https://github.com/urielmcgutierrez001-glitch/Sistema_Archivos_TAM.git"

# Verificar si el remote ya existe
$remoteExists = git remote | Select-String "origin"

if ($remoteExists) {
    Write-Host "  ℹ️  Remote 'origin' ya existe, actualizando URL..." -ForegroundColor Gray
    git remote set-url origin $repoUrl
} else {
    git remote add origin $repoUrl
}

Write-Host "  ✅ Conectado a: $repoUrl" -ForegroundColor Green

# ========================================
# PASO 5: Preparar Archivos
# ========================================
Write-Host "`n📋 PASO 5: Preparando archivos para commit..." -ForegroundColor Yellow

# Agregar todos los archivos EXCEPTO .gitignore primero
git add .

Write-Host "  ✅ Archivos agregados" -ForegroundColor Green

# Mostrar status
Write-Host "`n📊 Archivos a subir:" -ForegroundColor Cyan
git status --short | Select-Object -First 20

# ========================================
# PASO 6: Commit
# ========================================
Write-Host "`n📋 PASO 6: Creando commit..." -ForegroundColor Yellow

$commitMessage = "feat: Sistema de Gestión de Archivos TAMEP

- 36,484 documentos normalizados
- 1,348 contenedores físicos
- Normalización 3NF completada
- Configuración Clever Cloud incluida
- Redirect fix para producción"

git commit -m "$commitMessage"

if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✅ Commit creado exitosamente" -ForegroundColor Green
} else {
    Write-Host "  ⚠️  No hay cambios para commitear o error en commit" -ForegroundColor Yellow
}

# ========================================
# PASO 7: Push a GitHub
# ========================================
Write-Host "`n📋 PASO 7: Subiendo a GitHub..." -ForegroundColor Yellow
Write-Host "  ⚠️  Esto puede solicitar tus credenciales de GitHub" -ForegroundColor Yellow

git branch -M main
git push -u origin main --force

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n✅ ¡DEPLOYMENT EXITOSO!" -ForegroundColor Green
    Write-Host "`n🎯 Próximos pasos:" -ForegroundColor Cyan
    Write-Host "  1. Ve a GitHub: https://github.com/urielmcgutierrez001-glitch/Sistema_Archivos_TAM" -ForegroundColor Gray
    Write-Host "  2. Verifica que los archivos se subieron" -ForegroundColor Gray
    Write-Host "  3. Clever Cloud detectará el push y re-desplegará automáticamente (~2-3 min)" -ForegroundColor Gray
    Write-Host "  4. Verifica el deployment en: https://console.clever-cloud.com" -ForegroundColor Gray
} else {
    Write-Host "`n❌ Error en push" -ForegroundColor Red
    Write-Host "  Posibles causas:" -ForegroundColor Yellow
    Write-Host "  - Credenciales incorrectas" -ForegroundColor Gray
    Write-Host "  - No tienes permisos en el repositorio" -ForegroundColor Gray
    Write-Host "  - Problema de conexión a internet" -ForegroundColor Gray
}

Write-Host "`n=====================================" -ForegroundColor Cyan
Write-Host "PROCESO COMPLETADO" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
