<?php
// Turn on error reporting for easier debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start a session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Your Database Credentials ---
$servername = "localhost";
$username = "root";
$password = "ayesha123"; // Your correct password
$dbname = "college_tracker";

// Create and check the connection
$conn = new mysqli($servername, $username, $password, $dbname);

// If the connection fails, stop the script and show a clear error message.
if ($conn->connect_error) {
    die("<h1>Connection Failed</h1><p>Could not connect to the database. Please ensure MySQL is running in XAMPP and that your database is named 'college_tracker'.</p><p><strong>MySQL Error:</strong> " . $conn->connect_error . "</p>");
}

// --- Logic to handle theme settings ---
// This part is needed for your styles to work correctly.
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light'; // Default theme
}

// This allows other pages to know which theme is active.
$current_theme = $_SESSION['theme'];
?>
