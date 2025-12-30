# Instalación de Dependencias Python para Importación

## Paquetes Necesarios

El script de importación requiere las siguientes librerías Python:

```bash
pip install pandas openpyxl pymysql
```

## Detalle de Paquetes

- **pandas**: Para leer y procesar archivos Excel
- **openpyxl**: Motor para leer/escribir archivos .xlsx
- **pymysql**: Conector MySQL para Python

## Instalación Completa

```powershell
# Opción 1: Instalación simple
pip install pandas openpyxl pymysql

# Opción 2: Con archivo requirements.txt
# Crear requirements.txt con:
pandas>=2.0.0
openpyxl>=3.1.0
pymysql>=1.1.0

# Luego ejecutar:
pip install -r requirements.txt
```

## Verificación

```python
# Verificar instalación
python -c "import pandas; import pymysql; import openpyxl; print('✅ Todas las dependencias instaladas')"
```

## Configuración de Base de Datos

Antes de ejecutar el script, verificar en `importar_excel.py`:

```python
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # <-- CAMBIAR SI TIENES CONTRASEÑA
    'database': 'tamep',
    ...
}
```

## Ejecución

```powershell
# Desde la carpeta database/
python importar_excel.py
```
