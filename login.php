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
<div class="container auth-container">
    <div class="auth-card">

        <div class="auth-header">
            <h1>Login</h1>
            <p>Welcome back to the platform</p>
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

        <form action="process-login.php" method="POST" class="auth-form">

            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password</label>

                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" data-target="password">Show</button>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                Login
            </button>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>

        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>

<style>
/body{
    background:#f7f4ff;
}

/* Container */
.auth-container{
    min-height:85vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* Card */
.auth-card{
    background:white;
    padding:2.5rem;
    border-radius:18px;
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
    max-width:520px;
    width:100%;
}

/* Header */
.auth-header{
    text-align:center;
    margin-bottom:2rem;
}

.auth-header h1{
    color:#5a4fcf;
}

.auth-header p{
    color:#777;
}

/* Form */
.form-group{
    margin-bottom:1.3rem;
}

label{
    display:block;
    margin-bottom:6px;
    font-size:0.9rem;
    color:#555;
}

/* Inputs */
input{
    width:100%;
    padding:0.85rem 1rem;
    border:1.5px solid #e3e3e3;
    border-radius:12px;
    background:#fafaff;
    transition:0.3s;
}

input:focus{
    border-color:#a78bfa;
    background:white;
    box-shadow:0 0 0 3px rgba(167,139,250,0.15);
    outline:none;
}

/* Password */
.password-wrapper{
    position:relative;
}

.toggle-password{
    position:absolute;
    right:10px;
    top:50%;
    transform:translateY(-50%);
    border:none;
    background:none;
    cursor:pointer;
    font-size:0.8rem;
    color:#777;
}

/* Button */
.btn-primary{
    width:100%;
    padding:0.9rem;
    border:none;
    border-radius:14px;
    background:#a78bfa;
    color:white;
    font-weight:600;
    cursor:pointer;
    display:flex;
    justify-content:center;
    transition:0.3s;
}

.btn-primary:hover{
    background:#8b77f6;
    transform:translateY(-2px);
}

/* Footer */
.auth-footer{
    text-align:center;
    margin-top:1.5rem;
    font-size:0.9rem;
}

.auth-footer a{
    color:#8b77f6;
    text-decoration:none;
}

/* Alerts */
.alert{
    border-radius:10px;
    padding:0.8rem 1rem;
    margin-bottom:1rem;
    font-size:0.9rem;
    display:flex;
    justify-content:space-between;
}

.alert-error{
    background:#ff4d4d;
    color:white;
}

.alert-success{
    background:#27ae60;
    color:white;
}

.alert-close{
    background:none;
    border:none;
    color:white;
    cursor:pointer;
}
</style>

<script>

    document.querySelectorAll('.toggle-password').forEach(btn=>{
    btn.addEventListener('click',()=>{
        let input=document.getElementById(btn.dataset.target);

        if(input.type==="password"){
            input.type="text";
            btn.textContent="Hide";
        }else{
            input.type="password";
            btn.textContent="Show";
        }
    });
});

document.querySelectorAll('.alert-close').forEach(btn=>{
    btn.addEventListener('click',()=>{
        btn.parentElement.style.display="none";
    });
});
document.addEventListener('DOMContentLoaded', function() {
    // Close alert
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', () => btn.parentElement.style.display = 'none');
    });
});
</script>