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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driving Simulation - Driving School System</title>
    <link rel="stylesheet" href="../assets/css/simulation.css">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            background: #000;
            font-family: 'Arial', sans-serif;
            overflow: hidden;
            width: 100%;
            height: 100%;
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
            max-width: 600px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .question-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .question-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .option-btn {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            padding: 15px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            font-size: 16px;
        }
        
        .option-btn:hover {
            background: #e9ecef;
            border-color: #007bff;
        }
        
        .option-btn.selected {
            background: #007bff;
            color: white;
            border-color: #0056b3;
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
        }
        
        .submit-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
        
        .submit-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .question-feedback {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            display: none;
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
    </style>
</head>
<body>
    <!-- Start Screen -->
    <div id="startScreen" class="start-screen">
        <div class="start-content">
            <div class="start-title">🚗 Driving Simulation</div>
            <div class="start-description">
                Welcome to the driving simulation training! Test your driving knowledge through 5 challenging scenarios.
            </div>
            <button class="start-btn" onclick="startSimulation()">
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
            <div id="questionText"></div>
            <div class="question-options" id="questionOptions"></div>
            <div class="question-actions">
                <div>Question <span id="questionNumber">1</span> of 5</div>
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
            canvasWidth: window.innerWidth, // Use full window width
            canvasHeight: window.innerHeight, // Use full window height
            debug: false, // Set to true for debugging
            worldWidth: Math.max(window.innerWidth * 2, 4800), // Much wider world
            worldHeight: Math.max(window.innerHeight * 1.5, 2000), // Taller world
            cameraFollow: true,
            aspectRatio: window.innerWidth / window.innerHeight // Dynamic aspect ratio
        };
        
        // Initialize simulation when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎮 Starting Driving Simulation System...');
            
            // Initialize message system first
            if (window.MessageSystem) {
                window.MessageSystem.init();
            }
            
            setTimeout(() => {
                document.getElementById('loadingScreen').style.display = 'none';
                document.querySelector('.simulation-container').style.display = 'flex';
                
                // Initialize the simulation
                if (window.SimulationMain) {
                    window.SimulationMain.init();
                }
            }, 2000);
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
                
                // Initialize the game if not already initialized
                if (typeof initializeGame === 'function') {
                    initializeGame();
                }
            }, 2000); // 2 second loading delay
        }
    </script>
</body>
</html>
