<?php
/**
 * Controlador de Préstamos
 * Gestiona préstamos de documentos LIBRO/AMARRO
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Prestamo;
use TAMEP\Models\RegistroDiario;
use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\Usuario;
use TAMEP\Models\HojaRuta;
use TAMEP\Core\Session;

class PrestamosController extends BaseController
{
    private $prestamo;
    private $registroDiario;
    private $contenedorFisico;
    private $usuario;
    private $hojaRuta;
    
    public function __construct()
    {
        parent::__construct();
        $this->prestamo = new Prestamo();
        $this->registroDiario = new RegistroDiario();
        $this->contenedorFisico = new ContenedorFisico();
        $this->usuario = new Usuario();
        $this->hojaRuta = new HojaRuta();
    }
    
    /**
     * Listar préstamos
     */
    public function index()
    {
        $this->requireAuth();
        
        // Filtros
        $estado = $_GET['estado'] ?? '';
        $usuario_id = $_GET['usuario_id'] ?? '';
        
        // Construir query
        $where = [];
        $params = [];
        
        if (!empty($estado)) {
            $where[] = "p.estado = ?";
            $params[] = $estado;
        }
        
        if (!empty($usuario_id)) {
            $where[] = "p.usuario_id = ?";
            $params[] = $usuario_id;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Obtener préstamos con joins
        $sql = "SELECT p.*, 
                       u.nombre_completo as usuario_nombre,
                       cf.tipo_contenedor, cf.numero as contenedor_numero,
                       rd.nro_comprobante, rd.gestion
                FROM prestamos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN contenedores_fisicos cf ON p.contenedor_fisico_id = cf.id
                LEFT JOIN registro_diario rd ON p.documento_id = rd.id
                {$whereClause}
                ORDER BY p.fecha_prestamo DESC";
        
        $prestamos = $this->prestamo->getDb()->fetchAll($sql, $params);
        
        // Obtener usuarios para filtro
        $usuarios = $this->usuario->getActive();
        
        $this->view('prestamos.index', [
            'prestamos' => $prestamos,
            'usuarios' => $usuarios,
            'filtros' => [
                'estado' => $estado,
                'usuario_id' => $usuario_id
            ],
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Mostrar formulario de nuevo préstamo
     */
    public function crear()
    {
        $this->requireAuth();
        
        // Obtener documentos disponibles
        $documentos = $this->registroDiario->getAvailable();
        
        // Obtener usuarios activos
        $usuarios = $this->usuario->getActive();
        
        $this->view('prestamos.crear', [
            'documentos' => $documentos,
            'usuarios' => $usuarios,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Guardar nuevo préstamo
     */
    public function guardar()
    {
        $this->requireAuth();
        
        // Validar
        if (empty($_POST['documento_id']) || empty($_POST['usuario_id']) || empty($_POST['fecha_devolucion_esperada'])) {
            Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/prestamos/crear');
        }
        
        // Verificar que el documento esté disponible
        $documento = $this->registroDiario->find($_POST['documento_id']);
        if (!$documento || $documento['estado_documento'] !== 'DISPONIBLE') {
            Session::flash('error', 'El documento no está disponible para préstamo');
            $this->redirect('/prestamos/crear');
        }
        
        // Crear préstamo
        $data = [
            'documento_id' => $_POST['documento_id'],
            'contenedor_fisico_id' => $documento['contenedor_fisico_id'] ?? null,
            'usuario_id' => $_POST['usuario_id'],
            'fecha_prestamo' => date('Y-m-d'),
            'fecha_devolucion_esperada' => $_POST['fecha_devolucion_esperada'],
            'observaciones' => $_POST['observaciones'] ?? null,
            'estado' => 'Prestado'
        ];
        
        $id = $this->prestamo->create($data);
        
        if ($id) {
            // Actualizar estado del documento
            $this->registroDiario->update($_POST['documento_id'], ['estado_documento' => 'PRESTADO']);
            
            Session::flash('success', 'Préstamo registrado exitosamente');
            $this->redirect('/prestamos');
        } else {
            Session::flash('error', 'Error al registrar el préstamo');
            $this->redirect('/prestamos/crear');
        }
    }
    
    /**
     * Procesar devolución
     */
    public function devolver($id)
    {
        $this->requireAuth();
        
        $prestamo = $this->prestamo->find($id);
        
        if (!$prestamo) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }
        
        if ($prestamo['estado'] !== 'Prestado') {
            Session::flash('error', 'Este préstamo ya fue devuelto');
            $this->redirect('/prestamos');
        }
        
        // Actualizar préstamo
        $success = $this->prestamo->update($id, [
            'fecha_devolucion_real' => date('Y-m-d'),
            'estado' => 'Devuelto'
        ]);
        
        if ($success) {
            // Actualizar estado del documento
            $this->registroDiario->update($prestamo['documento_id'], ['estado_documento' => 'DISPONIBLE']);
            
            Session::flash('success', 'Devolución registrada exitosamente');
        } else {
            Session::flash('error', 'Error al registrar la devolución');
        }
        
        $this->redirect('/prestamos');
    }
    
    /**
     * Vista de nuevo préstamo con selección múltiple
     */
    public function nuevo()
    {
        $this->requireAuth();
        
        // Obtener parámetros de búsqueda
        $search = $_GET['search'] ?? '';
        $gestion = $_GET['gestion'] ?? '';
        $tipo_documento = $_GET['tipo_documento'] ?? '';
        
        $documentos = [];
        
        // Buscar según el tipo de documento
        if ($tipo_documento === 'HOJA_RUTA_DIARIOS') {
            // Buscar en registro_hojas_ruta
            $where = ["hr.activo = 1"];
            $params = [];
            
            if (!empty($search)) {
                $where[] = "(hr.nro_comprobante_diario LIKE ? OR hr.nro_hoja_ruta LIKE ? OR hr.rubro LIKE ? OR hr.interesado LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($gestion)) {
                $where[] = "hr.gestion = ?";
                $params[] = $gestion;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $where);
            
            $sql = "SELECT hr.id,
                           hr.gestion,
                           hr.nro_comprobante_diario as nro_comprobante,
                           hr.nro_hoja_ruta,
                           hr.rubro,
                           hr.interesado,
                           'HOJA_RUTA_DIARIOS' as tipo_documento,
                           cf.tipo_contenedor,
                           cf.numero as contenedor_numero
                    FROM registro_hojas_ruta hr
                    LEFT JOIN contenedores_fisicos cf ON hr.contenedor_fisico_id = cf.id
                    {$whereClause}
                    ORDER BY hr.gestion DESC, hr.nro_comprobante_diario DESC
                    LIMIT 100";
            
            $documentos = $this->hojaRuta->getDb()->fetchAll($sql, $params);
        } else {
            // Buscar en registro_diario (otros tipos)
            $where = ["rd.estado_documento = 'DISPONIBLE'"];
            $params = [];
            
            if (!empty($search)) {
                $where[] = "(rd.nro_comprobante LIKE ? OR rd.codigo_abc LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($gestion)) {
                $where[] = "rd.gestion = ?";
                $params[] = $gestion;
            }
            
            if (!empty($tipo_documento)) {
                $where[] = "rd.tipo_documento = ?";
                $params[] = $tipo_documento;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $where);
            
            $sql = "SELECT rd.*, cf.tipo_contenedor, cf.numero as contenedor_numero
                    FROM registro_diario rd
                    LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                    {$whereClause}
                    ORDER BY rd.gestion DESC, rd.nro_comprobante DESC
                    LIMIT 100";
            
            $documentos = $this->registroDiario->getDb()->fetchAll($sql, $params);
        }
        
        // Obtener usuarios activos
        $usuarios = $this->usuario->getActive();
        
        $this->view('prestamos.nuevo', [
            'documentos' => $documentos,
            'usuarios' => $usuarios,
            'filtros' => [
                'search' => $search,
                'gestion' => $gestion,
                'tipo_documento' => $tipo_documento
            ],
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Guardar préstamo múltiple
     */
    public function guardarMultiple()
    {
        $this->requireAuth();
        
        // Validar
        if (empty($_POST['usuario_id']) || empty($_POST['fecha_devolucion']) || empty($_POST['documentos'])) {
            Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/prestamos/nuevo');
        }
        
        $documentosIds = json_decode($_POST['documentos'], true);
        
        if (empty($documentosIds)) {
            Session::flash('error', 'Debe seleccionar al menos un documento');
            $this->redirect('/prestamos/nuevo');
        }
        
        $exitosos = 0;
        $errores = 0;
        
        foreach ($documentosIds as $docId) {
            // Verificar que el documento esté disponible
            $documento = $this->registroDiario->find($docId);
            if (!$documento || $documento['estado_documento'] !== 'DISPONIBLE') {
                $errores++;
                continue;
            }
            
            // Crear préstamo
            $data = [
                'documento_id' => $docId,
                'contenedor_fisico_id' => $documento['contenedor_fisico_id'] ?? null,
                'usuario_id' => $_POST['usuario_id'],
                'fecha_prestamo' => date('Y-m-d'),
                'fecha_devolucion_esperada' => $_POST['fecha_devolucion'],
                'observaciones' => $_POST['observaciones'] ?? null,
                'estado' => 'Prestado'
            ];
            
            $id = $this->prestamo->create($data);
            
            if ($id) {
                // Actualizar estado del documento
                $this->registroDiario->update($docId, ['estado_documento' => 'PRESTADO']);
                $exitosos++;
            } else {
                $errores++;
            }
        }
        
        if ($exitosos > 0) {
            Session::flash('success', "Préstamo registrado: {$exitosos} documento(s) prestado(s)" . ($errores > 0 ? ", {$errores} error(es)" : ''));
        } else {
            Session::flash('error', 'No se pudo registrar ningún préstamo');
        }
        
        $this->redirect('/prestamos');
    }
    
    /**
     * Ver detalle de préstamo
     */
    public function ver($id)
    {
        $this->requireAuth();
        
        $sql = "SELECT p.*, 
                       u.nombre_completo as usuario_nombre, u.username,
                       cf.tipo_contenedor, cf.numero as contenedor_numero,
                       rd.nro_comprobante, rd.gestion, rd.tipo_documento
                FROM prestamos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN contenedores_fisicos cf ON p.contenedor_fisico_id = cf.id
                LEFT JOIN registro_diario rd ON p.documento_id = rd.id
                WHERE p.id = ?";
        
        $prestamo = $this->prestamo->getDb()->fetchOne($sql, [$id]);
        
        if (!$prestamo) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }
        
        $this->view('prestamos.detalle', [
            'prestamo' => $prestamo,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    private function getCurrentUser()
    {
        return Session::user();
    }
}
