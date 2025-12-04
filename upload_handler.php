<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['game_file'])) {

    // 1. Get user identity via auth module
    $user_id = getSafeUserId();

    if (empty($user_id) || $user_id === 'default_user') {
        // Optional: Enforce stricter check? 
        // For now, we allow default_user if that's the intention, but usually uploads require auth.
        if ($user_id === 'default_user' && !isset($_SESSION['lti_data'])) {
             // Decide policy: Allow anonymous uploads? 
             // Previous code allowed 'default_user'. I'll keep it but usually we want a real user.
        }
    }
    
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

    $target_dir = "games/" . $user_id . "/" . $game_prefix . "/";

    // 3. Create directory
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            die("Failed to create directory: " . $target_dir);
        }
    }

    // 4. Move file
    $new_filepath = $target_dir . "index.html";
    if (move_uploaded_file($uploaded_file['tmp_name'], $new_filepath)) {
        
        $saves_dir = $target_dir . 'saves/';
        if (!is_dir($saves_dir)) {
            if (!mkdir($saves_dir, 0755, true)) {
                die("Failed to create directory: " . $saves_dir);
            }
        }

        // 5. Create a symlink to the master gameState.php
        // The relative path from /upload/<user>/<game>/saves to the root is ../../../../
        $symlink_target = '../../../../gameState.php';
        $symlink_name = $saves_dir . 'gameState.php';
        if (!file_exists($symlink_name)) {
            if (!symlink($symlink_target, $symlink_name)) {
                // Optional: add error handling if symlink fails
                // For now, we'll proceed even if it fails.
            }
        }

        // 6. Redirect to the new game
        $game_url_path = htmlspecialchars("games/" . $user_id . "/" . $game_prefix . "/" . "index.html");
        header("Location: index.php?game=" . $game_url_path);
        exit();

    } else {
        die("Failed to move uploaded file.");
    }

} else {
    header("Location: index.php");
    exit();
}
