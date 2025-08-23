<?php
$host = "localhost";
$user = "root";
$pass = "ayesha123";
$db   = "college_tracker";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "DELETE FROM tasks WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        header("Location: view-Task.php"); // redirect back to task list
        exit();
    } else {
        echo "❌ Error deleting task: " . mysqli_error($conn);
    }
} else {
    echo "❗Invalid task ID.";
}
?>
