<?php
require_once("db.php");

$message = "";

if (isset($_POST['register'])) {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare(
        "INSERT INTO users (full_name, username, password, role) VALUES (?,?,?,?)"
    );
    $stmt->bind_param("ssss", $fullname, $username, $password, $role);

    if ($stmt->execute()) {
        $message = "Account created successfully!";
    } else {
        $message = "Username already exists.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>BCTRMS Register</title>
    <style>
        body {
            font-family: Arial;
            background: #34495e;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .box {
            background: #fff;
            padding: 30px;
            width: 350px;
            border-radius: 8px;
            text-align: center;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        button {
            background: #27ae60;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .msg { color: green; }
        a { text-decoration: none; color: #3498db; }
    </style>
</head>
<body>

<div class="box">
    <h2>Create Account</h2>

    <form method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>

        <!-- <select name="role" required>
            <option value="">Select Role</option>
            <option value="Admin">Admin</option>
            <option value="Traffic Officer">Traffic Officer</option>
        </select> -->

        <button name="register">Register</button>
    </form>

    <?php if ($message) echo "<p class='msg'>$message</p>"; ?>

    <p>Already have an account? <a href="login.php">Login</a></p>
</div>

</body>
</html>
