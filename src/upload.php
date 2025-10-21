<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Protect this page with Shibboleth authentication
if (!isset($_SERVER['cn'])) {
    if (file_exists(".htaccess")) {
        $server = $_SERVER['SERVER_NAME'];
        $target = "https://{$server}{$_SERVER['REQUEST_URI']}";
        header("Location: /shib/?shibtarget=$target");
        exit;
    } else {
        die("Error: You must be authenticated to upload a game.");
    }
}

$user_id = $_SERVER['cn'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Game</title>
    <style>
        body { font-family: sans-serif; padding: 2em; line-height: 1.5; }
        .container { max-width: 600px; margin: 0 auto; border: 1px solid #ccc; padding: 2em; border-radius: 5px; }
        input[type=file] { display: block; margin-bottom: 1em; }
        input[type=submit] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        input[type=submit]:hover { background-color: #0056b3; }
        .message { padding: 1em; margin-bottom: 1em; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; }
        .message.error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Your Game File</h2>
        <p>Hello, <strong><?php echo htmlspecialchars($user_id); ?></strong>.</p>
        <p>Please select the single HTML file for your game. This will overwrite any existing game you have uploaded.</p>
        
        <?php if (isset($_GET['status'])):
            $status = $_GET['status'];
            if ($status === 'success') {
                echo '<div class="message success">Game uploaded successfully!</div>';
            } else if ($status === 'error') {
                echo '<div class="message error">Error uploading file: ' . htmlspecialchars($_GET['msg']) . '</div>';
            }
        endif; ?>

        <form action="handle_upload.php" method="post" enctype="multipart/form-data">
            <label for="game_file">Select game file (HTML only):</label>
            <input type="file" name="game_file" id="game_file" accept=".html,.htm" required>
            <input type="submit" value="Upload Game" name="submit">
        </form>
    </div>
</body>
</html>
