<?php

// print_r($_SERVER['REQUEST_URI']);
// exit;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php';
requireAuthentication();

$game_path = null;
$error_message = '';

// Check for the game path in the query string.
if (isset($_GET['game'])) {
    // IMPORTANT: Security check to prevent directory traversal attacks.
    // We ensure the path is relative, starts with 'games/', and doesn't go "up" the directory tree.
    $unsafe_path = $_GET['game'];
    if (strpos($unsafe_path, '..') === false && strpos($unsafe_path, 'games/') === 0) {
        $game_path = $unsafe_path;
    } else {
        $error_message = "Invalid game path specified.";
    }
} else {
    $error_message = "No game specified.";
}

// Retrieve LTI data from the user context for the grading script.
$userContext = getUserContext();
$lti_data = $userContext['lti_data'];
$JSON_LTI_DATA = $lti_data ? json_encode($lti_data) : 'null';
// print_r($game_path);
// exit;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Game Connector</title>
    <script src="js/grading.js"></script>
    <script>
        var ses = <?php echo $JSON_LTI_DATA; ?>;
        var gamePath = "<?php echo $game_path ? htmlspecialchars($game_path) : ''; ?>";
    </script>
</head>
<body>

    <?php if ($game_path && !$error_message): ?>
        <iframe id="game-frame" src="<?php echo htmlspecialchars($game_path); ?>" style="width: 100%; height: 95vh; border: none;"></iframe>
    <?php else: ?>
        <div style="padding: 20px; font-family: sans-serif;">
            <h2>Upload a Game</h2>
            <p>Please upload your HTML game file.</p>
            <form action="upload_handler.php" method="post" enctype="multipart/form-data">
                <input type="file" name="game_file" id="game_file" accept=".html">
                <br><br>
                <input type="submit" value="Upload Game" name="submit">
            </form>
            <?php if ($error_message): ?>
                <h2 style="color: red; margin-top: 20px;">Error</h2>
                <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script>
        window.addEventListener('message', function(event) {
            
            if (!event.data || event.data.source !== 'gemini-canvas-game') {
                return;
            }

            // Handle Game Completion / Score Update
            if (event.data.action === 'game_complete' || event.data.action === 'score_update') {
                if (event.data.data && typeof event.data.data.score === 'number') {
                    if (!ses) {
                        console.error("LTI session data not found. Cannot send score.");
                        return;
                    }

                    var score = event.data.data.score;
                    console.log("Score received from game: " + score);

                    // Add the received score to our session data object
                    ses.grade = score;

                    // Use the postLTI function (from grading.js) to send the grade
                    postLTI(ses, "game-score")
                }
            } 
            
            // Handle Save State Request
            else if (event.data.action === 'save_state') {
                if (!gamePath) return; // Only process if a game is loaded
                const gameStateUrl = gamePath.substring(0, gamePath.lastIndexOf('/')) + '/saves/gameState.php?action=save';
                
                fetch(gameStateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(event.data.data)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Game state saved via parent:', data);
                    // Optional: Notify game back?
                })
                .catch(error => console.error('Error saving game state via parent:', error));
            }
            
            // Handle Load State Request
            else if (event.data.action === 'load_state') {
                if (!gamePath) return; // Only process if a game is loaded
                const gameStateUrl = gamePath.substring(0, gamePath.lastIndexOf('/')) + '/saves/gameState.php?action=load';
                
                fetch(gameStateUrl)
                .then(response => response.json())
                .then(result => {
                    // Send data back to iframe IF it's successful
                    const gameFrame = document.getElementById('game-frame');
                    if (gameFrame && gameFrame.contentWindow) {
                        gameFrame.contentWindow.postMessage({
                            source: 'gemini-canvas-parent',
                            action: 'load_state_response',
                            data: result.data || null // Send null if no data or error
                        }, '*');
                    }
                })
                .catch(error => console.error('Error loading game state via parent:', error));
            }

        }, false);
    </script>

</body>
</html>
