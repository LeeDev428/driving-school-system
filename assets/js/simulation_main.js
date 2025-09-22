/**
 * Main Simulation Controller
 * Coordinates all modules and manages the simulation lifecycle
 */

const SimulationMain = {
    // Core components
    canvas: null,
    ctx: null,
    gameLoop: null,
    isRunning: false,
    
    // Timing
    lastTime: 0,
    deltaTime: 0,
    
    // Game state
    currentScore: 0,
    scenariosCompleted: 0,
    totalScenarios: 5,
    startTime: Date.now(),
    
    // Modules
    modules: {},
    
    /**
     * Initialize the entire simulation system
     */
    init() {
        console.log('🚀 Initializing Driving Simulation...');
        
        try {
            this.setupCanvas();
            this.initializeModules();
            this.setupEventListeners();
            this.startSimulation();
            
            console.log('✅ Simulation initialized successfully');
        } catch (error) {
            console.error('❌ Failed to initialize simulation:', error);
            this.showError('Failed to initialize simulation. Please refresh the page.');
        }
    },
    
    /**
     * Setup the main game canvas
     */
    setupCanvas() {
        this.canvas = document.getElementById('gameCanvas');
        if (!this.canvas) {
            throw new Error('Game canvas not found');
        }
        
        this.ctx = this.canvas.getContext('2d');
        
        // Set canvas size
        this.canvas.width = window.SimulationConfig.canvasWidth;
        this.canvas.height = window.SimulationConfig.canvasHeight;
        
        // Handle window resize
        window.addEventListener('resize', () => this.handleResize());
        
        console.log(`📱 Canvas setup: ${this.canvas.width}x${this.canvas.height}`);
    },
    
    /**
     * Initialize all game modules in correct order
     */
    initializeModules() {
        console.log('🔧 Loading game modules...');
        
        // Check if modules are available
        const requiredModules = ['WorldModule', 'CarModule', 'GameEngine', 'ScenariosModule', 'UIModule', 'GameStats'];
        const missingModules = [];
        
        requiredModules.forEach(moduleName => {
            if (window[moduleName]) {
                this.modules[moduleName] = window[moduleName];
                console.log(`✅ ${moduleName} loaded`);
            } else {
                missingModules.push(moduleName);
                console.warn(`⚠️ ${moduleName} not found`);
            }
        });
        
        if (missingModules.length > 0) {
            console.warn('Some modules are missing, simulation may have limited functionality');
        }
        
        // Initialize modules in dependency order
        this.initModulesInOrder();
    },
    
    /**
     * Initialize modules in proper dependency order
     */
    initModulesInOrder() {
        try {
            // 1. World (no dependencies)
            if (this.modules.WorldModule) {
                this.modules.WorldModule.init();
            }
            
            // 2. Car (depends on world for collision)
            if (this.modules.CarModule) {
                this.modules.CarModule.init();
            }
            
            // 3. Scenarios (depends on world)
            if (this.modules.ScenariosModule) {
                this.modules.ScenariosModule.init();
            }
            
            // 4. UI (depends on scenarios for questions)
            if (this.modules.UIModule) {
                this.modules.UIModule.init();
            }
            
            // 5. Game Stats (depends on all modules)
            if (this.modules.GameStats) {
                this.modules.GameStats.init();
            }
            
            // 6. Game Engine (orchestrates everything)
            if (this.modules.GameEngine) {
                this.modules.GameEngine.init(this.canvas, this.ctx);
            }
            
        } catch (error) {
            console.error('Error initializing modules:', error);
            throw error;
        }
    },
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Control buttons
        const brakeBtn = document.getElementById('brakeBtn');
        const moveBtn = document.getElementById('moveBtn');
        const resetBtn = document.getElementById('resetBtn');
        
        if (brakeBtn) {
            brakeBtn.addEventListener('click', () => this.handleBrake());
        }
        
        if (moveBtn) {
            moveBtn.addEventListener('click', () => this.handleMove());
        }
        
        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.handleReset());
        }
        
        // Keyboard controls
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));
        document.addEventListener('keyup', (e) => this.handleKeyUp(e));
        
        // Canvas interactions
        this.canvas.addEventListener('click', (e) => this.handleCanvasClick(e));
        
        console.log('🎮 Event listeners setup complete');
    },
    
    /**
     * Start the simulation game loop
     */
    startSimulation() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.lastTime = performance.now();
        
        console.log('▶️ Starting simulation loop...');
        this.gameLoop = requestAnimationFrame((time) => this.update(time));
        
        // Start UI updates
        this.startUIUpdates();
    },
    
    /**
     * Stop the simulation
     */
    stopSimulation() {
        if (!this.isRunning) return;
        
        this.isRunning = false;
        if (this.gameLoop) {
            cancelAnimationFrame(this.gameLoop);
            this.gameLoop = null;
        }
        
        console.log('⏸️ Simulation stopped');
    },
    
    /**
     * Main game loop update function
     */
    update(currentTime) {
        if (!this.isRunning) return;
        
        // Calculate delta time
        this.deltaTime = Math.min((currentTime - this.lastTime) / 1000, 0.1); // Cap at 100ms
        this.lastTime = currentTime;
        
        try {
            // Update all modules
            this.updateModules();
            
            // Render everything
            this.render();
            
            // Check game state
            this.checkGameConditions();
            
        } catch (error) {
            console.error('Error in game loop:', error);
            this.stopSimulation();
            this.showError('Game loop error. Please refresh the page.');
            return;
        }
        
        // Continue the loop
        this.gameLoop = requestAnimationFrame((time) => this.update(time));
    },
    
    /**
     * Update all modules
     */
    updateModules() {
        // Update in logical order
        if (this.modules.CarModule) {
            this.modules.CarModule.update(this.deltaTime);
        }
        
        if (this.modules.WorldModule) {
            this.modules.WorldModule.update(this.deltaTime);
        }
        
        if (this.modules.GameEngine) {
            this.modules.GameEngine.update(this.deltaTime);
        }
        
        if (this.modules.ScenariosModule) {
            this.modules.ScenariosModule.update(this.deltaTime);
        }
    },
    
    /**
     * Render all visual elements
     */
    render() {
        // Clear canvas
        this.ctx.fillStyle = '#2c3e50';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Get camera from game engine
        const camera = this.modules.GameEngine ? this.modules.GameEngine.getCamera() : { x: 0, y: 0 };
        
        // Render in proper order (back to front)
        if (this.modules.WorldModule) {
            this.modules.WorldModule.render(this.ctx, camera);
        }
        
        if (this.modules.CarModule) {
            this.modules.CarModule.render(this.ctx, camera);
        }
        
        if (this.modules.UIModule) {
            this.modules.UIModule.renderGame(this.ctx, camera);
        }
        
        // Debug info
        if (window.SimulationConfig.debug) {
            this.renderDebugInfo();
        }
    },
    
    /**
     * Check various game conditions and triggers
     */
    checkGameConditions() {
        // Check if all scenarios are completed
        if (this.scenariosCompleted >= this.totalScenarios) {
            this.handleSimulationComplete();
        }
        
        // Check for scenario triggers
        if (this.modules.ScenariosModule && this.modules.CarModule) {
            const carPosition = this.modules.CarModule.getPosition();
            const triggeredScenarios = this.modules.ScenariosModule.checkTriggers(carPosition);
            
            triggeredScenarios.forEach(scenario => {
                this.handleScenarioTriggered(scenario);
            });
        }
    },
    
    /**
     * Handle scenario being triggered
     */
    handleScenarioTriggered(scenario) {
        console.log('🎯 Scenario triggered:', scenario.id);
        
        // Pause the simulation
        this.stopSimulation();
        
        // Show the question
        if (this.modules.UIModule) {
            this.modules.UIModule.showQuestion(scenario, (answer, correct) => {
                this.handleQuestionAnswered(scenario, answer, correct);
            });
        }
    },
    
    /**
     * Handle question being answered
     */
    handleQuestionAnswered(scenario, answer, correct) {
        console.log(`📝 Question answered: ${correct ? 'Correct' : 'Incorrect'}`);
        
        // Update score
        if (correct) {
            this.currentScore += 20; // 20 points per correct answer
        }
        
        // Mark scenario as completed
        if (this.modules.ScenariosModule) {
            this.modules.ScenariosModule.markCompleted(scenario.id);
        }
        
        this.scenariosCompleted++;
        
        // Update UI
        this.updateScoreDisplay();
        
        // Resume simulation after a delay
        setTimeout(() => {
            this.startSimulation();
        }, 2000);
        
        // Save progress to database
        if (this.modules.GameStats) {
            this.modules.GameStats.saveProgress({
                scenarioId: scenario.id,
                answer: answer,
                correct: correct,
                score: this.currentScore
            });
        }
    },
    
    /**
     * Handle simulation completion
     */
    handleSimulationComplete() {
        console.log('🏁 Simulation complete!');
        
        this.stopSimulation();
        
        // Calculate final results
        const finalResults = {
            score: this.currentScore,
            totalTime: Date.now() - this.startTime,
            scenariosCompleted: this.scenariosCompleted,
            accuracy: (this.currentScore / (this.totalScenarios * 20)) * 100
        };
        
        // Save final results
        if (this.modules.GameStats) {
            this.modules.GameStats.saveFinalResults(finalResults);
        }
        
        // Show completion screen
        if (this.modules.UIModule) {
            this.modules.UIModule.showCompletionScreen(finalResults);
        }
    },
    
    /**
     * Update score display
     */
    updateScoreDisplay() {
        const scoreElement = document.getElementById('scoreDisplay');
        const scenarioElement = document.getElementById('scenarioDisplay');
        
        if (scoreElement) {
            scoreElement.textContent = this.currentScore;
        }
        
        if (scenarioElement) {
            scenarioElement.textContent = `${this.scenariosCompleted}/${this.totalScenarios}`;
        }
    },
    
    /**
     * Start UI updates (timer, etc.)
     */
    startUIUpdates() {
        setInterval(() => {
            this.updateTimer();
            this.updateSpeedDisplay();
        }, 100);
    },
    
    /**
     * Update timer display
     */
    updateTimer() {
        const timeElement = document.getElementById('timeDisplay');
        if (!timeElement) return;
        
        const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
        const minutes = Math.floor(elapsed / 60);
        const seconds = elapsed % 60;
        
        timeElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    },
    
    /**
     * Update speed display
     */
    updateSpeedDisplay() {
        const speedElement = document.getElementById('speedDisplay');
        if (!speedElement || !this.modules.CarModule) return;
        
        const speed = Math.round(this.modules.CarModule.getCurrentSpeed() || 0);
        speedElement.textContent = speed;
    },
    
    /**
     * Handle control button events
     */
    handleBrake() {
        if (this.modules.CarModule) {
            this.modules.CarModule.emergencyStop();
        }
    },
    
    handleMove() {
        // Toggle play/pause
        if (this.isRunning) {
            this.stopSimulation();
        } else {
            this.startSimulation();
        }
    },
    
    handleReset() {
        console.log('🔄 Resetting simulation...');
        
        // Reset all modules
        Object.values(this.modules).forEach(module => {
            if (module.reset) {
                module.reset();
            }
        });
        
        // Reset game state
        this.currentScore = 0;
        this.scenariosCompleted = 0;
        this.startTime = Date.now();
        
        // Update displays
        this.updateScoreDisplay();
        
        // Restart if not running
        if (!this.isRunning) {
            this.startSimulation();
        }
    },
    
    /**
     * Handle keyboard events
     */
    handleKeyDown(e) {
        // Pass to car module for vehicle controls
        if (this.modules.CarModule && this.modules.CarModule.handleKeyDown) {
            this.modules.CarModule.handleKeyDown(e);
        }
        
        // Global controls
        switch(e.key.toLowerCase()) {
            case 'p':
                // Pause/unpause
                if (this.isRunning) {
                    this.stopSimulation();
                } else {
                    this.startSimulation();
                }
                e.preventDefault();
                break;
                
            case 'r':
                // Reset
                this.handleReset();
                e.preventDefault();
                break;
        }
    },
    
    handleKeyUp(e) {
        if (this.modules.CarModule && this.modules.CarModule.handleKeyUp) {
            this.modules.CarModule.handleKeyUp(e);
        }
    },
    
    /**
     * Handle canvas click events
     */
    handleCanvasClick(e) {
        const rect = this.canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        console.log('Canvas clicked at:', x, y);
    },
    
    /**
     * Handle window resize
     */
    handleResize() {
        const newWidth = window.innerWidth;
        const newHeight = window.innerHeight - 120;
        
        this.canvas.width = newWidth;
        this.canvas.height = newHeight;
        
        window.SimulationConfig.canvasWidth = newWidth;
        window.SimulationConfig.canvasHeight = newHeight;
        
        console.log(`📱 Canvas resized: ${newWidth}x${newHeight}`);
    },
    
    /**
     * Render debug information
     */
    renderDebugInfo() {
        this.ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
        this.ctx.fillRect(10, 10, 300, 150);
        
        this.ctx.fillStyle = '#00FF00';
        this.ctx.font = '14px monospace';
        
        const debugInfo = [
            `FPS: ${Math.round(1 / this.deltaTime)}`,
            `Score: ${this.currentScore}`,
            `Scenarios: ${this.scenariosCompleted}/${this.totalScenarios}`,
            `Running: ${this.isRunning ? 'Yes' : 'No'}`,
            `Modules: ${Object.keys(this.modules).length}`,
            `Canvas: ${this.canvas.width}x${this.canvas.height}`
        ];
        
        debugInfo.forEach((info, index) => {
            this.ctx.fillText(info, 20, 30 + index * 20);
        });
    },
    
    /**
     * Show error message
     */
    showError(message) {
        console.error('💥 Simulation Error:', message);
        alert(`Simulation Error: ${message}`);
    },
    
    /**
     * Get current simulation state
     */
    getState() {
        return {
            score: this.currentScore,
            scenariosCompleted: this.scenariosCompleted,
            isRunning: this.isRunning,
            startTime: this.startTime
        };
    }
};

// Make it globally available
window.SimulationMain = SimulationMain;

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('🎮 DOM ready, simulation will auto-initialize...');
    });
} else {
    console.log('🎮 DOM already ready, simulation available for manual initialization');
}
