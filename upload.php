<?php
session_start();
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $folderId = $_POST['folder_id'];
    $currentFolder = findFolderById($_SESSION['folders'], $folderId);

    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
        $fileName = $_FILES['files']['name'][$key];
        $fileSize = $_FILES['files']['size'][$key];
        $fileType = $_FILES['files']['type'][$key];
        $fileTmpName = $_FILES['files']['tmp_name'][$key];

        $uploadDir = 'uploads/';
        $filePath = $uploadDir . basename($fileName);

        if (move_uploaded_file($fileTmpName, $filePath)) {
            $currentFolder['files'][] = [
                'name' => $fileName,
                'size' => formatFileSize($fileSize),
                'type' => getFileType($fileType),
                'timeAgo' => 'hace un momento',
                'url' => $filePath
            ];
        }
    }

    header('Location: index.php?folder_id=' . $folderId);
    exit();
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return parseFloat(($bytes / pow($k, $i)).toFixed(2)) . ' ' . $sizes[$i];
}
?>