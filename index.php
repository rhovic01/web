<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CICT Resources Inventory Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
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
            color: var(--dark-blue);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background-color: var(--dark-blue);
            padding: 1.5rem 0;
            box-shadow: 0 4px 12px rgba(1, 31, 75, 0.1);
        }
        
        .nav-link {
            color: var(--off-white) !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: 600;
            letter-spacing: 0.5px;
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
        
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 60%;
        }
        
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(1, 31, 75, 0.05) 0%, rgba(101, 151, 177, 0.05) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(179, 205, 224, 0.15) 0%, rgba(238, 238, 238, 0) 70%);
            z-index: 0;
        }
        
        .hero-content {
            max-width: 800px;
            width: 100%;
            padding: 3rem;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(1, 31, 75, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .btn-primary {
            background-color: var(--light-blue);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 91, 150, 0.2);
        }
        
        .btn-primary:hover {
            background-color: var(--medium-blue);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 91, 150, 0.3);
        }
        
        .btn-outline-secondary {
            border: 2px solid var(--light-blue);
            color: var(--light-blue);
            padding: 0.75rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--very-pale-blue);
            border-color: var(--very-pale-blue);
            color: var(--dark-blue);
            transform: translateY(-2px);
        }
        
        h1, h2, h3 {
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            font-size: 2.8rem;
            color: var(--dark-blue);
            letter-spacing: -0.5px;
        }
        
        h2 {
            font-size: 1.8rem;
            color: var(--medium-blue);
            font-weight: 600;
        }
        
        p {
            margin-bottom: 2.5rem;
            color: var(--medium-blue);
            font-size: 1.1rem;
        }
        
        .institution-name {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--very-pale-blue);
        }
        
        .institution-name h3 {
            font-size: 1.2rem;
            color: var(--pale-blue);
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Signup</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>CICT Resources Inventory Management</h1>
            <h2>College of Information Technology and Communication</h2>
            <p>A modern solution for tracking and managing educational resources efficiently and effectively</p>
            
            <div class="d-flex gap-3 justify-content-center">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-outline-secondary">Register</a>
            </div>
            
            <div class="institution-name">
                <h3>GRANBY COLLEGES OF SCIENCE AND TECHNOLOGY</h3>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>