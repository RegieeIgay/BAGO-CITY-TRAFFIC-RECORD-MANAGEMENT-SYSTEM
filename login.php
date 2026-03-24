<?php
session_start();
require_once("db.php");

$error = "";
$success = "";
$show_alert = false;

// Capture values to persist in the form if an error occurs
$login_user = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : "";
$sign_name = isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : "";
$sign_user = isset($_POST['signup_username']) ? htmlspecialchars($_POST['signup_username']) : "";

/* ================= LOGIN LOGIC ================= */
if (isset($_POST['login'])) {
    $username = trim($_POST['username']); 
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // CHECK IF ACCOUNT IS DISABLED
        if (isset($user['status']) && $user['status'] === 'Disabled') {
            $error = "This account has been disabled. Please contact the administrator.";
        } 
        elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Added role to the URL redirection
            header("Location: index.php?role=" . urlencode($user['role']));
            exit();
        } else {
            $error = "Incorrect Username or Password!";
        }
    } else {
        $error = "Incorrect Username or Password!";
    }
}

/* ================= SIGNUP LOGIC ================= */
if (isset($_POST['signup'])) {
    $full_name = trim($_POST['full_name']); 
    $username  = trim($_POST['signup_username']);
    $password  = $_POST['signup_password'];
    $confirm   = $_POST['confirm_password'];
    $role      = isset($_POST['role']) ? $_POST['role'] : ""; 

    if (empty($role)) {
        $error = "Please select a role!";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE username=?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if($check->num_rows > 0){
            $error = "Username already exists!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $default_status = "Active";
            
            // Updated to include status in the registration
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $username, $hashed, $role, $default_status);

            if ($stmt->execute()) {
                $success = "Account created successfully! You can now login.";
                $show_alert = true;
                $sign_name = $sign_user = "";
            } else {
                $error = "Something went wrong during registration.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCTRMS Login & Signup</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        *{ margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body{
            display: flex; flex-direction: column; min-height: 100vh; width: 100%; 
            justify-content: center; align-items: center;
            background: linear-gradient(to right, #003366, #004080, #0059b3, #0073e6);
            padding: 40px 20px;
        }

        .system-title {
            color: #fff;
            text-align: center;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
        }
        .system-title h1 { font-size: 32px; font-weight: 700; line-height: 1.1; }
        .system-title p { font-size: 16px; opacity: 0.9; margin-top: 8px; font-weight: 500; }

        .wrapper{
            overflow: hidden; 
            width: 100%;
            max-width: 450px;
            background: #fff; padding: 35px;
            border-radius: 15px; box-shadow: 0px 15px 25px rgba(0,0,0,0.2);
        }
        
        input[type="radio"]{ display: none; }

        .title-text{ display: flex; width: 200%; transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        .title-text .title{ width: 50%; font-size: 35px; font-weight: 600; text-align: center; }

        .slide-controls{
            position: relative; display: flex; height: 50px; width: 100%;
            margin: 30px 0 10px 0; border: 1px solid lightgrey; border-radius: 15px; overflow: hidden;
        }
        .slide-controls .slide{
            height: 100%; width: 100%; color: #000; font-size: 18px; font-weight: 500;
            text-align: center; line-height: 48px; cursor: pointer; z-index: 1; transition: all 0.4s ease;
        }
        .slide-controls .slider-tab{
            position: absolute; height: 100%; width: 50%; left: 0;
            background: linear-gradient(to right, #003366, #004080, #0059b3, #0073e6);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        #login:checked ~ .slide-controls .login{ color: #fff; cursor: default; }
        #login:checked ~ .slide-controls .slider-tab{ left: 0%; }
        #login:checked ~ .form-container .form-inner{ margin-left: 0%; }
        #login:checked ~ .title-text{ margin-left: 0%; }

        #signup:checked ~ .slide-controls .signup{ color: #fff; cursor: default; }
        #signup:checked ~ .slide-controls .slider-tab{ left: 50%; }
        #signup:checked ~ .form-container .form-inner{ margin-left: -100%; } 
        #signup:checked ~ .title-text{ margin-left: -100%; }

        .form-container{ width: 100%; overflow: hidden; }
        .form-inner{ display: flex; width: 200%; transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        .form-inner form{ width: 50%; padding: 0 10px; }
        
        .field{ height: 55px; width: 100%; margin-top: 20px; position: relative; }
        .field input, .field select{
            height: 100%; width: 100%; padding-left: 15px; padding-right: 45px;
            border-radius: 12px; border: 1px solid lightgrey; font-size: 16px; outline: none;
            transition: all 0.3s ease;
            background: #fff;
        }
        .field select { cursor: pointer; appearance: none; }
        .field input:focus, .field select:focus{ border-color: #0059b3; box-shadow: inset 0 0 3px rgba(0,89,179,0.2); }

        .field i {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            color: #999; cursor: pointer; transition: 0.3s;
        }
        .field i:hover { color: #0059b3; }
        
        .btn{ height: 50px; width: 100%; margin-top: 25px; }
        .btn input{
            height: 100%; width: 100%; color: #fff; border-radius: 12px; font-size: 18px;
            background: linear-gradient(to right, #003366, #004080, #0059b3, #0073e6);
            border: none; cursor: pointer; font-weight: 500; transition: transform 0.2s ease;
        }
        .btn input:active { transform: scale(0.98); }
        
        .error{ color: #e74c3c; text-align: center; margin-top: 20px; font-size: 14px; background: #fdf2f2; padding: 8px; border-radius: 8px; }
        .success{ color: #2ecc71; text-align: center; margin-top: 20px; font-size: 14px; background: #f2fdf7; padding: 8px; border-radius: 8px; }
    </style>
</head>
<body>

<div class="system-title">
    <h1>BAGO CITY</h1>
    <p>TRAFFIC RECORD MANAGEMENT SYSTEM</p>
</div>

<div class="wrapper">
    <input type="radio" name="slide" id="login" <?php echo !isset($_POST['signup']) ? 'checked' : ''; ?>>
    <input type="radio" name="slide" id="signup" <?php echo isset($_POST['signup']) ? 'checked' : ''; ?>>

    <div class="title-text">
        <div class="title">Login</div>
        <div class="title">Signup</div>
    </div>

    <div class="slide-controls">
        <label for="login" class="slide login">Login</label>
        <label for="signup" class="slide signup">Signup</label>
        <div class="slider-tab"></div>
    </div>

    <div class="form-container">
        <div class="form-inner">
            <form action="" method="POST" class="login">
                <div class="field">
                    <input type="text" name="username" placeholder="Username" value="<?php echo $login_user; ?>" required>
                </div>
                <div class="field">
                    <input type="password" name="password" id="log_pass" placeholder="Password" required>
                    <i class="fa-solid fa-eye" onclick="togglePassword('log_pass', this)"></i>
                </div>
                <div class="btn">
                    <input type="submit" name="login" value="Login">
                </div>
            </form>

            <form action="" method="POST" class="signup">
                <div class="field">
                    <input type="text" name="full_name" placeholder="Full Name" value="<?php echo $sign_name; ?>" required>
                </div>
                <div class="field">
                    <input type="text" name="signup_username" placeholder="Username" value="<?php echo $sign_user; ?>" required>
                </div>
                <div class="field">
                    <select name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="traffic enforcer">Traffic Enforcer</option>
                    </select>
                    <i class="fa-solid fa-chevron-down" style="font-size: 12px; pointer-events: none;"></i>
                </div>
                <div class="field">
                    <input type="password" name="signup_password" id="sign_pass" placeholder="Password" required>
                    <i class="fa-solid fa-eye" onclick="togglePassword('sign_pass', this)"></i>
                </div>
                <div class="field">
                    <input type="password" name="confirm_password" id="conf_pass" placeholder="Confirm Password" required>
                    <i class="fa-solid fa-eye" onclick="togglePassword('conf_pass', this)"></i>
                </div>
                <div class="btn">
                    <input type="submit" name="signup" value="Signup">
                </div>
            </form>
        </div>
    </div>

    <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <?php if($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
</div>

<script>
    function togglePassword(inputId, icon) {
        const passwordInput = document.getElementById(inputId);
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    <?php if($show_alert): ?>
        alert("Success! Your account has been created. You can now log in.");
    <?php endif; ?>
</script>

</body>
</html>