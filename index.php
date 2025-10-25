<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drive Ease</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        /* Navigation */
        header {
            background-color: rgba(0, 0, 0, 0.8);
            position: fixed;
            width: 100%;
            z-index: 100;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
        }
        
        .logo h1 {
            color: #ff3333;
            margin-left: 10px;
            font-size: 24px;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
        }
        
        .nav-links li {
            margin: 0 15px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: 0.3s;
        }
        
        .nav-links a:hover {
            color: #ffcc00;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-login,
        .btn-register {
            text-decoration: none;
            padding: 10px 28px;
            font-size: 15px;
            font-weight: 500;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: 2px solid;
        }
        
        .btn-login {
            background-color: white;
            color: black;
            border-color: white;
        }
        
        .btn-login:hover {
            background-color: transparent;
            color: white;
            border-color: white;
        }
        
        .btn-register {
            background-color: black;
            color: white;
            border-color: white;
        }
        
        .btn-register:hover {
            background-color: white;
            color: black;
            border-color: white;
        }
        
        .cart-icon {
            position: relative;
        }
        
        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ffcc00;
            color: black;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            font-size: 12px;
            line-height: 20px;
        }
        
        /* Hero Section */
        .hero {
            height: 100vh;
            background: url('assets/images/dss_bg.png') no-repeat center center;
            background-size: cover;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            padding: 0 20px;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .cta-button {
            display: inline-block;
            background: transparent;
            color: white;
            padding: 12px 30px;
            border: 2px solid white;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .cta-button:hover {
            background: white;
            color: black;
        }
        
        .slider-nav {
            position: absolute;
            bottom: 30px;
            display: flex;
            z-index: 1;
        }
        
        .slider-nav a {
            width: 12px;
            height: 12px;
            background: rgba(255, 255, 255, 0.5);
            margin: 0 5px;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .slider-nav a.active {
            background: white;
        }
        
        .slider-arrows {
            position: absolute;
            width: 100%;
            display: flex;
            justify-content: space-between;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1;
            padding: 0 30px;
        }
        
        .slider-arrows a {
            color: white;
            font-size: 30px;
            text-decoration: none;
            background: rgba(0, 0, 0, 0.3);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-container">
            <div class="logo">
                <img src="assets/images/dss_logo.png" alt="Success Driving Logo">
                <h1>Drive Ease</h1>
            </div>
            
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Courses</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
            
            <div class="user-actions">
                <a href="login.php" class="btn-login">Log In</a>
                <a href="register.php" class="btn-register">Register</a>
            </div>
        </div>
    </header>
    
    <section class="hero">
        <div class="hero-content">
            <h1>SUCCESS TECHNICAL & VOCATIONAL SCHOOL, INC.</h1>
            <p>Professional Driving School in Gomez St., Lucena, Philippines</p>
            <p style="margin-top: 15px; font-size: 16px;">
                <i class="fas fa-map-marker-alt"></i> Gomez St., Lucena, Philippines, 4301<br>
                <i class="fas fa-envelope"></i> successdrivingschool21@gmail.com<br>
                <i class="fas fa-calendar-alt"></i> Established February 25, 2017
            </p>
           
        </div>
        
  
        
      
    </section>
</body>
</html>