<?php

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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Game Connector</title>
    <script src="/js/grading.js"></script>
    <script>
        var ses = <?php echo $JSON_LTI_DATA; ?>;
    </script>
</head>
<body>

    <?php if ($game_path && !$error_message): ?>
        <iframe id="game-frame" src="<?php echo htmlspecialchars($game_path); ?>" style="width: 100%; height: 95vh; border: none;"></iframe>
    <?php else: ?>
        <h2>Error</h2>
        <p><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <script>
        window.addEventListener('message', function(event) {
            // Listen for the message format sent by the gemini-canvas-game
            if (event.data && event.data.source === 'gemini-canvas-game' && event.data.action === 'game_complete' && event.data.data && typeof event.data.data.score === 'number') {
                
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
                    .then(result => {
                        console.log("Full grading result: ", result);
                        // Check if the response from our server contains "success"
                        if (result.includes('success')) {
                            alert("Your score of " + (ses.grade * 100) + "% has been successfully submitted.");
                        } else {
                            // If not, the submission to the LMS failed. Show the error.
                            console.error("LMS submission failed. Response:", result);
                            alert("There was an error submitting your score. The server responded: " + result);
                        }
                    })
                    .catch(error => {
                        console.error("Failed to send score to our server:", error);
                        alert("There was a critical error submitting your score.");
                    });
            }
        }, false);
    </script>

</body>
</html>