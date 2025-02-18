<?php
session_start();
require_once 'includes/functions.php';
require_once 'src/db.php';

$usuario_id = 1;
$current_folder_id = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;

// Si estamos en una subcarpeta, obtener sus detalles
$current_folder = null;
$breadcrumbs = [];
if ($current_folder_id) {
    // Obtener detalles de la carpeta actual
    $sql = "SELECT id, nombre, carpeta_padre_id FROM Carpetas WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $current_folder_id]);
    $current_folder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Construir breadcrumbs
    $temp_folder = $current_folder;
    while ($temp_folder) {
        array_unshift($breadcrumbs, $temp_folder);
        if ($temp_folder['carpeta_padre_id']) {
            $stmt->execute([':id' => $temp_folder['carpeta_padre_id']]);
            $temp_folder = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            break;
        }
    }
}

// Obtener carpetas a mostrar
$carpetas = $current_folder_id ? 
    listarSubcarpetas($current_folder_id) : 
    listarCarpetas($usuario_id);

// Procesar las carpetas para incluir información adicional
$carpetas_procesadas = array_map(function($carpeta) {
    return array_merge($carpeta, [
        'ultima_modificacion' => date('Y-m-d H:i:s'),
        'tiene_subcarpetas' => tieneSubcarpetas($carpeta['id']),
        'archivos' => []
    ]);
}, $carpetas);

// Obtener documentos si estamos en una carpeta
$documentos = [];
if ($current_folder_id) {
    $documentos = listarDocumentos($current_folder_id);
}

$_SESSION['folders'] = $carpetas_procesadas;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestorDocs</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content">
                <!-- Breadcrumbs -->
                <div class="breadcrumbs">
                    <a href="index.php" class="breadcrumb-item">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <i class="fas fa-chevron-right"></i>
                        <a href="index.php?folder_id=<?php echo $crumb['id']; ?>" class="breadcrumb-item">
                            <?php echo htmlspecialchars($crumb['nombre']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <h1><?php echo $current_folder ? htmlspecialchars($current_folder['nombre']) : 'Carpetas'; ?></h1>
                
                <?php if ($current_folder): ?>
                    <a href="<?php echo $current_folder['carpeta_padre_id'] ? 
                        'index.php?folder_id=' . $current_folder['carpeta_padre_id'] : 
                        'index.php'; ?>" 
                        class="back-button">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                <?php endif; ?>

                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar..." id="searchInput">
                </div>

                <div class="action-buttons">
                    <button class="create-folder-button" id="createFolderButton">
                        <div class="folder-plus-icon">
                            <i class="fas fa-folder"></i>
                            <i class="fas fa-plus"></i>
                        </div>
                        Crear nueva carpeta
                    </button>

                    <?php if ($current_folder_id): ?>
                    <button class="upload-document-button" id="uploadDocumentButton">
                        <div class="document-plus-icon">
                            <i class="fas fa-file"></i>
                            <i class="fas fa-plus"></i>
                        </div>
                        Subir documento
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Grid de Carpetas -->
                <div class="folders-grid" id="foldersGrid">
                    <?php foreach ($carpetas_procesadas as $folder): ?>
                        <div class="folder-card" data-folder-id="<?php echo htmlspecialchars($folder['id']); ?>">
                            <div class="folder-info">
                                <div class="folder-icon-container">
                                    <i class="fas fa-folder folder-icon"></i>
                                    <?php if ($folder['tiene_subcarpetas']): ?>
                                        <i class="fas fa-folder-tree subcarpeta-indicator" title="Contiene subcarpetas"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="folder-details">
                                    <div class="editable-container">
                                        <h3 class="editable-folder-name" data-folder-id="<?php echo htmlspecialchars($folder['id']); ?>">
                                            <?php echo htmlspecialchars($folder['nombre']); ?>
                                        </h3>
                                        <button class="edit-button" title="Editar nombre">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </div>
                                    <p><?php echo timeAgo($folder['ultima_modificacion']); ?></p>
                                </div>
                                <div class="folder-right-section">
                                    <button class="delete-folder-button" 
                                            title="Eliminar carpeta" 
                                            data-folder-id="<?php echo htmlspecialchars($folder['id']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <div class="folder-avatar"><?php echo htmlspecialchars(substr($folder['nombre'], 0, 1)); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Grid de Documentos -->
                <?php if ($current_folder_id): ?>
                    <div class="section-title">
                        <h2>Documentos</h2>
                    </div>
                    <div class="documents-grid" id="documentsGrid">
                        <?php if (!empty($documentos)): ?>
                            <?php foreach ($documentos as $documento): ?>
                                <div class="document-card" data-document-id="<?php echo htmlspecialchars($documento['id']); ?>">
                                    <div class="document-info">
                                        <div class="document-icon-container">
                                            <?php
                                            $icon_class = 'fa-file';
                                            switch(strtolower($documento['tipo_documento'])) {
                                                case 'pdf':
                                                    $icon_class = 'fa-file-pdf';
                                                    break;
                                                case 'word':
                                                case 'doc':
                                                case 'docx':
                                                    $icon_class = 'fa-file-word';
                                                    break;
                                                case 'excel':
                                                case 'xls':
                                                case 'xlsx':
                                                    $icon_class = 'fa-file-excel';
                                                    break;
                                                case 'imagen':
                                                case 'image':
                                                    $icon_class = 'fa-file-image';
                                                    break;
                                            }
                                            ?>
                                            <i class="fas <?php echo $icon_class; ?> document-icon"></i>
                                        </div>
                                        <div class="document-details">
                                            <div class="editable-container">
                                                <h3 class="editable-document-name" data-document-id="<?php echo htmlspecialchars($documento['id']); ?>">
                                                    <?php echo htmlspecialchars($documento['titulo']); ?>
                                                </h3>
                                                <button class="edit-button" title="Editar nombre">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                            </div>
                                            <p class="document-type"><?php echo htmlspecialchars($documento['tipo_documento']); ?></p>
                                            <p class="document-date"><?php echo timeAgo($documento['fecha_carga']); ?></p>
                                            <?php if ($documento['descripcion']): ?>
                                                <p class="document-description"><?php echo htmlspecialchars($documento['descripcion']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="document-actions">
    <button class="action-button download-button" title="Descargar">
        <i class="fas fa-download"></i>
    </button>
    <button class="action-button preview-button" title="Ver documento" 
            data-document-id="<?php echo htmlspecialchars($documento['id']); ?>">
        <i class="fas fa-eye"></i>
    </button>
    <button class="action-button share-button" title="Compartir">
        <i class="fas fa-share-alt"></i>
    </button>
    <button class="action-button delete-button" title="Eliminar">
        <i class="fas fa-trash"></i>
    </button>
</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-documents">No hay documentos en esta carpeta.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <div id="uploadModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Subir Documento</h2>
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="titulo">Título:</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion"></textarea>
            </div>
            
            <div class="form-group">
                <label for="tipo_id">Tipo de Documento:</label>
                <select id="tipo_id" name="tipo_id" required>
                    <!-- Se llenará dinámicamente -->
                </select>
            </div>
            
            <div class="form-group">
                <label for="documento">Archivo:</label>
                <input type="file" id="documento" name="documento" required>
            </div>
            
            <input type="hidden" id="carpeta_id" name="carpeta_id" value="">
            <input type="hidden" id="usuario_id" name="usuario_id" value="1">
            
            <button type="submit" class="submit-button">Subir Documento</button>
        </form>
    </div>
</div>

    <style>
    /* Estilos para la cuadrícula de documentos */
    .documents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px 0;
    }

    .document-card {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .document-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .document-info {
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    .document-icon-container {
        flex-shrink: 0;
    }

    .document-icon {
        font-size: 24px;
        color: #4a5568;
    }

    .document-details {
        flex: 1;
    }

    .editable-container {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .editable-document-name {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
    }

    .document-type {
        font-size: 13px;
        color: #718096;
        margin: 4px 0;
        text-transform: uppercase;
    }

    .document-date {
        font-size: 12px;
        color: #a0aec0;
    }

    .document-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
    }

    .action-button {
        background: none;
        border: none;
        padding: 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #718096;
    }

    .action-button:hover {
        background-color: #f7fafc;
    }

    .download-button:hover {
        color: #48bb78;
    }

    .preview-button:hover {
        color: #3182ce;
    }

    .delete-button:hover {
        color: #e53e3e;
    }

    /* Estilos específicos para tipos de documentos */
    .fa-file-invoice-dollar {
        color: #805ad5;
    }

    .fa-file-pdf {
        color: #e53e3e;
    }

    .fa-file-word {
        color: #3182ce;
    }

    .fa-file-excel {
        color: #48bb78;
    }

    .fa-file-image {
        color: #ed8936;
    }

    /* Estilos actualizados para la tarjeta de carpeta */
    .folder-card {
        background-color: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        min-height: 100px;
    }

    .folder-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .folder-info {
        display: flex;
        align-items: center;
        gap: 15px;
        width: 100%;
    }

    .folder-icon-container {
        flex-shrink: 0;
    }

    .folder-details {
        flex: 1;
        min-width: 0; /* Evita que el contenido se desborde */
    }

    .folder-right-section {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
    }

    .delete-folder-button {
        background: none;
        border: none;
        color: #718096;
        padding: 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        opacity: 0;
    }

    .folder-card:hover .delete-folder-button {
        opacity: 1;
    }
    .delete-folder-button:hover {
        background-color: #FEE2E2;
        color: #e53e3e;
    }

    .folder-avatar {
        width: 32px;
        height: 32px;
        background-color: #E2E8F0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #4A5568;
        text-transform: uppercase;
    }

    .editable-folder-name {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
        color: #2d3748;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .folder-icon {
        font-size: 24px;
        color:
#e9c750;
    }

    .subcarpeta-indicator {
        font-size: 12px;
        position: absolute;
        bottom: -5px;
        right: -5px;
        color #66b68e;
    }

    .folder-card {
    cursor: grab;
}

.folder-card.dragging {
    opacity: 0.5;
    cursor: grabbing;
}

.folder-card.drop-target {
    border: 2px dashed #4a5568;
    background-color: #f7fafc;
}

.drop-indicator {
    border: 2px dashed #4a5568;
    margin: 10px 0;
    height: 100px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4a5568;
    font-size: 14px;
    background-color: #f7fafc;
}

    /* Responsive */
    @media (max-width: 768px) {
        .documents-grid {
            grid-template-columns: 1fr;
        }

        .folder-card {
            padding: 12px;
        }

        .folder-right-section {
            gap: 5px;
        }

        .folder-avatar {
            width: 28px;
            height: 28px;
            font-size: 14px;
        }

        .delete-folder-button {
            opacity: 1;
            padding: 6px;
        }
    }
    .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 8px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.submit-button {
    background-color: #4CAF50;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.submit-button:hover {
    background-color: #45a049;
}
    
.preview-button:hover {
    color: #3182ce;
}
    </style>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para cargar tipos de documentos
    function cargarTiposDocumentos() {
        const select = document.getElementById('tipo_id');
        
        if (!select) {
            console.error('No se encontró el elemento select#tipo_id');
            return;
        }

        // Mostrar estado de carga
        select.innerHTML = '<option value="">Cargando tipos de documentos...</option>';
        select.disabled = true;

        fetch('get_document_types.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                select.innerHTML = '<option value="">Seleccione un tipo de documento...</option>';
                
                if (data.success && Array.isArray(data.data)) {
                    data.data.forEach(tipo => {
                        const option = document.createElement('option');
                        option.value = tipo.id;
                        option.textContent = tipo.nombre;
                        select.appendChild(option);
                    });
                } else {
                    throw new Error(data.message || 'Formato de respuesta inválido');
                }
            })
            .catch(error => {
                console.error('Error al cargar tipos de documentos:', error);
                select.innerHTML = '<option value="">Error al cargar tipos de documentos</option>';
                
                // Mostrar mensaje de error visual
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = 'Error al cargar los tipos de documentos. Por favor, intente nuevamente.';
                select.parentNode.appendChild(errorDiv);
            })
            .finally(() => {
                select.disabled = false;
            });
    }

    // Evento para crear carpeta
    const createFolderButton = document.getElementById('createFolderButton');
    if (createFolderButton) {
        createFolderButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const folderName = prompt('Ingrese el nombre de la nueva carpeta:');
            if (folderName && folderName.trim() !== '') {
                const urlParams = new URLSearchParams(window.location.search);
                const currentFolderId = urlParams.get('folder_id');
                
                const requestData = {
                    name: folderName.trim(),
                    parent_id: currentFolderId || null,
                    usuario_id: 1
                };
                
                fetch('create_folder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error al crear la carpeta: ' + (data.message || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al crear la carpeta: ' + error.message);
                });
            }
        });
    }

    // Manejar el clic en las carpetas
    const folders = document.querySelectorAll('.folder-card');
    folders.forEach(folder => {
        folder.addEventListener('click', function(e) {
            if (!e.target.closest('.edit-button') && !e.target.closest('.delete-folder-button')) {
                const folderId = this.dataset.folderId;
                window.location.href = `index.php?folder_id=${folderId}`;
            }
        });
    });

    // Manejar edición de carpetas
    document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const folderCard = this.closest('.folder-card');
            const folderNameElement = folderCard.querySelector('.editable-folder-name');
            const folderId = folderNameElement.dataset.folderId;
            const currentName = folderNameElement.textContent.trim();
            
            const newName = prompt('Ingrese el nuevo nombre de la carpeta:', currentName);
            
            if (newName && newName.trim() !== '' && newName !== currentName) {
                fetch('edit_folder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        folder_id: folderId,
                        new_name: newName.trim()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        folderNameElement.textContent = newName.trim();
                        const avatar = folderCard.querySelector('.folder-avatar');
                        if (avatar) {
                            avatar.textContent = newName.trim().charAt(0).toUpperCase();
                        }
                    } else {
                        alert('Error al editar la carpeta: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al editar la carpeta');
                });
            }
        });
    });

    // Manejar eliminación de carpetas
    document.querySelectorAll('.delete-folder-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const folderId = this.dataset.folderId;
            
            if (confirm('¿Estás seguro de que deseas eliminar esta carpeta? Esta acción no se puede deshacer.')) {
                fetch('delete_folder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        folder_id: folderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error al eliminar la carpeta: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la carpeta');
                });
            }
        });
    });

    // Manejar el clic en los documentos y sus botones
    const documents = document.querySelectorAll('.document-card');
    documents.forEach(document => {
        // Para el botón de vista previa
        const previewButtons = document.querySelectorAll('.preview-button');
        previewButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const documentId = this.dataset.documentId;
                
                // Abrir documento en una nueva ventana o pestaña
                window.open(`view_document.php?id=${documentId}`, '_blank');
            });
        });
        
        // Para otros botones de acción
        document.querySelectorAll('.action-button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Manejar descarga de documentos
        document.querySelectorAll('.download-button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const documentId = this.closest('.document-card').dataset.documentId;
                window.location.href = `download_document.php?id=${documentId}`;
            });
        });

        // Manejar compartir documentos
        document.querySelectorAll('.share-button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const documentId = this.closest('.document-card').dataset.documentId;
                // Implementar lógica para compartir (podría ser un modal, por ejemplo)
                alert('Funcionalidad de compartir documento ID: ' + documentId + ' - En desarrollo');
            });
        });

        // Manejar eliminación de documentos
        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const documentId = this.closest('.document-card').dataset.documentId;
                
                if (confirm('¿Estás seguro de que deseas eliminar este documento? Esta acción no se puede deshacer.')) {
                    fetch('delete_document.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            document_id: documentId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.document-card').remove();
                        } else {
                            alert('Error al eliminar el documento: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al eliminar el documento');
                    });
                }
            });
        });

        // Clic en el documento (para previsualización general)
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.action-button') && !e.target.closest('.edit-button')) {
                const documentId = this.dataset.documentId;
                // Podemos usar la misma funcionalidad que el botón de vista previa
                window.open(`view_document.php?id=${documentId}`, '_blank');
            }
        });
    });

    // Búsqueda en tiempo real
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            document.querySelectorAll('.folder-card').forEach(folder => {
                const folderName = folder.querySelector('.editable-folder-name').textContent.toLowerCase();
                folder.style.display = folderName.includes(searchTerm) ? '' : 'none';
            });

            document.querySelectorAll('.document-card').forEach(doc => {
                const docName = doc.querySelector('.editable-document-name').textContent.toLowerCase();
                const docType = doc.querySelector('.document-type').textContent.toLowerCase();
                doc.style.display = (docName.includes(searchTerm) || docType.includes(searchTerm)) ? '' : 'none';
            });
        });
    }

    // Implementar drag & drop para carpetas
    const folderCards = document.querySelectorAll('.folder-card');
    folderCards.forEach(folder => {
        folder.setAttribute('draggable', true);
        
        folder.addEventListener('dragstart', function(e) {
            e.stopPropagation();
            this.classList.add('dragging');
            e.dataTransfer.setData('text/plain', this.dataset.folderId);
            e.dataTransfer.effectAllowed = 'move';
        });

        folder.addEventListener('dragend', function(e) {
            e.stopPropagation();
            this.classList.remove('dragging');
            document.querySelectorAll('.folder-card').forEach(f => {
                f.classList.remove('drop-target');
            });
            const dropIndicator = document.querySelector('.drop-indicator');
            if (dropIndicator) {
                dropIndicator.remove();
            }
        });

        folder.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const draggingFolder = document.querySelector('.dragging');
            if (draggingFolder && draggingFolder !== this) {
                this.classList.add('drop-target');
            }
        });

        folder.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drop-target');
        });

        folder.addEventListener('drop', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const draggedFolderId = e.dataTransfer.getData('text/plain');
            const targetFolderId = this.dataset.folderId;
            
            if (draggedFolderId === targetFolderId) {
                return;
            }

            try {
                const response = await fetch('move_folder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        folder_id: draggedFolderId,
                        new_parent_id: targetFolderId
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error al mover la carpeta: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al mover la carpeta');
            }
            
            this.classList.remove('drop-target');
        });
    });

    // Permitir soltar carpetas en el área principal
    const mainContent = document.querySelector('.folders-grid');
    if (mainContent) {
        mainContent.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!e.target.closest('.folder-card')) {
                if (!document.querySelector('.drop-indicator')) {
                    const dropIndicator = document.createElement('div');
                    dropIndicator.className = 'drop-indicator';
                    dropIndicator.textContent = 'Soltar aquí para mover a la raíz';
                    this.appendChild(dropIndicator);
                }
            }
        });

        mainContent.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropIndicator = document.querySelector('.drop-indicator');
            if (dropIndicator && !this.contains(e.relatedTarget)) {
                dropIndicator.remove();
            }
        });

        mainContent.addEventListener('drop', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!e.target.closest('.folder-card')) {
                const draggedFolderId = e.dataTransfer.getData('text/plain');
                
                try {
                    const response = await fetch('move_folder.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            folder_id: draggedFolderId,
                            new_parent_id: null
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error al mover la carpeta: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al mover la carpeta');
                }
            }
            
            const dropIndicator = document.querySelector('.drop-indicator');
            if (dropIndicator) {
                dropIndicator.remove();
            }
        });
    }

    // Manejar el modal de subida de documentos
    const uploadModal = document.getElementById('uploadModal');
    const uploadButton = document.getElementById('uploadDocumentButton');
    const closeButton = uploadModal?.querySelector('.close');
    const uploadForm = document.getElementById('uploadForm');

    // Inicializar el modal y cargar tipos de documentos
    if (uploadButton) {
        uploadButton.addEventListener('click', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const carpetaId = urlParams.get('folder_id');
            document.getElementById('carpeta_id').value = carpetaId;
            uploadModal.style.display = 'block';
            
            // Cargar tipos de documentos solo cuando se abre el modal
            cargarTiposDocumentos();
        });
    }

    // Cerrar modal
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            uploadModal.style.display = 'none';
        });
    }

    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', function(event) {
        if (event.target === uploadModal) {
            uploadModal.style.display = 'none';
        }
    });

    // Manejar envío del formulario
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('upload_document.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Documento subido exitosamente');
                    window.location.reload();
                } else {
                    alert('Error al subir el documento: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al subir el documento');
            });
        });
    }
});
    </script>
</body>
</html>