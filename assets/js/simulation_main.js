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
    initialized: false, // Flag to prevent duplicate initialization
    
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
        // Prevent duplicate initialization
        if (this.initialized) {
            console.warn('⚠️ SimulationMain already initialized, skipping duplicate init');
            return;
        }
        
        console.log('🚀 Initializing Driving Simulation...');
        
        try {
            this.setupCanvas();
            this.initializeModules();
            this.setupEventListeners();
            this.startSimulation();
            
            // Mark as initialized to prevent duplicates
            this.initialized = true;
            
            console.log('✅ Simulation initialized successfully');
        } catch (error) {
            console.error('❌ Failed to initialize simulation:', error);
            this.showError('Failed to initialize simulation. Please refresh the page.');
        }
    },
    
    /**
     * Setup the main game canvas with forced fullscreen
     */
    setupCanvas() {
        this.canvas = document.getElementById('gameCanvas');
        if (!this.canvas) {
            throw new Error('Game canvas not found');
        }
        
        this.ctx = this.canvas.getContext('2d');
        
        // Use actual viewport dimensions
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // Set canvas resolution to match viewport
        this.canvas.width = viewportWidth;
        this.canvas.height = viewportHeight;
        
        // Force CSS to cover entire viewport without gaps
        this.canvas.style.position = 'fixed';
        this.canvas.style.top = '0';
        this.canvas.style.left = '0';
        this.canvas.style.width = '100vw';
        this.canvas.style.height = '100vh';
        this.canvas.style.zIndex = '999';
        this.canvas.style.objectFit = 'cover';
        
        // Update config for TRUE FULLSCREEN world that utilizes entire viewport
        window.SimulationConfig.canvasWidth = viewportWidth;
        window.SimulationConfig.canvasHeight = viewportHeight;
        window.SimulationConfig.worldWidth = Math.max(viewportWidth * 2.0, 4800); // Increased multiplier
        window.SimulationConfig.worldHeight = Math.max(viewportHeight * 1.8, 2400); // Increased multiplier
        
        // Handle window resize to maintain fullscreen
        window.addEventListener('resize', () => this.handleResize());
        
        console.log(`📱 Canvas setup (TRUE FULLSCREEN): ${this.canvas.width}x${this.canvas.height} (World: ${window.SimulationConfig.worldWidth}x${window.SimulationConfig.worldHeight})`);
        console.log(`🔍 DEBUG: Viewport=${viewportWidth}x${viewportHeight}, Canvas CSS=${this.canvas.style.width}x${this.canvas.style.height}`);
        console.log(`🔍 DEBUG: Canvas actual size=${this.canvas.width}x${this.canvas.height}, Canvas position=${this.canvas.style.position}`);
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
            // Update all modules with proper error handling
            this.updateModules();
            
            // Render everything
            this.render();
            
            // Check game state
            this.checkGameConditions();
            
        } catch (error) {
            console.error('Error in game loop:', error);
            console.error('Error stack:', error.stack);
            
            // Don't stop simulation immediately, try to continue
            console.warn('⚠️ Attempting to continue simulation despite error...');
            
            // Only stop if we get multiple consecutive errors
            if (!this.errorCount) this.errorCount = 0;
            this.errorCount++;
            
            if (this.errorCount > 5) {
                this.stopSimulation();
                this.showError('Multiple game loop errors detected. Please refresh the page.');
                return;
            }
        }
        
        // Continue the loop
        this.gameLoop = requestAnimationFrame((time) => this.update(time));
    },
    
    /**
     * Update all modules with individual error handling
     */
    updateModules() {
        // Update in logical order with individual try-catch blocks
        try {
            if (this.modules.CarModule) {
                this.modules.CarModule.update(this.deltaTime);
            }
        } catch (error) {
            console.error('Error updating CarModule:', error);
        }
        
        try {
            if (this.modules.WorldModule) {
                this.modules.WorldModule.update(this.deltaTime);
            }
        } catch (error) {
            console.error('Error updating WorldModule:', error);
        }
        
        try {
            if (this.modules.GameEngine) {
                this.modules.GameEngine.update(this.deltaTime);
            }
        } catch (error) {
            console.error('Error updating GameEngine:', error);
        }
        
        try {
            if (this.modules.ScenariosModule) {
                this.modules.ScenariosModule.update(this.deltaTime);
            }
        } catch (error) {
            console.error('Error updating ScenariosModule:', error);
        }
    },
    
    /**
     * Render all visual elements
     */
    render() {
        // Clear canvas with background
        this.ctx.fillStyle = '#2c3e50';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Get camera from game engine (no zoom applied)
        const camera = this.modules.GameEngine ? this.modules.GameEngine.getCamera() : { x: 0, y: 0 };
        
        // Use camera directly without zoom transformation
        const adjustedCamera = {
            x: camera.x,
            y: camera.y,
            zoom: 1.0 // Always 1:1 scale for fullscreen display
        };
        
        // Render in proper order (back to front) - no transformation needed
        if (this.modules.WorldModule) {
            this.modules.WorldModule.render(this.ctx, adjustedCamera);
        }
        
        if (this.modules.CarModule) {
            this.modules.CarModule.render(this.ctx, adjustedCamera);
        }
        
        if (this.modules.UIModule) {
            this.modules.UIModule.renderGame(this.ctx, adjustedCamera);
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
        
        // Scenarios are now triggered by time-based system in GameEngine
        // No need for position-based checks here
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
            this.modules.UIModule.showScenarioQuestion(scenario);
        }
    },
    
    /**
     * Handle question being answered
     */
    handleQuestionAnswered(scenario, answer, correct) {
        console.log(`📝 Question answered: ${correct ? 'Correct' : 'Incorrect'}`);
        
        // Validate scenario parameter to prevent null reading errors
        if (!scenario || !scenario.id) {
            console.error('❌ Invalid scenario passed to handleQuestionAnswered:', scenario);
            return;
        }
        
        // Update score
        if (correct) {
            this.currentScore += 20; // 20 points per correct answer
        }
        
        // NOTE: markCompleted is now handled by UI.js to prevent duplicates
        // UI.js already handles: markCompleted + saveScenarioResult
        
        this.scenariosCompleted++;
        
        // Update UI
        this.updateScoreDisplay();
        
        // Resume simulation after a delay
        setTimeout(() => {
            this.startSimulation();
        }, 2000);
        
        // NOTE: Removed duplicate save call - UI.js now handles all database saves
        console.log('✅ Question processing complete, UI.js handles database save');
    },
    
    /**
     * Handle simulation completion - show proceed modal instead of automatic save
     */
    async handleSimulationComplete() {
        console.log('🏁 All 5 scenarios completed!');
        
        this.stopSimulation();
        
        // Calculate final results
        const finalResults = {
            score: this.currentScore,
            totalTime: Date.now() - this.startTime,
            scenariosCompleted: this.scenariosCompleted,
            accuracy: (this.currentScore / (this.totalScenarios * 20)) * 100
        };
        
        console.log('📊 Final results calculated:', finalResults);
        console.log('💾 Data is stored in localStorage, waiting for user to proceed...');
        
        // Show completion screen with Proceed button (no automatic database save)
        if (this.modules.UIModule) {
            this.modules.UIModule.showCompletionScreen(finalResults);
        } else {
            console.error('❌ UIModule not available for completion screen');
            // Fallback - show basic alert
            alert(`Simulation Complete!\nScore: ${finalResults.score}\nScenarios: ${finalResults.scenariosCompleted}/5\nClick OK to continue.`);
            window.location.href = 'simulation_result.php';
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
        const newHeight = window.innerHeight;
        
        this.canvas.width = newWidth;
        this.canvas.height = newHeight;
        
        // Update styles to maintain fullscreen coverage
        this.canvas.style.width = '100vw';
        this.canvas.style.height = '100vh';
        
        window.SimulationConfig.canvasWidth = newWidth;
        window.SimulationConfig.canvasHeight = newHeight;
        window.SimulationConfig.worldWidth = Math.max(newWidth * 1.5, 3200);
        window.SimulationConfig.worldHeight = Math.max(newHeight * 1.2, 1600);
        
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
