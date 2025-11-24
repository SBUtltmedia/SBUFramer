<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// --- User and Path Determination ---

// Get user identity, required for file path
$player_name = 'default_user'; // Default
if (isset($_SERVER['cn'])) {
    $player_name = $_SERVER['cn'];
} elseif (isset($_SESSION['lti_data']['user_id'])) {
    $player_name = $_SESSION['lti_data']['user_id'];
}
// Sanitize to use as a filename
$player_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $player_name);
if (empty($player_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Could not determine a valid user identity.']);
    exit();
}

// The gameState.php script is symlinked into the game's directory.
// __DIR__ will resolve to the actual directory of the game, not the root.
$game_dir = __DIR__;
$saves_dir = $game_dir . '/saves/';
$state_file = $saves_dir . $player_name . '.json';

// --- Request Handling ---

// Handle POST request (Save State)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = file_get_contents('php://input');
    
    // Basic validation that we received some data
    if ($json_data === false || empty($json_data)) {
        http_response_code(400);
        echo json_encode(['error' => 'No data received.']);
        exit();
    }
    
    // Create the 'saves' directory if it doesn't exist
    if (!is_dir($saves_dir)) {
        if (!mkdir($saves_dir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create saves directory.']);
            exit();
        }
    }

    // Write the state to the file
    if (file_put_contents($state_file, $json_data) !== false) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'State saved.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to write to state file.']);
    }
    exit();
}

// Handle GET request (Load State)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    if (file_exists($state_file)) {
        // If the file exists, return its content
        readfile($state_file);
    } else {
        // If no save state exists, return an empty JSON object
        // The game client will handle initialization
        echo '{}';
    }
    exit();
}

// If not GET or POST, respond with an error
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
exit();
