# Script para verificar conexión a Clever Cloud MySQL

$CC_HOST = "bf7yz05jw1xmnb2vukrs-mysql.services.clever-cloud.com"
$CC_USER = "uh5uxh0yxbs9cxva"
$CC_PASSWORD = "HdTIK6C8X5M5qsQUTXoE"
$CC_DB = "bf7yz05jw1xmnb2vukrs"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "VERIFICANDO CONEXIÓN A CLEVER CLOUD" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

Write-Host "`n📡 Conectando a MySQL..." -ForegroundColor Yellow

$query = @"
SELECT 'Conexión exitosa!' as mensaje;
SHOW TABLES;
"@

$query | mysql -h $CC_HOST -u $CC_USER -p$CC_PASSWORD $CC_DB

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n✅ Conexión exitosa a Clever Cloud MySQL" -ForegroundColor Green
    
    # Contar documentos
    Write-Host "`n📊 Estadísticas de la base de datos:" -ForegroundColor Cyan
    
    $countQuery = @"
SELECT 
    (SELECT COUNT(*) FROM registro_diario) as total_documentos,
    (SELECT COUNT(*) FROM contenedores_fisicos) as total_contenedores;
"@
    
    $countQuery | mysql -h $CC_HOST -u $CC_USER -p$CC_PASSWORD $CC_DB
    
}
else {
    Write-Host "`n❌ Error de conexión" -ForegroundColor Red
    Write-Host "Verifica:" -ForegroundColor Yellow
    Write-Host "  - MySQL client está instalado" -ForegroundColor Gray
    Write-Host "  - Credenciales son correctas" -ForegroundColor Gray
    Write-Host "  - Firewall permite conexión" -ForegroundColor Gray
}
