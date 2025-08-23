<?php
$host = "localhost";
$user = "root";
$pass = "ayesha123";
$db   = "college_tracker";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch existing task
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = mysqli_query($conn, "SELECT * FROM tasks WHERE id=$id");
    $task = mysqli_fetch_assoc($result);
} else {
    echo "❗Task ID not provided.";
    exit;
}

// Handle update form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name = $_POST['task_name'];
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];

    $updateQuery = "UPDATE tasks SET task_name='$task_name', deadline='$deadline', priority='$priority' WHERE id=$id";

    if (mysqli_query($conn, $updateQuery)) {
        header("Location: view-Task.php");
        exit();
    } else {
        echo "❌ Update failed: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Task</title>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(to right, #fdfbfb, #ebedee);
      padding: 50px;
      text-align: center;
    }
    form {
      background: #fff0f6;
      padding: 30px;
      border-radius: 12px;
      width: 300px;
      margin: auto;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 15px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    button {
      margin-top: 20px;
      padding: 10px 25px;
      background: #ff69b4;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    }
    button:hover {
      background: #ff85c1;
    }
  </style>
</head>
<body>

  <h2>📝 Edit Task</h2>

  <form method="post">
    <input type="text" name="task_name" value="<?= htmlspecialchars($task['task_name']) ?>" required><br>
    <input type="date" name="deadline" value="<?= $task['deadline'] ?>" required><br>
    <select name="priority" required>
      <option value="High" <?= $task['priority'] === 'High' ? 'selected' : '' ?>>High</option>
      <option value="Medium" <?= $task['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
      <option value="Low" <?= $task['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
    </select><br>
    <button type="submit">Update Task</button>
  </form>

</body>
</html>
