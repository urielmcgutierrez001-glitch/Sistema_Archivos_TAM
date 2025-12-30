<?php
/**
 * Controlador de autenticación
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\User;
use TAMEP\Core\Session;

class AuthController extends BaseController
{
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }
    
    /**
     * Mostrar formulario de login
     */
    public function showLogin()
    {
        if (Session::isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth.login', [
            'csrf_token' => $this->csrf()
        ]);
    }
    
    /**
     * Procesar login
     */
    public function login()
    {
        $username = $this->input('username');
        $password = $this->input('password');
        
        if (!$username || !$password) {
            Session::flash('error', 'Usuario y contraseña son requeridos');
            $this->redirect('/login');
        }
        
        $user = $this->userModel->authenticate($username, $password);
        
        if ($user) {
            // Guardar en sesión
            Session::set('user_id', $user['id']);
            Session::set('user', [
                'id' => $user['id'],
                'username' => $user['username'],
                'nombre_completo' => $user['nombre_completo'],
                'rol' => $user['rol']
            ]);
            
            $this->redirect('/dashboard');
        } else {
            Session::flash('error', 'Credenciales inválidas');
            $this->redirect('/login');
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout()
    {
        Session::destroy();
        $this->redirect('/login');
    }
}
