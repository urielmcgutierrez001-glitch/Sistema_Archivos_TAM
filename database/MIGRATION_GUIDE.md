# Guía de Migración - TAMEP Database

## ⚠️ IMPORTANTE: Backup Primero

Antes de ejecutar cualquier migración, **DEBES crear un backup**:

```bash
# Opción 1: Desde PowerShell
cd "C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Proyecto\database"
mysqldump -u root -p tamep_archivos > backup_tamep_2025-12-22.sql

# Opción 2: Desde MySQL Workbench
# File → Export → Export Database
# Seleccionar: tamep_archivos
# Guardar como: backup_tamep_2025-12-22.sql
```

---

## 📋 Pasos de Migración

### Paso 1: Crear Tabla tipo_documento

Ejecuta: `migration_01_tipo_documento.sql`

**Qué hace:**
- ✅ Crea tabla `tipo_documento` normalizada
- ✅ Inserta los 8 tipos de documentos existentes
- ✅ Agrega código, nombre, descripción para cada tipo

**Verificación:**
```sql
SELECT * FROM tipo_documento ORDER BY orden;
```

Deberías ver 8 registros.

---

### Paso 2: Migrar registro_egreso → registro_diario

Ejecuta: `migration_02_migrar_egreso.sql`

**Qué hace:**
- ✅ Agrega columna `tabla_origen` en `registro_diario`
- ✅ Migra TODOS los registros de `registro_egreso` a `registro_diario`
- ✅ Asigna `tipo_documento = 'REGISTRO_CEPS'` a todos
- ✅ Convierte `estado_perdido` → `estado_documento`

**Verificación:**
```sql
-- Ver cuántos se migraron
SELECT COUNT(*) FROM registro_diario WHERE tabla_origen = 'registro_egreso';

-- Ver el CEPS 8255
SELECT * FROM registro_diario WHERE nro_comprobante = '8255';
```

---

### Paso 3 (OPCIONAL): Normalizar con Foreign Key

Ejecuta: `migration_03_normalizar_opcional.sql`

**Qué hace:**
- ✅ Agrega columna `tipo_documento_id` (INT)
- ✅ Crea relación foreign key con `tipo_documento`
- ✅ Mantiene `tipo_documento` (VARCHAR) por compatibilidad

**Ventajas:**
- 🚀 Búsquedas más rápidas
- 🔒 Integridad referencial
- 📊 Facilita reportes

**Este paso es OPCIONAL** - el sistema funcionará sin él.

---

## ✅ Verificación Final

Después de ejecutar las migraciones:

### 1. Buscar el CEPS 8255
```sql
SELECT * FROM registro_diario 
WHERE nro_comprobante = '8255' AND tipo_documento = 'REGISTRO_CEPS';
```

### 2. Verificar totales
```sql
SELECT 
    tipo_documento,
    COUNT(*) as total
FROM registro_diario
GROUP BY tipo_documento;
```

### 3. Probar búsqueda en la aplicación
- Ir a: http://localhost:8000/catalogacion
- Buscar: 8255
- Tipo: Registro CEPS
- ✅ Debería aparecer

---

## 🔄 Rollback (Si algo sale mal)

```bash
# Restaurar desde backup
mysql -u root -p tamep_archivos < backup_tamep_2025-12-22.sql
```

---

## 📝 Notas

- La tabla `registro_egreso` **NO se elimina** - queda intacta
- Todos los registros se **copian** (no se mueven)
- Puedes marcar `activo=0` en `registro_egreso` después si quieres
- El campo `tabla_origen` te permite saber de dónde vino cada registro

---

## 🎯 Resultado Esperado

Después de la migración:
- ✅ Tabla `tipo_documento` con 8 tipos
- ✅ Todos los CEPS en `registro_diario`
- ✅ CEPS 8255 visible en búsqueda
- ✅ Base de datos con backup seguro
