/**
 * Car Module - Enhanced Realistic Player Vehicle
 * Handles realistic vehicle movement, physics, and visual representation
 */

const CarModule = {
    // Car position and rotation (SAFELY IN CENTER OF FIRST ROADS)
    x: 210,  // Center of first vertical road (150 + streetWidth/2 = 150 + 60 = 210)
    y: 210,  // Center of first horizontal road (150 + streetWidth/2 = 150 + 60 = 210)
    angle: 0, // Rotation in radians
    
    // Vehicle type (car or motorcycle)
    vehicleType: 'car', // Will be set from config
    
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
        console.log('🔧 CarModule.init() called');
        console.log('🔍 Checking SimulationConfig:', window.SimulationConfig);
        
        // Get vehicle type from config
        if (window.SimulationConfig && window.SimulationConfig.vehicleType && window.SimulationConfig.vehicleType !== 'NONE') {
            this.vehicleType = window.SimulationConfig.vehicleType;
            console.log(`✅ Vehicle type set to: ${this.vehicleType}`);
            
            // Adjust dimensions for motorcycle
            if (this.vehicleType === 'motorcycle') {
                this.width = 30;  // Narrower
                this.height = 15; // Shorter
                this.maxSpeed = 180; // Slightly faster
                this.acceleration = 180; // Quicker acceleration
                this.turnSpeed = 3.5; // Better turning
                console.log('🏍️ Motorcycle dimensions set: width=30, height=15');
            } else {
                console.log('🚗 Car dimensions set: width=45, height=25');
            }
        } else {
            console.warn('⚠️ SimulationConfig or vehicleType not found, defaulting to car');
            console.log('⚠️ SimulationConfig exists?', !!window.SimulationConfig);
            if (window.SimulationConfig) {
                console.log('⚠️ vehicleType value:', window.SimulationConfig.vehicleType);
            }
        }
        
        const icon = this.vehicleType === 'motorcycle' ? '🏍️' : '🚗';
        console.log(`${icon} Initializing Enhanced Player Vehicle (Type: ${this.vehicleType})...`);
        this.setupControls();
        this.resetPosition();
        console.log(`🔍 DEBUG: ${this.vehicleType} initialized at position (${this.x}, ${this.y}) with dimensions ${this.width}x${this.height}`);
        console.log('✅ Vehicle ready for driving - Press W or UP arrow to move!');
    },
    
    /**
     * Reset car to starting position
     */
    resetPosition() {
        this.x = 210;  // Center of first vertical road (150 + 60 = 210)
        this.y = 210;  // Center of first horizontal road (150 + 60 = 210)
        this.angle = 0;
        this.speed = 0;
        this.resetLights();
        console.log('🔄 Car reset to starting position - CENTER OF INTERSECTION');
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
        
        // Render based on vehicle type
        if (this.vehicleType === 'motorcycle') {
            this.renderMotorcycle(ctx);
        } else {
            this.renderCar(ctx);
        }
        
        ctx.restore();
        
        // Debug information overlay
        if (window.SimulationConfig?.debug) {
            this.renderDebugInfo(ctx, screenX, screenY);
        }
    },
    
    /**
     * Render motorcycle - Simple iconic top-down view (like image 3)
     */
    renderMotorcycle(ctx) {
        // Shadow
        ctx.fillStyle = 'rgba(0, 0, 0, 0.25)';
        ctx.beginPath();
        ctx.ellipse(1, 2, this.width/2, this.height/2, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // BACK WHEEL (Black tire)
        ctx.fillStyle = '#2d2d2d';
        ctx.beginPath();
        ctx.arc(-this.width/2 + 6, 0, 4, 0, 2 * Math.PI);
        ctx.fill();
        
        // Back wheel rim
        ctx.fillStyle = '#555';
        ctx.beginPath();
        ctx.arc(-this.width/2 + 6, 0, 2, 0, 2 * Math.PI);
        ctx.fill();
        
        // RIDER - Body (brown/tan jacket)
        ctx.fillStyle = '#c4a57b';
        ctx.beginPath();
        ctx.ellipse(-2, 0, 6, 5, 0, 0, 2 * Math.PI);
        ctx.fill();
        ctx.strokeStyle = '#a08860';
        ctx.lineWidth = 1;
        ctx.stroke();
        
        // RIDER - Head/Helmet (Blue helmet like image 3)
        ctx.fillStyle = '#4a90e2';
        ctx.beginPath();
        ctx.arc(3, 0, 4, 0, 2 * Math.PI);
        ctx.fill();
        
        // Helmet highlight
        ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
        ctx.beginPath();
        ctx.arc(2, -1, 2, 0, 2 * Math.PI);
        ctx.fill();
        
        // Helmet visor (dark)
        ctx.fillStyle = '#1a1a1a';
        ctx.beginPath();
        ctx.ellipse(4, 0, 1.5, 2, 0, -Math.PI/2, Math.PI/2);
        ctx.fill();
        
        // ARMS/HANDLEBARS
        ctx.strokeStyle = '#c4a57b';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        // Left arm
        ctx.beginPath();
        ctx.moveTo(1, -4);
        ctx.lineTo(7, -2);
        ctx.stroke();
        // Right arm
        ctx.beginPath();
        ctx.moveTo(1, 4);
        ctx.lineTo(7, 2);
        ctx.stroke();
        
        // Handlebars (black)
        ctx.strokeStyle = '#2d2d2d';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(7, -3);
        ctx.lineTo(9, -2);
        ctx.moveTo(7, 3);
        ctx.lineTo(9, 2);
        ctx.stroke();
        
        // MOTORCYCLE BODY/FRAME (blue to match helmet)
        ctx.fillStyle = '#4a90e2';
        ctx.beginPath();
        ctx.ellipse(-5, 0, 7, 4, 0, 0, 2 * Math.PI);
        ctx.fill();
        ctx.strokeStyle = '#2a5a9a';
        ctx.lineWidth = 1.5;
        ctx.stroke();
        
        // Body shine/highlight
        ctx.fillStyle = 'rgba(255, 255, 255, 0.25)';
        ctx.beginPath();
        ctx.ellipse(-5, -1, 4, 2, 0, 0, Math.PI);
        ctx.fill();
        
        // FRONT WHEEL (Black tire)
        ctx.fillStyle = '#2d2d2d';
        ctx.beginPath();
        ctx.arc(this.width/2 - 6, 0, 4, 0, 2 * Math.PI);
        ctx.fill();
        
        // Front wheel rim
        ctx.fillStyle = '#555';
        ctx.beginPath();
        ctx.arc(this.width/2 - 6, 0, 2, 0, 2 * Math.PI);
        ctx.fill();
        
        // FRONT FENDER (small arc above front wheel)
        ctx.strokeStyle = '#4a90e2';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(this.width/2 - 6, 0, 5, -Math.PI/3, Math.PI/3);
        ctx.stroke();
        
        // HEADLIGHT (small white circle when moving)
        if (Math.abs(this.speed) > 5) {
            ctx.fillStyle = '#fff';
            ctx.shadowColor = '#fff';
            ctx.shadowBlur = 8;
            ctx.beginPath();
            ctx.arc(this.width/2 - 2, 0, 2, 0, 2 * Math.PI);
            ctx.fill();
            ctx.shadowBlur = 0;
        }
        
        // TAIL LIGHT (red when braking)
        if (this.lights.brake) {
            ctx.fillStyle = '#ff0000';
            ctx.shadowColor = '#ff0000';
            ctx.shadowBlur = 6;
            ctx.beginPath();
            ctx.arc(-this.width/2 + 2, 0, 1.5, 0, 2 * Math.PI);
            ctx.fill();
            ctx.shadowBlur = 0;
        }
    },
    
    /**
     * Render car - Simple iconic top-down view (like image 4)
     */
    renderCar(ctx) {
        // Shadow
        ctx.fillStyle = 'rgba(0, 0, 0, 0.25)';
        ctx.beginPath();
        ctx.ellipse(1, 2, this.width/2 + 1, this.height/2 + 1, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // BACK LEFT WHEEL (black tire)
        ctx.fillStyle = '#2d2d2d';
        ctx.beginPath();
        ctx.ellipse(-this.width/2 + 8, -this.height/2 - 1, 5, 3, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // Back left rim
        ctx.fillStyle = '#6a6a6a';
        ctx.beginPath();
        ctx.ellipse(-this.width/2 + 8, -this.height/2 - 1, 3, 2, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // BACK RIGHT WHEEL (black tire)
        ctx.fillStyle = '#2d2d2d';
        ctx.beginPath();
        ctx.ellipse(-this.width/2 + 8, this.height/2 + 1, 5, 3, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // Back right rim
        ctx.fillStyle = '#6a6a6a';
        ctx.beginPath();
        ctx.ellipse(-this.width/2 + 8, this.height/2 + 1, 3, 2, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // MAIN CAR BODY (clean gray/silver like image 4)
        const bodyGradient = ctx.createLinearGradient(-this.width/2, 0, this.width/2, 0);
        bodyGradient.addColorStop(0, '#b0b0b0');
        bodyGradient.addColorStop(0.5, '#d0d0d0');
        bodyGradient.addColorStop(1, '#b0b0b0');
        
        ctx.fillStyle = bodyGradient;
        ctx.beginPath();
        ctx.roundRect(-this.width/2 + 5, -this.height/2, this.width - 10, this.height, 4);
        ctx.fill();
        
        // Car body outline (darker gray)
        ctx.strokeStyle = '#7a7a7a';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.roundRect(-this.width/2 + 5, -this.height/2, this.width - 10, this.height, 4);
        ctx.stroke();
        
        // ROOF/CABIN (slightly darker gray rectangle)
        ctx.fillStyle = '#9a9a9a';
        ctx.beginPath();
        ctx.roundRect(-8, -7, 20, 14, 3);
        ctx.fill();
        ctx.strokeStyle = '#7a7a7a';
        ctx.lineWidth = 1;
        ctx.stroke();
        
        // WINDSHIELD (front - light blue tint)
        ctx.fillStyle = 'rgba(150, 200, 230, 0.6)';
        ctx.beginPath();
        ctx.roundRect(this.width/2 - 12, -7, 7, 14, 2);
        ctx.fill();
        ctx.strokeStyle = '#5a5a5a';
        ctx.lineWidth = 1;
        ctx.stroke();
        
        // REAR WINDOW (back - light blue tint)
        ctx.fillStyle = 'rgba(150, 200, 230, 0.5)';
        ctx.beginPath();
        ctx.roundRect(-this.width/2 + 9, -6, 5, 12, 1.5);
        ctx.fill();
        ctx.strokeStyle = '#5a5a5a';
        ctx.lineWidth = 1;
        ctx.stroke();
        
        // SIDE WINDOWS (left and right)
        ctx.fillStyle = 'rgba(150, 200, 230, 0.5)';
        // Left window
        ctx.fillRect(-6, -this.height/2 + 1, 12, 3);
        // Right window
        ctx.fillRect(-6, this.height/2 - 4, 12, 3);
        
        // SIDE MIRRORS (small rectangles)
        ctx.fillStyle = '#7a7a7a';
        // Left mirror
        ctx.fillRect(-10, -this.height/2 - 2, 3, 2);
        // Right mirror
        ctx.fillRect(-10, this.height/2, 3, 2);
        
        // FRONT LEFT WHEEL (black tire)
        ctx.fillStyle = '#2d2d2d';
        ctx.beginPath();
        ctx.ellipse(this.width/2 - 8, -this.height/2 - 1, 5, 3, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // Front left rim
        ctx.fillStyle = '#6a6a6a';
        ctx.beginPath();
        ctx.ellipse(this.width/2 - 8, -this.height/2 - 1, 3, 2, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // FRONT RIGHT WHEEL (black tire)
        ctx.fillStyle = '#2d2d2d';
        ctx.beginPath();
        ctx.ellipse(this.width/2 - 8, this.height/2 + 1, 5, 3, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // Front right rim
        ctx.fillStyle = '#6a6a6a';
        ctx.beginPath();
        ctx.ellipse(this.width/2 - 8, this.height/2 + 1, 3, 2, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // HEADLIGHTS (white circles at front)
        if (Math.abs(this.speed) > 5) {
            ctx.fillStyle = '#fff';
            ctx.shadowColor = '#fff';
            ctx.shadowBlur = 8;
        } else {
            ctx.fillStyle = '#ffffcc';
        }
        // Left headlight
        ctx.beginPath();
        ctx.arc(this.width/2 - 2, -8, 2, 0, 2 * Math.PI);
        ctx.fill();
        // Right headlight
        ctx.beginPath();
        ctx.arc(this.width/2 - 2, 8, 2, 0, 2 * Math.PI);
        ctx.fill();
        ctx.shadowBlur = 0;
        
        // FRONT BUMPER/GRILLE (dark gray bar)
        ctx.fillStyle = '#5a5a5a';
        ctx.fillRect(this.width/2 - 5, -6, 3, 12);
        
        // TAIL LIGHTS (red at back)
        if (this.lights.brake) {
            ctx.fillStyle = '#ff0000';
            ctx.shadowColor = '#ff0000';
            ctx.shadowBlur = 6;
        } else {
            ctx.fillStyle = '#cc0000';
        }
        // Left tail light
        ctx.beginPath();
        ctx.arc(-this.width/2 + 6, -8, 1.5, 0, 2 * Math.PI);
        ctx.fill();
        // Right tail light
        ctx.beginPath();
        ctx.arc(-this.width/2 + 6, 8, 1.5, 0, 2 * Math.PI);
        ctx.fill();
        ctx.shadowBlur = 0;
        
        // TURN SIGNALS
        const turnBlink = this.lights.turn.timer < 0.25;
        
        if (this.lights.turn.left && turnBlink) {
            ctx.fillStyle = '#ffa500';
            ctx.shadowColor = '#ffa500';
            ctx.shadowBlur = 6;
            ctx.beginPath();
            ctx.arc(-this.width/2 + 6, -10, 1.5, 0, 2 * Math.PI);
            ctx.fill();
            ctx.shadowBlur = 0;
        }
        
        if (this.lights.turn.right && turnBlink) {
            ctx.fillStyle = '#ffa500';
            ctx.shadowColor = '#ffa500';
            ctx.shadowBlur = 6;
            ctx.beginPath();
            ctx.arc(-this.width/2 + 6, 10, 1.5, 0, 2 * Math.PI);
            ctx.fill();
            ctx.shadowBlur = 0;
        }
        
        // ROOF HIGHLIGHT (white shine on top)
        ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
        ctx.beginPath();
        ctx.ellipse(0, 0, 8, 5, 0, 0, 2 * Math.PI);
        ctx.fill();
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