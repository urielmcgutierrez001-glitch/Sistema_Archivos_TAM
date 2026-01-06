<?php
/**
 * Controlador Dashboard
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\RegistroDiario;
use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\Prestamo;

class DashboardController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        
        // Obtener estadísticas
        $registroDiario = new RegistroDiario();
        $contenedorFisico = new ContenedorFisico();
        $prestamo = new Prestamo();
        
        $stats = [
            'total_documentos' => $registroDiario->count(),
            'total_contenedores' => $contenedorFisico->count(),
            'total_libros' => $contenedorFisico->count("tipo_contenedor = 'LIBRO'"),
            'total_amarros' => $contenedorFisico->count("tipo_contenedor = 'AMARRO'"),
            'prestamos_activos' => $prestamo->count("estado = 'Prestado'"),
            // Stats for Charts
            'docs_prestados' => $registroDiario->count("estado_documento = 'PRESTADO'"),
            'docs_faltantes' => $registroDiario->count("estado_documento = 'FALTA'"),
            'docs_disponibles' => $registroDiario->count("estado_documento = 'DISPONIBLE'"),
            'docs_no_utilizados' => $registroDiario->count("estado_documento = 'NO UTILIZADO'"),
            'docs_anulados' => $registroDiario->count("estado_documento = 'ANULADO'"),
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
