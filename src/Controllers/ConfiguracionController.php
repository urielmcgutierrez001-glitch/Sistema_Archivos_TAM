<?php

namespace TAMEP\Controllers;

use TAMEP\Models\Usuario;
use TAMEP\Core\Session;

class ConfiguracionController extends BaseController
{
    private $usuario;
    
    public function __construct()
    {
        parent::__construct();
        $this->usuario = new Usuario();
    }
    
    /**
     * Show password change form
     */
    public function password()
    {
        $this->requireAuth();
        
        $this->view('configuracion.password', [
            'user' => Session::user()
        ]);
    }
    
    /**
     * Update password
     */
    public function updatePassword()
    {
        $this->requireAuth();
        
        $currentUser = Session::user();
        $userId = $currentUser['id'];
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Session::flash('error', 'Todos los campos son obligatorios');
            $this->redirect('/configuracion/password');
        }
        
        if ($newPassword !== $confirmPassword) {
            Session::flash('error', 'La nueva contraseña y su confirmación no coinciden');
            $this->redirect('/configuracion/password');
        }
        
        if (strlen($newPassword) < 6) {
            Session::flash('error', 'La nueva contraseña debe tener al menos 6 caracteres');
            $this->redirect('/configuracion/password');
        }
        
        // Verify current password
        // Since we don't have a direct method in Usuario model to just check password, 
        // we'll fetch the user and check manually.
        $userRecord = $this->usuario->find($userId);
        
        if (!$userRecord || !password_verify($currentPassword, $userRecord['password_hash'])) {
            Session::flash('error', 'La contraseña actual es incorrecta');
            $this->redirect('/configuracion/password');
        }
        
        // Update password
        $data = [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ];
        
        if ($this->usuario->update($userId, $data)) {
            Session::flash('success', 'Contraseña actualizada exitosamente');
            $this->redirect('/configuracion/password');
        } else {
            Session::flash('error', 'Error al actualizar la contraseña');
            $this->redirect('/configuracion/password');
        }
    }
}
