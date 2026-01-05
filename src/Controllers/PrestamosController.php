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
use TAMEP\Models\PrestamoHeader;
use TAMEP\Models\Ubicacion;
use TAMEP\Core\Session;

class PrestamosController extends BaseController
{
    private $registroDiario;
    private $prestamo;
    private $prestamoHeader;
    private $contenedorFisico;
    private $usuario;
    private $hojaRuta;
    private $ubicacion;
    
    public function __construct()
    {
        parent::__construct();
        $this->registroDiario = new RegistroDiario();
        $this->prestamo = new Prestamo();
        $this->prestamoHeader = new PrestamoHeader();
        $this->contenedorFisico = new ContenedorFisico();
        $this->usuario = new Usuario();
        $this->hojaRuta = new HojaRuta();
        $this->ubicacion = new Ubicacion();
    }
    
    /**
     * Listar préstamos (Agrupados por cabecera)
     */
    public function index()
    {
        $this->requireAuth();
        
        // Filtros
        $estado = $_GET['estado'] ?? '';
        $usuario_id = $_GET['usuario_id'] ?? '';
        
        // Construir query para encabezados
        $where = [];
        $params = [];
        
        if (!empty($estado)) {
            $where[] = "ph.estado = ?";
            $params[] = $estado;
        }
        
        if (!empty($usuario_id)) {
            $where[] = "ph.usuario_id = ?";
            $params[] = $usuario_id;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Fetch headers with user info and item count
        $sql = "SELECT ph.*, 
                       u.nombre_completo as usuario_nombre,
                       ub.nombre as unidad_nombre,
                       ph.nombre_prestatario,
                       COUNT(p.id) as total_documentos
                FROM prestamos_encabezados ph
                LEFT JOIN usuarios u ON ph.usuario_id = u.id
                LEFT JOIN ubicaciones ub ON ph.unidad_area_id = ub.id
                LEFT JOIN prestamos p ON ph.id = p.encabezado_id
                {$whereClause}
                GROUP BY ph.id
                ORDER BY ph.fecha_prestamo DESC, ph.id DESC";
        
        $prestamos = $this->prestamoHeader->getDb()->fetchAll($sql, $params);
        
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
        
        // Obtener ubicaciones (unidades/áreas)
        $ubicaciones = $this->ubicacion->getActive();
        
        $this->view('prestamos.crear', [
            'documentos' => $documentos,
            'ubicaciones' => $ubicaciones,
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
        if (empty($_POST['documento_id']) || empty($_POST['unidad_area_id']) || empty($_POST['fecha_devolucion_esperada'])) {
            Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/prestamos/crear');
        }
        
        // Verificar que el documento esté disponible
        $documento = $this->registroDiario->find($_POST['documento_id']);
        if (!$documento || $documento['estado_documento'] !== 'DISPONIBLE') {
            Session::flash('error', 'El documento no está disponible para préstamo');
            $this->redirect('/prestamos/crear');
        }
        
        // Crear Encabezado
        $headerData = [
            'usuario_id' => Session::user()['id'], // El usuario que REGISTRA el préstamo (Admin)
            'unidad_area_id' => $_POST['unidad_area_id'],
            'nombre_prestatario' => $_POST['nombre_prestatario'] ?? null,
            'fecha_prestamo' => date('Y-m-d'),
            'fecha_devolucion_esperada' => $_POST['fecha_devolucion_esperada'],
            'observaciones' => $_POST['observaciones'] ?? null,
            'estado' => 'Prestado'
        ];

        $headerId = $this->prestamoHeader->create($headerData);

        if ($headerId) {
            // Crear préstamo detalle
            $data = [
                'encabezado_id' => $headerId,
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
                Session::flash('error', 'Error al registrar el detalle del préstamo');
                $this->redirect('/prestamos/crear');
            }
        } else {
            Session::flash('error', 'Error al registrar el préstamo');
            $this->redirect('/prestamos/crear');
        }
    }
    
    /**
     * Procesar devolución
     */
    /**
     * Actualizar estados de devolución (Bulk Update)
     */
    /**
     * Actualizar estados de devolución (Bulk Update)
     */
    public function actualizarEstados()
    {
        $this->requireAuth();
        
        $encabezado_id = $_POST['encabezado_id'] ?? null;
        $devueltos = $_POST['devueltos'] ?? []; // IDs checked
        $action = $_POST['action'] ?? 'actualizar'; // devolver | revertir
        
        if (!$encabezado_id) {
            Session::flash('error', 'ID de préstamo no válido');
            $this->redirect('/prestamos');
        }

        // Get all details for this header
        $detalles = $this->prestamoHeader->getDetalles($encabezado_id);
        
        $changes = 0;
        
        foreach ($detalles as $doc) {
            $is_checked = in_array($doc['id'], $devueltos);
            $current_status = $doc['estado'];
            
            if ($action === 'devolver') {
                // Only process items that are CHECKED and currently PRESTADO
                if ($is_checked && $current_status === 'Prestado') {
                    $this->prestamo->update($doc['id'], [
                        'fecha_devolucion_real' => date('Y-m-d'),
                        'estado' => 'Devuelto'
                    ]);
                    $this->registroDiario->update($doc['documento_id'], ['estado_documento' => 'DISPONIBLE']);
                    $changes++;
                }
            } elseif ($action === 'revertir') {
                // Process items that are CURRENTLY DEVUELTO but usage UNCHECKED them
                // This means they want to "un-return" them
                if (!$is_checked && $current_status === 'Devuelto') {
                    $this->prestamo->update($doc['id'], [
                        'fecha_devolucion_real' => null,
                        'estado' => 'Prestado'
                    ]);
                    $this->registroDiario->update($doc['documento_id'], ['estado_documento' => 'PRESTADO']);
                    $changes++;
                }
            }
        }
        
        // Update Header Status
        $newDetalles = $this->prestamoHeader->getDetalles($encabezado_id);
        $allReturned = true;
        foreach ($newDetalles as $d) {
            if ($d['estado'] === 'Prestado') {
                $allReturned = false;
                break;
            }
        }
        
        $this->prestamoHeader->update($encabezado_id, [
            'estado' => $allReturned ? 'Devuelto' : 'Prestado'
        ]);

        if ($changes > 0) {
            $msg = $action === 'devolver' ? 'Documentos devueltos correctamente' : 'Devoluciones revertidas correctamente';
            Session::flash('success', $msg);
        } else {
            Session::flash('info', 'No se realizaron cambios (verifique su selección)');
        }
        
        $this->redirect('/prestamos/ver/' . $encabezado_id);
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
        
        // Obtener ubicaciones
        $ubicaciones = $this->ubicacion->getActive();
        
        $this->view('prestamos.nuevo', [
            'documentos' => $documentos,
            'ubicaciones' => $ubicaciones,
            'filtros' => [
                'search' => $search,
                'gestion' => $gestion,
                'tipo_documento' => $tipo_documento
            ],
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Guardar préstamo múltiple (con cabecera)
     */
    public function guardarMultiple()
    {
        $this->requireAuth();
        
        // Validar
        if (empty($_POST['unidad_area_id']) || empty($_POST['fecha_devolucion']) || empty($_POST['documentos'])) {
            Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/prestamos/nuevo');
        }
        
        $documentosIds = json_decode($_POST['documentos'], true);
        
        if (empty($documentosIds)) {
            Session::flash('error', 'Debe seleccionar al menos un documento');
            $this->redirect('/prestamos/nuevo');
        }

        // Crear Encabezado
        $headerData = [
            'usuario_id' => Session::user()['id'],
            'unidad_area_id' => $_POST['unidad_area_id'],
            'nombre_prestatario' => $_POST['nombre_prestatario'] ?? null,
            'fecha_prestamo' => date('Y-m-d'),
            'fecha_devolucion_esperada' => $_POST['fecha_devolucion'],
            'observaciones' => $_POST['observaciones'] ?? null,
            'estado' => 'Prestado'
        ];

        $headerId = $this->prestamoHeader->create($headerData);

        if (!$headerId) {
            Session::flash('error', 'Error al crear la cabecera del préstamo');
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
            
            // Crear préstamo detalle
            $data = [
                'encabezado_id' => $headerId,
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
            Session::flash('success', "Préstamo registrado: {$exitosos} documento(s) prestado(s)");
        } else {
            // Si fallaron todos, eliminar el header (opcional, pero limpio)
            // Por ahora solo avisar
            Session::flash('error', 'No se pudo registrar ningún documento en el préstamo');
        }
        
        $this->redirect('/prestamos');
    }
    
    /**
     * Ver detalle de grupo de préstamo
     */
    public function ver($id)
    {
        $this->requireAuth();
        
        // Get Header Info
        $sql = "SELECT ph.*, 
                       u.nombre_completo as usuario_nombre, u.username,
                       ub.nombre as unidad_nombre
                FROM prestamos_encabezados ph
                LEFT JOIN usuarios u ON ph.usuario_id = u.id
                LEFT JOIN ubicaciones ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);
        
        if (!$prestamo) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }

        // Get Details
        $detalles = $this->prestamoHeader->getDetalles($id);
        
        $this->view('prestamos.detalle', [
            'prestamo' => $prestamo,
            'detalles' => $detalles,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    private function getCurrentUser()
    {
        return Session::user();
    }

    /**
     * Exportar a Excel (CSV)
     */
    public function exportarExcel($id)
    {
        $this->requireAuth();
        
        // Fetch Header and Details
        $sql = "SELECT ph.*, u.nombre_completo as usuario_nombre, ub.nombre as unidad_nombre 
                FROM prestamos_encabezados ph 
                LEFT JOIN usuarios u ON ph.usuario_id = u.id 
                LEFT JOIN ubicaciones ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);
        
        if (!$prestamo) {
            die("Préstamo no encontrado");
        }
        
        $detalles = $this->prestamoHeader->getDetalles($id);
        
        // Output headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="prestamo_' . $id . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel
        fputs($output, "\xEF\xBB\xBF");
        
        // Header Info
        fputcsv($output, ['REPORTE DE PRESTAMO', '#' . $id]);
        fputcsv($output, ['Solicitante (Unidad):', $prestamo['unidad_nombre'] ?? 'N/A']);
        fputcsv($output, ['Prestatario:', $prestamo['nombre_prestatario'] ?? 'N/A']);
        fputcsv($output, ['Registrado por:', $prestamo['usuario_nombre']]);
        fputcsv($output, ['Fecha Prestamo:', $prestamo['fecha_prestamo']]);
        fputcsv($output, ['Devolucion Esperada:', $prestamo['fecha_devolucion_esperada']]);
        fputcsv($output, []); // Empty line
        
        // Columns
        fputcsv($output, ['Gestion', 'Nro Comprobante', 'Tipo Documento', 'Contenedor', 'Numero', 'Ubicacion', 'Estado']);
        
        
        foreach ($detalles as $doc) {
            fputcsv($output, [
                $doc['gestion'] ?? '',
                $doc['nro_comprobante'] ?? '',
                $doc['tipo_documento'] ?? '',
                $doc['tipo_contenedor'] ?? '',
                $doc['contenedor_numero'] ?? '',
                $doc['ubicacion_fisica'] ?? '',
                $doc['estado']
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Exportar a PDF (Vista de Impresión)
     */
    public function exportarPdf($id)
    {
        $this->requireAuth();
        
        $sql = "SELECT ph.*, u.nombre_completo as usuario_nombre, ub.nombre as unidad_nombre 
                FROM prestamos_encabezados ph 
                LEFT JOIN usuarios u ON ph.usuario_id = u.id 
                LEFT JOIN ubicaciones ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);
        
        if (!$prestamo) {
            die("Préstamo no encontrado");
        }
        
        $detalles = $this->prestamoHeader->getDetalles($id);
        
        require __DIR__ . '/../../views/prestamos/pdf_report.php';
        exit;
    }
}
