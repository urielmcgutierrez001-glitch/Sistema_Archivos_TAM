# Script para importar base de datos a Clever Cloud
# Ejecutar después de export_local_db.ps1

# CREDENCIALES DE CLEVER CLOUD
$CC_HOST = "bf7yz05jw1xmnb2vukrs-mysql.services.clever-cloud.com"
$CC_USER = "uh5uxh0yxbs9cxva"
$CC_PASSWORD = "HdTIK6C8X5M5qsQUTXoE"
$CC_DB = "bf7yz05jw1xmnb2vukrs"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "IMPORTANDO A CLEVER CLOUD" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

# Buscar el archivo SQL más reciente
$sqlFile = Get-ChildItem -Filter "tamep_export_*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 1

if ($null -eq $sqlFile) {
    Write-Host "❌ No se encontró archivo de exportación" -ForegroundColor Red
    Write-Host "   Ejecuta primero: export_local_db.ps1" -ForegroundColor Yellow
    exit 1
}

Write-Host "`n📁 Archivo a importar: $($sqlFile.Name)" -ForegroundColor Yellow
Write-Host "   Tamaño: $([math]::Round($sqlFile.Length / 1MB, 2)) MB" -ForegroundColor Gray

# Confirmar
Write-Host "`n⚠️  ADVERTENCIA: Esto reemplazará la base de datos en Clever Cloud" -ForegroundColor Yellow
$confirm = Read-Host "¿Continuar? (S/N)"

if ($confirm -ne "S" -and $confirm -ne "s") {
    Write-Host "❌ Importación cancelada" -ForegroundColor Red
    exit 0
}

Write-Host "`n🚀 Importando a Clever Cloud..." -ForegroundColor Cyan
Write-Host "   (Esto puede tomar varios minutos)" -ForegroundColor Gray

# Importar
$command = "mysql -h $CC_HOST -u $CC_USER -p$CC_PASSWORD $CC_DB"
Get-Content $sqlFile.FullName | & cmd /c $command

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n✅ ¡Importación completada exitosamente!" -ForegroundColor Green
    Write-Host "`n📊 Verifica los datos en:" -ForegroundColor Cyan
    Write-Host "   https://console.clever-cloud.com" -ForegroundColor Gray
}
else {
    Write-Host "`n❌ Error en la importación" -ForegroundColor Red
    Write-Host "   Verifica las credenciales y la conexión" -ForegroundColor Yellow
}
