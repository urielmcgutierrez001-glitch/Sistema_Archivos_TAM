<?php
/**
 * Controlador de Catalogación
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\RegistroDiario;
use TAMEP\Models\Ubicacion;
use TAMEP\Models\UnidadArea;
use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\HojaRuta;

class CatalogacionController extends BaseController
{
    private $registroDiario;
    private $ubicacion;
    private $unidadArea;
    private $contenedorFisico;
    private $hojaRuta;
    
    public function __construct()
    {
        parent::__construct();
        $this->registroDiario = new RegistroDiario();
        $this->ubicacion = new Ubicacion();
        $this->unidadArea = new UnidadArea();
        $this->contenedorFisico = new ContenedorFisico();
        $this->hojaRuta = new HojaRuta();
    }
    
    /**
     * Mostrar listado y búsqueda de documentos
     */
    public function index()
    {
        $this->requireAuth();
        
        // Obtener parámetros de búsqueda
        $search = $_GET['search'] ?? '';
        $gestion = $_GET['gestion'] ?? '';
        $ubicacion_id = $_GET['ubicacion_id'] ?? '';
        $estado_documento = $_GET['estado_documento'] ?? '';
        $tipo_documento = $_GET['tipo_documento'] ?? '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 20;
        
        // Realizar búsqueda - usar HojaRuta si es ese tipo
        if ($tipo_documento === 'HOJA_RUTA_DIARIOS') {
            // Buscar en registro_hojas_ruta
            $documentos = $this->hojaRuta->buscarAvanzado([
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'page' => $page,
                'per_page' => $perPage
            ]);
            
            $total = $this->hojaRuta->contarBusqueda([
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id
            ]);
        } else if ($search || $gestion || $ubicacion_id || $estado_documento || $tipo_documento) {
            // Buscar en registro_diario (otros tipos)
            $documentos = $this->registroDiario->buscarAvanzado([
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'estado_documento' => $estado_documento,
                'tipo_documento' => $tipo_documento,
                'page' => $page,
                'per_page' => $perPage
            ]);
            
            $total = $this->registroDiario->contarBusqueda([
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'estado_documento' => $estado_documento,
                'tipo_documento' => $tipo_documento
            ]);
        } else {
            // Sin filtros, mostrar los más recientes usando buscarAvanzado sin filtros
            $documentos = $this->registroDiario->buscarAvanzado([
                'page' => $page,
                'per_page' => $perPage
            ]);
            $total = $this->registroDiario->count();
        }
        
        // Obtener datos para filtros
        $ubicaciones = $this->ubicacion->all();
        
        // Calcular paginación
        $totalPages = ceil($total / $perPage);
        
        $this->view('catalogacion.index', [
            'documentos' => $documentos,
            'ubicaciones' => $ubicaciones,
            'filtros' => [
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'estado_documento' => $estado_documento,
                'tipo_documento' => $tipo_documento
            ],
            'paginacion' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ],
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Ver detalle de un documento
     */
    public function ver($id)
    {
        $this->requireAuth();
        
        $documento = $this->registroDiario->find($id);
        
        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }
        
        // Obtener información relacionada
        if (isset($documento['ubicacion_id']) && $documento['ubicacion_id']) {
            $documento['ubicacion'] = $this->ubicacion->find($documento['ubicacion_id']);
        }
        
        if (isset($documento['unidad_id']) && $documento['unidad_id']) {
            $documento['unidad'] = $this->unidadArea->find($documento['unidad_id']);
        }
        
        $this->view('catalogacion.detalle', [
            'documento' => $documento,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Mostrar formulario de creación
     */
    public function crear()
    {
        $this->requireAuth();
        
        // Obtener contenedores para el select
        $contenedores = $this->contenedorFisico->all();
        
        $this->view('catalogacion.crear', [
            'contenedores' => $contenedores,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Guardar nuevo documento
     */
    public function guardar()
    {
        $this->requireAuth();
        
        // Validar datos requeridos
        if (empty($_POST['tipo_documento']) || empty($_POST['gestion']) || empty($_POST['nro_comprobante'])) {
            \TAMEP\Core\Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/catalogacion/crear');
        }
        
        // Preparar datos
        $data = [
            'tipo_documento' => $_POST['tipo_documento'],
            'gestion' => $_POST['gestion'],
            'nro_comprobante' => $_POST['nro_comprobante'],
            'codigo_abc' => $_POST['codigo_abc'] ?? null,
            'contenedor_fisico_id' => !empty($_POST['contenedor_fisico_id']) ? $_POST['contenedor_fisico_id'] : null,
            'estado_documento' => $_POST['estado_documento'] ?? 'DISPONIBLE',
            'observaciones' => $_POST['observaciones'] ?? null,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];
        
        // Guardar
        $id = $this->registroDiario->create($data);
        
        if ($id) {
            \TAMEP\Core\Session::flash('success', 'Documento creado exitosamente');
            $this->redirect('/catalogacion/ver/' . $id);
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al crear el documento');
            $this->redirect('/catalogacion/crear');
        }
    }
    
    /**
     * Mostrar formulario de edición
     */
    public function editar($id)
    {
        $this->requireAuth();
        
        $documento = $this->registroDiario->find($id);
        
        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }
        
        // Obtener contenedores para el select
        $contenedores = $this->contenedorFisico->all();
        
        $this->view('catalogacion.editar', [
            'documento' => $documento,
            'contenedores' => $contenedores,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Actualizar documento
     */
    public function actualizar($id)
    {
        $this->requireAuth();
        
        $documento = $this->registroDiario->find($id);
        
        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }
        
        // Preparar datos
        $data = [
            'tipo_documento' => $_POST['tipo_documento'],
            'gestion' => $_POST['gestion'],
            'nro_comprobante' => $_POST['nro_comprobante'],
            'codigo_abc' => $_POST['codigo_abc'] ?? null,
            'contenedor_fisico_id' => !empty($_POST['contenedor_fisico_id']) ? $_POST['contenedor_fisico_id'] : null,
            'estado_documento' => $_POST['estado_documento'] ?? 'DISPONIBLE',
            'observaciones' => $_POST['observaciones'] ?? null
        ];
        
        // Actualizar
        $success = $this->registroDiario->update($id, $data);
        
        if ($success) {
            \TAMEP\Core\Session::flash('success', 'Documento actualizado exitosamente');
            $this->redirect('/catalogacion/ver/' . $id);
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al actualizar el documento');
            $this->redirect('/catalogacion/editar/' . $id);
        }
    }
    
    /**
     * Eliminar documento
     */
    public function eliminar($id)
    {
        $this->requireAuth();
        
        $documento = $this->registroDiario->find($id);
        
        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }
        
        // Eliminar
        $success = $this->registroDiario->delete($id);
        
        if ($success) {
            \TAMEP\Core\Session::flash('success', 'Documento eliminado exitosamente');
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al eliminar el documento');
        }
        
        $this->redirect('/catalogacion');
    }
    
    private function getCurrentUser()
    {
        return \TAMEP\Core\Session::user();
    }
}
