<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = '';
$game_url = '';

// Step 1: Check for Shibboleth authentication
if (!isset($_SERVER['cn'])) {
    // If not authenticated, display an error and stop.
    // In a real environment, .htaccess would likely handle the redirect to the Shibboleth login page.
    header("HTTP/1.1 403 Forbidden");
    echo "Access Denied. You must be authenticated via Shibboleth to upload a game.";
    exit;
}

$user_cn = $_SERVER['cn'];

// Step 2: Handle the file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['game_file'])) {
    $upload_dir = __DIR__ . '/../games/' . $user_cn . '/'; // Go up one level and then into 'games'
    $upload_file = $upload_dir . 'index.html'; // We always name it index.html for consistency

    // Check if the uploaded file is an HTML file
    $file_type = mime_content_type($_FILES['game_file']['tmp_name']);
    if ($file_type !== 'text/html') {
        $message = "Error: Only .html files are allowed.";
    } else {
        // Create the user-specific directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Move the uploaded file to the destination
        if (move_uploaded_file($_FILES['game_file']['tmp_name'], $upload_file)) {
            // The URL path needed by the main index.php, relative to the 'src' directory
            $game_url_for_player = 'games/' . $user_cn . '/';
            $message = "File uploaded successfully! Your game's path for the LTI tool is: <b>" . htmlspecialchars($game_url_for_player) . "</b>";
        } else {
            $message = "Sorry, there was an error uploading your file.";
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Game</title>
    <style>
        body { font-family: sans-serif; padding: 2em; }
        .container { max-width: 600px; margin: 0 auto; border: 1px solid #ccc; padding: 2em; }
        .message { margin-bottom: 1em; padding: 1em; border-radius: 4px; }
        .success { background-color: #e7f4e7; border: 1px solid #6c9c6c; }
        .error { background-color: #f4e7e7; border: 1px solid #9c6c6c; }
    </style>
</head>
<body>

<div class="container">
    <h2>Faculty Game Upload</h2>
    <p>Welcome, <?php echo htmlspecialchars($user_cn); ?>.</p>
    <p>Upload your single-file HTML game here. The file will be saved as `index.html` in your personal game directory.</p>

    <?php if ($message): ?>
        <div class="message <?php echo $game_url ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <p>
            <label for="game_file">Select game file (.html only):</label><br>
            <input type="file" name="game_file" id="game_file" accept=".html">
        </p>
        <p>
            <input type="submit" value="Upload Game" name="submit">
        </p>
    </form>

</div>

</body>
</html>
