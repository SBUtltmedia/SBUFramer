<?php
// gameState.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

/**
 * Get the current user's identifier (email or unique ID).
 * This will be used to name save files.
 * @return string|null User identifier or null if not found.
 */
function getUserIdentifier() {
    // Preferred method: Shibboleth mail (email)
    if (isset($_SERVER['mail'])) {
        return $_SERVER['mail'];
    }
    // Fallback: Shibboleth common name (cn)
    if (isset($_SERVER['cn'])) {
        return $_SERVER['cn'];
    }
    // Fallback for LTI simulation or LTI launch
    if (isset($_SESSION['lti_data']['lis_person_contact_email_primary'])) {
        return $_SESSION['lti_data']['lis_person_contact_email_primary'];
    }
    if (isset($_SESSION['lti_data']['user_id'])) {
        return $_SESSION['lti_data']['user_id'];
    }
    // Default or anonymous user if no identifier found
    return 'anonymous_user';
}

/**
 * Get the path to the current game's saves directory.
 * This function assumes it's being called from within a symlinked context like:
 * /games/<user_id>/<game_name>/saves/gameState.php
 * @return string The absolute path to the saves directory.
 */
function getSaveDirectory() {
    // getcwd() returns the directory where the script is being executed from (the symlink location)
    // e.g., /path/to/project/games/<user_id>/<game_name>/saves/
    return getcwd() . '/';
}

/**
 * Get the full path for a user's save file.
 * @return string The full path to the user's JSON save file.
 */
function getSaveFilePath() {
    $user_identifier = getUserIdentifier();
    $save_dir = getSaveDirectory();
    // Sanitize user identifier for filename, allowing '@' for emails
    $filename = preg_replace('/[^a-zA-Z0-9_.-@]/', '_', $user_identifier) . '.json';
    return $save_dir . $filename;
}

/**
 * Save game state data for the current user.
 * @param array $data The game state data to save.
 * @return bool True on success, false on failure.
 */
function saveGameState($data) {
    $filepath = getSaveFilePath();
    $json_data = json_encode($data, JSON_PRETTY_PRINT);
    if ($json_data === false) {
        error_log("Failed to encode game state data to JSON.");
        return false;
    }
    if (file_put_contents($filepath, $json_data) === false) {
        error_log("Failed to write game state to file: " . $filepath);
        return false;
    }
    return true;
}

/**
 * Load game state data for the current user.
 * @return array|null The loaded game state data, or null if no save file exists or on error.
 */
function loadGameState() {
    $filepath = getSaveFilePath();
    if (!file_exists($filepath)) {
        return null; // No save file found
    }
    $json_data = file_get_contents($filepath);
    if ($json_data === false) {
        error_log("Failed to read game state from file: " . $filepath);
        return null;
    }
    $data = json_decode($json_data, true);
    if ($data === null) {
        error_log("Failed to decode game state JSON from file: " . $filepath);
        return null;
    }
    return $data;
}

// Handle API requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    switch ($action) {
        case 'save':
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if ($data !== null) {
                if (saveGameState($data)) {
                    echo json_encode(['status' => 'success', 'message' => 'Game state saved.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Failed to save game state.']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data for saving.']);
            }
            break;
        case 'load':
            $data = loadGameState();
            if ($data !== null) {
                echo json_encode(['status' => 'success', 'data' => $data]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'No game state found.']);
            }
            break;
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
            break;
    }
    exit;
}

?>