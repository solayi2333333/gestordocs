document.addEventListener('DOMContentLoaded', function() {
    // Elementos DOM
    let searchInput = document.getElementById('searchInput');
    const foldersGrid = document.getElementById('foldersGrid');
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    // Obtener carpetas del PHP (las que vienen de la base de datos)
    let folders = window.initialFolders || [];
    
    // Estado inicial
    let currentView = 'folders';
    let currentFolder = null;

    // Función para renderizar las carpetas
    function renderFolders(foldersToRender) {
        const content = document.querySelector('.content');
        content.innerHTML = `
            <h1>Carpetas compartidas</h1>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Buscar..." id="searchInput">
            </div>
            <button class="create-folder-button" id="createFolderButton">
                <div class="folder-plus-icon">
                    <i class="fas fa-folder"></i>
                    <i class="fas fa-plus"></i>
                </div>
                Crear nueva carpeta
            </button>
            <div class="folders-grid" id="foldersGrid"></div>
        `;

        const grid = document.getElementById('foldersGrid');
        grid.innerHTML = '';
        
        foldersToRender.forEach(folder => {
            const folderElement = document.createElement('div');
            folderElement.className = 'folder-card';
            folderElement.innerHTML = `
                <div class="folder-info">
                    <i class="fas fa-folder folder-icon"></i>
                    <div class="folder-details">
                        <div class="editable-container">
                            <h3 class="editable-folder-name" data-folder-id="${folder.id}">${folder.nombre}</h3>
                            <button class="edit-button" title="Editar nombre">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </div>
                        <p>${folder.timeAgo || 'hace 1 día'}</p>
                    </div>
                </div>
                <div class="folder-avatar">${folder.nombre.charAt(0)}</div>
            `;
            
            folderElement.addEventListener('click', () => {
                window.location.href = `index.php?folder_id=${folder.id}`;
            });

            // Agregar evento de clic al botón de edición
            const editButton = folderElement.querySelector('.edit-button');
            const folderNameElement = folderElement.querySelector('.editable-folder-name');
            editButton.addEventListener('click', (e) => {
                e.stopPropagation();
                editFolderName(folder, folderNameElement);
            });
            
            grid.appendChild(folderElement);
        });

        // Actualizar el searchInput después de recrearlo
        searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', handleSearch);

        // Agregar evento al botón de crear carpeta
        const createFolderButton = document.getElementById('createFolderButton');
        createFolderButton.addEventListener('click', createNewFolder);
    }

    // Función para editar el nombre de una carpeta
    function editFolderName(folder, folderNameElement) {
        const newName = prompt('Editar nombre de la carpeta:', folder.nombre);
        if (newName && newName.trim() !== '') {
            // Hacer la petición AJAX para actualizar el nombre
            fetch('update_folder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    folder_id: folder.id,
                    new_name: newName.trim()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    folder.nombre = newName.trim();
                    folderNameElement.textContent = newName.trim();
                    const avatar = folderNameElement.closest('.folder-card').querySelector('.folder-avatar');
                    avatar.textContent = newName.trim().charAt(0);
                } else {
                    alert('Error al actualizar el nombre de la carpeta');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el nombre de la carpeta');
            });
        }
    }

    // Función para crear una nueva carpeta
    function createNewFolder() {
        const folderName = prompt('Ingrese el nombre de la nueva carpeta:');
        if (folderName && folderName.trim() !== '') {
            // Hacer la petición AJAX para crear la carpeta
            fetch('create_folder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: folderName.trim(),
                    parent_id: currentFolder ? currentFolder.id : null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const newFolder = {
                        id: data.folder_id,
                        nombre: folderName.trim(),
                        timeAgo: 'hace un momento',
                        files: [],
                        subfolders: []
                    };
                    folders.push(newFolder);
                    renderFolders(folders);
                } else {
                    alert('Error al crear la carpeta');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear la carpeta');
            });
        }
    }

    // Función de búsqueda
    function handleSearch(event) {
        const searchTerm = event.target.value.toLowerCase();
        
        if (currentView === 'folders') {
            const filteredFolders = folders.filter(folder => 
                folder.nombre.toLowerCase().includes(searchTerm)
            );
            renderFolders(filteredFolders);
        } else {
            const filteredFiles = currentFolder.files.filter(file => 
                file.name.toLowerCase().includes(searchTerm)
            );
            renderFiles(filteredFiles);
        }
    }

    // Función para mostrar la vista de carpeta
    function showFolderView(folder) {
        currentFolder = folder;
        currentView = 'files';
        
        const content = document.querySelector('.content');
        content.innerHTML = `
            <div class="folder-header">
                <button class="back-button" id="backButton">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
                <h1 class="folder-title">${folder.name}</h1>
            </div>
            
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Buscar en ${folder.name}..." id="searchInput">
            </div>

            <div class="actions-container">
                <input type="file" id="fileInput" multiple style="display: none">
                <button class="upload-button" id="uploadButton">
                    <i class="fas fa-plus"></i> Agregar archivo
                </button>
                <button class="create-subfolder-button" id="createSubfolderButton">
                    <div class="folder-plus-icon">
                        <i class="fas fa-folder"></i>
                        <i class="fas fa-plus"></i>
                    </div>
                    Crear subcarpeta
                </button>
            </div>

            <div class="files-container">
                <div class="files-list" id="filesList"></div>
                <div class="subfolders-grid" id="subfoldersGrid"></div>
                
                <div id="dropZone" class="drop-zone">
                    <div class="drop-zone-content">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Arrastra y suelta archivos aquí</p>
                    </div>
                </div>
            </div>
        `;

        setupFolderViewHandlers();
        renderFiles(folder.files);
        renderSubfolders(folder.subfolders);
    }

    // Función para crear una subcarpeta
    function createSubfolder() {
        const subfolderName = prompt('Ingrese el nombre de la subcarpeta:');
        if (subfolderName) {
            const newSubfolder = {
                id: currentFolder.subfolders.length + 1,
                name: subfolderName,
                timeAgo: 'hace un momento',
                avatar: 'A',
                files: [],
                subfolders: [] // Subcarpetas anidadas
            };
            currentFolder.subfolders.push(newSubfolder);
            renderSubfolders(currentFolder.subfolders);
        }
    }

    // Función para renderizar subcarpetas
    function renderSubfolders(subfolders) {
        const subfoldersGrid = document.getElementById('subfoldersGrid');
        subfoldersGrid.innerHTML = '';

        if (subfolders.length === 0) {
            subfoldersGrid.innerHTML = '<div class="no-subfolders">No hay subcarpetas en esta carpeta</div>';
            return;
        }

        subfolders.forEach(subfolder => {
            const subfolderElement = document.createElement('div');
            subfolderElement.className = 'folder-card';
            subfolderElement.innerHTML = `
                <div class="folder-info">
                    <i class="fas fa-folder folder-icon"></i>
                    <div class="folder-details">
                        <div class="editable-container">
                            <h3 class="editable-folder-name" data-folder-id="${subfolder.id}">${subfolder.name}</h3>
                            <button class="edit-button" title="Editar nombre">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </div>
                        <p>${subfolder.timeAgo}</p>
                    </div>
                </div>
                <div class="folder-avatar">${subfolder.avatar}</div>
            `;
            
            subfolderElement.addEventListener('click', () => {
                showFolderView(subfolder);
            });

            // Agregar evento de clic al botón de edición
            const editButton = subfolderElement.querySelector('.edit-button');
            const subfolderNameElement = subfolderElement.querySelector('.editable-folder-name');
            editButton.addEventListener('click', (e) => {
                e.stopPropagation(); // Evitar que el clic en el botón active el evento de la subcarpeta
                editFolderName(subfolder, subfolderNameElement);
            });
            
            subfoldersGrid.appendChild(subfolderElement);
        });
    }

    // Configurar manejadores de eventos para la vista de carpeta
    function setupFolderViewHandlers() {
        const backButton = document.getElementById('backButton');
        const uploadButton = document.getElementById('uploadButton');
        const createSubfolderButton = document.getElementById('createSubfolderButton');
        const fileInput = document.getElementById('fileInput');
        const dropZone = document.getElementById('dropZone');
        searchInput = document.getElementById('searchInput');

        backButton.addEventListener('click', () => {
            currentView = 'folders';
            renderFolders(folders);
        });

        uploadButton.addEventListener('click', () => {
            fileInput.click();
        });

        createSubfolderButton.addEventListener('click', createSubfolder);

        fileInput.addEventListener('change', handleFileSelect);

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', handleFileDrop);

        searchInput.addEventListener('input', handleSearch);
    }

    // Manejar la selección de archivos
    function handleFileSelect(e) {
        const files = Array.from(e.target.files);
        addFiles(files);
    }

    // Manejar el drop de archivos
    function handleFileDrop(e) {
        e.preventDefault();
        const dropZone = document.getElementById('dropZone');
        dropZone.classList.remove('dragover');
        
        const files = Array.from(e.dataTransfer.files);
        addFiles(files);
    }

    // Añadir archivos a la carpeta actual
    function addFiles(files) {
        files.forEach(file => {
            const fileUrl = URL.createObjectURL(file); // Generar URL temporal
            const newFile = {
                name: file.name,
                size: formatFileSize(file.size),
                type: getFileType(file.type),
                timeAgo: 'hace un momento',
                url: fileUrl // Agregar la URL del archivo
            };
            currentFolder.files.unshift(newFile);
        });
        
        renderFiles(currentFolder.files);
    }

    // Renderizar lista de archivos
    function renderFiles(files) {
        const filesList = document.getElementById('filesList');
        filesList.innerHTML = '';

        if (files.length === 0) {
            filesList.innerHTML = '<div class="no-files">No hay archivos en esta carpeta</div>';
            return;
        }

        files.forEach(file => {
            const fileElement = document.createElement('div');
            fileElement.className = 'file-item';
            fileElement.innerHTML = `
                <div class="file-icon">
                    <i class="fas ${getFileIcon(file.type)}"></i>
                </div>
                <div class="file-info">
                    <div class="editable-container">
                        <div class="file-name editable-file-name" data-file-name="${file.name}">${file.name}</div>
                        <button class="edit-button" title="Editar nombre">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </div>
                    <div class="file-details">
                        <span>${file.size || 'N/A'}</span>
                        <span>${file.timeAgo}</span>
                    </div>
                </div>
                <div class="file-actions">
                    <button class="action-button download-button" title="Descargar" data-file-url="${file.url}">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="action-button preview-button" title="Vista previa">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-button delete-button" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;

            // Agregar evento de clic al botón de edición
            const editButton = fileElement.querySelector('.edit-button');
            const fileNameElement = fileElement.querySelector('.editable-file-name');
            editButton.addEventListener('click', (e) => {
                e.stopPropagation(); // Evitar que el clic en el botón active el evento del archivo
                editFileName(file, fileNameElement);
            });

            const downloadButton = fileElement.querySelector('.download-button');
            downloadButton.addEventListener('click', (e) => {
                e.stopPropagation();
                const fileUrl = e.target.closest('.download-button').getAttribute('data-file-url');
                if (fileUrl) {
                    window.open(fileUrl, '_blank');
                }
            });

            const previewButton = fileElement.querySelector('.preview-button');
            previewButton.addEventListener('click', (e) => {
                e.stopPropagation();
                openFilePreview(file);
            });

            const deleteButton = fileElement.querySelector('.delete-button');
            deleteButton.addEventListener('click', (e) => {
                e.stopPropagation();
                const index = currentFolder.files.indexOf(file);
                currentFolder.files.splice(index, 1);
                renderFiles(currentFolder.files);
            });

            filesList.appendChild(fileElement);
        });
    }

    // Función para editar el nombre de un archivo
    function editFileName(file, fileNameElement) {
        const newName = prompt('Editar nombre del archivo:', file.name);
        if (newName && newName.trim() !== '') {
            file.name = newName.trim();
            fileNameElement.textContent = newName.trim();
        }
    }

    // Funciones auxiliares
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function getFileType(mimeType) {
        if (mimeType.startsWith('image/')) return 'image';
        if (mimeType.startsWith('video/')) return 'video';
        if (mimeType.startsWith('audio/')) return 'audio';
        if (mimeType.includes('pdf')) return 'pdf';
        if (mimeType.includes('word')) return 'word';
        if (mimeType.includes('excel')) return 'excel';
        return 'document';
    }

    function getFileIcon(type) {
        const icons = {
            image: 'fa-image',
            video: 'fa-video',
            audio: 'fa-music',
            pdf: 'fa-file-pdf',
            word: 'fa-file-word',
            excel: 'fa-file-excel',
            document: 'fa-file'
        };
        return icons[type] || 'fa-file';
    }

    // Función para abrir la vista previa de archivos
    function openFilePreview(file) {
        if (file.type === 'image') {
            const modal = document.createElement('div');
            modal.className = 'file-preview-modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <img src="${file.url}" alt="${file.name}" />
                </div>
            `;
            document.body.appendChild(modal);

            const closeModal = modal.querySelector('.close-modal');
            closeModal.addEventListener('click', () => {
                document.body.removeChild(modal);
            });
        } else if (file.type === 'pdf') {
            window.open(file.url, '_blank');
        }
    }

    if (typeof window.initialFolders !== 'undefined') {
        folders = window.initialFolders;
        renderFolders(folders);
    }
});


document.addEventListener('DOMContentLoaded', function() {
    const createFolderButton = document.getElementById('createFolderButton');
    
    if (createFolderButton) {
        createFolderButton.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 1. Obtener nombre de la carpeta
            const folderName = prompt('Ingrese el nombre de la nueva carpeta:');
            
            if (!folderName || folderName.trim() === '') {
                console.log('Nombre de carpeta vacío o cancelado');
                return;
            }
            
            // 2. Obtener el ID de la carpeta actual
            const urlParams = new URLSearchParams(window.location.search);
            const currentFolderId = urlParams.get('folder_id');
            
            // 3. Preparar datos
            const requestData = {
                name: folderName.trim(),
                parent_id: currentFolderId || null,
                usuario_id: 1 // Asegúrate de que este ID coincida con un usuario válido en tu BD
            };
            
            console.log('Enviando petición con datos:', requestData);
            
            try {
                // 4. Enviar petición
                const response = await fetch('create_folder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });
                
                // 5. Verificar respuesta HTTP
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // 6. Procesar respuesta
                const data = await response.json();
                console.log('Respuesta del servidor:', data);
                
                if (data.success) {
                    alert('Carpeta creada exitosamente!');
                    window.location.reload();
                } else {
                    alert('Error al crear la carpeta: ' + (data.message || 'Error desconocido'));
                }
                
            } catch (error) {
                console.error('Error en la petición:', error);
                alert('Error al crear la carpeta: ' + error.message);
            }
        });
    } else {
        console.error('No se encontró el botón de crear carpeta');
    }
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
                    // Eliminar la carpeta del DOM o recargar la página
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