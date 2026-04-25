<?php
require_once 'includes/config.php';
$page_title = "Login - Buddhist Art Heritage";
require_once 'includes/navbar.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Buddhist Art Heritage</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>

    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="logo-icon">
                        <i class="fas fa-lotus"></i>
                    </div>
                    <h1>Welcome Back</h1>
                </div>

                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-error">
                        <?php 
                        foreach ($_SESSION['errors'] as $error) {
                            echo htmlspecialchars($error) . "<br>";
                        }
                        unset($_SESSION['errors']);
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">
                        Registration successful! You can now login.
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="process-login.php" method="POST" class="auth-form" novalidate>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username or Email</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username or email">
                        <small id="usernameError" class="error-text">Please enter your username or email</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                       <div class="password-wrapper">
    <input type="password" id="password" name="password" placeholder="Enter your password">
    <button type="button" class="toggle-password" data-target="password">
        
    </button>
</div>
                        <small id="passwordError" class="error-text">Please enter your password</small>
                    </div>

                    <button type="submit" class="btn-primary">
                         Login
                    </button>

                    <div class="auth-footer">
                        <p>Don't have an account? <a href="register.php">Create Account</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f0;
            min-height: 100vh;
        }

        .auth-wrapper {
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            background: url('https://media.app.happylandtreks.com/uploads/media/thangka-painting-art-shop-in-kathmandu-nepal.webp') no-repeat center center;
            background-size: cover;
            margin: 1rem;
            border-radius: 24px;
        }

        .auth-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(241, 196, 15, 0.15), rgba(231, 76, 60, 0.1));
            border-radius: 24px;
            z-index: 0;
        }

        .auth-card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(2px);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
            border: 1px solid rgba(241, 196, 15, 0.3);
            transition: transform 0.3s;
        }

        .auth-card:hover {
            transform: translateY(-5px);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e74c3c, #f1c40f);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.2);
        }

        .logo-icon i {
            font-size: 2rem;
            color: white;
        }

        .auth-header h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: #2c3e50;
            font-weight: 600;
        }

        label i {
            color: #e74c3c;
            margin-right: 6px;
            width: 18px;
        }

        input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1.5px solid #e9ecef;
            border-radius: 12px;
            background: white;
            color: #2c3e50;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        input:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
            outline: none;
        }

        input.error {
            border-color: #e74c3c;
            background: #fef5f4;
        }

        .error-text {
            color: #e74c3c;
            display: none;
            font-size: 0.7rem;
            margin-top: 5px;
        }

        .error-text.show {
            display: block;
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.75rem;
            color: #6c757d;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .toggle-password:hover {
            background: #f0f0f0;
            color: #e74c3c;
        }

        .btn-primary {
            width: 100%;
            padding: 0.9rem;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.8rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .auth-footer p {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .auth-footer a {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .auth-footer a:hover {
            color: #c0392b;
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            padding: 0.8rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-error {
            background: #fef5f4;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.2rem;
            opacity: 0.7;
            padding: 0 4px;
        }

        .alert-close:hover {
            opacity: 1;
        }

        @media (max-width: 576px) {
            .auth-wrapper {
                margin: 0.5rem;
                padding: 1rem;
            }
            
            .auth-card {
                padding: 1.8rem;
            }
            
            .logo-icon {
                width: 55px;
                height: 55px;
            }
            
            .logo-icon i {
                font-size: 1.5rem;
            }
            
            .auth-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>

    <script>
   document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
        let input = document.getElementById(btn.dataset.target);
        let icon = btn.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

        // Close alerts
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.parentElement.style.display = "none";
            });
        });

        // Clear error on typing
        document.getElementById('username').addEventListener('input', function() {
            if (this.value.trim() !== "") {
                this.classList.remove('error');
                document.getElementById('usernameError').classList.remove('show');
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            if (this.value.trim() !== "") {
                this.classList.remove('error');
                document.getElementById('passwordError').classList.remove('show');
            }
        });

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Username validation
            const username = document.getElementById('username').value.trim();
            if (username === "") {
                document.getElementById('username').classList.add('error');
                document.getElementById('usernameError').classList.add('show');
                isValid = false;
            } else {
                document.getElementById('username').classList.remove('error');
                document.getElementById('usernameError').classList.remove('show');
            }
            
            // Password validation
            const password = document.getElementById('password').value.trim();
            if (password === "") {
                document.getElementById('password').classList.add('error');
                document.getElementById('passwordError').classList.add('show');
                isValid = false;
            } else {
                document.getElementById('password').classList.remove('error');
                document.getElementById('passwordError').classList.remove('show');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>