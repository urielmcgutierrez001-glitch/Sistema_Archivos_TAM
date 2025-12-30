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
