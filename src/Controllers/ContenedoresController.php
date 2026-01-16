<?php

namespace TAMEP\Controllers;

use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\Ubicacion;

class ContenedoresController extends BaseController
{
    private $contenedorFisico;
    private $ubicacion;
    
    public function __construct()
    {
        parent::__construct();
        $this->contenedorFisico = new ContenedorFisico();
        $this->ubicacion = new Ubicacion();
    }
    
    public function index()
    {
        $this->requireAuth();
        
        $filtros = [
            'tipo_documento' => $_GET['tipo_documento'] ?? null,
            'numero' => $_GET['numero'] ?? null,
            'gestion' => $_GET['gestion'] ?? null,
            'tipo_contenedor' => $_GET['tipo_contenedor'] ?? null,
            'ubicacion_id' => $_GET['ubicacion_id'] ?? null
        ];
        
        $contenedores = $this->contenedorFisico->buscar($filtros);
        
        $this->view('contenedores.index', [
            'contenedores' => $contenedores,
            'user' => $this->getCurrentUser(),
            'ubicaciones' => $this->ubicacion->getActive(),
            'filtros' => $filtros
        ]);
    }
    
    public function crear()
    {
        $this->requireAuth();
        $this->view('contenedores.crear', [
            'ubicaciones' => $this->ubicacion->getActive(),
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
