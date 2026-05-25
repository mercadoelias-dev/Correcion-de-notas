<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/helpers.php';
requireLogin();
$user = currentUser($pdo);
require_once __DIR__.'/../src/solicitudes.php';

$estudiantes = $pdo->query('SELECT e.*, CONCAT(e.primer_nombre, " ", e.primer_apellido) as nombre_completo, c.nombre as carrera_nombre FROM estudiantes e LEFT JOIN carreras c ON c.carrera_id = e.carrera_id ORDER BY e.primer_apellido, e.primer_nombre')->fetchAll();
$asignaturas = $pdo->query('SELECT * FROM asignaturas ORDER BY nombre')->fetchAll();

// Indexar estudiantes por id para autocompletar
$est_map = [];
foreach ($estudiantes as $e) $est_map[$e['estudiante_id']] = $e;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $estudiante_id = intval($_POST['estudiante_id'] ?? 0);
        $asignatura_id = intval($_POST['asignatura_id'] ?? 0);

        $data = [
            'estudiante_documento'      => trim($_POST['estudiante_documento'] ?? ''),
            'estudiante_semestre'       => trim($_POST['estudiante_semestre'] ?? ''),
            'periodo_academico'         => trim($_POST['periodo_academico'] ?? ''),
            'tipo_solicitud'            => $_POST['tipo_solicitud'] ?? 'CORRECCION',
            'corte'                     => $_POST['corte'] ?? '',
            'motivo'                    => trim($_POST['motivo'] ?? ''),
            'nota_inicial_formativa'    => $_POST['nota_inicial_formativa'] !== '' ? floatval($_POST['nota_inicial_formativa']) : null,
            'nota_inicial_aplicativa'   => $_POST['nota_inicial_aplicativa'] !== '' ? floatval($_POST['nota_inicial_aplicativa']) : null,
            'nota_inicial_cognitiva'    => $_POST['nota_inicial_cognitiva'] !== '' ? floatval($_POST['nota_inicial_cognitiva']) : null,
            'nota_inicial_letras'       => trim($_POST['nota_inicial_letras'] ?? ''),
            'nota_corregida_formativa'  => $_POST['nota_corregida_formativa'] !== '' ? floatval($_POST['nota_corregida_formativa']) : null,
            'nota_corregida_aplicativa' => $_POST['nota_corregida_aplicativa'] !== '' ? floatval($_POST['nota_corregida_aplicativa']) : null,
            'nota_corregida_cognitiva'  => $_POST['nota_corregida_cognitiva'] !== '' ? floatval($_POST['nota_corregida_cognitiva']) : null,
            'nota_corregida_letras'     => trim($_POST['nota_corregida_letras'] ?? ''),
        ];

        $tipos_validos = ['CORRECCION','REPORTE','VALIDACION','SUFICIENCIA','HABILITACION','SUPLETORIOS'];
        $cortes_validos = ['PRIMERO','SEGUNDO','TERCERO'];

        if ($estudiante_id <= 0)                          $errors[] = 'Seleccione un estudiante.';
        if ($asignatura_id <= 0)                          $errors[] = 'Seleccione una asignatura.';
        if (empty($data['periodo_academico']))             $errors[] = 'El período académico es obligatorio.';
        if (!in_array($data['tipo_solicitud'], $tipos_validos)) $errors[] = 'Tipo de solicitud inválido.';
        if (!in_array($data['corte'], $cortes_validos))   $errors[] = 'Seleccione el corte académico.';

        if (empty($errors)) {
            try {
                $solicitud_id = crearSolicitud($pdo, $user['profesor_id'], $estudiante_id, $asignatura_id, $data);

                // Subir evidencias
                $uploadDir = __DIR__.'/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $allowedExt  = ['pdf','jpg','jpeg','png'];
                $allowedMime = ['application/pdf','image/jpeg','image/png'];

                if (!empty($_FILES['evidencias']['name'][0])) {
                    for ($i = 0; $i < count($_FILES['evidencias']['name']); $i++) {
                        if (empty($_FILES['evidencias']['name'][$i])) continue;
                        $orig = $_FILES['evidencias']['name'][$i];
                        $tmp  = $_FILES['evidencias']['tmp_name'][$i];
                        $size = $_FILES['evidencias']['size'][$i];
                        $mime = mime_content_type($tmp);
                        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowedExt))   { $errors[] = 'Extensión no permitida: '.$orig; continue; }
                        if (!in_array($mime, $allowedMime)) { $errors[] = 'Tipo no permitido: '.$orig; continue; }
                        if ($size > 8*1024*1024)            { $errors[] = 'Archivo demasiado grande: '.$orig; continue; }
                        $stored = uniqid('evid_').'.'.$ext;
                        if (move_uploaded_file($tmp, $uploadDir.$stored)) {
                            $pdo->prepare('INSERT INTO evidencias (solicitud_id,filename_original,filename_stored,mime_type,tamano_bytes) VALUES (?,?,?,?,?)')->execute([$solicitud_id,$orig,$stored,$mime,$size]);
                        }
                    }
                }

                // Notificar admins
                $admins = $pdo->query("SELECT profesor_id FROM profesores WHERE rol='ADMINISTRADOR'")->fetchAll();
                foreach ($admins as $admin) {
                    crearNotificacion($pdo, $admin['profesor_id'],
                        "Nueva solicitud #{$solicitud_id} de {$user['nombre']} pendiente de revisión.",
                        "solicitud_detalle.php?id={$solicitud_id}"
                    );
                }

                // Correo a administradores
                require_once __DIR__.'/../vendor/autoload.php';  // Cargar PHPMailer
                require_once __DIR__.'/../src/mailer_smtp.php';
                $sol_completa = getSolicitud($pdo, $solicitud_id);
                if ($sol_completa) {
                    $mailer = new Mailer($pdo);
                    $mailer->notificarNuevaSolicitud($sol_completa, $user['nombre'], $user['email']);
                }

                header('Location: solicitud_detalle.php?id='.$solicitud_id);
                exit;
            } catch (\Exception $e) {
                $errors[] = 'Error al guardar: '.$e->getMessage();
            }
        }
    }
}

include 'header.php';
?>

<?php foreach ($errors as $e) echo '<div class="alert alert-error">'.h($e).'</div>'; ?>

<div class="card">
  <h3>Nueva Solicitud de Corrección de Notas</h3>
  <form method="post" enctype="multipart/form-data" id="solForm">
    <?= csrf_field() ?>

    <!-- SECCIÓN 1: DATOS PERSONALES DEL ESTUDIANTE -->
    <div class="form-section-title">Datos personales del estudiante</div>
    <div class="form-row">
      <div class="col">
        <label>Nombre del estudiante *</label>
        <select name="estudiante_id" class="input" required id="selectEst" onchange="autoFill(this.value)">
          <option value="">-- Seleccione --</option>
          <?php foreach ($estudiantes as $est): ?>
            <option value="<?= $est['estudiante_id'] ?>"
              data-doc="<?= h($est['documento_identidad'] ?? '') ?>"
              data-sem="<?= h($est['semestre'] ?? '') ?>"
              data-carrera="<?= h($est['carrera_nombre'] ?? '') ?>">
              <?= h($est['nombre_completo']) ?> (<?= h($est['codigo']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <label>Documento de identidad</label>
        <input class="input" name="estudiante_documento" id="estDoc" placeholder="Número de documento">
      </div>
    </div>
    <div class="form-row" style="margin-top:10px">
      <div class="col">
        <label>Programa</label>
        <input class="input" id="estCarrera" placeholder="Se completa automáticamente" disabled>
      </div>
      <div class="col">
        <label>Semestre</label>
        <input class="input" name="estudiante_semestre" id="estSem" placeholder="Ej: 4">
      </div>
    </div>

    <!-- SECCIÓN 2: DATOS ACADÉMICOS -->
    <div class="form-section-title" style="margin-top:20px">Datos académicos</div>
    <div class="form-row">
      <div class="col">
        <label>Período académico *</label>
        <input class="input" name="periodo_academico" placeholder="Ej: 2024-1" required>
      </div>
      <div class="col">
        <label>Nombre de la asignatura *</label>
        <select name="asignatura_id" class="input" required id="selectAsig" onchange="fillCodigo(this)">
          <option value="">-- Seleccione --</option>
          <?php foreach ($asignaturas as $a): ?>
            <option value="<?= $a['asignatura_id'] ?>" data-codigo="<?= h($a['codigo']) ?>">
              <?= h($a['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row" style="margin-top:10px">
      <div class="col">
        <label>Código de la asignatura</label>
        <input class="input" id="asigCodigo" placeholder="Se completa automáticamente" disabled>
      </div>
      <div class="col">
        <label>Profesor</label>
        <input class="input" value="<?= h($user['nombre']) ?>" disabled>
      </div>
    </div>

    <!-- SECCIÓN 3: CORTE ACADÉMICO -->
    <div class="form-section-title" style="margin-top:20px">Corte académico a corregir o reportar</div>
    <div class="form-row">
      <div class="col">
        <label>Tipo *</label>
        <select name="tipo_solicitud" class="input" required>
          <option value="CORRECCION">Corrección</option>
          <option value="REPORTE">Reporte</option>
          <option value="VALIDACION">Validación</option>
          <option value="SUFICIENCIA">Suficiencia</option>
          <option value="HABILITACION">Habilitación</option>
          <option value="SUPLETORIOS">Supletorios</option>
        </select>
      </div>
      <div class="col">
        <label>Corte *</label>
        <select name="corte" class="input" required>
          <option value="">-- Seleccione --</option>
          <option value="PRIMERO">Primer corte (30%)</option>
          <option value="SEGUNDO">Segundo corte (30%)</option>
          <option value="TERCERO">Tercer corte (40%)</option>
        </select>
      </div>
    </div>
    <div style="margin-top:10px">
      <label>Motivo</label>
      <textarea class="input" name="motivo" rows="3" placeholder="Describa el motivo de la solicitud..."></textarea>
    </div>

    <!-- SECCIÓN 4: CORRECCIÓN DE NOTA -->
    <div class="form-section-title" style="margin-top:20px">Corrección de nota</div>
    <div class="nota-table-wrap">
      <table class="nota-table">
        <thead>
          <tr>
            <th rowspan="2"></th>
            <th colspan="3">Nota</th>
            <th rowspan="2">En letras</th>
          </tr>
          <tr>
            <th>Formativa</th>
            <th>Aplicativa</th>
            <th>Cognitiva</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="nota-label">Nota inicialmente reportada</td>
            <td><input class="input input-nota" type="number" step="0.01" min="0" max="5" name="nota_inicial_formativa" placeholder="0.00"></td>
            <td><input class="input input-nota" type="number" step="0.01" min="0" max="5" name="nota_inicial_aplicativa" placeholder="0.00"></td>
            <td><input class="input input-nota" type="number" step="0.01" min="0" max="5" name="nota_inicial_cognitiva" placeholder="0.00"></td>
            <td><input class="input" name="nota_inicial_letras" placeholder="Ej: Tres punto cinco"></td>
          </tr>
          <tr>
            <td class="nota-label">Nota corregida</td>
            <td><input class="input input-nota" type="number" step="0.01" min="0" max="5" name="nota_corregida_formativa" placeholder="0.00"></td>
            <td><input class="input input-nota" type="number" step="0.01" min="0" max="5" name="nota_corregida_aplicativa" placeholder="0.00"></td>
            <td><input class="input input-nota" type="number" step="0.01" min="0" max="5" name="nota_corregida_cognitiva" placeholder="0.00"></td>
            <td><input class="input" name="nota_corregida_letras" placeholder="Ej: Cuatro punto cero"></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- EVIDENCIAS -->
    <div class="form-section-title" style="margin-top:20px">Evidencias</div>
    
    <div class="file-upload-container">
        <input type="file" name="evidencias[]" multiple id="fileInput" class="file-input-hidden" accept=".pdf,.jpg,.jpeg,.png">
        <label for="fileInput" class="file-upload-label">
            <div class="file-upload-icon">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <div class="file-upload-text">
                <span class="file-upload-title">Haz clic para seleccionar archivos</span>
                <span class="file-upload-subtitle">o arrastra y suelta aquí</span>
            </div>
            <div class="file-upload-info">
                <span class="file-format-badge">PDF</span>
                <span class="file-format-badge">JPG</span>
                <span class="file-format-badge">PNG</span>
                <span class="file-size-text">Máximo 8 MB por archivo</span>
            </div>
        </label>
        <div id="fileList" class="file-list"></div>
    </div>

    <div style="margin-top:20px">
      <button class="btn btn-primary" type="submit">Enviar solicitud</button>
      <a href="dashboard_profesor.php" class="btn btn-secondary" style="margin-left:8px">Cancelar</a>
    </div>
  </form>
</div>

<style>
.form-section-title {
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  background: #1F2347;
  padding: 7px 14px;
  border-radius: 8px;
  margin-bottom: 12px;
  letter-spacing: .4px;
  text-transform: uppercase;
}
.nota-table-wrap { overflow-x: auto; }
.nota-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.nota-table th, .nota-table td {
  border: 1px solid #E2E4EF;
  padding: 8px 10px;
  text-align: center;
}
.nota-table th { background: #f0ebff; color: #1F2347; font-weight: 600; }
.nota-label { text-align: left; font-weight: 500; color: #1F2347; white-space: nowrap; }
.input-nota { width: 80px; text-align: center; }

/* ═══════════════════════════════════════════════════════════════
   ESTILOS MEJORADOS PARA FILE UPLOAD
   ═══════════════════════════════════════════════════════════════ */
.file-upload-container {
  margin-top: 12px;
}

.file-input-hidden {
  display: none;
}

.file-upload-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  border: 3px dashed #D1D5DB;
  border-radius: 16px;
  background: linear-gradient(135deg, #F9FAFB 0%, #FFFFFF 100%);
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.file-upload-label::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.file-upload-label:hover {
  border-color: #667eea;
  background: linear-gradient(135deg, #F0EBFF 0%, #FFFFFF 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
}

.file-upload-label:hover::before {
  opacity: 1;
}

.file-upload-label:hover .file-upload-icon {
  transform: translateY(-5px) scale(1.05);
}

.file-upload-label:hover .file-upload-icon svg {
  stroke: #667eea;
}

.file-upload-icon {
  margin-bottom: 16px;
  transition: all 0.3s ease;
  position: relative;
  z-index: 1;
}

.file-upload-icon svg {
  color: #9CA3AF;
  transition: all 0.3s ease;
}

.file-upload-text {
  text-align: center;
  margin-bottom: 16px;
  position: relative;
  z-index: 1;
}

.file-upload-title {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: #1F2347;
  margin-bottom: 4px;
}

.file-upload-subtitle {
  display: block;
  font-size: 14px;
  color: #6B7280;
}

.file-upload-info {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: center;
  position: relative;
  z-index: 1;
}

.file-format-badge {
  padding: 4px 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.file-size-text {
  font-size: 12px;
  color: #9CA3AF;
  font-weight: 500;
  margin-left: 4px;
}

.file-list {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.file-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 12px;
  transition: all 0.2s ease;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.file-item:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.file-item-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.file-item-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 11px;
  color: white;
  flex-shrink: 0;
}

.file-item-icon.pdf {
  background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
}

.file-item-icon.jpg,
.file-item-icon.jpeg,
.file-item-icon.png {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
}

.file-item-details {
  flex: 1;
  min-width: 0;
}

.file-item-name {
  font-size: 14px;
  font-weight: 600;
  color: #1F2347;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-item-size {
  font-size: 12px;
  color: #6B7280;
}

.file-item-remove {
  padding: 8px;
  background: #FEE2E2;
  color: #DC2626;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.file-item-remove:hover {
  background: #DC2626;
  color: white;
  transform: scale(1.1);
}

.file-item-remove svg {
  width: 18px;
  height: 18px;
}

/* Drag and drop active state */
.file-upload-label.drag-over {
  border-color: #667eea;
  background: linear-gradient(135deg, #EDE9FE 0%, #FFFFFF 100%);
  transform: scale(1.02);
}

.file-upload-label.drag-over::before {
  opacity: 1;
}

/* Responsive */
@media (max-width: 640px) {
  .file-upload-label {
    padding: 30px 15px;
  }
  
  .file-upload-icon svg {
    width: 40px;
    height: 40px;
  }
  
  .file-upload-title {
    font-size: 14px;
  }
  
  .file-upload-subtitle {
    font-size: 12px;
  }
  
  .file-item {
    padding: 12px;
  }
  
  .file-item-icon {
    width: 36px;
    height: 36px;
    font-size: 10px;
  }
}
</style>

<script>
const estData = <?= json_encode(array_values($est_map)) ?>;
function autoFill(id) {
  const opt = document.querySelector('#selectEst option[value="'+id+'"]');
  if (!opt) return;
  document.getElementById('estDoc').value    = opt.dataset.doc || '';
  document.getElementById('estSem').value    = opt.dataset.sem || '';
  document.getElementById('estCarrera').value = opt.dataset.carrera || '';
}
function fillCodigo(sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('asigCodigo').value = opt ? (opt.dataset.codigo || '') : '';
}

// ═══════════════════════════════════════════════════════════════
// MANEJO MEJORADO DE ARCHIVOS
// ═══════════════════════════════════════════════════════════════
const fileInput = document.getElementById('fileInput');
const fileList = document.getElementById('fileList');
const uploadLabel = document.querySelector('.file-upload-label');

// Mostrar archivos seleccionados
fileInput.addEventListener('change', function(e) {
  displayFiles(this.files);
});

// Drag and drop
uploadLabel.addEventListener('dragover', function(e) {
  e.preventDefault();
  this.classList.add('drag-over');
});

uploadLabel.addEventListener('dragleave', function(e) {
  e.preventDefault();
  this.classList.remove('drag-over');
});

uploadLabel.addEventListener('drop', function(e) {
  e.preventDefault();
  this.classList.remove('drag-over');
  
  const dt = e.dataTransfer;
  const files = dt.files;
  
  fileInput.files = files;
  displayFiles(files);
});

function displayFiles(files) {
  fileList.innerHTML = '';
  
  if (files.length === 0) return;
  
  Array.from(files).forEach((file, index) => {
    const fileItem = createFileItem(file, index);
    fileList.appendChild(fileItem);
  });
}

function createFileItem(file, index) {
  const div = document.createElement('div');
  div.className = 'file-item';
  
  const ext = file.name.split('.').pop().toLowerCase();
  const size = formatFileSize(file.size);
  
  div.innerHTML = `
    <div class="file-item-info">
      <div class="file-item-icon ${ext}">
        ${ext.toUpperCase()}
      </div>
      <div class="file-item-details">
        <div class="file-item-name" title="${file.name}">${file.name}</div>
        <div class="file-item-size">${size}</div>
      </div>
    </div>
    <button type="button" class="file-item-remove" onclick="removeFile(${index})" title="Eliminar archivo">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  `;
  
  return div;
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function removeFile(index) {
  const dt = new DataTransfer();
  const files = fileInput.files;
  
  for (let i = 0; i < files.length; i++) {
    if (i !== index) {
      dt.items.add(files[i]);
    }
  }
  
  fileInput.files = dt.files;
  displayFiles(fileInput.files);
}
</script>

<?php include 'footer.php'; ?>
