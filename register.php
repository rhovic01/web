<?php
// [Keep all your existing PHP registration logic exactly the same]
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CICT Inventory</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Shared CSS -->
    <style>
        :root {
            --dark-blue: #011f4b;
            --medium-blue: #03396c;
            --light-blue: #005b96;
            --pale-blue: #6497b1;
            --very-pale-blue: #b3cde0;
            --off-white: #eeeeee;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--off-white);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Identical Navbar */
        .navbar {
            background-color: var(--dark-blue);
            padding: 1.5rem 0;
            box-shadow: 0 4px 12px rgba(1, 31, 75, 0.1);
        }
        
        .nav-link {
            color: var(--off-white) !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: 600;
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--very-pale-blue);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 60%;
        }
        
        /* Registration Card (matches homepage) */
        .register-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .register-card {
            width: 100%;
            max-width: 500px;
            padding: 3rem;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(1, 31, 75, 0.1);
        }
        
        /* Form Elements */
         .form-control {
            border: 1px solid var(--very-pale-blue);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            height: calc(2.25rem + 2px); /* Fixed height matching Bootstrap */
            line-height: 1.5; /* Consistent text alignment */
        }
        
        .form-control:focus {
            border-color: var(--light-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 91, 150, 0.1);
        }
        
        /* Phone Input */
        .phone-input {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .phone-prefix {
            background: var(--very-pale-blue);
            padding: 0.625rem 0.75rem; /* Slightly reduced padding */
            border: 1px solid var(--very-pale-blue);
            border-right: none;
            border-radius: 8px 0 0 8px;
            color: var(--dark-blue);
            display: inline-flex;
            align-items: center;
            font-size: 0.875rem; /* Match Bootstrap's input font size */
            line-height: 1; /* Match Bootstrap's input line-height */
            height: calc(2.25rem + 14px); /* Exact match for Bootstrap default input height */
        }
        
        .phone-number {
            border-radius: 0 8px 8px 0 !important;
            flex: 1;
            height: auto; /* Allows natural height */
        }
        
        /* Password Toggle */
        .input-group {
            margin-bottom: 1rem;
            align-items: stretch; /* Ensures child elements match height */
        }
        .input-group .form-control {
            margin-bottom: 0; /* Remove duplicate margin */
        }
        
        .input-group-text {
            background-color: transparent;
            border: 1px solid var(--very-pale-blue);
            border-left: none;
            border-radius: 0 8px 8px 0;
            color: var(--pale-blue);
            padding: 0 1rem; /* Horizontal padding only */
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem; /* Fixed width */
        }
        
        /* Buttons (identical to homepage) */
        .btn-primary {
            background-color: var(--light-blue);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            margin: 1rem 0;
        }
        
        .btn-primary:hover {
            background-color: var(--medium-blue);
            transform: translateY(-2px);
        }
        
        .text-link {
            color: var(--light-blue);
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        h2 {
            color: var(--dark-blue);
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Identical Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="register.php">Signup</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Registration Card -->
    <section class="register-container">
        <div class="register-card">
            <h2>Create Account</h2>
            <form action="register_process.php" method="POST" onsubmit="return validateForm()">
                <div class="row">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
                
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                
                <label for="contact_number" class="form-label">Phone Number</label>
                <div class="phone-input">
                    <span class="phone-prefix">+63</span>
                    <input type="text" class="form-control phone-number" id="contact_number" name="contact_number" 
                        maxlength="10" required placeholder="9123456789">
                </div>
                
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <span class="input-group-text toggle-password">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <span class="input-group-text toggle-password">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                
                <input type="hidden" name="role" value="officer">
                
                <button type="submit" class="btn btn-primary">Register</button>
                
                <a href="index.php" class="text-link">Back to Home</a>
            </form>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        function validateForm() {
            let password = document.getElementById("password").value;
            let confirmPassword = document.getElementById("confirm_password").value;
            let errorMessages = [];

            // Password validation
            if (password.length < 8) {
                errorMessages.push("Password must be at least 8 characters long.");
            }
            if (!/[A-Z]/.test(password)) {
                errorMessages.push("Password must contain at least one uppercase letter.");
            }
            if (!/[a-z]/.test(password)) {
                errorMessages.push("Password must contain at least one lowercase letter.");
            }
            if (!/[0-9]/.test(password)) {
                errorMessages.push("Password must contain at least one number.");
            }
            if (!/[!@#$%^&*()\-_=+{};:,<.>]/.test(password)) {
                errorMessages.push("Password must contain at least one special character (!@#$%^&*()-_=+{};:,<.>).");
            }

            // Password match validation
            if (password !== confirmPassword) {
                errorMessages.push("The passwords you entered do not match. Please try again.");
            }

            if (errorMessages.length > 0) {
                alert(errorMessages.join("\n\n"));
                return false;
            }

            return true;
        }
        
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(function(element) {
            element.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Phone number validation
        document.getElementById('contact_number').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>