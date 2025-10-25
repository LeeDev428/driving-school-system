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
        
        html {
            scroll-behavior: smooth;
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

        /* Courses Section */
        .courses-section {
            background: linear-gradient(135deg, #FFC107 0%, #FFD54F 100%);
            padding: 60px 5%;
            text-align: center;
        }

        .courses-section h2 {
            font-size: 36px;
            font-weight: 700;
            color: #000;
            margin-bottom: 40px;
        }

        .courses-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .course-card {
            background: transparent;
            padding: 0;
            text-align: left;
        }

        .course-card h3 {
            font-size: 22px;
            font-weight: 600;
            color: #000;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .course-card h3::before {
            content: '●';
            font-size: 12px;
            margin-right: 10px;
        }

        .course-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .course-card ul li {
            font-size: 18px;
            color: #000;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .course-card ul li::before {
            content: '●';
            font-size: 10px;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .courses-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        /* About Section */
        .about-section {
            padding: 80px 5%;
            background: #fff;
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .about-content h2 {
            font-size: 36px;
            font-weight: 700;
            color: #000;
            margin-bottom: 20px;
        }

        .about-content h3 {
            font-size: 24px;
            color: #FFC107;
            margin-bottom: 15px;
        }

        .about-content p {
            font-size: 16px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 15px;
        }

        .about-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature-item i {
            color: #FFC107;
            font-size: 24px;
        }

        .feature-item span {
            font-size: 16px;
            color: #333;
        }

        .about-image {
            text-align: center;
        }

        .about-image img {
            width: 100%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Contact Section */
        .contact-section {
            padding: 80px 5%;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .contact-container h2 {
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
        }

        .contact-info h3 {
            font-size: 24px;
            margin-bottom: 30px;
            color: #FFC107;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .contact-item i {
            color: #FFC107;
            font-size: 24px;
            margin-right: 15px;
            margin-top: 5px;
        }

        .contact-item-content h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .contact-item-content p {
            font-size: 16px;
            color: #ddd;
            line-height: 1.6;
        }

        .contact-form h3 {
            font-size: 24px;
            margin-bottom: 30px;
            color: #FFC107;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            margin-bottom: 8px;
            color: #fff;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            background: #FFC107;
            color: #000;
            padding: 12px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background: #FFD54F;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .alert-message {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
            animation: slideDown 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Footer */
        .footer {
            background: #000;
            color: white;
            text-align: center;
            padding: 20px 5%;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .about-container,
            .contact-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .about-features {
                grid-template-columns: 1fr;
            }
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
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#courses">Courses</a></li>
                <li><a href="#contact">Contact Us</a></li>
            </ul>
            
            <div class="user-actions">
                <a href="login.php" class="btn-login">Log In</a>
                <a href="register.php" class="btn-register">Register</a>
            </div>
        </div>
    </header>
    
    <section class="hero" id="home">
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

    <section class="about-section" id="about">
        <div class="about-container">
            <div class="about-content">
                <h2>About Us</h2>
                <h3>Success Technical & Vocational School, Inc.</h3>
                <p>Established on <strong>February 25, 2017</strong>, Success Technical & Vocational School, Inc. has been committed to providing quality driving education to students in Lucena, Philippines.</p>
                <p>We are a professional driving school dedicated to producing safe, responsible, and skilled drivers through comprehensive theoretical and practical training programs.</p>
                <p>Our mission is to ensure that every student gains the knowledge, confidence, and skills needed to become a competent driver on the road.</p>
                
                <div class="about-features">
                    <div class="feature-item">
                        <i class="fas fa-certificate"></i>
                        <span>LTO Accredited</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <span>Expert Instructors</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-car"></i>
                        <span>Modern Vehicles</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Safety First</span>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <img src="assets/images/dss_bg.png" alt="Success Driving School">
            </div>
        </div>
    </section>

    <section class="courses-section" id="courses">
        <h2>Courses We Offer:</h2>
        <div class="courses-container">
            <div class="course-card">
                <h3>Theoretical Driving Course</h3>
                <ul>
                    <li>Manual Transmission</li>
                </ul>
            </div>
            <div class="course-card">
                <h3>Practical Driving Course</h3>
                <ul>
                    <li>Automatic Transmission</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="contact-section" id="contact">
        <div class="contact-container">
            <h2>Contact Us</h2>
            <div class="contact-content">
                <div class="contact-info">
                    <h3>Get In Touch</h3>
                    
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="contact-item-content">
                            <h4>Address</h4>
                            <p>Gomez St., Lucena, Philippines, 4301</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div class="contact-item-content">
                            <h4>Email</h4>
                            <p>successdrivingschool21@gmail.com</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div class="contact-item-content">
                            <h4>Business Hours</h4>
                            <p>Monday - Saturday: 8:00 AM - 5:00 PM<br>
                            Sunday: Closed</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="fab fa-facebook"></i>
                        <div class="contact-item-content">
                            <h4>Follow Us</h4>
                            <p>Success Driving School</p>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <h3>Send Us A Message</h3>
                    <div id="alert-container"></div>
                    <form id="contactForm" method="POST">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter your name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" placeholder="Enter subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="Enter your message" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn" id="submitBtn">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; 2017-2025 Success Technical & Vocational School, Inc. All rights reserved.</p>
    </footer>

    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            const alertContainer = document.getElementById('alert-container');
            const formData = new FormData(form);
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            
            // Clear previous alerts
            alertContainer.innerHTML = '';
            
            fetch('send_contact_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alertContainer.innerHTML = `
                        <div class="alert-message alert-success" style="display: block;">
                            <i class="fas fa-check-circle"></i> ${data.message}
                        </div>
                    `;
                    // Reset form
                    form.reset();
                } else {
                    // Show error message with debug info if available
                    let errorMsg = data.message;
                    if (data.debug) {
                        errorMsg += '<br><small><strong>Debug:</strong> ' + data.debug + '</small>';
                    }
                    alertContainer.innerHTML = `
                        <div class="alert-message alert-error" style="display: block;">
                            <i class="fas fa-exclamation-circle"></i> ${errorMsg}
                        </div>
                    `;
                }
                
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Message';
                
                // Scroll to alert
                alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                
                // Auto-hide alert after 5 seconds
                setTimeout(() => {
                    const alert = alertContainer.querySelector('.alert-message');
                    if (alert) {
                        alert.style.display = 'none';
                    }
                }, 5000);
            })
            .catch(error => {
                alertContainer.innerHTML = `
                    <div class="alert-message alert-error" style="display: block;">
                        <i class="fas fa-exclamation-circle"></i> An error occurred. Please try again later.
                    </div>
                `;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Message';
            });
        });
    </script>
</body>
</html>