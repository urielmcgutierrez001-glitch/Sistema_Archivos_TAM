<?php
/**
 * Punto de entrada de la aplicación
 * Sistema TAMEP - Gestión Documental
 */

// Autoloader
require_once __DIR__ . '/autoload.php';

use TAMEP\Core\Router;
use TAMEP\Core\Session;

// Iniciar sesión
Session::start();

// Error reporting (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Crear router
$router = new Router();

// ====================================
// RUTAS PÚBLICAS
// ====================================

// Login
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// ====================================
// RUTAS PROTEGIDAS (requieren auth)
// ====================================

// Dashboard
$router->get('/', 'DashboardController@index', ['AuthMiddleware']);
$router->get('/dashboard', 'DashboardController@index', ['AuthMiddleware']);

// Catalogación
$router->get('/catalogacion', 'CatalogacionController@index', ['AuthMiddleware']);
$router->get('/catalogacion/crear', 'CatalogacionController@crear', ['AuthMiddleware']);
$router->post('/catalogacion/guardar', 'CatalogacionController@guardar', ['AuthMiddleware']);
$router->get('/catalogacion/ver/{id}', 'CatalogacionController@ver', ['AuthMiddleware']);
$router->get('/catalogacion/editar/{id}', 'CatalogacionController@editar', ['AuthMiddleware']);
$router->post('/catalogacion/actualizar/{id}', 'CatalogacionController@actualizar', ['AuthMiddleware']);
$router->get('/catalogacion/eliminar/{id}', 'CatalogacionController@eliminar', ['AuthMiddleware']);

// Préstamos
$router->get('/prestamos', 'PrestamosController@index', ['AuthMiddleware']);
$router->get('/prestamos/nuevo', 'PrestamosController@nuevo', ['AuthMiddleware']);
$router->post('/prestamos/guardar-multiple', 'PrestamosController@guardarMultiple', ['AuthMiddleware']);
$router->get('/prestamos/crear', 'PrestamosController@crear', ['AuthMiddleware']);
$router->post('/prestamos/guardar', 'PrestamosController@guardar', ['AuthMiddleware']);
$router->get('/prestamos/ver/{id}', 'PrestamosController@ver', ['AuthMiddleware']);
$router->get('/prestamos/devolver/{id}', 'PrestamosController@devolver', ['AuthMiddleware']);

// Reportes
$router->get('/reportes', 'ReportesController@index', ['AuthMiddleware']);

// Usuarios (solo administrador)
$router->get('/admin/usuarios', 'UsuariosController@index', ['AuthMiddleware']);
$router->get('/admin/usuarios/crear', 'UsuariosController@crear', ['AuthMiddleware']);
$router->post('/admin/usuarios/guardar', 'UsuariosController@guardar', ['AuthMiddleware']);
$router->get('/admin/usuarios/editar/{id}', 'UsuariosController@editar', ['AuthMiddleware']);
$router->post('/admin/usuarios/actualizar/{id}', 'UsuariosController@actualizar', ['AuthMiddleware']);
$router->get('/admin/usuarios/eliminar/{id}', 'UsuariosController@eliminar', ['AuthMiddleware']);

// Normalización (solo admin) - TODO: Crear NormalizacionController
// $router->get('/normalizacion', 'NormalizacionController@index', ['AuthMiddleware']);

// Ejecutar router
$router->dispatch();
