// Game Engine Module
// Handles core game loop, rendering, camera, and game state management

const GameEngine = {
    // Engine state
    canvas: null,
    ctx: null,
    miniMapCanvas: null,
    miniMapCtx: null,
    gameRunning: false,
    animationId: null,
    lastTime: 0,
    isCarStopped: false,
    currentScenario: null,
    scenarioIndex: 0,
    
    // Camera system for following car
    camera: {
        x: 0,
        y: 0,
        targetX: 0,
        targetY: 0,
        smoothing: 0.1
    },
    
    // Control states
    keys: {},
    buttonStates: {
        forward: false,
        reverse: false,
        left: false,
        right: false
    },

    // Initialize the game engine
    init() {
        console.log('🎮 Initializing Game Engine...');
        
        this.setupCanvas();
        this.setupEventListeners();
        this.resizeCanvas();
        
        console.log('✅ Game Engine initialized successfully');
    },

    // Setup canvas elements
    setupCanvas() {
        this.canvas = document.getElementById('simulationCanvas');
        this.miniMapCanvas = document.getElementById('miniMapCanvas');
        
        if (!this.canvas) {
            console.error('❌ Main canvas not found');
            return;
        }
        
        this.ctx = this.canvas.getContext('2d');
        
        if (this.miniMapCanvas) {
            this.miniMapCtx = this.miniMapCanvas.getContext('2d');
        }
        
        console.log('🖼️ Canvas setup complete');
    },

    // Setup event listeners
    setupEventListeners() {
        // Window resize
        window.addEventListener('resize', () => this.resizeCanvas());
        
        // Note: Keyboard controls are handled by the main simulation controller
        // to avoid duplicate event listeners
    },

    // Resize canvas to fit window
    resizeCanvas() {
        if (this.canvas) {
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
        }
    },

    // Start the simulation
    startSimulation() {
        console.log('🚀 Starting simulation...');
        
        this.gameRunning = true;
        this.scenarioIndex = 0;
        this.currentScenario = null;
        this.isCarStopped = false;
        
        // Reset car position
        if (typeof CarModule !== 'undefined') {
            CarModule.reset();
        }
        
        // Start statistics tracking
        if (typeof GameStats !== 'undefined') {
            GameStats.startSession();
        }
        
        // Start game loop
        this.lastTime = performance.now();
        this.gameLoop(this.lastTime);
        
        // Show initial status
        if (typeof UIModule !== 'undefined') {
            UIModule.showStatus('🚗 Simulation Started! Drive safely and watch for scenarios.', 3000);
        }
    },

    // Main game loop
    gameLoop(currentTime) {
        if (!this.gameRunning) return;
        
        const deltaTime = currentTime - this.lastTime;
        this.lastTime = currentTime;
        
        // Update game systems
        this.update(deltaTime);
        this.render();
        
        // Continue loop
        this.animationId = requestAnimationFrame((time) => this.gameLoop(time));
    },

    // Update all game systems
    update(deltaTime) {
        // Update car physics
        if (typeof CarModule !== 'undefined' && typeof WorldModule !== 'undefined') {
            const worldDims = WorldModule.getDimensions();
            CarModule.update(window.keys || {}, window.buttonStates || {}, this.isCarStopped, worldDims.width, worldDims.height);
            
            // Update speed display
            if (typeof UIModule !== 'undefined') {
                UIModule.updateSpeedDisplay(CarModule.getSpeedKmh());
            }
        }
        
        // Update camera to follow car
        this.updateCamera();
        
        // Check for scenario triggers
        this.checkScenarioTriggers();
        
        // Update UI
        if (typeof UIModule !== 'undefined') {
            UIModule.updateStatsDisplay();
        }
    },

    // Update camera position
    updateCamera() {
        if (typeof CarModule === 'undefined') return;
        
        const carProps = CarModule.getProperties();
        
        // Set camera target to car position (centered on screen)
        this.camera.targetX = carProps.x - this.canvas.width / 2;
        this.camera.targetY = carProps.y - this.canvas.height / 2;
        
        // Smooth camera movement
        this.camera.x += (this.camera.targetX - this.camera.x) * this.camera.smoothing;
        this.camera.y += (this.camera.targetY - this.camera.y) * this.camera.smoothing;
        
        // Keep camera within world bounds
        if (typeof WorldModule !== 'undefined') {
            const worldDims = WorldModule.getDimensions();
            this.camera.x = Math.max(0, Math.min(worldDims.width - this.canvas.width, this.camera.x));
            this.camera.y = Math.max(0, Math.min(worldDims.height - this.canvas.height, this.camera.y));
        }
    },

    // Check if car triggers any scenarios
    checkScenarioTriggers() {
        if (this.currentScenario || this.isCarStopped) return; // Already in scenario
        if (typeof ScenarioManager === 'undefined') return;
        
        // Simple trigger: random chance based on distance traveled
        if (typeof CarModule !== 'undefined') {
            const carProps = CarModule.getProperties();
            
            // Trigger scenario every ~500 units of movement
            const triggerChance = Math.abs(carProps.speed) * 0.001;
            
            if (Math.random() < triggerChance && ScenarioManager.getCurrentScenario()) {
                this.triggerScenario();
            }
        }
    },

    // Trigger a scenario
    triggerScenario() {
        if (typeof ScenarioManager === 'undefined') return;
        
        this.currentScenario = ScenarioManager.getCurrentScenario();
        if (!this.currentScenario) return;
        
        console.log('🎯 Triggering scenario:', this.currentScenario.title);
        
        // Stop the car
        this.isCarStopped = true;
        if (typeof CarModule !== 'undefined') {
            CarModule.stop();
        }
        
        // Show scenario panel
        if (typeof UIModule !== 'undefined') {
            UIModule.updateScenarioPanel(this.currentScenario, this.scenarioIndex);
            UIModule.showStatus(`⚠️ SCENARIO: ${this.currentScenario.title}`, 3000);
        }
    },

    // Continue after scenario completion
    continueAfterScenario() {
        if (typeof ScenarioManager === 'undefined') return;
        
        // Move to next scenario
        ScenarioManager.getNextScenario();
        this.scenarioIndex++;
        
        // Reset scenario state
        this.currentScenario = null;
        this.isCarStopped = false;
        
        // Check if simulation is complete
        if (ScenarioManager.isCompleted()) {
            this.endSimulation();
        }
    },

    // End the simulation
    endSimulation() {
        console.log('🏁 Simulation completed');
        
        this.gameRunning = false;
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        
        // Show completion screen
        if (typeof UIModule !== 'undefined') {
            UIModule.showEndSimulation();
        }
        
        // Save statistics
        if (typeof GameStats !== 'undefined') {
            GameStats.saveToDatabase().then(result => {
                if (result.success) {
                    console.log('✅ Final statistics saved');
                } else {
                    console.error('❌ Failed to save final statistics');
                }
            });
        }
    },

    // Render all game elements
    render() {
        if (!this.ctx) return;
        
        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Set background
        this.ctx.fillStyle = '#2c3e50';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Render world
        if (typeof WorldModule !== 'undefined') {
            WorldModule.render(this.ctx, this.camera);
        }
        
        // Render car
        if (typeof CarModule !== 'undefined') {
            CarModule.render(this.ctx, this.camera);
        }
        
        // Update minimap
        if (typeof UIModule !== 'undefined' && typeof WorldModule !== 'undefined' && typeof CarModule !== 'undefined') {
            UIModule.updateMiniMap(CarModule, this.camera, WorldModule);
        }
    },

    // Stop the simulation
    stopSimulation() {
        console.log('⏹️ Stopping simulation...');
        
        this.gameRunning = false;
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        
        // Clean up
        this.cleanup();
    },

    // Cleanup resources
    cleanup() {
        // Clear timeouts and intervals
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
        
        // Clean up UI
        if (typeof UIModule !== 'undefined') {
            UIModule.cleanup();
        }
        
        console.log('🧹 Game engine cleaned up');
    },

    // Get current game state
    getGameState() {
        return {
            running: this.gameRunning,
            scenarioIndex: this.scenarioIndex,
            isCarStopped: this.isCarStopped,
            currentScenario: this.currentScenario,
            camera: { ...this.camera }
        };
    },

    // Pause/Resume simulation
    togglePause() {
        this.gameRunning = !this.gameRunning;
        
        if (this.gameRunning) {
            this.lastTime = performance.now();
            this.gameLoop(this.lastTime);
            console.log('▶️ Simulation resumed');
        } else {
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
            }
            console.log('⏸️ Simulation paused');
        }
    }
};

// Global function for UI callback
window.continueAfterScenario = () => {
    GameEngine.continueAfterScenario();
};

// Export to global window object for browser use
window.GameEngineModule = GameEngine;

// Export for use in other modules (Node.js compatibility)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GameEngine;
}
