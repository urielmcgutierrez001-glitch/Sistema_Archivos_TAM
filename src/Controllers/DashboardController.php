<?php
/**
 * Controlador Dashboard
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Documento;
use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\Prestamo;

class DashboardController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        
        // Obtener estadísticas
        $documento = new Documento();
        $contenedorFisico = new ContenedorFisico();
        $prestamo = new Prestamo();
        
        $stats = [
            'total_documentos' => $documento->count(),
            'total_contenedores' => $contenedorFisico->count(),
            'total_libros' => $contenedorFisico->count("tipo_contenedor = 'LIBRO'"),
            'total_amarros' => $contenedorFisico->count("tipo_contenedor = 'AMARRO'"),
            'prestamos_activos' => $prestamo->count("estado = 'Prestado'"),
            // Stats for Charts
            'docs_prestados' => $documento->count("estado_documento = 'PRESTADO'"),
            'docs_faltantes' => $documento->count("estado_documento = 'FALTA'"),
            'docs_disponibles' => $documento->count("estado_documento = 'DISPONIBLE'"),
            'docs_no_utilizados' => $documento->count("estado_documento = 'NO UTILIZADO'"),
            'docs_anulados' => $documento->count("estado_documento = 'ANULADO'"),
        ];
        
        $this->view('dashboard.index', [
            'stats' => $stats,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    private function getCurrentUser()
    {
        return \TAMEP\Core\Session::user();
    }
}
