<?php
/**
 * Modelo ContenedorFisico
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class ContenedorFisico extends BaseModel
{
    protected $table = 'contenedores_fisicos';
    
    /**
     * Buscar libros
     */
    public function getLibros($limit = null)
    {
        return $this->where("tipo_contenedor = 'LIBRO'", [],  $limit);
    }
    
    /**
     * Buscar amarros
     */
    public function getAmarros($limit = null)
    {
        return $this->where("tipo_contenedor = 'AMARRO'", [], $limit);
    }
    
    /**
     * Verificar si está disponible para préstamo
     */
    public function isDisponible($id)
    {
        $contenedor = $this->find($id);
        
        if (!$contenedor) {
            return false;
        }
        
        // Verificar si hay préstamos activos
        $sql = "SELECT COUNT(*) as total 
                FROM prestamos 
                WHERE contenedor_fisico_id = ? 
                AND estado = 'Prestado'";
        
        $result = $this->db->fetchOne($sql, [$id]);
        
        return $result['total'] == 0;
    }
}
