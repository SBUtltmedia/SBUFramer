<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Function to redirect with an error message
function redirect_error($message) {
    header("Location: upload.php?status=error&msg=" . urlencode($message));
    exit;
}

// 1. AUTHENTICATION: Protect this script with Shibboleth
if (!isset($_SERVER['cn'])) {
    die("Authentication Error: Cannot process upload without user identification.");
}
$user_id = $_SERVER['cn'];

// 2. FORM SUBMISSION CHECK: Ensure the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['game_file'])) {
    redirect_error("Invalid request.");
}

// 3. FILE UPLOAD VALIDATION
if ($_FILES['game_file']['error'] !== UPLOAD_ERR_OK) {
    redirect_error("File upload error code: " . $_FILES['game_file']['error']);
}

$tmp_name = $_FILES['game_file']['tmp_name'];
$file_size = $_FILES['game_file']['size'];

if ($file_size === 0) {
    redirect_error("Uploaded file is empty.");
}

// 4. DIRECTORY CREATION: Create user's directory if it doesn't exist
// Assumes the web root is the parent directory of this script's location.
$user_dir_path = __DIR__ . '/' . $user_id;
if (!is_dir($user_dir_path)) {
    if (!mkdir($user_dir_path, 0755, true)) {
        redirect_error("Failed to create user directory.");
    }
}

// 5. MOVE FILE: Move the uploaded file to the user's directory as index.html
$destination_path = $user_dir_path . '/index.html';
if (!move_uploaded_file($tmp_name, $destination_path)) {
    redirect_error("Failed to save the uploaded file.");
}

// 6. SUCCESS: Redirect back to the upload page with a success message
header("Location: upload.php?status=success");
exit;

?>
