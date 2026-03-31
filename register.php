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

<div class="container auth-container">
    <div class="auth-card">

        <div class="auth-header">
            <h1><?php echo $role == 'artist' ? 'Artist Registration' : 'Create Account'; ?></h1>
            <p><?php echo $role == 'artist' ? 'Share your artwork with the community' : 'Join our platform'; ?></p>
        </div>

        <form action="process-register.php" method="POST" id="registerForm" class="auth-form" enctype="multipart/form-data">

            <input type="hidden" name="role" value="<?php echo $role; ?>">

            <div class="form-group">
                <label>Full Name </label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Username </label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Email </label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password </label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" data-target="password">Show</button>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password </label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
                </div>
            </div>

            <?php if ($role == 'artist'): ?>

            <div class="artist-fields">

                <h3>Artist Information</h3>

                <div class="form-group">
                    <label>Specialization </label>
                    <select name="specialization" required>
                        <option value="">Select</option>
                        <option>Thangka Painting</option>
                        <option>Sculpture</option>
                        <option>Mandala Art</option>
                        <option>Buddhist Painting</option>
                        <option>Wood Carving</option>
                        <option>Metal Work</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Experience (Years)</label>
                    <input type="number" name="experience_years" min="0" max="50">
                </div>

                <div class="form-group">
                    <label>Biography</label>
                    <textarea name="bio"></textarea>
                </div>

            </div>

            <?php endif; ?>

            <div class="form-group">
                <label>Profile Picture</label>
                <input type="file" name="profile_image" accept="image/*">
            </div>

            

            <button type="submit" class="btn-primary">
                Create Account
            </button>

            <div class="auth-footer">
                <p>Already have account? <a href="login.php">Login</a></p>

                <?php if ($role != 'artist'): ?>
                <p><a href="register.php?role=artist">Register as Artist</a></p>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>


<style>

body{
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
input,select,textarea{
    width:100%;
    padding:0.85rem 1rem;
    border:1.5px solid #e3e3e3;
    border-radius:12px;
    background:#fafaff;
    transition:0.3s;
}

input:focus,select:focus,textarea:focus{
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

/* Artist box */
.artist-fields{
    background:#f4f1ff;
    padding:1.3rem;
    border-radius:14px;
    margin-top:1rem;
}

/* Checkbox */
.checkbox-group{
    display:flex;
    gap:8px;
    align-items:center;
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

document.getElementById('registerForm').addEventListener('submit',function(e){

    let pass=document.getElementById('password').value;
    let confirm=document.getElementById('confirm_password').value;

    if(pass!==confirm){
        alert("Passwords do not match");
        e.preventDefault();
    }

});

</script>