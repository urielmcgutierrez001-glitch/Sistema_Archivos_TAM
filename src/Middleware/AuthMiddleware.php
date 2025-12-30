<?php
/**
 * Middleware de Autenticación
 * 
 * @package TAMEP\Middleware
 */

namespace TAMEP\Middleware;

use TAMEP\Core\Session;

class AuthMiddleware
{
    public function handle()
    {
        if (!Session::isAuthenticated()) {
            // Redirigir a login (funciona tanto en local como en Clever Cloud)
            header('Location: /login');
            exit;
        }
        
        return true;
    }
}
