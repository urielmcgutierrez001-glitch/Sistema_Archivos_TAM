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
$router->get('/inicio', 'DashboardController@index', ['AuthMiddleware']);

// Catalogación
$router->get('/catalogacion', 'CatalogacionController@index', ['AuthMiddleware']);
$router->get('/catalogacion/crear', 'CatalogacionController@crear', ['AuthMiddleware']);
$router->post('/catalogacion/guardar', 'CatalogacionController@guardar', ['AuthMiddleware']);
$router->get('/catalogacion/ver/{id}', 'CatalogacionController@ver', ['AuthMiddleware']);
$router->get('/catalogacion/editar/{id}', 'CatalogacionController@editar', ['AuthMiddleware']);
$router->post('/catalogacion/actualizar/{id}', 'CatalogacionController@actualizar', ['AuthMiddleware']);
$router->get('/catalogacion/eliminar/{id}', 'CatalogacionController@eliminar', ['AuthMiddleware']);
$router->post('/catalogacion/lote/actualizar', 'CatalogacionController@actualizarLote', ['AuthMiddleware']);

// Préstamos
$router->get('/prestamos', 'PrestamosController@index', ['AuthMiddleware']);
$router->get('/prestamos/nuevo', 'PrestamosController@nuevo', ['AuthMiddleware']);
$router->post('/prestamos/guardar-multiple', 'PrestamosController@guardarMultiple', ['AuthMiddleware']);
$router->get('/prestamos/crear', 'PrestamosController@crear', ['AuthMiddleware']);
$router->post('/prestamos/guardar', 'PrestamosController@guardar', ['AuthMiddleware']);
$router->get('/prestamos/ver/{id}', 'PrestamosController@ver', ['AuthMiddleware']);
$router->post('/prestamos/actualizarEstados', 'PrestamosController@actualizarEstados', ['AuthMiddleware']); // New Route
$router->get('/prestamos/devolver/{id}', 'PrestamosController@devolver', ['AuthMiddleware']);
$router->get('/prestamos/exportar-pdf/{id}', 'PrestamosController@exportarPdf', ['AuthMiddleware']);
$router->get('/prestamos/exportar-excel/{id}', 'PrestamosController@exportarExcel', ['AuthMiddleware']);
$router->post('/prestamos/agregarDetalle', 'PrestamosController@agregarDetalle', ['AuthMiddleware']);
$router->get('/prestamos/quitarDetalle/{id}', 'PrestamosController@quitarDetalle', ['AuthMiddleware']);
$router->get('/prestamos/importar', 'PrestamosController@vistaImportar', ['AuthMiddleware']);
$router->post('/prestamos/importar/procesar', 'PrestamosController@procesarImportacion', ['AuthMiddleware']);
$router->get('/prestamos/procesar/{id}', 'PrestamosController@procesar', ['AuthMiddleware']);
$router->get('/prestamos/editar/{id}', 'PrestamosController@editar', ['AuthMiddleware']);
$router->post('/prestamos/actualizarEncabezado/{id}', 'PrestamosController@actualizarEncabezado', ['AuthMiddleware']);
$router->post('/prestamos/confirmarProceso', 'PrestamosController@confirmarProceso', ['AuthMiddleware']);
$router->get('/prestamos/revertirProceso/{id}', 'PrestamosController@revertirProceso', ['AuthMiddleware']);

// Reportes
$router->get('/reportes', 'ReportesController@index', ['AuthMiddleware']);

// Contenedores
$router->get('/contenedores', 'ContenedoresController@index', ['AuthMiddleware']);
$router->get('/contenedores/crear', 'ContenedoresController@crear', ['AuthMiddleware']);
$router->post('/contenedores/guardar', 'ContenedoresController@guardar', ['AuthMiddleware']);
$router->post('/contenedores/guardar-rapido', 'ContenedoresController@guardarRapido', ['AuthMiddleware']);
$router->get('/contenedores/ver/{id}', 'ContenedoresController@ver', ['AuthMiddleware']);
$router->get('/contenedores/editar/{id}', 'ContenedoresController@editar', ['AuthMiddleware']);
$router->post('/contenedores/actualizar/{id}', 'ContenedoresController@actualizar', ['AuthMiddleware']);
$router->get('/contenedores/eliminar/{id}', 'ContenedoresController@eliminar', ['AuthMiddleware']);
$router->get('/contenedores/api-buscar', 'ContenedoresController@apiBuscar', ['AuthMiddleware']);
$router->post('/contenedores/actualizar-ubicacion-masiva', 'ContenedoresController@actualizarUbicacionMasiva', ['AuthMiddleware']);

// Usuarios (solo administrador)
$router->get('/admin/usuarios', 'UsuariosController@index', ['AuthMiddleware']);
$router->get('/admin/usuarios/crear', 'UsuariosController@crear', ['AuthMiddleware']);
$router->post('/admin/usuarios/guardar', 'UsuariosController@guardar', ['AuthMiddleware']);
$router->get('/admin/usuarios/editar/{id}', 'UsuariosController@editar', ['AuthMiddleware']);
$router->post('/admin/usuarios/actualizar/{id}', 'UsuariosController@actualizar', ['AuthMiddleware']);
$router->get('/admin/usuarios/eliminar/{id}', 'UsuariosController@eliminar', ['AuthMiddleware']);
$router->get('/admin/usuarios/reset-password/{id}', 'UsuariosController@resetPassword', ['AuthMiddleware']);

// Herramientas
$router->get('/herramientas/control-amarros', 'HerramientasController@controlAmarros', ['AuthMiddleware']);
$router->get('/herramientas/varita-magica', 'HerramientasController@varitaMagica', ['AuthMiddleware']);

// Normalización (solo admin) - TODO: Crear NormalizacionController
// $router->get('/normalizacion', 'NormalizacionController@index', ['AuthMiddleware']);

// Configuración
$router->get('/configuracion/tipos', 'ConfiguracionController@tipos', ['AuthMiddleware']);
$router->get('/configuracion/tipos/crear', 'ConfiguracionController@crearTipo', ['AuthMiddleware']); // Fix internal redirect to this route
$router->get('/configuracion/crearTipo', 'ConfiguracionController@crearTipo', ['AuthMiddleware']); // Alias for controller redirect
$router->post('/configuracion/tipos/guardar', 'ConfiguracionController@guardarTipo', ['AuthMiddleware']);
$router->get('/configuracion/tipos/editar/{id}', 'ConfiguracionController@editarTipo', ['AuthMiddleware']);
$router->post('/configuracion/tipos/actualizar/{id}', 'ConfiguracionController@actualizarTipo', ['AuthMiddleware']);
$router->get('/configuracion/tipos/eliminar/{id}', 'ConfiguracionController@eliminarTipo', ['AuthMiddleware']);

$router->get('/configuracion/password', 'ConfiguracionController@password', ['AuthMiddleware']);
$router->post('/configuracion/password/actualizar', 'ConfiguracionController@updatePassword', ['AuthMiddleware']);

// Ejecutar router
$router->dispatch();
