/**
 * Car Module - Enhanced Realistic Player Vehicle
 * Handles realistic vehicle movement, physics, and visual representation
 */

const CarModule = {
    // Car position and rotation
    x: 200,
    y: 480, // Start on main road
    angle: 0, // Rotation in radians
    
    // Car dimensions (realistic car size)
    width: 45,
    height: 25,
    
    // Enhanced physics properties
    speed: 0,
    maxSpeed: 200, // Increased maximum speed
    acceleration: 120, // Faster acceleration
    deceleration: 180, // Better braking
    turnSpeed: 2.8, // Smoother turning
    friction: 0.88, // More realistic friction
    
    // Control state
    controls: {
        up: false,
        down: false,
        left: false,
        right: false,
        brake: false
    },
    
    // Enhanced visual properties
    color: '#E53935', // Bright red car
    secondaryColor: '#C62828',
    
    // Lighting system
    lights: {
        headlights: false,
        brake: false,
        turn: { left: false, right: false, timer: 0 }
    },
    
    // Engine sound simulation
    engineSound: {
        rpm: 0,
        volume: 0
    },
    
    /**
     * Initialize the car
     */
    init() {
        console.log('🚗 Initializing player vehicle...');
        this.setupControls();
        this.resetPosition();
        console.log('✅ Vehicle ready for driving');
    },
    
    /**
     * Reset car to starting position
     */
    resetPosition() {
        this.x = 200;
        this.y = 480; // On the main road
        this.angle = 0;
        this.speed = 0;
        this.resetLights();
    },
    
    /**
     * Reset all lights
     */
    resetLights() {
        this.lights.headlights = false;
        this.lights.brake = false;
        this.lights.turn.left = false;
        this.lights.turn.right = false;
        this.lights.turn.timer = 0;
    },
    
    /**
     * Setup keyboard controls (ENHANCED WITH DEBUG)
     */
    setupControls() {
        console.log('🎮 Setting up car controls...');
        
        // Add focus to the canvas or document to receive key events
        if (document.activeElement) {
            document.activeElement.blur();
        }
        document.body.focus();
        
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));
        document.addEventListener('keyup', (e) => this.handleKeyUp(e));
        
        console.log('✅ Car controls setup complete');
    },
    
    /**
     * Handle key press events (ENHANCED WITH DEBUG)
     */
    handleKeyDown(e) {
        const key = e.key.toLowerCase();
        const wasPressed = this.controls.up || this.controls.down || this.controls.left || this.controls.right;
        
        console.log(`🔽 Key pressed: "${key}" (code: ${e.code})`);
        
        switch(key) {
            case 'w':
            case 'arrowup':
                this.controls.up = true;
                console.log('🚗 ACCELERATE activated!');
                e.preventDefault();
                break;
                
            case 's':
            case 'arrowdown':
                this.controls.down = true;
                this.lights.brake = true;
                console.log('🛑 REVERSE/BRAKE activated!');
                e.preventDefault();
                break;
                
            case 'a':
            case 'arrowleft':
                this.controls.left = true;
                this.lights.turn.left = true;
                console.log('⬅️ TURN LEFT activated!');
                e.preventDefault();
                break;
                
            case 'd':
            case 'arrowright':
                this.controls.right = true;
                this.lights.turn.right = true;
                console.log('➡️ TURN RIGHT activated!');
                e.preventDefault();
                break;
                
            case ' ':
                // Space bar for emergency brake
                this.controls.brake = true;
                this.lights.brake = true;
                console.log('🚨 EMERGENCY BRAKE activated!');
                e.preventDefault();
                break;
                
            case 'h':
                // Toggle headlights
                this.lights.headlights = !this.lights.headlights;
                console.log('💡 Headlights toggled:', this.lights.headlights);
                e.preventDefault();
                break;
        }
        
        // Check for activation change
        const isPressed = this.controls.up || this.controls.down || this.controls.left || this.controls.right;
        if (!wasPressed && isPressed) {
            console.log('🎯 First control activated - car should respond now!');
        }
    },
    
    /**
     * Handle key release events (ENHANCED WITH DEBUG)
     */
    handleKeyUp(e) {
        const key = e.key.toLowerCase();
        
        console.log(`🔼 Key released: "${key}"`);
        
        switch(key) {
            case 'w':
            case 'arrowup':
                this.controls.up = false;
                console.log('🚗 ACCELERATE deactivated!');
                break;
                
            case 's':
            case 'arrowdown':
                this.controls.down = false;
                this.lights.brake = false;
                console.log('🛑 REVERSE/BRAKE deactivated!');
                break;
                
            case 'a':
            case 'arrowleft':
                this.controls.left = false;
                this.lights.turn.left = false;
                console.log('⬅️ TURN LEFT deactivated!');
                break;
                
            case 'd':
            case 'arrowright':
                this.controls.right = false;
                this.lights.turn.right = false;
                console.log('➡️ TURN RIGHT deactivated!');
                break;
                
            case ' ':
                this.controls.brake = false;
                this.lights.brake = false;
                console.log('🚨 EMERGENCY BRAKE deactivated!');
                break;
        }
        
        // Log current control state
        const activeControls = Object.entries(this.controls).filter(([k, v]) => v).map(([k]) => k);
        console.log('🎮 Active controls:', activeControls.length > 0 ? activeControls.join(', ') : 'None');
    },
    
    /**
     * Update car physics and movement (FIXED VERSION)
     */
    update(deltaTime) {
        // Ensure deltaTime is reasonable
        deltaTime = Math.min(deltaTime, 0.1);
        
        this.updateMovementFixed(deltaTime);
        this.updateLights(deltaTime);
        this.updateEngineSound();
        this.constrainToWorld();
    },
    
    /**
     * FIXED movement system
     */
    updateMovementFixed(deltaTime) {
        // Debug logging
        const activeControls = Object.entries(this.controls).filter(([k, v]) => v).map(([k]) => k);
        if (activeControls.length > 0) {
            console.log('Active controls:', activeControls, 'Current speed:', this.speed.toFixed(2));
        }
        
        // Handle acceleration and deceleration
        if (this.controls.up) {
            this.speed += this.acceleration * deltaTime;
            console.log('Accelerating! Speed now:', this.speed.toFixed(2));
        } else if (this.controls.down) {
            this.speed -= this.deceleration * deltaTime;
            this.lights.brake = true;
        } else {
            this.lights.brake = false;
        }
        
        // Emergency brake
        if (this.controls.brake) {
            this.speed *= 0.7; // Rapid deceleration
            this.lights.brake = true;
        }
        
        // Apply friction
        if (!this.controls.up && !this.controls.down) {
            this.speed *= this.friction;
        }
        
        // Constrain speed
        this.speed = Math.max(-this.maxSpeed * 0.6, Math.min(this.maxSpeed, this.speed));
        
        // Stop very slow movement
        if (Math.abs(this.speed) < 2) {
            this.speed = 0;
        }
        
        // Handle turning (only when moving)
        if (Math.abs(this.speed) > 5) {
            const turnAmount = this.turnSpeed * deltaTime * Math.min(Math.abs(this.speed) / this.maxSpeed, 1);
            
            if (this.controls.left) {
                this.angle -= turnAmount;
                this.lights.turn.left = true;
                console.log('Turning left, angle:', this.angle.toFixed(2));
            } else {
                this.lights.turn.left = false;
            }
            
            if (this.controls.right) {
                this.angle += turnAmount;
                this.lights.turn.right = true;
                console.log('Turning right, angle:', this.angle.toFixed(2));
            } else {
                this.lights.turn.right = false;
            }
        } else {
            this.lights.turn.left = false;
            this.lights.turn.right = false;
        }
        
        // Move the car if there's speed
        if (Math.abs(this.speed) > 0.5) {
            const moveX = Math.cos(this.angle) * this.speed * deltaTime;
            const moveY = Math.sin(this.angle) * this.speed * deltaTime;
            
            const oldX = this.x;
            const oldY = this.y;
            
            this.x += moveX;
            this.y += moveY;
            
            console.log(`Car moved from (${oldX.toFixed(1)}, ${oldY.toFixed(1)}) to (${this.x.toFixed(1)}, ${this.y.toFixed(1)})`);
        }
    },
    
    /**
     * Update engine sound simulation
     */
    updateEngineSound() {
        this.engineSound.rpm = Math.abs(this.speed) * 50; // Simulate RPM
        this.engineSound.volume = Math.min(this.engineSound.rpm / 5000, 1);
    },
    
    /**
     * Update light animations
     */
    updateLights(deltaTime) {
        // Update turn signal blinking
        this.lights.turn.timer += deltaTime;
        if (this.lights.turn.timer >= 0.5) { // Blink every 0.5 seconds
            this.lights.turn.timer = 0;
        }
    },
    
    /**
     * Keep car within world boundaries
     */
    constrainToWorld() {
        if (window.WorldModule) {
            const worldDim = window.WorldModule.getDimensions();
            
            // Keep car within world bounds
            this.x = Math.max(this.width / 2, Math.min(worldDim.width - this.width / 2, this.x));
            this.y = Math.max(this.height / 2, Math.min(worldDim.height - this.height / 2, this.y));
        }
    },
    
    /**
     * Get car's current position
     */
    getPosition() {
        return { x: this.x, y: this.y };
    },
    
    /**
     * Get current speed in km/h equivalent
     */
    getCurrentSpeed() {
        return Math.abs(this.speed) * 0.6; // Convert to reasonable km/h scale
    },
    
    /**
     * Get car bounding box for collisions
     */
    getBoundingBox() {
        // Calculate rotated corners
        const cos = Math.cos(this.angle);
        const sin = Math.sin(this.angle);
        
        const corners = [
            { x: -this.width/2, y: -this.height/2 },
            { x: this.width/2, y: -this.height/2 },
            { x: this.width/2, y: this.height/2 },
            { x: -this.width/2, y: this.height/2 }
        ];
        
        return corners.map(corner => ({
            x: this.x + corner.x * cos - corner.y * sin,
            y: this.y + corner.x * sin + corner.y * cos
        }));
    },
    
    /**
     * Check if car is on road
     */
    isOnRoad() {
        if (!window.WorldModule) return true;
        return window.WorldModule.isOnRoad(this.x, this.y);
    },
    
    /**
     * Emergency stop function
     */
    emergencyStop() {
        this.speed = 0;
        this.lights.brake = true;
        
        // Remove brake light after a moment
        setTimeout(() => {
            this.lights.brake = false;
        }, 1000);
    },
    
    /**
     * Render the car
     */
    render(ctx, camera) {
        const screenX = this.x - camera.x;
        const screenY = this.y - camera.y;
        
        ctx.save();
        ctx.translate(screenX, screenY);
        ctx.rotate(this.angle);
        
        // Car shadow
        ctx.fillStyle = 'rgba(0, 0, 0, 0.3)';
        ctx.fillRect(-this.width/2 + 3, -this.height/2 + 3, this.width, this.height);
        
        // Main car body (bus-like design from reference)
        ctx.fillStyle = this.color;
        ctx.fillRect(-this.width/2, -this.height/2, this.width, this.height);
        
        // Car outline
        ctx.strokeStyle = '#2C3E50';
        ctx.lineWidth = 2;
        ctx.strokeRect(-this.width/2, -this.height/2, this.width, this.height);
        
        // Windows (like reference bus)
        ctx.fillStyle = '#87CEEB';
        const windowMargin = 4;
        ctx.fillRect(-this.width/2 + windowMargin, -this.height/2 + windowMargin, 
                    this.width - windowMargin * 2, this.height - windowMargin * 2);
        
        // Window frames
        ctx.strokeStyle = '#2C3E50';
        ctx.lineWidth = 1;
        ctx.strokeRect(-this.width/2 + windowMargin, -this.height/2 + windowMargin, 
                      this.width - windowMargin * 2, this.height - windowMargin * 2);
        
        // Front indicator (direction of travel)
        ctx.fillStyle = '#FFD700';
        ctx.fillRect(this.width/2 - 6, -4, 8, 8);
        
        // Headlights
        if (this.lights.headlights) {
            ctx.fillStyle = '#FFFFE0';
            ctx.fillRect(this.width/2 - 3, -this.height/2 + 3, 5, 6);
            ctx.fillRect(this.width/2 - 3, this.height/2 - 9, 5, 6);
        }
        
        // Brake lights
        if (this.lights.brake) {
            ctx.fillStyle = '#FF0000';
            ctx.fillRect(-this.width/2 - 2, -this.height/2 + 3, 4, 6);
            ctx.fillRect(-this.width/2 - 2, this.height/2 - 9, 4, 6);
        }
        
        // Turn signals (blinking)
        const turnBlink = this.lights.turn.timer < 0.25;
        
        if (this.lights.turn.left && turnBlink) {
            ctx.fillStyle = '#FFA500';
            ctx.fillRect(-this.width/2 - 4, -this.height/2 + 8, 6, 8);
        }
        
        if (this.lights.turn.right && turnBlink) {
            ctx.fillStyle = '#FFA500';
            ctx.fillRect(-this.width/2 - 4, this.height/2 - 16, 6, 8);
        }
        
        // Car details (doors, etc.)
        this.renderCarDetails(ctx);
        
        ctx.restore();
        
        // Debug information
        if (window.SimulationConfig?.debug) {
            this.renderDebugInfo(ctx, screenX, screenY);
        }
    },
    
    /**
     * Render additional car details
     */
    renderCarDetails(ctx) {
        // Door lines
        ctx.strokeStyle = '#2C3E50';
        ctx.lineWidth = 1;
        
        // Vertical door lines
        ctx.beginPath();
        ctx.moveTo(-this.width/4, -this.height/2);
        ctx.lineTo(-this.width/4, this.height/2);
        ctx.moveTo(this.width/4, -this.height/2);
        ctx.lineTo(this.width/4, this.height/2);
        ctx.stroke();
        
        // Wheels (simple representation)
        ctx.fillStyle = '#333';
        const wheelSize = 6;
        
        // Front wheels
        ctx.fillRect(this.width/2 - 10, -this.height/2 - wheelSize/2, wheelSize, wheelSize);
        ctx.fillRect(this.width/2 - 10, this.height/2 - wheelSize/2, wheelSize, wheelSize);
        
        // Rear wheels
        ctx.fillRect(-this.width/2 + 4, -this.height/2 - wheelSize/2, wheelSize, wheelSize);
        ctx.fillRect(-this.width/2 + 4, this.height/2 - wheelSize/2, wheelSize, wheelSize);
    },
    
    /**
     * Render debug information
     */
    renderDebugInfo(ctx, screenX, screenY) {
        ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
        ctx.fillRect(screenX + 35, screenY - 50, 200, 80);
        
        ctx.fillStyle = '#00FF00';
        ctx.font = '10px monospace';
        ctx.textAlign = 'left';
        
        const debugText = [
            `Position: (${Math.round(this.x)}, ${Math.round(this.y)})`,
            `Speed: ${this.getCurrentSpeed().toFixed(1)} km/h`,
            `Angle: ${(this.angle * 180 / Math.PI).toFixed(1)}°`,
            `On Road: ${this.isOnRoad() ? 'Yes' : 'No'}`,
            `Controls: ${Object.entries(this.controls).filter(([k,v]) => v).map(([k]) => k).join(', ') || 'None'}`
        ];
        
        debugText.forEach((text, index) => {
            ctx.fillText(text, screenX + 40, screenY - 35 + index * 12);
        });
    },
    
    /**
     * Get current car state
     */
    getState() {
        return {
            x: this.x,
            y: this.y,
            angle: this.angle,
            speed: this.speed
        };
    },
    
    /**
     * Set car state
     */
    setState(state) {
        this.x = state.x || this.x;
        this.y = state.y || this.y;
        this.angle = state.angle || this.angle;
        this.speed = state.speed || this.speed;
    },
    
    /**
     * Reset car to initial state
     */
    reset() {
        this.resetPosition();
        this.controls = {
            up: false, down: false, left: false, right: false, brake: false
        };
        console.log('🔄 Car reset to starting position');
    }
};

// Export module
window.CarModule = CarModule;
