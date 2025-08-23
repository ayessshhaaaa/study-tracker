<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "ayesha123"; // your MySQL password
$db = "college_tracker";

// Establish database connection
$conn = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$conn) {
    // More professional error message for users
    die("Database connection failed. Please try again later.");
}

$error_message = ""; // Initialize error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Sanitize input to prevent SQL injection (basic example for username)
    $username = mysqli_real_escape_string($conn, $username);

    // Query to check if user exists
    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) == 1) { // Check if query was successful and user found
        $row = mysqli_fetch_assoc($result);

        // Verify hashed password
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username']; // store the actual DB username in session
            header("Location: dashboard.php"); // Redirect to dashboard on successful login
            exit();
        } else {
            $error_message = "Incorrect username or password. Please try again."; // Generic message for security
        }
    } else {
        $error_message = "Incorrect username or password. Please try again."; // Generic message for security
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Study Tracker - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />

    <style>
        /* Specific styles for the login page to fit the professional aesthetic */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 0;
            overflow: hidden;
            background-color: var(--color-primary-bg);
            transition: background-color 0.4s ease;
        }

        .login-container {
            background-color: var(--color-card-bg);
            padding: 50px;
            border-radius: 25px;
            box-shadow: 0 10px 30px var(--shadow-heavy);
            text-align: center;
            width: 450px;
            max-width: 90%;
            position: relative;
            z-index: 20;
            transition: background-color 0.4s ease, box-shadow 0.4s ease;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid var(--color-border);
        }

        .login-container h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.5em;
            color: var(--color-heading);
            margin-bottom: 20px;
            font-weight: 700;
        }

        .login-container p {
            color: var(--color-text);
            margin-bottom: 30px;
            font-size: 1.05em;
        }

        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 18px;
            margin: 12px 0;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            font-size: 1.1em;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            background-color: var(--color-secondary-bg);
            color: var(--color-text);
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
        }

        .login-form input[type="text"]::placeholder,
        .login-form input[type="password"]::placeholder {
            color: var(--color-text-muted);
        }

        .login-form input[type="text"]:focus,
        .login-form input[type="password"]:focus {
            border-color: var(--color-accent-primary);
            box-shadow: 0 0 0 3px rgba(76, 91, 212, 0.2);
            background-color: var(--color-card-bg);
            outline: none;
        }

        .login-button {
            background: var(--color-accent-primary);
            color: white;
            border: none;
            padding: 18px 35px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.25em;
            box-shadow: 0 5px 15px rgba(76, 91, 212, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            width: 100%;
            margin-top: 30px;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 91, 212, 0.4);
            background-color: var(--color-accent-secondary);
        }

        .error-message {
            color: #E74C3C;
            background-color: rgba(231, 76, 60, 0.1);
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #E74C3C;
            font-weight: 500;
            display: <?php echo (!empty($error_message)) ? 'block' : 'none'; ?>;
            font-size: 0.95em;
        }

        .signup-link {
            margin-top: 30px;
            font-size: 1em;
            color: var(--color-text);
        }

        .signup-link a {
            color: var(--color-accent-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .signup-link a:hover {
            color: var(--color-accent-secondary);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Welcome Back.</h2>
        <p>Your productive journey awaits.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-form">
            <input type="text" name="username" placeholder="Username" required autocomplete="username">
            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            <button type="submit" class="login-button">Login</button>
        </form>

        <p class="signup-link">Don't have an account? <a href="signup.html">Sign Up.</a></p>
    </div>
</body>
</html>