<?php
// Start session and include database connection
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Initialize variables
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_name = $_POST['task_name'] ?? '';
    $priority = $_POST['priority'] ?? 'Medium';
    $deadline = $_POST['deadline'] ?? '';
    $description = $_POST['description'] ?? '';

    // Validate input
    if (empty($task_name) || empty($deadline)) {
        $error = "Task name and deadline are required!";
    } else {
        // Insert task into database
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, task_name, priority, deadline, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $task_name, $priority, $deadline, $description);
        
        if ($stmt->execute()) {
            $success = "Task added successfully!";
            // Clear form fields
            $task_name = $priority = $deadline = $description = '';
        } else {
            $error = "Error adding task: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Task - Study Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .notification {
            padding: 12px;
            margin: 15px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
    </style>
</head>
<body class="app-body">
    <div class="app-container">
        <header class="app-create-header">
            <a href="dashboard.php" class="app-back-button"><i class="fas fa-arrow-left"></i></a>
            <h1 class="app-create-title">Create New Task</h1>
            <i class="fas fa-clipboard-list"></i>
        </header>

        <main class="app-create-form">
            <!-- Display notifications -->
            <?php if (!empty($error)): ?>
                <div class="notification error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="notification success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group-create">
                    <label for="task-name">Task Name</label>
                    <input type="text" id="task-name" name="task_name" placeholder="Enter task name" 
                           value="<?php echo htmlspecialchars($task_name ?? ''); ?>" required>
                </div>

                <div class="form-group-create">
                    <label>Select Priority</label>
                    <input type="hidden" id="priority-input" name="priority" value="<?php echo htmlspecialchars($priority ?? 'Medium'); ?>" required>
                    
                    <div class="category-pills">
                        <button type="button" class="category-pill <?php echo ($priority ?? 'Medium') == 'Low' ? 'active' : ''; ?>" data-value="Low">Low</button>
                        <button type="button" class="category-pill <?php echo ($priority ?? 'Medium') == 'Medium' ? 'active' : ''; ?>" data-value="Medium">Medium</button>
                        <button type="button" class="category-pill <?php echo ($priority ?? 'Medium') == 'High' ? 'active' : ''; ?>" data-value="High">High</button>
                    </div>
                </div>

                <div class="form-group-create">
                    <label for="deadline">Date</label>
                    <div class="input-with-icon">
                        <input type="date" id="deadline" name="deadline" 
                               value="<?php echo htmlspecialchars($deadline ?? ''); ?>" required>
                        <i class="far fa-calendar"></i>
                    </div>
                </div>
                
                <div class="form-group-create">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" placeholder="Add task details..."><?php 
                        echo htmlspecialchars($description ?? ''); 
                    ?></textarea>
                </div>
                
                <button type="submit" class="app-create-button">Create Task</button>
            </form>
        </main>
    </div>

    <script>
        // Simple script to handle the active state and value of the priority buttons
        document.querySelectorAll('.category-pill').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                document.querySelectorAll('.category-pill').forEach(btn => btn.classList.remove('active'));
                
                // Add active class to the clicked button
                button.classList.add('active');
                
                // Update the hidden input's value
                document.getElementById('priority-input').value = button.dataset.value;
            });
        });
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('deadline').setAttribute('min', today);
    </script>
</body>
</html>