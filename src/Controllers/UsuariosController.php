<?php
/**
 * Controlador de Usuarios
 * Solo accesible para administradores
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Usuario;
use TAMEP\Core\Session;

class UsuariosController extends BaseController
{
    private $usuario;
    
    public function __construct()
    {
        parent::__construct();
        $this->usuario = new Usuario();
    }
    
    /**
     * Verificar que el usuario sea administrador
     */
    private function requireAdmin()
    {
        $this->requireAuth();
        
        $user = Session::user();
        if ($user['rol'] !== 'Administrador') {
            Session::flash('error', 'No tiene permisos para acceder a esta sección');
            $this->redirect('/dashboard');
        }
    }
    
    /**
     * Listar usuarios
     */
    public function index()
    {
        $this->requireAdmin();
        
        $usuarios = $this->usuario->all();
        
        $this->view('usuarios.index', [
            'usuarios' => $usuarios,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Mostrar formulario de creación
     */
    public function crear()
    {
        $this->requireAdmin();
        
        $this->view('usuarios.crear', [
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Guardar nuevo usuario
     */
    public function guardar()
    {
        $this->requireAdmin();
        
        // Validar datos requeridos
        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['nombre_completo']) || empty($_POST['rol'])) {
            Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/admin/usuarios/crear');
        }
        
        // Verificar que el username no exista
        $existente = $this->usuario->findByUsername($_POST['username']);
        if ($existente) {
            Session::flash('error', 'El nombre de usuario ya existe');
            $this->redirect('/admin/usuarios/crear');
        }
        
        // Preparar datos
        $data = [
            'username' => $_POST['username'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'nombre_completo' => $_POST['nombre_completo'],
            'rol' => $_POST['rol'],
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        // Guardar
        $id = $this->usuario->create($data);
        
        if ($id) {
            Session::flash('success', 'Usuario creado exitosamente');
            $this->redirect('/admin/usuarios');
        } else {
            Session::flash('error', 'Error al crear el usuario');
            $this->redirect('/admin/usuarios/crear');
        }
    }
    
    /**
     * Mostrar formulario de edición
     */
    public function editar($id)
    {
        $this->requireAdmin();
        
        $usuario = $this->usuario->find($id);
        
        if (!$usuario) {
            Session::flash('error', 'Usuario no encontrado');
            $this->redirect('/admin/usuarios');
        }
        
        $this->view('usuarios.editar', [
            'usuario' => $usuario,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Actualizar usuario
     */
    public function actualizar($id)
    {
        $this->requireAdmin();
        
        $usuario = $this->usuario->find($id);
        
        if (!$usuario) {
            Session::flash('error', 'Usuario no encontrado');
            $this->redirect('/admin/usuarios');
        }
        
        // Verificar que el username no exista en otro usuario
        if ($_POST['username'] !== $usuario['username']) {
            $existente = $this->usuario->findByUsername($_POST['username']);
            if ($existente) {
                Session::flash('error', 'El nombre de usuario ya existe');
                $this->redirect('/admin/usuarios/editar/' . $id);
            }
        }
        
        // Preparar datos
        $data = [
            'username' => $_POST['username'],
            'nombre_completo' => $_POST['nombre_completo'],
            'rol' => $_POST['rol'],
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        // Actualizar contraseña solo si se proporcionó una nueva
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        // Actualizar
        $success = $this->usuario->update($id, $data);
        
        if ($success) {
            Session::flash('success', 'Usuario actualizado exitosamente');
            $this->redirect('/admin/usuarios');
        } else {
            Session::flash('error', 'Error al actualizar el usuario');
            $this->redirect('/admin/usuarios/editar/' . $id);
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function eliminar($id)
    {
        $this->requireAdmin();
        
        $usuario = $this->usuario->find($id);
        
        if (!$usuario) {
            Session::flash('error', 'Usuario no encontrado');
            $this->redirect('/admin/usuarios');
        }
        
        // No permitir eliminar el usuario actual
        $currentUser = Session::user();
        if ($currentUser['id'] == $id) {
            Session::flash('error', 'No puede eliminar su propio usuario');
            $this->redirect('/admin/usuarios');
        }
        
        // Eliminar
        $success = $this->usuario->delete($id);
        
        if ($success) {
            Session::flash('success', 'Usuario eliminado exitosamente');
        } else {
            Session::flash('error', 'Error al eliminar el usuario');
        }
        
        $this->redirect('/admin/usuarios');
    }
    
    private function getCurrentUser()
    {
        return Session::user();
    }
}
