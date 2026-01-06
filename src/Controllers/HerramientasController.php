<?php
/**
 * Controlador de Herramientas
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

class HerramientasController extends BaseController
{
    /**
     * Mostrar vista de Control de Amarros
     */
    public function controlAmarros()
    {
        $this->requireAuth();
        
        $this->view('herramientas.control_amarros', [
            'user' => $this->getCurrentUser()
        ]);
    }

    private function getCurrentUser()
    {
        return \TAMEP\Core\Session::user();
    }
}
