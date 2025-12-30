# Script para exportar base de datos local
# Ejecutar desde: Proyecto/database/

# Configuración
$LOCAL_USER = "root"
$LOCAL_PASSWORD = ""  # Cambiar si tienes password
$LOCAL_DB = "tamep_archivos"
$OUTPUT_FILE = "tamep_export_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "EXPORTANDO BASE DE DATOS LOCAL" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

# Exportar base de datos
Write-Host "`nExportando $LOCAL_DB..." -ForegroundColor Yellow

if ($LOCAL_PASSWORD -eq "") {
    mysqldump -u $LOCAL_USER $LOCAL_DB > $OUTPUT_FILE
}
else {
    mysqldump -u $LOCAL_USER -p$LOCAL_PASSWORD $LOCAL_DB > $OUTPUT_FILE
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Exportación exitosa: $OUTPUT_FILE" -ForegroundColor Green
    
    # Mostrar tamaño
    $fileSize = (Get-Item $OUTPUT_FILE).Length / 1MB
    Write-Host "   Tamaño: $([math]::Round($fileSize, 2)) MB" -ForegroundColor Gray
    
    Write-Host "`n📤 Siguiente paso: Ejecutar import_to_clevercloud.ps1" -ForegroundColor Cyan
}
else {
    Write-Host "❌ Error en la exportación" -ForegroundColor Red
    Write-Host "Verifica que MySQL esté corriendo y las credenciales sean correctas" -ForegroundColor Yellow
}
