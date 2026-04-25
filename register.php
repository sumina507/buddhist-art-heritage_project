<?php
require_once 'includes/config.php';
$page_title = "Register";
require_once 'includes/navbar.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$role = isset($_GET['role']) && $_GET['role'] == 'artist' ? 'artist' : 'user';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Buddhist Art Heritage</title>
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
                    <h1><?php echo $role == 'artist' ? 'Artist Registration' : 'Create Account'; ?></h1>
                    <p><?php echo $role == 'artist' ? 'Share your art with the world' : 'Join our Buddhist art community'; ?></p>
                </div>

                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-error">
                        <?php 
                        foreach ($_SESSION['errors'] as $error) {
                            echo "<i class='fas fa-exclamation-circle'></i> " . htmlspecialchars($error) . "<br>";
                        }
                        unset($_SESSION['errors']);
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <form id="registerForm" action="process-register.php" method="POST" enctype="multipart/form-data" class="auth-form" novalidate>
                    <input type="hidden" name="role" value="<?php echo $role; ?>">

                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name *</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name">
                        <small id="fullNameError" class="error-text">Full name is required (min 3 characters)</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-at"></i> Username *</label>
                        <input type="text" id="username" name="username" placeholder="Choose a username (letters, numbers, underscore)">
                        <small id="usernameError" class="error-text">Username must be 4-20 characters (letters, numbers, _ only)</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address">
                        <small id="emailError" class="error-text">Enter a valid email address</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password *</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Create a password">
                            <button type="button" class="toggle-password" data-target="password">Show</button>
                        </div>
                        <small id="passwordError" class="error-text">Password must be 8+ characters with uppercase, lowercase, and number</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-check-circle"></i> Confirm Password *</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password">
                            <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
                        </div>
                        <small id="confirmError" class="error-text">Passwords do not match</small>
                    </div>

                    <?php if ($role == 'artist'): ?>
                        <div class="artist-fields">
                            <h3>Artist Information</h3>
                            
                            <div class="form-group">
                                <label><i class="fas fa-paint-brush"></i> Specialization *</label>
                                <select id="specialization" name="specialization">
                                    <option value="">Select your art form</option>
                                    <option value="Thangka Painting">Thangka Painting</option>
                                    <option value="Sculpture">Sculpture</option>
                                    <option value="Mandala Art">Mandala Art</option>
                                    <option value="Buddhist Painting">Buddhist Painting</option>
                                    <option value="Wood Carving">Wood Carving</option>
                                    <option value="Metal Work">Metal Work</option>
                                </select>
                                <small id="specializationError" class="error-text">Please select your specialization</small>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Experience (Years)</label>
                                <input type="number" id="experience_years" name="experience_years" min="0" max="50" placeholder="Years of experience">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-align-left"></i> Biography</label>
                                <textarea id="bio" name="bio" rows="3" placeholder="Tell us about your art journey..."></textarea>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label><i class="fas fa-camera"></i> Profile Picture</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <small>Optional. Max size: 2MB (JPG, PNG, GIF)</small>
                    </div>

                    <button type="submit" class="btn-primary">
                        Create Account
                    </button>

                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php">Login</a></p>
                        <?php if ($role != 'artist'): ?>
                            <p><a href="register.php?role=artist">Register as Artist</a></p>
                        <?php endif; ?>
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
            max-width: 550px;
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
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.85rem;
            color: #2c3e50;
            font-weight: 600;
        }

        label i {
            color: #e74c3c;
            margin-right: 6px;
            width: 18px;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1.5px solid #e9ecef;
            border-radius: 12px;
            background: white;
            color: #2c3e50;
            font-size: 0.9rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
            outline: none;
        }

        input.error, select.error, textarea.error {
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

        .artist-fields {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 16px;
            margin: 1rem 0;
        }

        .artist-fields h3 {
            color: #2c3e50;
            font-size: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
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
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .auth-footer p {
            color: #6c757d;
            font-size: 0.85rem;
            margin: 0.3rem 0;
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
                padding: 1.5rem;
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
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', () => {
                let input = document.getElementById(btn.dataset.target);
                if (input.type === "password") {
                    input.type = "text";
                    btn.textContent = "Hide";
                } else {
                    input.type = "password";
                    btn.textContent = "Show";
                }
            });
        });

        // Close alerts
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.parentElement.style.display = "none";
            });
        });

        // Clear error on input
        const fields = ['full_name', 'username', 'email', 'password', 'confirm_password', 'specialization'];
        fields.forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.addEventListener('input', function() {
                    this.classList.remove('error');
                    const errorId = this.id + 'Error';
                    const errorEl = document.getElementById(errorId);
                    if (errorEl) errorEl.classList.remove('show');
                });
            }
        });

        // Real-time password strength indicator (optional)
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const isValidLength = password.length >= 8;
            
            if (password !== "" && isValidLength && hasUpper && hasLower && hasNumber) {
                this.classList.remove('error');
                document.getElementById('passwordError').classList.remove('show');
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Full Name validation (min 3 characters)
            const fullName = document.getElementById('full_name').value.trim();
            if (fullName === "" || fullName.length < 3) {
                document.getElementById('full_name').classList.add('error');
                document.getElementById('fullNameError').classList.add('show');
                isValid = false;
            }
            
            // Username validation (4-20 chars, alphanumeric + underscore)
            const username = document.getElementById('username').value.trim();
            const usernameRegex = /^[a-zA-Z0-9_]{4,20}$/;
            if (!usernameRegex.test(username)) {
                document.getElementById('username').classList.add('error');
                document.getElementById('usernameError').classList.add('show');
                isValid = false;
            }
            
            // Email validation
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/;
            if (!emailRegex.test(email)) {
                document.getElementById('email').classList.add('error');
                document.getElementById('emailError').classList.add('show');
                isValid = false;
            }
            
            // Password validation (min 8 chars, uppercase, lowercase, number)
            const password = document.getElementById('password').value;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            if (password.length < 8 || !hasUpper || !hasLower || !hasNumber) {
                document.getElementById('password').classList.add('error');
                document.getElementById('passwordError').classList.add('show');
                isValid = false;
            }
            
            // Confirm password
            const confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword) {
                document.getElementById('confirm_password').classList.add('error');
                document.getElementById('confirmError').classList.add('show');
                isValid = false;
            }
            
            // Specialization for artists
            const role = document.querySelector('input[name="role"]').value;
            if (role === 'artist') {
                const specialization = document.getElementById('specialization').value;
                if (specialization === "") {
                    document.getElementById('specialization').classList.add('error');
                    document.getElementById('specializationError').classList.add('show');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>