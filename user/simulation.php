<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

$user_id = $_SESSION["id"];

// Fetch user's license type from database
$license_type = 'car'; // Default to car
$sql = "SELECT license_type FROM users WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $license_type = strtolower($row['license_type']);
            // Normalize: motorcycle, motorbike, motor -> motorcycle; anything else -> car
            if (in_array($license_type, ['motorcycle', 'motorbike', 'motor'])) {
                $license_type = 'motorcycle';
            } else {
                $license_type = 'car';
            }
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driving Simulation - Driving School System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/simulation.css">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            width: 100%;
            min-height: 100vh;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .simulation-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            display: block;
            z-index: 1000;
        }
        
        .game-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 2000;
            height: 60px;
            flex-shrink: 0;
        }
        
        .game-title {
            font-size: 24px;
            font-weight: bold;
        }
        
        .game-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 5px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
        }
        
        .game-canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #2c3e50;
            overflow: hidden;
            z-index: 1000;
        }
        
        #gameCanvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw !important;
            height: 100vh !important;
            display: block;
            cursor: crosshair;
            background: #2c3e50;
            z-index: 999;
            object-fit: cover;
        }
        
        .game-controls {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            padding: 15px 25px;
            border-radius: 15px;
            display: flex;
            gap: 15px;
            align-items: center;
            color: white;
            z-index: 3000;
        }
        
        .control-btn {
            background: linear-gradient(45deg, #FF6B6B, #FF8E8E);
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            min-width: 80px;
        }
        
        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .control-btn:active {
            transform: translateY(0);
        }
        
        .control-btn.brake {
            background: linear-gradient(45deg, #FF4757, #FF3742);
        }
        
        .control-btn.move {
            background: linear-gradient(45deg, #2ECC71, #27AE60);
        }
        
        .speed-indicator {
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-weight: bold;
        }
        
        .question-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        
        .question-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 900px;  /* Wider for landscape */
            width: 95%;
            max-height: 70vh;  /* Limit height */
            overflow-y: auto;  /* Allow scrolling if needed */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .question-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
            text-align: center;
        }
        
        .question-text {
            font-size: 18px;
            margin-bottom: 25px;
            line-height: 1.6;
            color: #495057;
        }
        
        .question-options {
            display: grid;
            grid-template-columns: 1fr 1fr;  /* Two columns for landscape */
            gap: 15px;
            margin-bottom: 25px;
        }
        
        @media (max-width: 768px) {
            .question-options {
                grid-template-columns: 1fr;  /* Single column on mobile */
            }
            .question-content {
                max-width: 95%;
                padding: 20px;
            }
        }
        
        .option-btn {
            background: #f8f9fa;
            border: 3px solid #e9ecef;
            padding: 20px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            font-size: 16px;
            font-weight: 500;
            line-height: 1.4;
            min-height: 80px;
            display: flex;
            align-items: center;
        }
        
        .option-btn:hover {
            background: #e9ecef;
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
        }
        
        .option-btn.selected {
            background: #007bff;
            color: white;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        }
        
        .option-btn.correct {
            background: #28a745;
            color: white;
            border-color: #1e7e34;
        }
        
        .option-btn.incorrect {
            background: #dc3545;
            color: white;
            border-color: #c82333;
        }
        
        .question-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .question-counter {
            font-size: 16px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .submit-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }
        
        .submit-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .next-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .next-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3);
        }
        
        .question-feedback {
            margin-top: 20px;
            padding: 20px;
            border-radius: 12px;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
            color: #155724;
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .question-feedback.incorrect {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border-color: #dc3545;
            color: #721c24;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 3000;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Start Screen Styles */
        .start-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            color: white;
            text-align: center;
        }
        
        .start-content {
            max-width: 600px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .start-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .start-description {
            font-size: 1.2rem;
            margin-bottom: 30px;
            line-height: 1.6;
            opacity: 0.9;
        }
        
        .start-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 20px 50px;
            font-size: 1.5rem;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .start-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .instructions {
            margin-top: 30px;
            font-size: 1rem;
            opacity: 0.8;
        }
        
        .instructions ul {
            text-align: left;
            margin-top: 15px;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
        /* Vehicle Selection Styles */
        .vehicle-selection-screen {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .vehicle-selection-container {
            max-width: 1000px;
            width: 100%;
        }
        
        .vehicle-header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        
        .vehicle-header h1 {
            font-size: 42px;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .vehicle-header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .vehicle-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .vehicle-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .vehicle-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .vehicle-icon {
            font-size: 100px;
            margin-bottom: 20px;
        }
        
        .vehicle-card h2 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .vehicle-card p {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .vehicle-play-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #2c3e50;
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            margin: 20px auto 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .vehicle-play-btn:hover {
            background: #34495e;
            transform: scale(1.1);
        }
        
        /* Ready to Drive Screen Styles */
        .ready-screen {
            display: none;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .ready-container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .ready-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .vehicle-icon-large {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .ready-header h1 {
            font-size: 36px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .ready-header p {
            color: #7f8c8d;
            font-size: 18px;
        }
        
        .info-box {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .info-box.green {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .info-box.yellow {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .info-box h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .info-box p {
            color: #495057;
            line-height: 1.6;
            margin: 0;
        }
        
        .ready-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .ready-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .ready-btn.back {
            background: #e9ecef;
            color: #495057;
        }
        
        .ready-btn.back:hover {
            background: #dee2e6;
        }
        
        .ready-btn.start {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .ready-btn.start:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 768px) {
            .vehicle-cards {
                grid-template-columns: 1fr;
            }
            
            .vehicle-header h1 {
                font-size: 32px;
            }
            
            .ready-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Vehicle Selection Screen -->
    <div id="vehicleSelection" class="vehicle-selection-screen">
        <div class="vehicle-selection-container">
            <div class="vehicle-header">
                <h1>Choose Your Vehicle</h1>
                <p>Select a vehicle type to begin your driving simulation</p>
            </div>
            
            <div class="vehicle-cards">
                <!-- Car Card -->
                <div class="vehicle-card" onclick="selectVehicle('car')">
                    <div class="vehicle-icon">🚗</div>
                    <h2>Car</h2>
                    <p>Standard passenger vehicle</p>
                    <div class="vehicle-play-btn">
                        <i class="fas fa-car"></i>
                    </div>
                </div>
                
                <!-- Motorcycle Card -->
                <div class="vehicle-card" onclick="selectVehicle('motorcycle')">
                    <div class="vehicle-icon">🏍️</div>
                    <h2>Motorcycle</h2>
                    <p>Two-wheeled motor vehicle</p>
                    <div class="vehicle-play-btn">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ready to Drive Screen -->
    <div id="readyScreen" class="ready-screen">
        <div class="ready-container">
            <div class="ready-header">
                <div class="vehicle-icon-large" id="selectedVehicleIcon">🚗</div>
                <h1>Ready to Drive?</h1>
                <p id="vehicleTypeText">You've selected a car. Let's review the simulation rules.</p>
            </div>
            
            <div class="info-box green">
                <h3>
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    How it works
                </h3>
                <p>You will encounter various road scenarios with traffic signs and situations. Make the correct driving decision to continue safely.</p>
            </div>
            
            <div class="info-box yellow">
                <h3>
                    <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                    Learning from mistakes
                </h3>
                <p>Wrong answers will show detailed feedback explaining the correct response. Use this as a learning opportunity to improve your road safety knowledge.</p>
            </div>
            
            <div class="ready-actions">
                <button class="ready-btn back" onclick="backToVehicleSelection()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="ready-btn start" onclick="startSimulation()">
                    Start Simulation <i class="fas fa-play"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Start Screen (Original) -->
    <div id="startScreen" class="start-screen" style="display: none;">
        <div class="start-content">
            <div class="start-title">🚗 Driving Simulation</div>
            <div class="start-description">
                Welcome to the driving simulation training! Test your driving knowledge through 5 challenging scenarios.
            </div>
            <button class="start-btn" onclick="continueToGame()">
                🎯 START SIMULATION
            </button>
            <div class="instructions">
                <strong>Instructions:</strong>
                <ul>
                    <li>Complete 5 driving scenarios</li>
                    <li>Use BRAKE and MOVE buttons to control your vehicle</li>
                    <li>Answer questions about traffic situations</li>
                    <li>Achieve 60% or higher to pass</li>
                    <li>Results will be saved to your profile</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen" style="display: none;">
        <div class="loading-spinner"></div>
        <div>Loading Driving Simulation...</div>
    </div>

    <!-- Game Container -->
    <div class="simulation-container" style="display: none;">
        <!-- Header -->
        <div class="game-header">
            <div class="game-title">🚗 Driving Simulation Training</div>
            <div class="game-stats">
                <div class="stat-item">
                    <div class="stat-label">SCORE</div>
                    <div class="stat-value" id="scoreDisplay">0</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">SCENARIOS</div>
                    <div class="stat-value" id="scenarioDisplay">0/5</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">TIME</div>
                    <div class="stat-value" id="timeDisplay">00:00</div>
                </div>
            </div>
        </div>

        <!-- Canvas Container -->
        <div class="game-canvas-container">
            <canvas id="gameCanvas"></canvas>
        </div>

        <!-- Controls -->
        <div class="game-controls">
            <div class="speed-indicator">
                Speed: <span id="speedDisplay">0</span> km/h
            </div>
            <button class="control-btn brake" id="brakeBtn">🛑 BRAKE</button>
            <button class="control-btn move" id="moveBtn">▶️ MOVE</button>
            <button class="control-btn" id="resetBtn">🔄 RESET</button>
        </div>
    </div>

    <!-- Question Modal -->
    <div id="questionModal" class="question-modal">
        <div class="question-content">
            <h3 class="question-title" id="questionTitle">Traffic Scenario Question</h3>
            <div class="question-text" id="questionText"></div>
            <div class="question-options" id="questionOptions"></div>
            <div class="question-actions">
                <div class="question-counter">Question <span id="questionNumber">1</span> of 5</div>
                <button class="submit-btn" id="submitAnswer" disabled>Submit Answer</button>
            </div>
            <div class="question-feedback" id="questionFeedback"></div>
        </div>
    </div>

    <!-- JavaScript Modules -->
    <script src="../assets/js/modules/message_system.js"></script>
    <script>
        // Global configuration and state
        window.SimulationConfig = {
            userId: <?php echo $user_id; ?>,
            vehicleType: 'NONE', // MUST be set by user selection - will error if not changed
            canvasWidth: window.innerWidth, // Use full window width
            canvasHeight: window.innerHeight, // Use full window height
            debug: false, // Set to true for debugging
            worldWidth: Math.max(window.innerWidth * 2, 4800), // Much wider world
            worldHeight: Math.max(window.innerHeight * 1.5, 2000), // Taller world
            cameraFollow: true,
            aspectRatio: window.innerWidth / window.innerHeight // Dynamic aspect ratio
        };
        
        console.log('🔧 SimulationConfig initialized with vehicleType: NONE (awaiting user selection)');
        
        // Note: Initialization now happens via startSimulation() function
        // when user clicks the START button, preventing duplicate initialization
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎮 Simulation page loaded - waiting for user to start...');
            console.log('🔍 Config check - Vehicle Type: ' + window.SimulationConfig.vehicleType);
            
            // Initialize message system only
            if (window.MessageSystem) {
                window.MessageSystem.init();
            }
        });
    </script>
    
    <!-- Load all simulation modules with cache busting -->
    <?php $timestamp = time(); ?>
    <script src="../assets/js/modules/world.js?v=<?php echo $timestamp; ?>"></script>
    <script src="../assets/js/modules/car.js?v=<?php echo $timestamp; ?>"></script>
    <script src="../assets/js/modules/gameEngine.js?v=<?php echo $timestamp; ?>"></script>
    <script src="../assets/js/modules/scenarios.js?v=<?php echo $timestamp; ?>"></script>
    <script src="../assets/js/modules/ui.js?v=<?php echo $timestamp; ?>"></script>
    <script src="../assets/js/modules/gameStats.js?v=<?php echo $timestamp; ?>"></script>
    <script src="../assets/js/simulation_main.js?v=<?php echo $timestamp; ?>"></script>
    
    <script>
        function startSimulation() {
            // Hide start screen
            document.getElementById('startScreen').style.display = 'none';
            
            // Show loading screen
            document.getElementById('loadingScreen').style.display = 'flex';
            
            // Start the simulation after a brief delay
            setTimeout(() => {
                document.getElementById('loadingScreen').style.display = 'none';
                document.querySelector('.simulation-container').style.display = 'block';
                
                // Initialize the simulation (SINGLE POINT OF INITIALIZATION)
                if (window.SimulationMain && !window.SimulationMain.initialized) {
                    console.log('🎮 Starting Driving Simulation System...');
                    window.SimulationMain.init();
                } else if (window.SimulationMain && window.SimulationMain.initialized) {
                    console.log('⚠️ Simulation already initialized, skipping duplicate init');
                } else {
                    console.error('❌ SimulationMain not available');
                }
            }, 2000); // 2 second loading delay
        }
        
        // Vehicle Selection Functions
        let selectedVehicleType = null;
        
        function selectVehicle(vehicleType) {
            selectedVehicleType = vehicleType;
            
            // CRITICAL: Set the vehicle type in SimulationConfig IMMEDIATELY
            window.SimulationConfig.vehicleType = vehicleType;
            console.log('✅ Vehicle selected: ' + vehicleType);
            console.log('✅ SimulationConfig.vehicleType set to: ' + window.SimulationConfig.vehicleType);
            
            // Update ready screen with selected vehicle
            const icon = vehicleType === 'car' ? '🚗' : '🏍️';
            const name = vehicleType === 'car' ? 'car' : 'motorcycle';
            
            document.getElementById('selectedVehicleIcon').textContent = icon;
            document.getElementById('vehicleTypeText').textContent = 
                `You've selected a ${name}. Let's review the simulation rules.`;
            
            // Hide vehicle selection, show ready screen
            document.getElementById('vehicleSelection').style.display = 'none';
            document.getElementById('readyScreen').style.display = 'flex';
        }
        
        function backToVehicleSelection() {
            // Hide ready screen, show vehicle selection
            document.getElementById('readyScreen').style.display = 'none';
            document.getElementById('vehicleSelection').style.display = 'flex';
        }
        
        function startSimulation() {
            // Hide ready screen, show start screen (original simulation start)
            document.getElementById('readyScreen').style.display = 'none';
            document.getElementById('startScreen').style.display = 'flex';
        }
        
        function continueToGame() {
            console.log('🎮 continueToGame() called');
            console.log('🚗 Selected Vehicle Type: ' + selectedVehicleType);
            console.log('📋 SimulationConfig.vehicleType: ' + window.SimulationConfig.vehicleType);
            
            // CRITICAL VALIDATION: Ensure vehicle was actually selected
            if (!selectedVehicleType || window.SimulationConfig.vehicleType === 'NONE') {
                alert('❌ ERROR: No vehicle selected! Please refresh and select a vehicle.');
                console.error('❌ CRITICAL: Vehicle not selected!');
                console.error('selectedVehicleType:', selectedVehicleType);
                console.error('SimulationConfig.vehicleType:', window.SimulationConfig.vehicleType);
                return; // Don't continue without vehicle selection
            }
            
            // This is the original startSimulation function
            document.getElementById('startScreen').style.display = 'none';
            document.getElementById('loadingScreen').style.display = 'flex';
            
            setTimeout(() => {
                document.getElementById('loadingScreen').style.display = 'none';
                document.querySelector('.simulation-container').style.display = 'block';
                
                console.log('🔍 Before init - SimulationConfig.vehicleType: ' + window.SimulationConfig.vehicleType);
                
                // Initialize the simulation (SINGLE POINT OF INITIALIZATION)
                if (window.SimulationMain && !window.SimulationMain.initialized) {
                    console.log('🎮 Starting Driving Simulation System with vehicle: ' + window.SimulationConfig.vehicleType);
                    window.SimulationMain.init();
                } else if (window.SimulationMain && window.SimulationMain.initialized) {
                    console.log('⚠️ Simulation already initialized, skipping duplicate init');
                } else {
                    console.error('❌ SimulationMain not available');
                }
            }, 2000);
        }
    </script>
</body>
</html>
