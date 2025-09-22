// Car Physics and Movement Module
// Handles car properties, movement, controls, and collision detection

// Car physics object
const Car = {
    // Car properties with realistic physics
    properties: {
        x: 2000,
        y: 3900, // Start at bottom
        angle: 0,
        speed: 0,
        width: 40,
        height: 20,
        acceleration: 0.3,
        deceleration: 0.15,
        turnSpeed: 0.08,
        maxSpeed: 6, // Maximum 60 km/h (adjusted scale: 1 unit = 10 km/h)
        friction: 0.95,
        color: '#ff4444'
    },

    // Initialize car
    init() {
        this.reset();
    },

    // Reset car to starting position
    reset() {
        this.properties.x = 2000;
        this.properties.y = 3900; // Bottom of screen
        this.properties.angle = 0;
        this.properties.speed = 0;
    },

    // Update car physics and movement
    update(keys, buttonStates, isCarStopped, worldWidth, worldHeight) {
        if (isCarStopped) return; // Don't move during scenarios

        // Handle input (keyboard or buttons)
        let accelerating = keys['ArrowUp'] || keys['w'] || keys['W'] || buttonStates.forward;
        let reversing = keys['ArrowDown'] || keys['s'] || keys['S'] || buttonStates.reverse;
        let turningLeft = keys['ArrowLeft'] || keys['a'] || keys['A'] || buttonStates.left;
        let turningRight = keys['ArrowRight'] || keys['d'] || keys['D'] || buttonStates.right;

        // Apply acceleration/deceleration
        if (accelerating && this.properties.speed < this.properties.maxSpeed) {
            this.properties.speed += this.properties.acceleration;
        } else if (reversing && this.properties.speed > -this.properties.maxSpeed / 2) {
            this.properties.speed -= this.properties.acceleration;
        } else {
            // Apply friction when no input
            this.properties.speed *= this.properties.friction;
            if (Math.abs(this.properties.speed) < 0.1) {
                this.properties.speed = 0;
            }
        }

        // Handle turning (only when moving)
        if (Math.abs(this.properties.speed) > 0.5) {
            if (turningLeft) {
                this.properties.angle -= this.properties.turnSpeed * Math.abs(this.properties.speed) / this.properties.maxSpeed;
            }
            if (turningRight) {
                this.properties.angle += this.properties.turnSpeed * Math.abs(this.properties.speed) / this.properties.maxSpeed;
            }
        }

        // Update position based on angle and speed
        this.properties.x += Math.cos(this.properties.angle) * this.properties.speed;
        this.properties.y += Math.sin(this.properties.angle) * this.properties.speed;

        // Keep car within world bounds
        this.properties.x = Math.max(50, Math.min(worldWidth - 50, this.properties.x));
        this.properties.y = Math.max(50, Math.min(worldHeight - 50, this.properties.y));
    },

    // Render the car
    render(ctx, camera) {
        ctx.save();
        
        // Calculate screen position relative to camera
        const screenX = this.properties.x - camera.x;
        const screenY = this.properties.y - camera.y;
        
        // Move to car position and rotate
        ctx.translate(screenX, screenY);
        ctx.rotate(this.properties.angle);
        
        // Draw car body
        ctx.fillStyle = this.properties.color;
        ctx.fillRect(-this.properties.width / 2, -this.properties.height / 2, this.properties.width, this.properties.height);
        
        // Draw car windows
        ctx.fillStyle = '#87CEEB';
        ctx.fillRect(-12, -6, 8, 12);
        ctx.fillRect(4, -6, 8, 12);
        
        // Draw headlights
        ctx.fillStyle = '#FFFF99';
        ctx.fillRect(15, -8, 5, 4);
        ctx.fillRect(15, 4, 5, 4);
        
        // Draw speed indicator
        if (Math.abs(this.properties.speed) > 0.1) {
            ctx.fillStyle = 'rgba(255, 255, 0, 0.6)';
            ctx.fillRect(20, -2, Math.abs(this.properties.speed) * 3, 4);
        }
        
        ctx.restore();
    },

    // Get car bounds for collision detection
    getBounds() {
        return {
            x: this.properties.x - this.properties.width / 2,
            y: this.properties.y - this.properties.height / 2,
            width: this.properties.width,
            height: this.properties.height,
            centerX: this.properties.x,
            centerY: this.properties.y
        };
    },

    // Check collision with rectangular object
    checkCollision(object) {
        const carBounds = this.getBounds();
        return (carBounds.x < object.x + object.width &&
                carBounds.x + carBounds.width > object.x &&
                carBounds.y < object.y + object.height &&
                carBounds.y + carBounds.height > object.y);
    },

    // Get current speed in km/h for display
    getSpeedKmh() {
        return Math.round(Math.abs(this.properties.speed) * 10);
    },

    // Get all car properties (for external access)
    getProperties() {
        return { ...this.properties };
    },

    // Set car position (for scenarios or reset)
    setPosition(x, y) {
        this.properties.x = x;
        this.properties.y = y;
    },

    // Update button states from keyboard input (for external control)
    updateControlsFromKeyboard(keys) {
        // This method allows the main controller to update button states
        // based on keyboard input. The actual movement logic is in update()
        // which receives the button states as parameters
    },

    // Stop the car immediately
    stop() {
        this.properties.speed = 0;
    }
};

// Export to global window object for browser use
window.CarModule = Car;

// Export for use in other modules (Node.js compatibility)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Car;
}