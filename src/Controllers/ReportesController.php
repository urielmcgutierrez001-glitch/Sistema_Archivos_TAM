<?php
/**
 * Controlador de Reportes
 * Gestiona reportes y estadísticas del sistema
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Prestamo;
use TAMEP\Models\RegistroDiario;
use TAMEP\Models\Usuario;
use TAMEP\Core\Session;

class ReportesController extends BaseController
{
    private $prestamo;
    private $registroDiario;
    private $usuario;
    
    public function __construct()
    {
        parent::__construct();
        $this->prestamo = new Prestamo();
        $this->registroDiario = new RegistroDiario();
        $this->usuario = new Usuario();
    }
    
    /**
     * Dashboard de reportes
     */
    public function index()
    {
        $this->requireAuth();
        
        // SECCIÓN 1: Préstamos Activos
        $sql = "SELECT p.id,
                       p.documento_tipo,
                       p.documento_id,
                       p.usuario_id,
                       p.contenedor_fisico_id,
                       p.fecha_prestamo,
                       p.fecha_devolucion_esperada,
                       p.fecha_devolucion_real,
                       p.observaciones,
                       p.estado,
                       u.nombre_completo as usuario_nombre,
                       u.username,
                       rd.tipo_documento,
                       rd.gestion,
                       rd.nro_comprobante,
                       rd.codigo_abc,
                       cf.tipo_contenedor,
                       cf.numero as contenedor_numero,
                       DATEDIFF(p.fecha_devolucion_esperada, CURDATE()) as dias_restantes
                FROM prestamos p
                INNER JOIN usuarios u ON p.usuario_id = u.id
                INNER JOIN registro_diario rd ON p.documento_id = rd.id
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                WHERE p.estado = 'Prestado'
                ORDER BY p.fecha_devolucion_esperada ASC";
        
        $prestamosActivos = $this->prestamo->getDb()->fetchAll($sql);
        
        // SECCIÓN 2: Documentos No Disponibles
        $sqlNoDisponibles = "SELECT rd.*,
                                    cf.tipo_contenedor,
                                    cf.numero as contenedor_numero,
                                    CASE 
                                        WHEN rd.estado_documento = 'PRESTADO' THEN p.usuario_id
                                        ELSE NULL
                                    END as prestado_a_usuario_id,
                                    CASE 
                                        WHEN rd.estado_documento = 'PRESTADO' THEN u.nombre_completo
                                        ELSE NULL
                                    END as prestado_a_usuario
                            FROM registro_diario rd
                            LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                            LEFT JOIN prestamos p ON rd.id = p.documento_id AND p.estado = 'Prestado'
                            LEFT JOIN usuarios u ON p.usuario_id = u.id
                            WHERE rd.estado_documento IN ('FALTA', 'ANULADO', 'PRESTADO')
                            ORDER BY 
                                FIELD(rd.estado_documento, 'FALTA', 'PRESTADO', 'ANULADO'),
                                rd.gestion DESC,
                                rd.nro_comprobante DESC";
        
        $documentosNoDisponibles = $this->registroDiario->getDb()->fetchAll($sqlNoDisponibles);
        
        // Estadísticas rápidas
        $stats = [
            'total_prestados' => count($prestamosActivos),
            'prestamos_vencidos' => count(array_filter($prestamosActivos, fn($p) => $p['dias_restantes'] < 0)),
            'total_faltantes' => count(array_filter($documentosNoDisponibles, fn($d) => $d['estado_documento'] === 'FALTA')),
            'total_anulados' => count(array_filter($documentosNoDisponibles, fn($d) => $d['estado_documento'] === 'ANULADO'))
        ];
        
        $this->view('reportes.index', [
            'prestamosActivos' => $prestamosActivos,
            'documentosNoDisponibles' => $documentosNoDisponibles,
            'stats' => $stats,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    private function getCurrentUser()
    {
        return Session::user();
    }
}
