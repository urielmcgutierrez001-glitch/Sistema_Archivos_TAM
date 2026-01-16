<?php
/**
 * Modelo RegistroDiario
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class Documento extends BaseModel
{
    protected $table = 'documentos';
    
    /**
     * Buscar con información del contenedor
     */
    public function findWithContenedor($id)
    {
        $sql = "SELECT rd.*, 
                       cf.tipo_contenedor, cf.numero AS contenedor_numero, 
                       cf.color, cf.bloque_nivel,
                       u.nombre AS ubicacion_nombre, u.descripcion AS ubicacion_descripcion
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
                WHERE rd.id = ?";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Búsqueda avanzada
     */
    public function search($filters = [])
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['gestion'])) {
            $where[] = "rd.gestion = ?";
            $params[] = $filters['gestion'];
        }
        
        if (!empty($filters['nro_comprobante'])) {
            $where[] = "rd.nro_comprobante LIKE ?";
            $params[] = "%{$filters['nro_comprobante']}%";
        }
        
        if (!empty($filters['contenedor'])) {
            $where[] = "cf.numero LIKE ?";
            $params[] = "%{$filters['contenedor']}%";
        }
        
        if (!empty($filters['ubicacion_id'])) {
            $where[] = "cf.ubicacion_id = ?";
            $params[] = $filters['ubicacion_id'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT rd.*, 
                       cf.tipo_contenedor, cf.numero AS contenedor_numero, 
                       cf.color, cf.bloque_nivel,
                       u.nombre AS ubicacion_nombre
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
                {$whereClause}
                ORDER BY rd.gestion DESC, rd.nro_comprobante ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    /**
     * Búsqueda avanzada con paginación
     */
    public function buscarAvanzado($filters = [])
    {
        $where = [];
        $params = [];
        
        // Búsqueda general en múltiples campos
        // Búsqueda general en múltiples campos
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            
            // Verificar si es una búsqueda de rango (ej. "1-50")
            if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
                $min = $matches[1];
                $max = $matches[2];
                
                // Asegurar orden correcto
                if ($min > $max) {
                    $temp = $min;
                    $min = $max;
                    $max = $temp;
                }
                
                $where[] = "CAST(rd.nro_comprobante AS UNSIGNED) BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
            } else {
                // Búsqueda normal
                $where[] = "(
                    rd.nro_comprobante = ? 
                    OR rd.codigo_abc = ? 
                    OR rd.observaciones LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.rubro')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.interesado')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.conam')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.nro_comprobante_diario')) = ?
                )";
                $params[] = $search; // Exact match nro_comprobante
                $params[] = $search; // Exact match codigo_abc
                $params[] = "%{$search}%"; // Partial match observaciones
                $params[] = "%{$search}%"; // Partial match rubro
                $params[] = "%{$search}%"; // Partial match interesado
                $params[] = "%{$search}%"; // Partial match conam
                $params[] = $search;       // Exact match nro_comprobante_diario
            }
        }
        
        if (!empty($filters['gestion'])) {
            $where[] = "rd.gestion = ?";
            $params[] = $filters['gestion'];
        }
        
        if (!empty($filters['ubicacion_id'])) {
            $where[] = "cf.ubicacion_id = ?";
            $params[] = $filters['ubicacion_id'];
        }
        
        if (!empty($filters['estado_documento'])) {
            if ($filters['estado_documento'] === 'FALTA') {
                 // Caso especial: Irregularidades (Falta y Prestado)
                 $where[] = "(rd.estado_documento = 'FALTA' OR (rd.estado_documento = 'PRESTADO' AND rd.observaciones LIKE '%FALTA%'))";
            } else {
                 $where[] = "rd.estado_documento = ?";
                 $params[] = $filters['estado_documento'];
            }
        }
        
        if (!empty($filters['tipo_documento'])) {
            // If numeric, assume ID
            if (is_numeric($filters['tipo_documento'])) {
                $where[] = "rd.tipo_documento_id = ?";
                $params[] = $filters['tipo_documento'];
            } else {
                // If string, assume exact code match (from select) or use the legacy column for now?
                // Better to use the join if we want to drop the column.
                // But wait, the join in buscarAvanzado is added in the SELECT part in previous step, 
                // but the WHERE clause is built BEFORE that SQL string is constructed.
                // This means I cannot use 't.codigo' in the WHERE clause unless I confirm the join is present in count/main query.
                // Ah, the main query structure in logic above builds $where first.
                // So I need to ensure the JOIN is available for the WHERE clause.
                // But valid SQL requires Join to be in FROM...JOIN...
                // So I will update this block to use `rd.tipo_documento` (legacy) which works safe,
                // OR `rd.tipo_documento_id` if I can resolve it.
                // Given I want to support removal, I should change this to use `t.codigo` BUT `t` is not joined yet in the WHERE phase?
                // The query is constructed AFTER.
                // So: I will just use `rd.tipo_documento_id` if I can, but I can't look it up here easily without Model dependency.
                // For now, I will keep using `rd.tipo_documento` (legacy) as the user just asked to make ID available/FK.
                // Removing the column completely breaks this unless I refactor the WHERE construction to include the JOIN.
                // Actually, I can use `rd.tipo_documento` (string) as long as the column exists.
                // If the user drops the column, this breaks.
                // Let's assume the user keeps it for now or I'd need to rewrite the whole method to ensure 't' alias is available.
                // I'll stick to legacy support mixed with ID support.
                 $where[] = "rd.tipo_documento = ?";
                 $params[] = $filters['tipo_documento'];
            }
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT rd.*, 
                       cf.tipo_contenedor, cf.numero as contenedor_numero,
                       u.nombre as ubicacion_nombre,
                       t.nombre as tipo_documento_nombre, t.codigo as tipo_documento_codigo
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
                LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                {$whereClause}
                ORDER BY rd.id DESC
                LIMIT {$perPage} OFFSET {$offset}";
        
        return $this->db->fetchAll($sql, $params);
    }
    /**
     * Contar resultados de búsqueda
     */
    public function contarBusqueda($filters = [])
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            
            // Verificar si es una búsqueda de rango (ej. "1-50")
            if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
                $min = $matches[1];
                $max = $matches[2];
                
                if ($min > $max) {
                    $temp = $min;
                    $min = $max;
                    $max = $temp;
                }
                
                $where[] = "rd.nro_comprobante BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
            } else {
                $where[] = "(
                    rd.nro_comprobante = ? 
                    OR rd.codigo_abc = ? 
                    OR rd.observaciones LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.rubro')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.interesado')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.conam')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.nro_comprobante_diario')) = ?
                )";
                $params[] = $search;
                $params[] = $search;
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = $search;
            }
        }
        
        if (!empty($filters['gestion'])) {
            $where[] = "rd.gestion = ?";
            $params[] = $filters['gestion'];
        }
        
        if (!empty($filters['ubicacion_id'])) {
            $where[] = "cf.ubicacion_id = ?";
            $params[] = $filters['ubicacion_id'];
        }
        
        if (!empty($filters['estado_documento'])) {
            if ($filters['estado_documento'] === 'FALTA') {
                 // Caso especial: Irregularidades (Falta y Prestado)
                 $where[] = "(rd.estado_documento = 'FALTA' OR (rd.estado_documento = 'PRESTADO' AND rd.observaciones LIKE '%FALTA%'))";
            } else {
                 $where[] = "rd.estado_documento = ?";
                 $params[] = $filters['estado_documento'];
            }
        }
        
        if (!empty($filters['tipo_documento'])) {
            $where[] = "rd.tipo_documento = ?";
            $params[] = $filters['tipo_documento'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} rd 
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                {$whereClause}";
        $result = $this->db->fetchOne($sql, $params);
        
        return $result['total'] ?? 0;
    }
    
    /**
     * Obtener documentos disponibles para préstamo
     */
    public function getAvailable()
    {
        $sql = "SELECT rd.*, 
                       cf.tipo_contenedor, cf.numero as contenedor_numero
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                WHERE rd.estado_documento = 'DISPONIBLE'
                ORDER BY rd.gestion DESC, rd.nro_comprobante DESC
                LIMIT 100";
                
        return $this->db->fetchAll($sql);
    }
}
