/**
 * Car Module - Enhanced Realistic Player Vehicle
 * Handles realistic vehicle movement, physics, and visual representation
 */

const CarModule = {
    // Car position and rotation (positioned on left lane of first horizontal road)
    x: 120,
    y: 175,  // Inside the first horizontal road (y: 160-220)
    angle: 0, // Rotation in radians
    
    // Car dimensions (realistic car size)
    width: 45,
    height: 25,
    
    // Enhanced physics properties
    speed: 0,
    maxSpeed: 200,
    acceleration: 150,
    deceleration: 200,
    turnSpeed: 3.0,
    friction: 0.92,
    
    // Control state
    controls: {
        up: false,
        down: false,
        left: false,
        right: false,
        brake: false
    },
    
    // Enhanced visual properties
    color: '#E53935',
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
        console.log('🚗 Initializing Enhanced Player Vehicle...');
        this.setupControls();
        this.resetPosition();
        console.log('✅ Vehicle ready for driving - Press W or UP arrow to move!');
    },
    
    /**
     * Reset car to starting position
     */
    resetPosition() {
        this.x = 200;  // Left side of road
        this.y = 480;  // On main horizontal road
        this.angle = 0;
        this.speed = 0;
        this.resetLights();
        console.log('🔄 Car reset to starting position');
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
     * Setup keyboard controls with comprehensive debugging
     */
    setupControls() {
        console.log('🎮 Setting up Enhanced Car Controls...');
        
        // Ensure focus for key events
        document.body.focus();
        
        // Key down events
        document.addEventListener('keydown', (e) => {
            this.handleKeyDown(e);
        });
        
        // Key up events  
        document.addEventListener('keyup', (e) => {
            this.handleKeyUp(e);
        });
        
        console.log('✅ Car controls ready - Use W/↑ (forward), S/↓ (reverse), A/← (left), D/→ (right), SPACE (brake)');
    },
    
    /**
     * Handle key press events with enhanced debugging
     */
    handleKeyDown(e) {
        const key = e.key.toLowerCase();
        const wasPressed = this.controls.up || this.controls.down || this.controls.left || this.controls.right;
        
        console.log(`🔽 Key pressed: "${key}" (code: ${e.code})`);
        
        switch(key) {
            case 'w':
            case 'arrowup':
                this.controls.up = true;
                console.log('🚀 ACCELERATE activated!');
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
                this.controls.brake = true;
                this.lights.brake = true;
                console.log('🚨 EMERGENCY BRAKE activated!');
                e.preventDefault();
                break;
                
            case 'h':
                this.lights.headlights = !this.lights.headlights;
                console.log('💡 Headlights toggled:', this.lights.headlights);
                e.preventDefault();
                break;
        }
        
        // Log activation change
        const isPressed = this.controls.up || this.controls.down || this.controls.left || this.controls.right;
        if (!wasPressed && isPressed) {
            console.log('🎯 First control activated - car should respond now!');
        }
    },
    
    /**
     * Handle key release events
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
     * Main update function called from game loop
     */
    update(deltaTime) {
        // Ensure reasonable deltaTime
        deltaTime = Math.min(deltaTime, 0.1);
        
        // Update movement with enhanced debugging
        this.updateMovement(deltaTime);
        this.updateLights(deltaTime);
        this.updateEngineSound();
        this.checkTrafficLightViolations();
        this.constrainToWorld();
    },
    
    /**
     * Enhanced movement system with comprehensive debugging
     */
    updateMovement(deltaTime) {
        // Debug logging for active controls
        const activeControls = Object.entries(this.controls).filter(([k, v]) => v).map(([k]) => k);
        
        if (activeControls.length > 0) {
            console.log(`🎮 Frame Update - Controls: [${activeControls.join(', ')}] Speed: ${this.speed.toFixed(2)} Position: (${Math.round(this.x)}, ${Math.round(this.y)})`);
        }
        
        // Track old values for debugging
        const oldSpeed = this.speed;
        const oldX = this.x;
        const oldY = this.y;
        
        // Handle acceleration and deceleration
        if (this.controls.up) {
            this.speed += this.acceleration * deltaTime;
            console.log(`🚀 ACCELERATING: ${oldSpeed.toFixed(2)} → ${this.speed.toFixed(2)} (Δt: ${deltaTime.toFixed(4)})`);
        } else if (this.controls.down) {
            this.speed -= this.deceleration * deltaTime;
            this.lights.brake = true;
            console.log(`🛑 BRAKING: ${oldSpeed.toFixed(2)} → ${this.speed.toFixed(2)}`);
        } else {
            this.lights.brake = false;
        }
        
        // Emergency brake
        if (this.controls.brake) {
            this.speed *= 0.7;
            this.lights.brake = true;
            console.log(`🚨 EMERGENCY BRAKE: Speed reduced to ${this.speed.toFixed(2)}`);
        }
        
        // Apply friction when not actively accelerating
        if (!this.controls.up && !this.controls.down && !this.controls.brake) {
            const frictionSpeed = this.speed * this.friction;
            if (Math.abs(this.speed - frictionSpeed) > 0.1) {
                console.log(`🛞 Friction: ${this.speed.toFixed(2)} → ${frictionSpeed.toFixed(2)}`);
            }
            this.speed = frictionSpeed;
        }
        
        // Constrain speed to limits
        const maxReverseSpeed = -this.maxSpeed * 0.6;
        this.speed = Math.max(maxReverseSpeed, Math.min(this.maxSpeed, this.speed));
        
        // Stop very slow movement to prevent jitter
        if (Math.abs(this.speed) < 1) {
            if (this.speed !== 0) {
                console.log(`🛑 Stopping due to low speed: ${this.speed.toFixed(2)}`);
            }
            this.speed = 0;
        }
        
        // Handle turning (only when moving at reasonable speed)
        if (Math.abs(this.speed) > 3) {
            const speedFactor = Math.min(Math.abs(this.speed) / this.maxSpeed, 1);
            const turnAmount = this.turnSpeed * deltaTime * speedFactor;
            
            if (this.controls.left) {
                this.angle -= turnAmount;
                this.lights.turn.left = true;
                console.log(`⬅️ TURNING LEFT: Angle = ${(this.angle * 180 / Math.PI).toFixed(1)}°`);
            } else {
                this.lights.turn.left = false;
            }
            
            if (this.controls.right) {
                this.angle += turnAmount;
                this.lights.turn.right = true;
                console.log(`➡️ TURNING RIGHT: Angle = ${(this.angle * 180 / Math.PI).toFixed(1)}°`);
            } else {
                this.lights.turn.right = false;
            }
        } else {
            this.lights.turn.left = false;
            this.lights.turn.right = false;
        }
        
        // Move the car if there's sufficient speed
        if (Math.abs(this.speed) > 0.5) {
            const moveX = Math.cos(this.angle) * this.speed * deltaTime;
            const moveY = Math.sin(this.angle) * this.speed * deltaTime;
            
            // Calculate target position
            const targetX = this.x + moveX;
            const targetY = this.y + moveY;
            
            // Check for building collision if WorldModule is available
            if (window.WorldModule && window.WorldModule.checkBuildingCollision) {
                const validPosition = window.WorldModule.resolveCollision(
                    this.x, this.y, targetX, targetY, this.width, this.height
                );
                
                // Use the resolved position
                this.x = validPosition.x;
                this.y = validPosition.y;
                
                // If collision prevented movement, reduce speed
                if (validPosition.x === this.x && validPosition.y === this.y && (targetX !== this.x || targetY !== this.y)) {
                    this.speed *= 0.5; // Slow down when hitting buildings
                    console.log(`🏢 Building collision! Speed reduced to ${this.speed.toFixed(2)}`);
                }
            } else {
                // Fallback to normal movement if collision detection not available
                this.x = targetX;
                this.y = targetY;
            }
            
            // Check road boundaries after movement
            this.checkRoadBoundaries();
            
            // Check for pedestrian lane proximity
            this.checkPedestrianLaneProximity();
            
            // Check for approaching intersections
            this.checkIntersectionWarnings();
            
            console.log(`🚗 MOVED: (${oldX.toFixed(1)}, ${oldY.toFixed(1)}) → (${this.x.toFixed(1)}, ${this.y.toFixed(1)}) | Delta: (${moveX.toFixed(2)}, ${moveY.toFixed(2)}) | Speed: ${this.speed.toFixed(2)}`);
        } else if (activeControls.length > 0) {
            console.log(`⚠️ Controls active but speed too low to move: ${this.speed.toFixed(2)}`);
        }
    },
    
    /**
     * Check if car is outside road boundaries and show violation message
     */
    checkRoadBoundaries() {
        if (!window.WorldModule || !window.MessageSystem) return;
        
        const isOnRoad = window.WorldModule.isOnRoad(this.x + this.width/2, this.y + this.height/2);
        
        if (!isOnRoad) {
            // Car is off road - show violation message
            if (!this.offRoadWarning || Date.now() - this.lastOffRoadWarning > 3000) {
                window.MessageSystem.showViolationMessage(
                    'ROAD VIOLATION',
                    'Vehicle is outside designated road area! Return to the road immediately.',
                    'warning'
                );
                this.offRoadWarning = true;
                this.lastOffRoadWarning = Date.now();
                
                // Reduce speed when off road
                this.speed *= 0.7;
                console.log(`🛣️ Off-road violation detected at (${Math.round(this.x)}, ${Math.round(this.y)})`);
            }
        } else {
            // Car is back on road
            if (this.offRoadWarning) {
                this.offRoadWarning = false;
                window.MessageSystem.showViolationMessage(
                    'BACK ON ROAD',
                    'Vehicle returned to designated road area. Good driving!',
                    'info'
                );
            }
        }
    },
    
    /**
     * Check traffic light violations
     */
    checkTrafficLightViolations() {
        if (!window.WorldModule || !window.MessageSystem) return;
        
        // Check if car is near any traffic lights
        if (window.WorldModule.trafficLights) {
            window.WorldModule.trafficLights.forEach(light => {
                const distance = Math.sqrt(
                    Math.pow(this.x - light.x, 2) + Math.pow(this.y - light.y, 2)
                );
                
                // If car is close to a red light and moving
                if (distance < 80 && light.state === 'RED' && Math.abs(this.speed) > 5) {
                    if (!this.redLightWarning || Date.now() - this.lastRedLightWarning > 5000) {
                        window.MessageSystem.showViolationMessage(
                            'TRAFFIC LIGHT VIOLATION',
                            'Running a red light! Stop immediately when the light is red.',
                            'error'
                        );
                        this.redLightWarning = true;
                        this.lastRedLightWarning = Date.now();
                        
                        // Heavy speed penalty for red light violation
                        this.speed *= 0.3;
                        console.log(`🚦 Red light violation at intersection (${Math.round(light.x)}, ${Math.round(light.y)})`);
                    }
                }
            });
        }
    },
    
    /**
     * Check for pedestrian lanes nearby and warn to slow down
     */
    checkPedestrianLaneProximity() {
        if (!window.WorldModule || !window.MessageSystem) return;
        
        // Get pedestrian lanes from WorldModule
        const pedestrianLanes = window.WorldModule.getPedestrianLanes ? window.WorldModule.getPedestrianLanes() : [];
        
        let nearestLane = null;
        let nearestDistance = Infinity;
        
        pedestrianLanes.forEach(lane => {
            // Calculate distance to center of pedestrian lane
            const laneCenterX = lane.x + lane.width / 2;
            const laneCenterY = lane.y + lane.height / 2;
            const carCenterX = this.x + this.width / 2;
            const carCenterY = this.y + this.height / 2;
            
            const distance = Math.sqrt(
                Math.pow(carCenterX - laneCenterX, 2) + 
                Math.pow(carCenterY - laneCenterY, 2)
            );
            
            if (distance < nearestDistance) {
                nearestDistance = distance;
                nearestLane = lane;
            }
        });
        
        // If pedestrian lane is within alert radius and car is moving
        if (nearestLane && nearestDistance < nearestLane.alertRadius && Math.abs(this.speed) > 10) {
            if (!this.pedestrianLaneWarning || Date.now() - this.lastPedestrianLaneWarning > 4000) {
                window.MessageSystem.showViolationMessage(
                    'PEDESTRIAN CROSSING',
                    `Approaching pedestrian crossing at ${nearestLane.street}! Slow down and check for pedestrians.`,
                    'warning'
                );
                this.pedestrianLaneWarning = true;
                this.lastPedestrianLaneWarning = Date.now();
                
                // Moderate speed reduction near pedestrian crossings
                this.speed *= 0.6;
                console.log(`🚶 Pedestrian crossing nearby! Distance: ${nearestDistance.toFixed(1)}px, Speed reduced to ${this.speed.toFixed(2)}`);
            }
        } else if (nearestDistance > 100) {
            // Reset warning when far from pedestrian crossings
            this.pedestrianLaneWarning = false;
        }
    },
    
    /**
     * Check for approaching intersections and warn driver
     */
    checkIntersectionWarnings() {
        if (!window.WorldModule || !window.MessageSystem) return;
        
        // Get intersections from WorldModule
        const intersections = window.WorldModule.getIntersections ? window.WorldModule.getIntersections() : [];
        
        intersections.forEach(intersection => {
            const distance = Math.sqrt(
                Math.pow(this.x + this.width/2 - intersection.x, 2) + 
                Math.pow(this.y + this.height/2 - intersection.y, 2)
            );
            
            // Warn when approaching intersection (within warning radius but not too close)
            const warningRadius = intersection.radius + 60;
            if (distance < warningRadius && distance > intersection.radius && Math.abs(this.speed) > 15) {
                const intersectionKey = `intersection_${intersection.x}_${intersection.y}`;
                
                if (!this.intersectionWarnings) {
                    this.intersectionWarnings = {};
                }
                
                if (!this.intersectionWarnings[intersectionKey] || 
                    Date.now() - this.intersectionWarnings[intersectionKey] > 8000) {
                    
                    window.MessageSystem.showViolationMessage(
                        'INTERSECTION AHEAD',
                        `Approaching ${intersection.name || 'intersection'}. Reduce speed and check for traffic.`,
                        'info'
                    );
                    this.intersectionWarnings[intersectionKey] = Date.now();
                    
                    // Slight speed reduction when approaching intersection
                    this.speed *= 0.85;
                    console.log(`🛣️ Intersection ahead: ${intersection.name}, Distance: ${distance.toFixed(1)}px`);
                }
            }
        });
    },
    
    /**
     * Update engine sound simulation
     */
    updateEngineSound() {
        this.engineSound.rpm = Math.abs(this.speed) * 50;
        this.engineSound.volume = Math.min(this.engineSound.rpm / 5000, 1);
    },
    
    /**
     * Update light animations
     */
    updateLights(deltaTime) {
        // Update turn signal blinking
        this.lights.turn.timer += deltaTime;
        if (this.lights.turn.timer >= 0.5) {
            this.lights.turn.timer = 0;
        }
    },
    
    /**
     * Keep car within world boundaries
     */
    constrainToWorld() {
        if (window.WorldModule) {
            const worldDim = window.WorldModule.getDimensions();
            
            // Keep car within world bounds with padding
            const padding = 30;
            this.x = Math.max(padding, Math.min(worldDim.width - padding, this.x));
            this.y = Math.max(padding, Math.min(worldDim.height - padding, this.y));
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
        return Math.abs(this.speed) * 0.6;
    },
    
    /**
     * Get car bounding box for collisions
     */
    getBoundingBox() {
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
        console.log('🚨 EMERGENCY STOP ENGAGED!');
        
        setTimeout(() => {
            this.lights.brake = false;
        }, 1000);
    },
    
    /**
     * Enhanced render function with realistic 2D graphics
     */
    render(ctx, camera) {
        const screenX = this.x - camera.x;
        const screenY = this.y - camera.y;
        
        ctx.save();
        ctx.translate(screenX, screenY);
        ctx.rotate(this.angle);
        
        // Enhanced shadow with better positioning
        ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
        ctx.beginPath();
        ctx.ellipse(2, 3, this.width/2 + 2, this.height/2 + 1, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // Main car body with gradient
        const gradient = ctx.createLinearGradient(-this.width/2, -this.height/2, this.width/2, this.height/2);
        gradient.addColorStop(0, this.color);
        gradient.addColorStop(0.5, '#FF5722');
        gradient.addColorStop(1, this.secondaryColor);
        
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.roundRect(-this.width/2, -this.height/2, this.width, this.height, 4);
        ctx.fill();
        
        // Car outline
        ctx.strokeStyle = '#2C3E50';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.roundRect(-this.width/2, -this.height/2, this.width, this.height, 4);
        ctx.stroke();
        
        // Enhanced windshield and windows
        ctx.fillStyle = '#87CEEB';
        ctx.fillRect(-this.width/2 + 4, -this.height/2 + 3, this.width - 8, this.height - 6);
        
        // Window frames
        ctx.strokeStyle = '#34495E';
        ctx.lineWidth = 1;
        ctx.strokeRect(-this.width/2 + 4, -this.height/2 + 3, this.width - 8, this.height - 6);
        
        // Front grille
        ctx.fillStyle = '#2C3E50';
        ctx.fillRect(this.width/2 - 4, -6, 4, 12);
        
        // Headlights
        if (this.lights.headlights) {
            ctx.fillStyle = '#FFFFE0';
            ctx.beginPath();
            ctx.ellipse(this.width/2 - 1, -8, 3, 2, 0, 0, 2 * Math.PI);
            ctx.fill();
            ctx.beginPath();
            ctx.ellipse(this.width/2 - 1, 8, 3, 2, 0, 0, 2 * Math.PI);
            ctx.fill();
        }
        
        // Brake lights
        if (this.lights.brake) {
            ctx.fillStyle = '#FF0000';
            ctx.beginPath();
            ctx.ellipse(-this.width/2 + 1, -6, 2, 3, 0, 0, 2 * Math.PI);
            ctx.fill();
            ctx.beginPath();
            ctx.ellipse(-this.width/2 + 1, 6, 2, 3, 0, 0, 2 * Math.PI);
            ctx.fill();
        }
        
        // Turn signals with blinking effect
        const turnBlink = this.lights.turn.timer < 0.25;
        
        if (this.lights.turn.left && turnBlink) {
            ctx.fillStyle = '#FFA500';
            ctx.beginPath();
            ctx.ellipse(-this.width/2 + 1, -10, 2, 2, 0, 0, 2 * Math.PI);
            ctx.fill();
        }
        
        if (this.lights.turn.right && turnBlink) {
            ctx.fillStyle = '#FFA500';
            ctx.beginPath();
            ctx.ellipse(-this.width/2 + 1, 10, 2, 2, 0, 0, 2 * Math.PI);
            ctx.fill();
        }
        
        // Enhanced wheels
        ctx.fillStyle = '#2C3E50';
        const wheelSize = 5;
        
        // Front wheels
        ctx.beginPath();
        ctx.ellipse(this.width/2 - 8, -this.height/2 - 1, wheelSize, wheelSize/2, 0, 0, 2 * Math.PI);
        ctx.fill();
        ctx.beginPath();
        ctx.ellipse(this.width/2 - 8, this.height/2 + 1, wheelSize, wheelSize/2, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // Rear wheels
        ctx.beginPath();
        ctx.ellipse(-this.width/2 + 8, -this.height/2 - 1, wheelSize, wheelSize/2, 0, 0, 2 * Math.PI);
        ctx.fill();
        ctx.beginPath();
        ctx.ellipse(-this.width/2 + 8, this.height/2 + 1, wheelSize, wheelSize/2, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // Speed indicator arrow
        if (Math.abs(this.speed) > 5) {
            ctx.strokeStyle = '#FFD700';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.moveTo(this.width/2 - 2, 0);
            ctx.lineTo(this.width/2 + 8, 0);
            ctx.moveTo(this.width/2 + 4, -3);
            ctx.lineTo(this.width/2 + 8, 0);
            ctx.lineTo(this.width/2 + 4, 3);
            ctx.stroke();
        }
        
        ctx.restore();
        
        // Debug information overlay
        if (window.SimulationConfig?.debug) {
            this.renderDebugInfo(ctx, screenX, screenY);
        }
    },
    
    /**
     * Render debug information
     */
    renderDebugInfo(ctx, screenX, screenY) {
        ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
        ctx.fillRect(screenX + 40, screenY - 60, 220, 100);
        
        ctx.fillStyle = '#00FF00';
        ctx.font = '11px monospace';
        ctx.textAlign = 'left';
        
        const debugText = [
            `Position: (${Math.round(this.x)}, ${Math.round(this.y)})`,
            `Speed: ${this.getCurrentSpeed().toFixed(1)} km/h`,
            `Angle: ${(this.angle * 180 / Math.PI).toFixed(1)}°`,
            `On Road: ${this.isOnRoad() ? 'Yes' : 'No'}`,
            `Controls: ${Object.entries(this.controls).filter(([k,v]) => v).map(([k]) => k).join(', ') || 'None'}`,
            `Engine RPM: ${this.engineSound.rpm.toFixed(0)}`
        ];
        
        debugText.forEach((text, index) => {
            ctx.fillText(text, screenX + 45, screenY - 45 + index * 14);
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
        console.log('🔄 Car reset to starting position - Ready to drive!');
    }
};

// Export module
window.CarModule = CarModule;