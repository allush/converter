<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_POST['convert'])) {
        throw new Exception('Access denied.');
    }

    if (!isset($_FILES['csv']) or !$_FILES['csv']['size']) {
        throw new Exception('File is empty or absent.');
    }

    if ($_FILES['csv']['type'] != 'text/csv') {
        throw new Exception('Invalid file format: only supported format csv.');
    }

    $filename = 'tmp/' . time() . '_' . $_FILES['csv']['name'];
    if (!move_uploaded_file($_FILES['csv']['tmp_name'], $filename)) {
        throw new Exception('Unable to save file.');
    }

    $comment = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : null;

    shell_exec('php convert-csv-to-pdf.php ' . $filename . ' ' . $comment . ' > /dev/null 2>/dev/null &');

    header('Location: index.php?filename=' . $filename);
} catch (Exception $e) {
    session_start();

    $_SESSION['error'] = '<strong>Error!</strong> ' . $e->getMessage();

    header('Location: ' . $_SERVER['HTTP_REFERER']);
}