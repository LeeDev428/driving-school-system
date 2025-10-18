/**
 * Game Engine Module - Core Simulation Logic
 * Handles camera, collision detection, physics updates, and scenario triggering
 */

const GameEngine = {
    // Canvas and rendering
    canvas: null,
    ctx: null,
    camera: { x: 0, y: 0, zoom: 1.0 }, // No zoom by default for fullscreen view
    
    // Timing
    lastFrameTime: 0,
    
    // Camera properties
    cameraSmoothing: 0.08,
    cameraOffset: { x: 0, y: 0 },
    
    // Collision detection
    collisionChecks: [],
    
    // Scenario triggering
    triggeredScenarios: new Set(),
    
    // Time-based scenario triggering
    scenarioTimer: 0,
    nextScenarioTime: 3000, // First scenario at 3 seconds
    minTriggerInterval: 3000, // Minimum 3 seconds between scenarios
    maxTriggerInterval: 3000, // Maximum 3 seconds between scenarios
    isScenarioActive: false, // Flag to prevent overlapping scenarios
    
    /**
     * Initialize the game engine
     */
    init(canvas, ctx) {
        console.log('⚙️ Initializing game engine...');
        
        this.canvas = canvas;
        this.ctx = ctx;
        
        // Set camera to center car perfectly in viewport - NO BLANK SPACES
        this.cameraOffset.x = this.canvas.width / 2;   // Car perfectly centered horizontally
        this.cameraOffset.y = this.canvas.height / 2;  // Car perfectly centered vertically
        
        console.log(`✅ Game engine ready - Camera offset: ${this.cameraOffset.x}, ${this.cameraOffset.y}`);
    },
    
    /**
     * Update game engine (called every frame)
     */
    update(deltaTime) {
        this.updateCamera();
        this.checkCollisions();
        this.checkTimeBasedScenarioTriggers(deltaTime);
    },
    
    /**
     * Update camera to follow car smoothly
     */
    updateCamera() {
        if (!window.CarModule) return;
        
        const carPos = window.CarModule.getPosition();
        
        // Target camera position (car position minus offset)
        const targetX = carPos.x - this.cameraOffset.x;
        const targetY = carPos.y - this.cameraOffset.y;
        
        // Smooth camera movement
        this.camera.x += (targetX - this.camera.x) * this.cameraSmoothing;
        this.camera.y += (targetY - this.camera.y) * this.cameraSmoothing;
        
        // Keep camera within world bounds - NO BLANK AREAS ALLOWED
        if (window.WorldModule) {
            const worldDim = window.WorldModule.getDimensions();
            
            // Ensure camera never shows blank areas by constraining tightly
            this.camera.x = Math.max(0, 
                           Math.min(worldDim.width - this.canvas.width, this.camera.x));
            this.camera.y = Math.max(0, 
                           Math.min(worldDim.height - this.canvas.height, this.camera.y));
        }
    },
    
    /**
     * Check for collisions between car and world elements
     */
    checkCollisions() {
        if (!window.CarModule || !window.WorldModule) return;
        
        const carPos = window.CarModule.getPosition();
        const carBounds = window.CarModule.getBoundingBox();
        
        // Check if car is off-road
        const onRoad = window.WorldModule.isOnRoad(carPos.x, carPos.y);
        
        if (!onRoad) {
            // Apply off-road penalty (slower movement)
            this.handleOffRoadPenalty();
        }
    },
    
    /**
     * Handle off-road driving penalty
     */
    handleOffRoadPenalty() {
        // Reduce car speed when off-road
        if (window.CarModule && window.CarModule.speed) {
            window.CarModule.speed *= 0.95; // Gradual slowdown
        }
    },
    
    /**
     * Check for time-based scenario triggers (NEW SYSTEM)
     */
    checkTimeBasedScenarioTriggers(deltaTime) {
        // Don't trigger if scenario is already active or game is paused
        if (this.isScenarioActive || this.isPaused) {
            return;
        }
        
        // Don't trigger if all scenarios completed
        if (!window.ScenariosModule) {
            console.warn('⚠️ ScenariosModule not available');
            return;
        }
        
        const completedCount = window.ScenariosModule.completedScenarios.size;
        if (completedCount >= 5) {
            return; // All 5 scenarios done
        }
        
        // Increment timer (deltaTime is in SECONDS, convert to milliseconds)
        this.scenarioTimer += deltaTime * 1000;
        
        // Debug logging every 1 second
        if (Math.floor(this.scenarioTimer / 1000) !== Math.floor((this.scenarioTimer - (deltaTime * 1000)) / 1000)) {
            console.log(`⏱️ Scenario timer: ${(this.scenarioTimer / 1000).toFixed(1)}s / ${(this.nextScenarioTime / 1000).toFixed(1)}s (Completed: ${completedCount}/5)`);
        }
        
        // Check if it's time to trigger next scenario
        if (this.scenarioTimer >= this.nextScenarioTime) {
            this.triggerNextScenario();
        }
    },
    
    /**
     * Trigger the next available scenario
     */
    triggerNextScenario() {
        if (!window.ScenariosModule) {
            console.error('❌ ScenariosModule not available');
            return;
        }
        
        // Get next unasked scenario
        const scenario = window.ScenariosModule.getNextScenario();
        
        if (!scenario) {
            console.log('✅ All scenarios completed!');
            return;
        }
        
        console.log(`🎯 Time-based trigger: Scenario ${scenario.id} - ${scenario.title}`);
        
        // Mark scenario as active to prevent overlapping
        this.isScenarioActive = true;
        
        // Trigger the scenario
        this.handleScenarioActivation(scenario);
        
        // Set time until next scenario (3 seconds)
        this.scenarioTimer = 0; // Reset timer
        this.nextScenarioTime = 3000; // Next scenario in 3 seconds
        
        console.log(`⏱️ Next scenario in 3 seconds`);
    },
    
    /**
     * Resume scenario triggering after question is answered
     */
    resumeScenarioTriggering() {
        this.isScenarioActive = false;
        console.log('▶️ Scenario triggering resumed');
    },
    
    /**
     * Check for position-based scenario triggers (DEPRECATED - kept for compatibility)
     */
    checkScenarioTriggers() {
        // This method is deprecated - scenarios now trigger by time, not position
        // Kept for backward compatibility but does nothing
        return;
    },
    
    /**
     * Handle scenario activation
     */
    handleScenarioActivation(scenario) {
        // Pause car movement
        if (window.CarModule) {
            window.CarModule.emergencyStop();
        }
        
        // Show question UI
        if (window.UIModule) {
            window.UIModule.showScenarioQuestion(scenario);
        }
        
        // Notify main simulation
        if (window.SimulationMain && window.SimulationMain.handleScenarioTriggered) {
            window.SimulationMain.handleScenarioTriggered(scenario);
        }
    },
    
    /**
     * Get camera position
     */
    getCamera() {
        return { x: this.camera.x, y: this.camera.y };
    },
    
    /**
     * Convert world coordinates to screen coordinates
     */
    worldToScreen(worldX, worldY) {
        return {
            x: worldX - this.camera.x,
            y: worldY - this.camera.y
        };
    },
    
    /**
     * Convert screen coordinates to world coordinates
     */
    screenToWorld(screenX, screenY) {
        return {
            x: screenX + this.camera.x,
            y: screenY + this.camera.y
        };
    },
    
    /**
     * Check if a point is visible on screen
     */
    isVisible(worldX, worldY, margin = 50) {
        const screenPos = this.worldToScreen(worldX, worldY);
        
        return screenPos.x >= -margin && 
               screenPos.x <= this.canvas.width + margin &&
               screenPos.y >= -margin && 
               screenPos.y <= this.canvas.height + margin;
    },
    
    /**
     * Distance between two points
     */
    distance(x1, y1, x2, y2) {
        return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
    },
    
    /**
     * Check collision between two circles
     */
    circleCollision(x1, y1, r1, x2, y2, r2) {
        return this.distance(x1, y1, x2, y2) < (r1 + r2);
    },
    
    /**
     * Check collision between circle and rectangle
     */
    circleRectCollision(cx, cy, radius, rx, ry, rw, rh) {
        // Find closest point on rectangle to circle center
        const closestX = Math.max(rx, Math.min(cx, rx + rw));
        const closestY = Math.max(ry, Math.min(cy, ry + rh));
        
        // Calculate distance between circle center and closest point
        const distance = this.distance(cx, cy, closestX, closestY);
        
        return distance < radius;
    },
    
    /**
     * Angle between two points
     */
    angleBetween(x1, y1, x2, y2) {
        return Math.atan2(y2 - y1, x2 - x1);
    },
    
    /**
     * Normalize angle to -PI to PI range
     */
    normalizeAngle(angle) {
        while (angle > Math.PI) angle -= 2 * Math.PI;
        while (angle < -Math.PI) angle += 2 * Math.PI;
        return angle;
    },
    
    /**
     * Linear interpolation
     */
    lerp(start, end, factor) {
        return start + (end - start) * factor;
    },
    
    /**
     * Clamp value between min and max
     */
    clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    },
    
    /**
     * Resume scenario after question is answered
     */
    resumeFromScenario(scenarioId) {
        console.log(`▶️ Resuming from scenario ${scenarioId}`);
        
        // Resume car movement
        if (window.CarModule) {
            // Car will resume naturally from controls
        }
        
        // Remove scenario trigger
        this.triggeredScenarios.add(scenarioId);
    },
    
    /**
     * Reset all triggered scenarios
     */
    resetScenarios() {
        this.triggeredScenarios.clear();
        
        // Reactivate all scenario markers
        if (window.WorldModule) {
            const markers = window.WorldModule.getScenarioMarkers();
            markers.forEach(marker => {
                marker.active = true;
            });
        }
        
        console.log('🔄 All scenarios reset and reactivated');
    },
    
    /**
     * Set camera position manually
     */
    setCameraPosition(x, y) {
        this.camera.x = x;
        this.camera.y = y;
    },
    
    /**
     * Get triggered scenarios count
     */
    getTriggeredScenariosCount() {
        return this.triggeredScenarios.size;
    },
    
    /**
     * Check if scenario is triggered
     */
    isScenarioTriggered(scenarioId) {
        return this.triggeredScenarios.has(scenarioId);
    },
    
    /**
     * Get game engine statistics
     */
    getStats() {
        return {
            cameraPosition: { ...this.camera },
            triggeredScenarios: Array.from(this.triggeredScenarios),
            canvasSize: { 
                width: this.canvas?.width || 0, 
                height: this.canvas?.height || 0 
            }
        };
    },
    
    /**
     * Get camera object for rendering
     */
    getCamera() {
        return {
            x: this.camera.x,
            y: this.camera.y,
            zoom: this.camera.zoom || 1.5
        };
    },
    
    /**
     * Reset game engine
     */
    reset() {
        // Reset camera to initial position
        this.camera.x = 0;
        this.camera.y = 0;
        
        // Reset triggered scenarios
        this.resetScenarios();
        
        // Clear collision checks
        this.collisionChecks = [];
        
        console.log('🔄 Game engine reset complete');
    }
};

// Export module
window.GameEngine = GameEngine;
