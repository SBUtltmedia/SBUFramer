<?php

// print_r($_SERVER['REQUEST_URI']);
// exit;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$game_path = null;
$error_message = '';

// LTI Launch Logic: Store LTI data in the session.
if (array_key_exists("lis_person_name_given", $_POST)) {
    $_SESSION['lti_data'] = $_POST;
}

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

// Retrieve LTI data from the session for the grading script.
$lti_data = isset($_SESSION['lti_data']) ? $_SESSION['lti_data'] : null;
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
            
            // Check for either the final 'game_complete' OR the progressive 'score_update'
            const isScoreMessage = event.data && 
                                event.data.source === 'gemini-canvas-game' && 
                                (event.data.action === 'game_complete' || event.data.action === 'score_update') &&
                                event.data.data && 
                                typeof event.data.data.score === 'number';

            if (isScoreMessage) {
                
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
                // ... rest of the grading and alerting logic
                // ...
            }
        }, false);
    </script>

</body>
</html>
