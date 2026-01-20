<?php

namespace TAMEP\Controllers;

use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\Ubicacion;
use TAMEP\Models\TipoDocumento;

class ContenedoresController extends BaseController
{
    private $contenedorFisico;
    private $ubicacion;
    private $tipoDocumento;
    
    public function __construct()
    {
        parent::__construct();
        $this->contenedorFisico = new ContenedorFisico();
        $this->ubicacion = new Ubicacion();
        $this->tipoDocumento = new TipoDocumento();
    }
    
    public function index()
    {
        $this->requireAuth();
        
        // 1. Limpiar filtros explicitamente
        if (isset($_GET['clean'])) {
            unset($_SESSION['contenedores_filters']);
            $this->redirect('/contenedores');
            return;
        }
        
        // 2. Detectar nuevos filtros
        $hasFilters = isset($_GET['search']) || // Generic search param if added later
                      isset($_GET['tipo_documento']) || 
                      isset($_GET['numero']) || 
                      isset($_GET['gestion']) || 
                      isset($_GET['tipo_contenedor']) || 
                      isset($_GET['ubicacion_id']) || 
                      isset($_GET['sort']) ||
                      isset($_GET['per_page']);
                      
        if ($hasFilters) {
            $_SESSION['contenedores_filters'] = [
                'tipo_documento' => $_GET['tipo_documento'] ?? '',
                'numero' => $_GET['numero'] ?? '',
                'gestion' => $_GET['gestion'] ?? '',
                'tipo_contenedor' => $_GET['tipo_contenedor'] ?? '',
                'ubicacion_id' => $_GET['ubicacion_id'] ?? '',
                'sort' => $_GET['sort'] ?? '',
                'order' => $_GET['order'] ?? '',
                'per_page' => $_GET['per_page'] ?? 20
            ];
        } elseif (isset($_SESSION['contenedores_filters']) && empty($_GET['page'])) {
            // Restaurar sesión
            $params = http_build_query($_SESSION['contenedores_filters']);
            $this->redirect('/contenedores?' . $params);
            return;
        }
        
        // Params
        $tipo_documento = $_GET['tipo_documento'] ?? '';
        $numero = $_GET['numero'] ?? '';
        $gestion = $_GET['gestion'] ?? '';
        $tipo_contenedor = $_GET['tipo_contenedor'] ?? '';
        $ubicacion_id = $_GET['ubicacion_id'] ?? '';
        
        $sort = $_GET['sort'] ?? '';
        $order = $_GET['order'] ?? 'asc';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
        if ($perPage < 1) $perPage = 20;
        if ($perPage > 200) $perPage = 200;
        
        $filtros = [
            'tipo_documento' => $tipo_documento,
            'numero' => $numero,
            'gestion' => $gestion,
            'tipo_contenedor' => $tipo_contenedor,
            'ubicacion_id' => $ubicacion_id,
            'sort' => $sort,
            'order' => $order,
            'page' => $page,
            'per_page' => $perPage
        ];
        
        $contenedores = $this->contenedorFisico->buscar($filtros);
        $total = $this->contenedorFisico->contarBusqueda($filtros);
        
        $this->view('contenedores.index', [
            'contenedores' => $contenedores,
            'user' => $this->getCurrentUser(),
            'ubicaciones' => $this->ubicacion->getActive(),
            'tiposDocumento' => $this->tipoDocumento->getAll(),
            'filtros' => $filtros,
            'paginacion' => [
                'current' => $page,
                'total' => $total,
                'per_page' => $perPage,
                'page' => $page,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }
    
    public function crear()
    {
        $this->requireAuth();
        $this->view('contenedores.crear', [
            'ubicaciones' => $this->ubicacion->getActive(),
            'tiposDocumento' => $this->tipoDocumento->getActive(),
            'user' => $this->getCurrentUser()
        ]);
    }
    
    public function guardar()
    {
        $this->requireAuth();
        
        $data = [
            'tipo_contenedor' => $_POST['tipo_contenedor'],
            'tipo_documento' => $_POST['tipo_documento'] ?? null,
            'numero' => $_POST['numero'],
            'ubicacion_id' => !empty($_POST['ubicacion_id']) ? $_POST['ubicacion_id'] : null,
            'color' => $_POST['color'] ?? null,
            'bloque_nivel' => $_POST['bloque_nivel'] ?? null,
            'gestion' => $_POST['gestion'] ?? date('Y')
        ];
        
        if ($this->contenedorFisico->create($data)) {
            \TAMEP\Core\Session::flash('success', 'Contenedor creado exitosamente');
            $this->redirect('/contenedores');
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al crear contenedor');
            $this->redirect('/contenedores/crear');
        }
    }
    
    public function ver($id)
    {
        $this->requireAuth();
        $contenedor = $this->contenedorFisico->find($id);
        
        if (!$contenedor) {
            $this->redirect('/contenedores');
        }
        
        $documentos = $this->contenedorFisico->getDocumentos($id);
        
        // Cargar ubicación si existe
        if ($contenedor['ubicacion_id']) {
            $contenedor['ubicacion'] = $this->ubicacion->find($contenedor['ubicacion_id']);
        }

        $this->view('contenedores.ver', [
            'contenedor' => $contenedor,
            'documentos' => $documentos,
            'user' => $this->getCurrentUser()
        ]);
    }
    public function editar($id)
    {
        $this->requireAuth();
        $contenedor = $this->contenedorFisico->find($id);
        
        if (!$contenedor) {
            $this->redirect('/contenedores');
        }
        
        $documentos = $this->contenedorFisico->getDocumentos($id);

        $this->view('contenedores.editar', [
            'contenedor' => $contenedor,
            'ubicaciones' => $this->ubicacion->getActive(),
            'documentos' => $documentos,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    public function actualizar($id)
    {
        $this->requireAuth();
        
        $data = [
            'tipo_contenedor' => $_POST['tipo_contenedor'],
            'tipo_documento' => $_POST['tipo_documento'] ?? null,
            'numero' => $_POST['numero'],
            'ubicacion_id' => !empty($_POST['ubicacion_id']) ? $_POST['ubicacion_id'] : null,
            'color' => $_POST['color'] ?? null,
            'bloque_nivel' => $_POST['bloque_nivel'] ?? null,
            'gestion' => $_POST['gestion'] ?? null
        ];
        
        if ($this->contenedorFisico->update($id, $data)) {
            // Actualizar documentos contenidos (desvincular los desmarcados)
            $documentos_mantener = $_POST['documentos_ids'] ?? [];
            $this->contenedorFisico->actualizarContenido($id, $documentos_mantener);

            \TAMEP\Core\Session::flash('success', 'Contenedor actualizado');
            $this->redirect('/contenedores');
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al actualizar');
            $this->redirect('/contenedores/editar/' . $id);
        }
    }

    public function actualizarUbicacionMasiva()
    {
        $this->requireAuth();
        
        $ids = $_POST['ids'] ?? [];
        $ubicacion_id = $_POST['ubicacion_id'] ?? null;
        
        if (empty($ids) || !$ubicacion_id) {
            \TAMEP\Core\Session::flash('error', 'Seleccione contenedores y una ubicación válida');
            $this->redirect('/contenedores');
            return;
        }
        
        $count = 0;
        foreach ($ids as $id) {
            if ($this->contenedorFisico->update($id, ['ubicacion_id' => $ubicacion_id])) {
                $count++;
            }
        }
        
        \TAMEP\Core\Session::flash('success', "Se actualizaron $count contenedores exitosamente.");
        $this->redirect('/contenedores');
    }
    
    public function eliminar($id)
    {
        $this->requireAuth();
        // Check availability logic if needed (isDisponible)
        
        if ($this->contenedorFisico->delete($id)) {
            \TAMEP\Core\Session::flash('success', 'Contenedor eliminado');
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al eliminar');
        }
        $this->redirect('/contenedores');
    }
    
    private function getCurrentUser()
    {
        return \TAMEP\Core\Session::user();
    }
}
