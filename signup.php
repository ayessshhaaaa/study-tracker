<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Using the "Full Name" input as username
    $username = trim($_POST['fullname'] ?? ''); 
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $acceptedTerms = isset($_POST['terms']);

    // Validation
    if ($username === '' || $email === '' || $password === '' || $confirm === '') {
        die("All fields are required.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }
    if ($password !== $confirm) {
        die("Passwords do not match.");
    }
    if (!$acceptedTerms) {
        die("You must accept the terms.");
    }

    // Prevent duplicate email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        die("Email already registered.");
    }
    $check->close();

    // Hash password
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert into users (correct column is 'username')
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hash);

    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        header("Location: dashboard.php");
        exit();
    } else {
        die("Signup failed: " . $conn->error);
    }
} else {
    header("Location: signup.html");
    exit();
}
?>
