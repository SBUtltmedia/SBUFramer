<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['game_file'])) {

    // 1. Get user identity
    $user_id = 'default_user'; // Default value
    if (isset($_SERVER['cn'])) {
        // Preferred method: Shibboleth common name
        $user_id = $_SERVER['cn'];
    } elseif (isset($_SESSION['lti_data']['user_id'])) {
        // Fallback for LTI simulation
        $user_id = $_SESSION['lti_data']['user_id'];
    }

    // Sanitize user_id to prevent directory traversal issues
    $user_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $user_id);
    if (empty($user_id)) {
        die("Error: Could not determine a valid user identity.");
    }

    // 2. Process file and paths
    $uploaded_file = $_FILES['game_file'];
    
    if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
        die("File upload error: " . $uploaded_file['error']);
    }

    $original_filename = basename($uploaded_file['name']);
    $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    
    if (strtolower($file_extension) !== 'html') {
        die("Error: Only .html files are allowed.");
    }

    $game_prefix = pathinfo($original_filename, PATHINFO_FILENAME);
    $game_prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $game_prefix); // Sanitize prefix

    $target_dir = "upload/" . $user_id . "/" . $game_prefix . "/";

    // 3. Create directory
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            die("Failed to create directory: " . $target_dir);
        }
    }

    // 4. Move file
    $new_filepath = $target_dir . "index.html";
    if (move_uploaded_file($uploaded_file['tmp_name'], $new_filepath)) {
        
        // 5. Create a symlink to the master gameState.php
        // The relative path from /upload/<user>/<game>/ to the root is ../../../
        $symlink_target = '../../../gameState.php';
        $symlink_name = $target_dir . 'gameState.php';
        if (!file_exists($symlink_name)) {
            if (!symlink($symlink_target, $symlink_name)) {
                // Optional: add error handling if symlink fails
                // For now, we'll proceed even if it fails.
            }
        }

        // 6. Redirect to the new game
        $game_url_path = htmlspecialchars($target_dir . "index.html");
        header("Location: /index.php?game=" . $game_url_path);
        exit();

    } else {
        die("Failed to move uploaded file.");
    }

} else {
    header("Location: /index.php");
    exit();
}
