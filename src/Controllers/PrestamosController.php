<?php
/**
 * Controlador de Préstamos
 * Gestiona préstamos de documentos LIBRO/AMARRO
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Prestamo;
use TAMEP\Models\Documento;
use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\Usuario;
// use TAMEP\Models\HojaRuta;
use TAMEP\Models\PrestamoHeader;
use TAMEP\Models\Ubicacion;
use TAMEP\Models\UnidadArea;
use TAMEP\Core\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PrestamosController extends BaseController
{
    private $documento;
    private $prestamo;
    private $prestamoHeader;
    private $contenedorFisico;
    private $usuario;
    // private $hojaRuta;
    private $ubicacion;
    private $unidadArea;
    
    public function __construct()
    {
        parent::__construct();
        $this->documento = new Documento();
        $this->prestamo = new Prestamo();
        $this->prestamoHeader = new PrestamoHeader();
        $this->contenedorFisico = new ContenedorFisico();
        $this->usuario = new Usuario();
        // $this->hojaRuta = new HojaRuta();
        $this->ubicacion = new Ubicacion();
        $this->unidadArea = new UnidadArea();
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
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
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
        $documentos = $this->documento->getAvailable();
        
        // Obtener unidades/áreas
        $unidades = $this->unidadArea->getActive();
        
        $this->view('prestamos.crear', [
            'documentos' => $documentos,
            'unidades' => $unidades,
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
        
        // Verificar que el documento esté disponible para préstamo
        $documento = $this->documento->find($_POST['documento_id']);
        $estadosPermitidos = ['DISPONIBLE', 'NO UTILIZADO', 'ANULADO'];
        
        if (!$documento || !in_array($documento['estado_documento'], $estadosPermitidos)) {
            Session::flash('error', 'El documento no está disponible para préstamo (Estado: ' . ($documento['estado_documento'] ?? 'N/A') . ')');
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
            'estado' => 'En Proceso'
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
                'estado' => 'En Proceso'
            ];
            
            $id = $this->prestamo->create($data);
            
            if ($id) {
                // Actualizar estado del documento
                // $this->documento->update($_POST['documento_id'], ['estado_documento' => 'PRESTADO']);
                
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
     * Vista de nuevo préstamo con selección múltiple
     */
    public function nuevo()
    {
        $this->requireAuth();
        
        // 1. Limpiar filtros
        if (isset($_GET['clean'])) {
            unset($_SESSION['prestamos_nuevo_filters']);
            $this->redirect('/prestamos/nuevo');
            return;
        }

        // 2. Filtros
        $hasFilters = isset($_GET['search']) || isset($_GET['gestion']) || isset($_GET['tipo_documento']) ||
                      isset($_GET['sort']) || isset($_GET['per_page']);

        if ($hasFilters) {
            $_SESSION['prestamos_nuevo_filters'] = [
                'search' => $_GET['search'] ?? '',
                'gestion' => $_GET['gestion'] ?? '',
                'tipo_documento' => $_GET['tipo_documento'] ?? '',
                'sort' => $_GET['sort'] ?? '',
                'order' => $_GET['order'] ?? '',
                'per_page' => $_GET['per_page'] ?? 20
            ];
        } elseif (isset($_SESSION['prestamos_nuevo_filters']) && empty($_GET['page'])) {
             $params = http_build_query($_SESSION['prestamos_nuevo_filters']);
             $this->redirect('/prestamos/nuevo?' . $params);
             return;
        }

        $search = $_GET['search'] ?? '';
        $gestion = $_GET['gestion'] ?? '';
        $tipo_documento = $_GET['tipo_documento'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $order = $_GET['order'] ?? 'asc';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
        if ($perPage < 1) $perPage = 20;
        if ($perPage > 200) $perPage = 200;

        $documentos = [];
        $total = 0;
        
        // Solo buscar si hay filtros (optimización)
        if (!empty($search) || !empty($gestion) || !empty($tipo_documento)) {
            
            // Parametros comunes
            $limit = $perPage;
            $offset = ($page - 1) * $limit;
            $orderDir = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

            // Buscar según el tipo de documento
            if ($tipo_documento === 'HOJA_RUTA_DIARIOS') {
                $where = ["hr.activo = 1"];
                $params = [];
                
                if (!empty($search)) {
                    if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
                        $min = min((int)$matches[1], (int)$matches[2]);
                        $max = max((int)$matches[1], (int)$matches[2]);
                        $where[] = "CAST(hr.nro_comprobante_diario AS UNSIGNED) BETWEEN ? AND ?";
                        $params[] = $min; $params[] = $max;
                    } else {
                        $where[] = "(hr.nro_comprobante_diario = ? OR hr.nro_hoja_ruta = ? OR hr.rubro LIKE ? OR hr.interesado LIKE ?)";
                        $params[] = $search; $params[] = $search; $params[] = "%$search%"; $params[] = "%$search%";
                    }
                }
                
                if (!empty($gestion)) {
                    $where[] = "hr.gestion = ?";
                    $params[] = $gestion;
                }
                
                $whereClause = 'WHERE ' . implode(' AND ', $where);
                
                // Sorting HR
                $orderBy = "hr.gestion DESC, hr.nro_comprobante_diario DESC";
                if ($sort === 'gestion') $orderBy = "hr.gestion $orderDir";
                if ($sort === 'nro_comprobante') $orderBy = "hr.nro_comprobante_diario $orderDir";
                if ($sort === 'ubicacion') $orderBy = "ub.nombre $orderDir";
                
                // Count
                $sqlCount = "SELECT COUNT(*) as total FROM registro_hojas_ruta hr 
                             LEFT JOIN contenedores_fisicos cf ON hr.contenedor_fisico_id = cf.id 
                             LEFT JOIN ubicaciones ub ON cf.ubicacion_id = ub.id 
                             {$whereClause}";
                $resCount = $this->hojaRuta->getDb()->fetchOne($sqlCount, $params);
                $total = $resCount['total'] ?? 0;

                // Data
                $sql = "SELECT hr.id, hr.gestion, hr.nro_comprobante_diario as nro_comprobante, hr.nro_hoja_ruta,
                               hr.rubro, hr.interesado, 'HOJA_RUTA_DIARIOS' as tipo_documento,
                               cf.tipo_contenedor, cf.numero as contenedor_numero, ub.nombre as ubicacion_fisica
                        FROM registro_hojas_ruta hr
                        LEFT JOIN contenedores_fisicos cf ON hr.contenedor_fisico_id = cf.id
                        LEFT JOIN ubicaciones ub ON cf.ubicacion_id = ub.id
                        {$whereClause}
                        ORDER BY {$orderBy}
                        LIMIT {$limit} OFFSET {$offset}";
                
                $documentos = $this->hojaRuta->getDb()->fetchAll($sql, $params);

            } else {
                // Documentos comunes
                $where = ["rd.estado_documento IN ('DISPONIBLE', 'NO UTILIZADO', 'ANULADO', 'FALTA', 'PRESTADO')"];
                $params = [];
                
                if (!empty($search)) {
                    if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
                        $min = min((int)$matches[1], (int)$matches[2]);
                        $max = max((int)$matches[1], (int)$matches[2]);
                        $where[] = "CAST(rd.nro_comprobante AS UNSIGNED) BETWEEN ? AND ?";
                        $params[] = $min; $params[] = $max;
                    } else {
                        $where[] = "(rd.nro_comprobante = ? OR rd.codigo_abc = ?)";
                        $params[] = $search; $params[] = $search;
                    }
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
                
                // Sorting Docs
                $orderBy = "rd.gestion DESC, rd.nro_comprobante DESC";
                if ($sort === 'gestion') $orderBy = "rd.gestion $orderDir";
                if ($sort === 'nro_comprobante') {
                     if ($orderDir === 'ASC') $orderBy = "CAST(rd.nro_comprobante AS UNSIGNED) ASC, rd.nro_comprobante ASC";
                     else $orderBy = "CAST(rd.nro_comprobante AS UNSIGNED) DESC, rd.nro_comprobante DESC";
                }
                if ($sort === 'ubicacion') $orderBy = "ub.nombre $orderDir";
                if ($sort === 'estado') $orderBy = "rd.estado_documento $orderDir";

                // Count
                $sqlCount = "SELECT COUNT(*) as total FROM documentos rd 
                             LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id 
                             LEFT JOIN ubicaciones ub ON cf.ubicacion_id = ub.id 
                             {$whereClause}";
                $resCount = $this->documento->getDb()->fetchOne($sqlCount, $params);
                $total = $resCount['total'] ?? 0;

                // Data
                $sql = "SELECT rd.*, cf.tipo_contenedor, cf.numero as contenedor_numero, ub.nombre as ubicacion_fisica
                        FROM documentos rd
                        LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                        LEFT JOIN ubicaciones ub ON cf.ubicacion_id = ub.id
                        {$whereClause}
                        ORDER BY {$orderBy}
                        LIMIT {$limit} OFFSET {$offset}";
                
                $documentos = $this->documento->getDb()->fetchAll($sql, $params);
            }
        }
        
        // Obtener unidades
        $unidades = $this->unidadArea->getActive();
        
        $this->view('prestamos.nuevo', [
            'documentos' => $documentos,
            'unidades' => $unidades,
            'filtros' => [
                'search' => $search,
                'gestion' => $gestion,
                'tipo_documento' => $tipo_documento,
                'sort' => $sort,
                'order' => $order,
                'per_page' => $perPage
            ],
            'paginacion' => [
                'current' => $page,
                'total' => $total,
                'per_page' => $perPage,
                'page' => $page,
                'total_pages' => ceil($total / $perPage)
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
        if (empty($_POST['unidad_area_id']) || empty($_POST['documentos'])) {
            Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/prestamos/nuevo');
        }
        
        $documentosIds = json_decode($_POST['documentos'], true);
        
        if (empty($documentosIds)) {
            Session::flash('error', 'Debe seleccionar al menos un documento');
            $this->redirect('/prestamos/nuevo');
        }

        // Datos del Formulario
        $fechaPrestamo = !empty($_POST['fecha_prestamo']) ? $_POST['fecha_prestamo'] : date('Y-m-d');
        $fechaDevolucion = !empty($_POST['fecha_devolucion']) ? $_POST['fecha_devolucion'] : null;
        $esHistorico = !empty($_POST['es_historico']) && $_POST['es_historico'] == '1';
        $estadoInicial = !empty($_POST['estado_inicial']) ? $_POST['estado_inicial'] : 'En Proceso';
        
        // Si no es histórico, la fecha de devolución es obligatoria (aunque el front ya lo valida)
        if (!$esHistorico && empty($fechaDevolucion)) {
             // Fallback default +2 weeks if somehow bypassed
             $fechaDevolucion = date('Y-m-d', strtotime('+14 days'));
        }

        // Crear Encabezado
        $headerData = [
            'usuario_id' => Session::user()['id'],
            'unidad_area_id' => $_POST['unidad_area_id'],
            'nombre_prestatario' => $_POST['nombre_prestatario'] ?? null,
            'fecha_prestamo' => $fechaPrestamo,
            'fecha_devolucion_esperada' => $fechaDevolucion,
            'observaciones' => $_POST['observaciones'] ?? null,
            'estado' => $estadoInicial // 'En Proceso', 'Prestado', or 'Devuelto'
        ];

        $headerId = $this->prestamoHeader->create($headerData);

        if (!$headerId) {
            Session::flash('error', 'Error al crear la cabecera del préstamo');
            $this->redirect('/prestamos/nuevo');
        }
        
        $exitosos = 0;
        $errores = 0;
        
        foreach ($documentosIds as $docId) {
            $documento = $this->documento->find($docId);
            
            if (!$documento) {
                $errores++;
                continue;
            }
            
            // Determine detail state
            // If header is 'Devuelto', details should be 'Devuelto' too
            $detailState = $estadoInicial;
            $fechaDevReal = ($estadoInicial === 'Devuelto') ? ($fechaDevolucion ?? date('Y-m-d')) : null;

            // Crear préstamo detalle
            $data = [
                'encabezado_id' => $headerId,
                'documento_id' => $docId,
                'contenedor_fisico_id' => $documento['contenedor_fisico_id'] ?? null,
                'usuario_id' => $_POST['unidad_area_id'], // Note: This field in detail is technically 'usuario_id' but usually refers to user who registered OR borrower. In `guardar` simpler method it was used as unit? Let's keep consistency with previous code: `usuario_id` => $_POST['usuario_id'] which was missing in previous method, actually previous code used `$_POST['usuario_id']` which was undefined in `guardarMultiple` form! 
                // Ah, looking at previous code: `usuario_id` => $_POST['usuario_id'] ?? Session::user()['id']? 
                // In `guardarMultiple` original code, `usuario_id` was NOT in the form. It likely failed or used null. 
                // Let's use Session user ID as the registrant.
                'usuario_id' => Session::user()['id'],
                'fecha_prestamo' => $fechaPrestamo,
                'fecha_devolucion_esperada' => $fechaDevolucion,
                'fecha_devolucion_real' => $fechaDevReal,
                'observaciones' => $_POST['observaciones'] ?? null,
                'estado' => $detailState
            ];
            
            $id = $this->prestamo->create($data);
            
            if ($id) {
                // Actualizar estado del documento
                if ($detailState === 'Prestado') {
                    $this->documento->update($docId, ['estado_documento' => 'PRESTADO']);
                }
                // Si es 'Devuelto', no cambiamos estado o lo dejamos/volvemos a 'DISPONIBLE'?
                // Si es histórico y ya devuelto, el documento debería estar DISPONIBLE (o lo que sea actual).
                // No deberíamos tocar el documento si ya fue devuelto, salvo asegurarnos que NO esté PRESTADO.
                // Pero si el usuario está creando historial, asumimos que el documento YA ESTÁ en su sitio.
                // Así que no hacemos update si es Devuelto.
                
                $exitosos++;
            } else {
                $errores++;
            }
        }
        
        if ($exitosos > 0) {
            Session::flash('success', "Préstamo registrado: {$exitosos} documento(s). Estado: {$estadoInicial}");
        } else {
            Session::flash('error', 'No se pudo registrar ningún documento en el préstamo');
        }
        
        // Redirect logic
        // If 'En Proceso', go to processing
        // If 'Prestado' or 'Devuelto', go to View
        if ($estadoInicial === 'En Proceso') {
            $this->redirect('/prestamos/procesar/' . $headerId);
        } else {
            $this->redirect('/prestamos/ver/' . $headerId);
        }
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
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);
        
        if (!$prestamo) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }

        // Get Details
        $detalles = $this->prestamoHeader->getDetalles($id);
        
        // Split details into Prestados and No Prestados
        $prestados = [];
        $noPrestados = [];
        
        foreach ($detalles as $det) {
            if ($det['estado'] === 'Prestado' || $det['estado'] === 'Devuelto') {
                $prestados[] = $det;
            } else {
                // Includes 'No Prestado', 'Falta', 'En Proceso', etc.
                // Fetch current document state to show real status if needed
                // But $det should already have joined data.
                // Wait, getDetalles might need to join document status to be sure?
                // Usually getDetalles just gets prestamos table.
                // Let's assume getDetalles joins documents table too?  Need to check Model...
                // Assuming it does or we can add it. 
                // For now, let's just group them.
                $noPrestados[] = $det;
            }
        }
        
        $this->view('prestamos.detalle', [
            'prestamo' => $prestamo,
            'detalles' => $detalles, // Keep full list for safety/pdf
            'prestados' => $prestados,
            'noPrestados' => $noPrestados,
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
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
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
        fputcsv($output, ['Fecha Prestamo:', date('d/m/Y', strtotime($prestamo['fecha_prestamo']))]);
        fputcsv($output, ['Devolucion Esperada:', $prestamo['fecha_devolucion_esperada'] ? date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) : 'N/A']);
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
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);
        
        if (!$prestamo) {
            die("Préstamo no encontrado");
        }
        
        $detalles = $this->prestamoHeader->getDetalles($id);
        
        require __DIR__ . '/../../views/prestamos/pdf_report.php';
        exit;
    }
    /**
     * Vista de Procesar Préstamo (Verificación Física)
     */
    public function procesar($id)
    {
        $this->requireAuth();
        
        // Obtener Encabezado
        $sql = "SELECT ph.*, u.nombre_completo as usuario_nombre, ub.nombre as unidad_nombre 
                FROM prestamos_encabezados ph 
                LEFT JOIN usuarios u ON ph.usuario_id = u.id 
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);
        
        if (!$prestamo) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }
        
        if ($prestamo['estado'] === 'Prestado' || $prestamo['estado'] === 'Devuelto') {
            Session::flash('warning', 'Este préstamo ya ha sido procesado.');
            $this->redirect('/prestamos/ver/' . $id);
        }
        
        $detalles = $this->prestamoHeader->getDetalles($id);
        
        $this->view('prestamos.procesar', [
            'prestamo' => $prestamo,
            'detalles' => $detalles,
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Confirmar Proceso (Guardar checkeos)
     */
    public function confirmarProceso()
    {
        $this->requireAuth();
        
        $encabezado_id = $_POST['encabezado_id'] ?? null;
        $seleccionados = $_POST['documentos'] ?? []; // IDs of Prestamo Details that are FOUND
        
        if (!$encabezado_id) {
            Session::flash('error', 'ID no válido');
            $this->redirect('/prestamos');
        }
        
        // Obtener todos los detalles originales para iterar
        $detalles = $this->prestamoHeader->getDetalles($encabezado_id);
        
        foreach ($detalles as $doc) {
            $is_found = in_array($doc['id'], $seleccionados);
            
            if ($is_found) {
                // DOCUMENTO ENCONTRADO -> SE PRESTA
                // Verificar si era irregular (estaba FALTA antes)
                $currentDoc = $this->documento->find($doc['documento_id']);
                // Assuming find() returns associative array, check key logic
                $estadoOriginal = $currentDoc['estado_documento'] ?? 'Desconocido';
                
                // Actualizar detalle de prestamo
                $this->prestamo->update($doc['id'], ['estado' => 'Prestado']);
                
                // Actualizar documento principal
                // Si estaba FALTA y ahora se presta -> Es irregular pero pasa a PRESTADO
                // Debemos agregar una observación
                $obsData = [];
                if ($estadoOriginal === 'FALTA') {
                    $currentObs = $currentDoc['observaciones'] ?? '';
                    if (mb_stripos($currentObs, 'FALTA') === false) {
                         // Si no tiene la palabra falta, agregamos nota
                         $suffix = " (Irregularidad: Estaba marcado como FALTA pero fue Prestado en #$encabezado_id)";
                         $obsData['observaciones'] = trim($currentObs . $suffix);
                    }
                }
                
                $updateData = array_merge(['estado_documento' => 'PRESTADO'], $obsData);
                $this->documento->update($doc['documento_id'], $updateData);
                
            } else {
                // DOCUMENTO NO ENCONTRADO (No marcado)
                // Entendido: SI ESTABA DISPONIBLE y NO SE PRESTA -> SIGUE DISPONIBLE
                // Simplemente marcamos el detalle del préstamo como 'No Prestado' para historial
                $this->prestamo->update($doc['id'], ['estado' => 'No Prestado']);
                
                // NO tocamos la tabla de documentos
            }
        }
        
        // Actualizar Encabezado a Prestado
        $this->prestamoHeader->update($encabezado_id, ['estado' => 'Prestado']);
        
        Session::flash('success', 'Préstamo procesado correctamente.');
        $this->redirect('/prestamos/ver/' . $encabezado_id);
    }

    /**
     * Revertir Estado a En Proceso
     */
    public function revertirProceso($id)
    {
        $this->requireAuth();
        
        // Verificar existencia y estado
        $sqlHeader = "SELECT * FROM prestamos_encabezados WHERE id = ?";
        $header = $this->prestamoHeader->getDb()->fetchOne($sqlHeader, [$id]);
        
        if (!$header) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }

        // Permitimos revertir si NO está Devuelto (es decir, Prestado o En Proceso)
        if ($header['estado'] !== 'Devuelto') {
             
             // 1. Revertir Encabezado
             $this->prestamoHeader->update($id, ['estado' => 'En Proceso']);
             
             // 2. Revertir Detalles
             // Restablecer detalles a 'En Proceso'
             $this->prestamoHeader->getDb()->query(
                 "UPDATE prestamos SET estado = 'En Proceso' WHERE encabezado_id = ?", 
                 [$id]
             );
             
             Session::flash('success', 'Préstamo revertido a estado de verificación (En Proceso).');
        } else {
            Session::flash('warning', 'No se puede procesar un préstamo ya devuelto.');
        }
        
        // Redirigir a la vista de Procesar para verificar de nuevo
        $this->redirect('/prestamos/procesar/' . $id);
    }
    
    public function vistaImportar()
    {
        $this->requireAuth();
        $unidades = $this->unidadArea->getActive();
        $this->view('prestamos.importar', [
            'unidades' => $unidades,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    public function procesarImportacion()
    {
        $this->requireAuth();

        if (empty($_FILES['excel_file']['name'])) {
            Session::flash('error', 'Debe seleccionar un archivo Excel');
            $this->redirect('/prestamos/importar');
        }

        try {
            $inputFileName = $_FILES['excel_file']['tmp_name'];
            $spreadsheet = IOFactory::load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Asumimos primera fila es encabezado
            // 0: Tipo Documento, 1: GESTION, 2: NRO. DE COMPROBANTE DIARIO
            
            $found_ids = [];
            $missing = [];
            
            // Skip header (row 0)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty($row[0]) && empty($row[1]) && empty($row[2])) continue;

                $tipo = trim($row[0]);
                $gestion = intval($row[1]);
                $nro = trim($row[2]);
                
                $sql = "SELECT id, estado_documento, contenedor_fisico_id FROM documentos WHERE gestion = ? AND nro_comprobante = ?";
                $doc = $this->documento->getDb()->fetchOne($sql, [$gestion, $nro]);

                if ($doc) {
                    if ($doc['estado_documento'] === 'DISPONIBLE') {
                        $found_ids[] = $doc; // Save whole doc array to access contenedor_id
                    } else {
                        $missing[] = "Fila " . ($i+1) . ": Documento encontrado pero NO disponible (Estado: {$doc['estado_documento']})";
                    }
                } else {
                    $missing[] = "Fila " . ($i+1) . ": No encontrado [Gestion: $gestion, Nro: $nro]";
                }
            }

            if (empty($found_ids)) {
                Session::flash('error', 'No se encontraron documentos válidos para prestar en el archivo.');
                if (!empty($missing)) {
                    Session::flash('info', 'Errores: ' . implode('<br>', array_slice($missing, 0, 5)) . (count($missing)>5 ? '...' : ''));
                }
                $this->redirect('/prestamos/importar');
            }

            $headerData = [
                'usuario_id' => Session::user()['id'],
                'unidad_area_id' => $_POST['unidad_area_id'],
                'nombre_prestatario' => $_POST['nombre_prestatario'] ?? null,
                'fecha_prestamo' => date('Y-m-d'),
                'fecha_devolucion_esperada' => $_POST['fecha_devolucion'],
                'observaciones' => "Importado desde Excel (" . $_FILES['excel_file']['name'] . ")",
                'estado' => 'Prestado'
            ];

            $headerId = $this->prestamoHeader->create($headerData);

            if (!$headerId) {
                throw new \Exception("Error al crear cabecera de préstamo");
            }

            $count = 0;
            foreach ($found_ids as $doc) {
                $docId = $doc['id'];
                $data = [
                    'encabezado_id' => $headerId,
                    'documento_id' => $docId,
                    'contenedor_fisico_id' => $doc['contenedor_fisico_id'] ?? null,
                    'usuario_id' => $_POST['unidad_area_id'], 
                    'fecha_prestamo' => date('Y-m-d'),
                    'fecha_devolucion_esperada' => $_POST['fecha_devolucion'],
                    'estado' => 'Prestado'
                ];
                
                if ($this->prestamo->create($data)) {
                    $this->documento->update($docId, ['estado_documento' => 'PRESTADO']);
                    $count++;
                }
            }

            Session::flash('success', "Préstamo importado exitosamente! Se prestaron $count documentos.");
            $this->redirect('/prestamos');

        } catch (\Exception $e) {
            Session::flash('error', 'Error al procesar archivo: ' . $e->getMessage());
            $this->redirect('/prestamos/importar');
        }
    }
    
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
                    $this->documento->update($doc['documento_id'], ['estado_documento' => 'DISPONIBLE']);
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
                    $this->documento->update($doc['documento_id'], ['estado_documento' => 'PRESTADO']);
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
}
