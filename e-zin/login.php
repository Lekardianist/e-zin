<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    
    // Cari user dengan email dan password yang sesuai
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect berdasarkan role
        switch($user['role']) {
            case 'admin':
                header('Location: dashboard.php');
                break;
            case 'student':
                header('Location: student/dashboard.php');
                break;
            case 'lecturer':
                header('Location: lecturer/dashboard.php');
                break;
            case 'staff':
                header('Location: staff/dashboard.php');
                break;
            default:
                header('Location: dashboard.php');
        }
        exit();
    } else {
        $error = "Email atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-ZIN - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
        }
        
        .login-container {
            display: flex;
            height: 100vh;
        }
        
        /* Left Side - Form */
        .login-form-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
            overflow-y: auto;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .login-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 1rem;
            margin-top: 5px;
        }
        
        .login-card {
            max-width: 450px;
            margin: 0 auto;
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #444;
            margin-bottom: 8px;
            display: block;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 20px;
        }
        
        .form-control {
            padding: 15px 15px 15px 50px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
        }
        
        .btn-login {
            background: var(--gradient);
            color: white;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }
        
        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
        
        .signup-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        .alert-danger {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 25px;
        }
        
        /* Right Side - Illustration */
        .login-illustration {
            flex: 1;
            background: var(--gradient);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .illustration-container {
            position: relative;
            width: 100%;
            max-width: 600px;
            height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .illustration-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .illustration-subtitle {
            color: rgba(255,255,255,0.9);
            text-align: center;
            font-size: 1.1rem;
            max-width: 500px;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        
        .features-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 40px;
            max-width: 500px;
        }
        
        .feature-item {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }
        
        .feature-icon {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }
        
        .feature-text {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Decorative Elements */
        .blob {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            z-index: 0;
        }
        
        .blob-1 {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -150px;
        }
        
        .blob-2 {
            width: 200px;
            height: 200px;
            bottom: -100px;
            left: -100px;
        }
        
        .illustration-content {
            position: relative;
            z-index: 1;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-illustration {
                display: none;
            }
            
            .login-form-section {
                padding: 30px 20px;
            }
        }
        
        @media (min-width: 993px) {
            .mobile-only {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .login-title {
                font-size: 2rem;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        
        /* Custom checkbox */
        .checkbox-container {
            display: block;
            position: relative;
            padding-left: 35px;
            cursor: pointer;
            user-select: none;
        }
        
        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: #eee;
            border-radius: 5px;
        }
        
        .checkbox-container:hover input ~ .checkmark {
            background-color: #ccc;
        }
        
        .checkbox-container input:checked ~ .checkmark {
            background-color: var(--primary-color);
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }
        
        .checkbox-container .checkmark:after {
            left: 7px;
            top: 3px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Login Form -->
        <div class="login-form-section">
            <div class="login-header">
                <div class="logo-container">
                    <div class="logo-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h1 class="login-title">E-ZIN</h1>
                </div>
                <p class="login-subtitle">Permission Management System</p>
            </div>
            
            <div class="login-card">
                <!-- Error Message -->
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <h3 class="mb-4" style="color: #444;">Login to your account</h3>
                
                <!-- Login Form -->
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-with-icon">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" class="form-control" name="email" 
                                   placeholder="Enter your email address" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-with-icon">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" class="form-control" name="password" 
                                   id="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me checkbox-container">
                            <input type="checkbox" id="rememberMe">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="#" class="forgot-password" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                            Forgot password?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Login to System
                    </button>
                </form>
                
                <!-- Mobile Only Demo Accounts -->
                <div class="demo-accounts mobile-only mt-4">
                    <div class="demo-title" style="font-weight: 600; color: #444; margin-bottom: 10px;">
                        <i class="bi bi-info-circle"></i> Demo Accounts
                    </div>
                    <div class="demo-account" data-email="admin@ezin.com" data-password="admin123">
                        <span style="font-weight: 600; color: var(--primary-color);">Admin</span>
                        <span style="color: #666;">admin@ezin.com / admin123</span>
                    </div>
                    <div class="demo-account" data-email="john@student.edu" data-password="student123">
                        <span style="font-weight: 600; color: var(--primary-color);">Student</span>
                        <span style="color: #666;">john@student.edu / student123</span>
                    </div>
                </div>
                
                <div class="signup-link">
                    Don't have an account? 
                    <a href="#" data-bs-toggle="modal" data-bs-target="#signupModal">
                        Contact Administrator
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Illustration -->
        <div class="login-illustration">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            
            <div class="illustration-content">
                <h2 class="illustration-title">Welcome to E-ZIN</h2>
                <p class="illustration-subtitle">
                    Streamline your permission requests and approvals with our comprehensive 
                    management system designed for educational institutions.
                </p>
                
                <div class="features-list">
                    <div class="feature-item">
                        <i class="bi bi-clock-history feature-icon"></i>
                        <div class="feature-text">Real-time Tracking</div>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-shield-check feature-icon"></i>
                        <div class="feature-text">Secure Access</div>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-file-text feature-icon"></i>
                        <div class="feature-text">Digital Approvals</div>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-bar-chart feature-icon"></i>
                        <div class="feature-text">Analytics & Reports</div>
                    </div>
                </div>
                
                <!-- Demo Accounts for Desktop -->
                <div class="demo-accounts mt-5" style="background: rgba(255,255,255,0.1); border-radius: 15px; padding: 20px;">
                    <div class="demo-title" style="color: white; font-weight: 600; margin-bottom: 15px;">
                        <i class="bi bi-key"></i> Demo Login Credentials
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div class="demo-account" data-email="admin@ezin.com" data-password="admin123" 
                             style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; cursor: pointer; transition: background 0.3s;">
                            <div style="font-weight: 600; color: white; font-size: 14px;">Admin</div>
                            <div style="font-size: 12px; opacity: 0.9; color: white;">admin@ezin.com</div>
                        </div>
                        <div class="demo-account" data-email="john@student.edu" data-password="student123"
                             style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; cursor: pointer; transition: background 0.3s;">
                            <div style="font-weight: 600; color: white; font-size: 14px;">Student</div>
                            <div style="font-size: 12px; opacity: 0.9; color: white;">john@student.edu</div>
                        </div>
                        <div class="demo-account" data-email="lecturer@university.edu" data-password="lecturer123"
                             style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; cursor: pointer; transition: background 0.3s;">
                            <div style="font-weight: 600; color: white; font-size: 14px;">Lecturer</div>
                            <div style="font-size: 12px; opacity: 0.9; color: white;">dosen@ezin.com</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Forgot Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Please contact the system administrator to reset your password:</p>
                    <div class="alert alert-info">
                        <i class="bi bi-person-gear"></i> <strong>Admin Contact:</strong><br>
                        Email: admin@ezin.com<br>
                        Phone: (021) 1234-5678
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Signup Modal -->
    <div class="modal fade" id="signupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Account Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>For new account registration, please contact:</p>
                    <div class="alert alert-info">
                        <i class="bi bi-people"></i> <strong>HR Department</strong><br>
                        Email: hr@university.edu<br>
                        Phone: (021) 8765-4321<br>
                        Office: Building A, Room 101
                    </div>
                    <p class="small text-muted">Note: All accounts must be verified by the administration.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Demo account click handlers
        document.querySelectorAll('.demo-account').forEach(account => {
            account.addEventListener('click', function() {
                const email = this.dataset.email;
                const password = this.dataset.password;
                
                if (email && password) {
                    document.querySelector('input[name="email"]').value = email;
                    document.querySelector('input[name="password"]').value = password;
                    
                    // Show success message
                    const form = document.getElementById('loginForm');
                    const existingAlert = form.querySelector('.alert');
                    if (existingAlert) {
                        existingAlert.remove();
                    }
                    
                    const successDiv = document.createElement('div');
                    successDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                    successDiv.innerHTML = `
                        <i class="bi bi-check-circle"></i> Demo account loaded! Click "Login to System" to continue.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    form.insertBefore(successDiv, form.firstChild);
                    
                    // Auto-hide success message after 3 seconds
                    setTimeout(() => {
                        if (successDiv.parentNode) {
                            successDiv.classList.remove('show');
                            successDiv.classList.add('fade');
                            setTimeout(() => {
                                if (successDiv.parentNode) {
                                    successDiv.remove();
                                }
                            }, 300);
                        }
                    }, 3000);
                    
                    // Highlight the clicked demo account
                    this.style.background = 'rgba(102, 126, 234, 0.3)';
                    setTimeout(() => {
                        this.style.background = '';
                    }, 1000);
                }
            });
        });
        
        // Form submission with validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.querySelector('input[name="email"]').value;
            const password = document.querySelector('input[name="password"]').value;
            
            if (!email || !password) {
                e.preventDefault();
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    Please fill in both email and password fields.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const existingAlert = this.querySelector('.alert');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                this.insertBefore(alertDiv, this.firstChild);
                return false;
            }
            
            // Add loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Logging in...';
            submitBtn.disabled = true;
            
            // Re-enable button after 3 seconds (in case of error)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
            
            return true;
        });
        
        // Remember me functionality
        document.addEventListener('DOMContentLoaded', function() {
            const rememberMe = localStorage.getItem('rememberMe');
            const savedEmail = localStorage.getItem('savedEmail');
            
            if (rememberMe === 'true' && savedEmail) {
                document.querySelector('input[name="email"]').value = savedEmail;
                document.getElementById('rememberMe').checked = true;
            }
            
            document.getElementById('rememberMe').addEventListener('change', function() {
                const email = document.querySelector('input[name="email"]').value;
                
                if (this.checked && email) {
                    localStorage.setItem('rememberMe', 'true');
                    localStorage.setItem('savedEmail', email);
                } else {
                    localStorage.removeItem('rememberMe');
                    localStorage.removeItem('savedEmail');
                }
            });
        });
        
        // Auto-focus email field
        document.querySelector('input[name="email"]').focus();
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + Enter to submit form
            if (e.ctrlKey && e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
            
            // Escape to clear form
            if (e.key === 'Escape') {
                document.querySelector('input[name="email"]').value = '';
                document.querySelector('input[name="password"]').value = '';
            }
        });
        
        // Animate blobs
        function animateBlobs() {
            const blob1 = document.querySelector('.blob-1');
            const blob2 = document.querySelector('.blob-2');
            
            let x1 = 0, y1 = 0;
            let x2 = 0, y2 = 0;
            
            function updateBlobs() {
                x1 += Math.sin(Date.now() / 2000) * 0.5;
                y1 += Math.cos(Date.now() / 2000) * 0.5;
                x2 += Math.sin(Date.now() / 3000) * 0.3;
                y2 += Math.cos(Date.now() / 3000) * 0.3;
                
                blob1.style.transform = `translate(${x1}px, ${y1}px)`;
                blob2.style.transform = `translate(${x2}px, ${y2}px)`;
                
                requestAnimationFrame(updateBlobs);
            }
            
            updateBlobs();
        }
        
        // Start animation when page loads
        window.addEventListener('load', animateBlobs);
    </script>
</body>
</html>