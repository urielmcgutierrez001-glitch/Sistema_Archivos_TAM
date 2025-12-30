# Guía de Ejecución - Reimportación Completa de Base de Datos

## ⚠️ ADVERTENCIA
Este proceso **BORRARÁ** todos los datos actuales y los reimportará desde los Excel.
**Asegúrate de tener backups antes de proceder.**

## Pasos de Ejecución

### 1️⃣ Backup de Datos Actuales

**Archivo:** `01_backup_completo.sql`

```sql
-- Ejecutar en MySQL Workbench
-- Crea una base de datos de backup completa
```

✅ Verificar que se cree la base de datos `tamep_backup_20251223`

---

### 2️⃣ Crear Nueva Estructura de Tablas

**Archivo:** `02_nueva_estructura.sql`

```sql
-- Crea las nuevas tablas:
-- - tipos_contenedor
-- - clasificacion_contenedor_documento
-- Modifica contenedores_fisicos
```

✅ Verificar que las tablas se hayan creado correctamente

---

### 3️⃣ Limpiar Datos Actuales

**Archivo:** `03_limpiar_datos.sql`

```sql
-- BORRA todos los registros de las tablas
-- Resetea auto_increment
```

⚠️ Este paso es **DESTRUCTIVO** - verifica backup antes de ejecutar

✅ Todas las tablas deben quedar en 0 registros

---

### 4️⃣ Instalar Dependencias Python

**Archivo:** `INSTALACION_PYTHON.md`

```powershell
pip install pandas openpyxl pymysql
```

✅ Verificar instalación con:
```python
python -c "import pandas; import pymysql; import openpyxl; print('OK')"
```

---

### 5️⃣ Configurar Script de Importación

**Archivo:** `importar_excel.py`

Editar configuración de base de datos:
```python
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # <-- CAMBIAR SI ES NECESARIO
    'database': 'tamep',
    ...
}
```

---

### 6️⃣ Ejecutar Importación

```powershell
cd "c:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Proyecto\database"
python importar_excel.py
```

**Tiempo estimado:** 5-15 minutos dependiendo de la cantidad de datos

El script:
- ✅ Lee cada uno de los 8 archivos Excel
- ✅ Expande rangos (ej: "12-20" → 12,13,14...20)
- ✅ Crea contenedores automáticamente
- ✅ Inserta documentos
- ✅ Actualiza clasificaciones

---

### 7️⃣ Verificar Importación

**Archivo:** `04_verificar_importacion.sql`

```sql
-- Ejecuta todas las queries de verificación
-- Revisa conteos, clasificaciones, rangos expandidos
```

✅ Verificar:
- Total de documentos coincide con Excel
- Contenedores tienen clasificación
- No hay contenedores vacíos
- Rangos se expandieron correctamente

---

## 📊 Resultado Esperado

Al finalizar deberías tener:

| Tabla | Contenido |
|-------|-----------|
| `registro_diario` | Todos los documentos de 7 tipos |
| `registro_hojas_ruta` | Todas las hojas de ruta |
| `contenedores_fisicos` | Amarros y Libros creados |
| `clasificacion_contenedor_documento` | Relación contenedor-tipo-gestión |
| `tipos_contenedor` | AMARRO y LIBRO |

---

## 🔙 Rollback (Si algo sale mal)

**Desde backup SQL:**
```sql
-- Ver sección de ROLLBACK en 01_backup_completo.sql
-- Restaura los datos desde tamep_backup_20251223
```

---

## ✅ Checklist Final

- [ ] Backup creado exitosamente
- [ ] Nueva estructura de tablas creada
- [ ] Datos limpiados
- [ ] Python dependencies instaladas
- [ ] Script de importación configurado
- [ ] Importación ejecutada sin errores
- [ ] Verificaciones pasan correctamente
- [ ] Sistema funciona correctamente

---

## 📝 Notas

- Los números de contenedor ya NO son únicos globalmente
- Cada contenedor puede tener múltiples tipos de documentos en diferentes gestiones
- La tabla `clasificacion_contenedor_documento` muestra qué contiene cada contenedor
- Los rangos del Excel se expanden automáticamente

---

## 🆘 Soporte

Si encuentras errores durante la importación:
1. Verifica que los archivos Excel estén en la carpeta correcta
2. Revisa que las columnas en los Excel coincidan con las esperadas
3. Verifica la configuración de MySQL en el script Python
4. Consulta los mensajes de error del script

