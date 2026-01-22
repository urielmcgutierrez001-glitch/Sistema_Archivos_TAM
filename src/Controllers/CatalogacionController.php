<?php
/**
 * Controlador de Catalogación
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Documento;
use TAMEP\Models\Ubicacion;
use TAMEP\Models\UnidadArea;
use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\TipoDocumento;
// use TAMEP\Models\HojaRuta; // Deprecated

class CatalogacionController extends BaseController
{
    private $documento;
    private $ubicacion;
    private $unidadArea;
    private $contenedorFisico;
    private $tipoDocumento;
    // private $hojaRuta;
    
    public function __construct()
    {
        parent::__construct();
        $this->documento = new Documento();
        $this->ubicacion = new Ubicacion();
        $this->unidadArea = new UnidadArea();
        $this->contenedorFisico = new ContenedorFisico();
        $this->tipoDocumento = new TipoDocumento();
        // $this->hojaRuta = new HojaRuta();
    }
    
    /**
     * Mostrar listado y búsqueda de documentos
     */
    public function index()
    {
        $this->requireAuth();

        // 1. Limpiar filtros si se solicita explícitamente y limpiar sesión
        if (isset($_GET['clean'])) {
            unset($_SESSION['catalogacion_filters']);
            $modoLotes = isset($_GET['modo_lotes']) ? '?modo_lotes=1' : '';
            $this->redirect('/catalogacion' . $modoLotes);
            return;
        }
        
        // 2. Detectar si hay nuevos filtros en $_GET (Búsqueda activa)
        // Verificamos si hay algún parámetro de búsqueda presente
        $hasFilters = isset($_GET['search']) || isset($_GET['gestion']) || isset($_GET['ubicacion_id']) || 
                      isset($_GET['estado_documento']) || isset($_GET['tipo_documento']) || 
                      isset($_GET['sort']); // Added sort to filter detection
        
        if ($hasFilters) {
            // Guardar filtros en sesión
            $_SESSION['catalogacion_filters'] = [
                'search' => $_GET['search'] ?? '',
                'gestion' => $_GET['gestion'] ?? '',
                'ubicacion_id' => $_GET['ubicacion_id'] ?? '',
                'estado_documento' => $_GET['estado_documento'] ?? '',
                'tipo_documento' => $_GET['tipo_documento'] ?? '',
                'sort' => $_GET['sort'] ?? '',
                'order' => $_GET['order'] ?? ''
            ];
        } 
        // 3. Si NO hay filtros en $_GET (acceso directo), pero existen en sesión -> Restaurar
        elseif (isset($_SESSION['catalogacion_filters']) && empty($_GET['page'])) {
            // Restaurar y redirigir
            $saved = $_SESSION['catalogacion_filters'];
            
            // Si mantenemos modo lotes
            if (isset($_GET['modo_lotes'])) {
                $saved['modo_lotes'] = $_GET['modo_lotes'];
            }

            $params = http_build_query($saved);
            $this->redirect('/catalogacion?' . $params);
            return;
        }
        
        // Obtener parámetros de búsqueda (ya sea de GET o vacíos si se limpió)
        $search = $_GET['search'] ?? '';
        $gestion = $_GET['gestion'] ?? '';
        $ubicacion_id = $_GET['ubicacion_id'] ?? '';
        $estado_documento = $_GET['estado_documento'] ?? '';
        $tipo_documento = $_GET['tipo_documento'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $order = $_GET['order'] ?? 'asc';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // Per Page Logic
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
        if ($perPage < 1) $perPage = 20; // Minimum limit
        if ($perPage > 200) $perPage = 200; // Maximum limit to prevent performance issues
        
        // Guardar per_page en sesión si cambia
        if (isset($_GET['per_page'])) {
            if (!isset($_SESSION['catalogacion_filters'])) {
                $_SESSION['catalogacion_filters'] = [];
            }
            $_SESSION['catalogacion_filters']['per_page'] = $perPage;
        } elseif (isset($_SESSION['catalogacion_filters']['per_page'])) {
            // Restaurar de sesión si no viene en GET
            $perPage = $_SESSION['catalogacion_filters']['per_page'];
        }

        // Realizar búsqueda - usar HojaRuta si es ese tipo
        if ($search || $gestion || $ubicacion_id || $estado_documento || $tipo_documento) {
            // Buscar en documentos (otros tipos)
            $documentos = $this->documento->buscarAvanzado([
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'estado_documento' => $estado_documento,
                'tipo_documento' => $tipo_documento,
                'sort' => $sort,
                'order' => $order,
                'page' => $page,
                'per_page' => $perPage
            ]);
            
            $total = $this->documento->contarBusqueda([
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'estado_documento' => $estado_documento,
                'tipo_documento' => $tipo_documento
            ]);
        } else {
            // Sin filtros, mostrar los más recientes usando buscarAvanzado sin filtros
            $documentos = $this->documento->buscarAvanzado([
                'page' => $page,
                'per_page' => $perPage,
                'sort' => $sort,
                'order' => $order
            ]);
            $total = $this->documento->count();
        }
        
        // Obtener datos para filtros
        $ubicaciones = $this->ubicacion->all();
        
        // Calcular paginación
        $totalPages = ceil($total / $perPage);
        
        $this->view('documentos.index', [
            'documentos' => $documentos,
            'ubicaciones' => $ubicaciones,
            'tiposDocumento' => $this->tipoDocumento->getAllOrderedById(),
            'filtros' => [
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'estado_documento' => $estado_documento,
                'tipo_documento' => $tipo_documento,
                'sort' => $sort,
                'order' => $order,
                'per_page' => $perPage
            ],
            'paginacion' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ],
            'contenedores' => $this->contenedorFisico->all(),
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Ver detalle de un documento
     */
    public function ver($id)
    {
        $this->requireAuth();
        
        $documento = $this->documento->findWithContenedor($id);
        
        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }
        
        if (isset($documento['unidad_id']) && $documento['unidad_id']) {
            $documento['unidad'] = $this->unidadArea->find($documento['unidad_id']);
        }
        
        $this->view('documentos.detalle', [
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
        
        $this->view('documentos.crear', [
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
            'tipo_documento_id' => ($tipo = $this->tipoDocumento->findByCode($_POST['tipo_documento'])) ? $tipo['id'] : null,
            'gestion' => $_POST['gestion'],
            'nro_comprobante' => $_POST['nro_comprobante'],
            'codigo_abc' => $_POST['codigo_abc'] ?? null,
            'contenedor_fisico_id' => !empty($_POST['contenedor_fisico_id']) ? $_POST['contenedor_fisico_id'] : null,
            'estado_documento' => $_POST['estado_documento'] ?? 'DISPONIBLE',
            'observaciones' => $_POST['observaciones'] ?? null,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];
        
        // Guardar
        $id = $this->documento->create($data);
        
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
        
        $documento = $this->documento->find($id);
        
        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }
        
        // Obtener contenedores para el select
        $contenedores = $this->contenedorFisico->all();
        
        $this->view('documentos.editar', [
            'documento' => $documento,
            'contenedores' => $contenedores,
            'ubicaciones' => $this->ubicacion->all(), // Pass locations for filtering
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Actualizar documento
     */
    public function actualizar($id)
    {
        $this->requireAuth();
        
        $documento = $this->documento->find($id);
        
        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }
        
        // Preparar datos
        $data = [
            'tipo_documento' => $_POST['tipo_documento'],
            'tipo_documento_id' => ($tipo = $this->tipoDocumento->findByCode($_POST['tipo_documento'])) ? $tipo['id'] : null,
            'gestion' => $_POST['gestion'],
            'nro_comprobante' => $_POST['nro_comprobante'],
            'codigo_abc' => $_POST['codigo_abc'] ?? null,
            'contenedor_fisico_id' => !empty($_POST['contenedor_fisico_id']) ? $_POST['contenedor_fisico_id'] : null,
            'estado_documento' => $_POST['estado_documento'] ?? 'DISPONIBLE',
            'observaciones' => $_POST['observaciones'] ?? null
        ];
        
        // Actualizar
        $success = $this->documento->update($id, $data);
        
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
        
        $documento = $this->documento->find($id);
        
        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }
        
        // Eliminar
        $success = $this->documento->delete($id);
        
        if ($success) {
            \TAMEP\Core\Session::flash('success', 'Documento eliminado exitosamente');
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al eliminar el documento');
        }
        
        $this->redirect('/catalogacion');
    }
    
    /**
     * Actualizar contenedor de un lote de documentos
     */
    public function actualizarLote()
    {
        $this->requireAuth();
        
        $ids = $_POST['ids'] ?? [];
        $contenedor_id = $_POST['contenedor_id'] ?? null;
        $estado_documento = $_POST['estado_documento'] ?? null;
        
        if (empty($ids)) {
            \TAMEP\Core\Session::flash('error', 'Debe seleccionar al menos un documento');
            $this->redirect('/catalogacion?modo_lotes=1');
            return;
        }

        // Validate at least one action is taken
        if (empty($contenedor_id) && empty($estado_documento)) {
             \TAMEP\Core\Session::flash('warning', 'No se seleccionó ninguna acción (Contenedor o Estado) para actualizar.');
             $this->redirect('/catalogacion?modo_lotes=1');
             return;
        }
        
        // Decodificar IDs si vienen como string JSON
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        
        // Build Update Array
        $updateData = [];
        if (!empty($contenedor_id)) {
            $updateData['contenedor_fisico_id'] = $contenedor_id;
        }
        if (!empty($estado_documento)) {
            $updateData['estado_documento'] = $estado_documento;
        }

        $count = 0;
        foreach ($ids as $id) {
            if ($this->documento->update($id, $updateData)) {
                $count++;
            }
        }
        
        $msg = "Se actualizaron $count documentos";
        if (count($updateData) > 0) {
            $changes = [];
            if(isset($updateData['contenedor_fisico_id'])) $changes[] = "Contenedor";
            if(isset($updateData['estado_documento'])) $changes[] = "Estado";
            $msg .= " (" . implode(', ', $changes) . ")";
        } 
        
        \TAMEP\Core\Session::flash('success', $msg);
        $this->redirect('/catalogacion?modo_lotes=1');
    }

    private function getCurrentUser()
    {
        return \TAMEP\Core\Session::user();
    }
}
