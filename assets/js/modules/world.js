/**
 * World Module - Enhanced Realistic 2D City Environment
 * Creates a beautiful top-down city view with detailed graphics
 */

const WorldModule = {
    // World dimensions
    width: 1600,
    height: 1200,
    
    // Visual elements
    roads: [],
    buildings: [],
    trafficLights: [],
    scenarioMarkers: [],
    decorations: [], // Trees, streetlights, etc.
    
    // Colors and styling
    colors: {
        grass: '#2E7D32',
        grassDark: '#1B5E20',
        road: '#424242',
        roadLine: '#FFD54F',
        sidewalk: '#BDBDBD',
        building: '#8D6E63',
        buildingWindow: '#1976D2',
        tree: '#388E3C'
    },
    
    // Traffic light timing
    lightTimer: 0,
    lightCycle: 8000, // 8 seconds per light cycle
    
    /**
     * Initialize the world environment
     */
    init() {
        console.log('🌍 Building realistic city world...');
        this.createCityLayout();
        this.createTrafficElements();
        console.log('✅ City world ready with traffic infrastructure');
    },
    
    /**
     * Create the main city layout with enhanced graphics
     */
    createCityLayout() {
        this.createEnhancedRoadNetwork();
        this.createDetailedBuildings();
        this.createDecorations();
        this.createScenarioMarkers();
    },
    
    /**
     * Create enhanced road network with multiple lanes and details
     */
    createEnhancedRoadNetwork() {
        // Main boulevard (horizontal)
        this.roads.push({
            x: 0,
            y: 400,
            width: this.width,
            height: 160,
            type: 'boulevard',
            lanes: 4,
            markings: true,
            sidewalks: true
        });
        
        // Secondary street
        this.roads.push({
            x: 0,
            y: 700,
            width: this.width,
            height: 120,
            type: 'street',
            lanes: 2,
            markings: true,
            sidewalks: true
        });
        
        // Vertical roads (intersecting streets)
        this.roads.push({
            x: 300,
            y: 0,
            width: 120,
            height: this.height,
            type: 'avenue',
            lanes: 3,
            markings: true,
            sidewalks: true
        });
        
        this.roads.push({
            x: 600,
            y: 0,
            width: 120,
            height: this.height,
            type: 'avenue',
            lanes: 3,
            markings: true,
            sidewalks: true
        });
        
        this.roads.push({
            x: 900,
            y: 0,
            width: 120,
            height: this.height,
            type: 'avenue',
            lanes: 3,
            markings: true,
            sidewalks: true
        });
        
        this.roads.push({
            x: 1200,
            y: 0,
            width: 120,
            height: this.height,
            type: 'avenue',
            lanes: 3,
            markings: true,
            sidewalks: true
        });
    },
    
    /**
     * Create detailed buildings with enhanced graphics
     */
    createDetailedBuildings() {
        // Residential area (left side)
        this.buildings.push(
            { x: 50, y: 50, width: 200, height: 300, color: '#D84315', type: 'Apartment Complex', floors: 3 },
            { x: 50, y: 600, width: 200, height: 80, color: '#F57C00', type: 'Coffee Shop', floors: 1 },
            { x: 50, y: 880, width: 200, height: 120, color: '#E65100', type: 'Restaurant', floors: 2 }
        );
        
        // Commercial district (center-left)
        this.buildings.push(
            { x: 450, y: 50, width: 120, height: 300, color: '#5D4037', type: 'Office Building', floors: 4 },
            { x: 450, y: 600, width: 120, height: 80, color: '#6D4C41', type: 'Bank', floors: 1 },
            { x: 450, y: 880, width: 120, height: 120, color: '#795548', type: 'Shopping Center', floors: 2 }
        );
        
        // Business district (center)
        this.buildings.push(
            { x: 750, y: 50, width: 120, height: 300, color: '#424242', type: 'Corporate Tower', floors: 5 },
            { x: 750, y: 600, width: 120, height: 80, color: '#616161', type: 'Tech Company', floors: 1 },
            { x: 750, y: 880, width: 120, height: 120, color: '#757575', type: 'Startup Hub', floors: 2 }
        );
        
        // Downtown area (center-right)
        this.buildings.push(
            { x: 1050, y: 50, width: 120, height: 300, color: '#1565C0', type: 'City Hall', floors: 4 },
            { x: 1050, y: 600, width: 120, height: 80, color: '#1976D2', type: 'Police Station', floors: 1 },
            { x: 1050, y: 880, width: 120, height: 120, color: '#1E88E5', type: 'Fire Department', floors: 2 }
        );
        
        // Educational area (right side)
        this.buildings.push(
            { x: 1350, y: 50, width: 200, height: 300, color: '#388E3C', type: 'University', floors: 3 },
            { x: 1350, y: 600, width: 200, height: 80, color: '#43A047', type: 'Library', floors: 1 },
            { x: 1350, y: 880, width: 200, height: 120, color: '#4CAF50', type: 'Research Center', floors: 2 }
        );
    },
    
    /**
     * Create decorative elements (trees, streetlights, etc.)
     */
    createDecorations() {
        this.decorations = [];
        
        // Street trees along sidewalks
        for (let x = 80; x < this.width - 80; x += 150) {
            if (!this.isOnRoad(x, 370)) {
                this.decorations.push({ type: 'tree', x: x, y: 370, size: 25 });
            }
            if (!this.isOnRoad(x, 590)) {
                this.decorations.push({ type: 'tree', x: x, y: 590, size: 25 });
            }
            if (!this.isOnRoad(x, 850)) {
                this.decorations.push({ type: 'tree', x: x, y: 850, size: 25 });
            }
        }
        
        // Streetlights at intersections
        const intersections = [
            { x: 360, y: 460 }, { x: 660, y: 460 }, { x: 960, y: 460 }, { x: 1260, y: 460 },
            { x: 360, y: 760 }, { x: 660, y: 760 }, { x: 960, y: 760 }, { x: 1260, y: 760 }
        ];
        
        intersections.forEach(pos => {
            this.decorations.push({ type: 'streetlight', x: pos.x - 20, y: pos.y - 20 });
            this.decorations.push({ type: 'streetlight', x: pos.x + 20, y: pos.y - 20 });
        });
    },
    
    /**
     * Create traffic elements (lights, signs)
     */
    createTrafficElements() {
        // Traffic lights at major intersections
        this.trafficLights = [
            { x: 340, y: 340, state: 'RED', timer: 0 },    // Intersection 1
            { x: 640, y: 340, state: 'GREEN', timer: 2000 }, // Intersection 2
            { x: 940, y: 340, state: 'YELLOW', timer: 4000 }, // Intersection 3
            { x: 340, y: 540, state: 'GREEN', timer: 1000 }, // Intersection 4
            { x: 640, y: 540, state: 'RED', timer: 3000 },   // Intersection 5
        ];
    },
    
    /**
     * Create scenario markers for the 5 questions
     */
    createScenarioMarkers() {
        this.scenarioMarkers = [
            // Scenario 1: Red Traffic Light
            {
                id: 1,
                x: 280, y: 360,
                type: 'RED_LIGHT',
                triggerRadius: 80,
                active: true,
                icon: '🚦'
            },
            
            // Scenario 2: Stop Sign
            {
                id: 2,
                x: 500, y: 360,
                type: 'STOP_SIGN',
                triggerRadius: 60,
                active: true,
                icon: '🛑'
            },
            
            // Scenario 3: Pedestrian Crossing
            {
                id: 3,
                x: 700, y: 360,
                type: 'PEDESTRIAN',
                triggerRadius: 70,
                active: true,
                icon: '🚶'
            },
            
            // Scenario 4: School Zone
            {
                id: 4,
                x: 1000, y: 250,
                type: 'SCHOOL_ZONE',
                triggerRadius: 90,
                active: true,
                icon: '🏫'
            },
            
            // Scenario 5: Busy Intersection
            {
                id: 5,
                x: 640, y: 540,
                type: 'INTERSECTION',
                triggerRadius: 85,
                active: true,
                icon: '⚠️'
            }
        ];
    },
    
    /**
     * Update world elements (traffic lights)
     */
    update(deltaTime) {
        this.updateTrafficLights(deltaTime);
    },
    
    /**
     * Update traffic light states
     */
    updateTrafficLights(deltaTime) {
        this.lightTimer += deltaTime * 1000; // Convert to milliseconds
        
        this.trafficLights.forEach(light => {
            light.timer += deltaTime * 1000;
            
            if (light.timer >= this.lightCycle) {
                light.timer = 0;
                
                // Cycle through states
                switch(light.state) {
                    case 'RED':
                        light.state = 'GREEN';
                        break;
                    case 'GREEN':
                        light.state = 'YELLOW';
                        break;
                    case 'YELLOW':
                        light.state = 'RED';
                        break;
                }
            }
        });
    },
    
    /**
     * Render the entire enhanced world
     */
    render(ctx, camera) {
        // Render background
        this.renderBackground(ctx, camera);
        
        // Render roads
        this.renderRoads(ctx, camera);
        
        // Render buildings
        this.renderBuildings(ctx, camera);
        
        // Render decorations (trees, streetlights)
        this.renderDecorations(ctx, camera);
        
        // Render traffic elements
        this.renderTrafficElements(ctx, camera);
        
        // Render scenario markers
        this.renderScenarioMarkers(ctx, camera);
    },
    
    /**
     * Render enhanced background with realistic textures
     */
    renderBackground(ctx, camera) {
        // Main grass background
        ctx.fillStyle = this.colors.grass;
        ctx.fillRect(-camera.x, -camera.y, this.width, this.height);
        
        // Add grass texture with subtle patterns
        ctx.fillStyle = this.colors.grassDark;
        for (let x = 0; x < this.width; x += 40) {
            for (let y = 0; y < this.height; y += 40) {
                if ((x + y) % 80 === 0) {
                    ctx.fillRect(x - camera.x + Math.random() * 10, 
                               y - camera.y + Math.random() * 10, 15, 15);
                }
            }
        }
        
        // Add some darker grass patches for realism
        ctx.fillStyle = 'rgba(46, 125, 50, 0.3)';
        for (let i = 0; i < 50; i++) {
            const x = Math.random() * this.width;
            const y = Math.random() * this.height;
            if (!this.isOnRoad(x, y)) {
                ctx.beginPath();
                ctx.arc(x - camera.x, y - camera.y, Math.random() * 30 + 10, 0, Math.PI * 2);
                ctx.fill();
            }
        }
    },
    
    /**
     * Render enhanced roads with realistic details
     */
    renderRoads(ctx, camera) {
        this.roads.forEach(road => {
            const screenX = road.x - camera.x;
            const screenY = road.y - camera.y;
            
            // Render sidewalks first
            if (road.sidewalks) {
                ctx.fillStyle = this.colors.sidewalk;
                if (road.type.includes('horizontal') || road.type === 'boulevard' || road.type === 'street') {
                    // Top sidewalk
                    ctx.fillRect(screenX, screenY - 15, road.width, 15);
                    // Bottom sidewalk
                    ctx.fillRect(screenX, screenY + road.height, road.width, 15);
                } else {
                    // Left sidewalk
                    ctx.fillRect(screenX - 15, screenY, 15, road.height);
                    // Right sidewalk
                    ctx.fillRect(screenX + road.width, screenY, 15, road.height);
                }
            }
            
            // Road surface with slight gradient
            const gradient = ctx.createLinearGradient(screenX, screenY, screenX, screenY + road.height);
            gradient.addColorStop(0, '#515151');
            gradient.addColorStop(0.5, this.colors.road);
            gradient.addColorStop(1, '#373737');
            
            ctx.fillStyle = gradient;
            ctx.fillRect(screenX, screenY, road.width, road.height);
            
            // Road border lines
            ctx.strokeStyle = '#FFD54F';
            ctx.lineWidth = 3;
            ctx.strokeRect(screenX, screenY, road.width, road.height);
            
            // Lane markings with enhanced detail
            if (road.markings) {
                this.renderEnhancedLaneMarkings(ctx, road, camera);
            }
            
            // Add road texture
            this.renderRoadTexture(ctx, road, camera);
        });
    },
    
    /**
     * Render enhanced lane markings
     */
    renderEnhancedLaneMarkings(ctx, road, camera) {
        ctx.strokeStyle = '#FFFFFF';
        ctx.lineWidth = 3;
        ctx.setLineDash([20, 15]);
        
        if (road.type === 'boulevard') {
            // Multiple lanes for boulevard
            for (let lane = 1; lane < road.lanes; lane++) {
                const lanePos = (road.height / road.lanes) * lane;
                ctx.beginPath();
                ctx.moveTo(road.x - camera.x, road.y + lanePos - camera.y);
                ctx.lineTo(road.x + road.width - camera.x, road.y + lanePos - camera.y);
                ctx.stroke();
            }
        } else if (road.type.includes('horizontal') || road.type === 'street') {
            // Center line for horizontal roads
            const centerY = road.y + road.height / 2 - camera.y;
            ctx.beginPath();
            ctx.moveTo(road.x - camera.x, centerY);
            ctx.lineTo(road.x + road.width - camera.x, centerY);
            ctx.stroke();
        } else {
            // Center and lane lines for vertical roads
            for (let lane = 1; lane < road.lanes; lane++) {
                const lanePos = (road.width / road.lanes) * lane;
                ctx.beginPath();
                ctx.moveTo(road.x + lanePos - camera.x, road.y - camera.y);
                ctx.lineTo(road.x + lanePos - camera.x, road.y + road.height - camera.y);
                ctx.stroke();
            }
        }
        
        ctx.setLineDash([]);
    },
    
    /**
     * Render road texture for realism
     */
    renderRoadTexture(ctx, road, camera) {
        // Add subtle asphalt texture
        ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        for (let x = road.x; x < road.x + road.width; x += 8) {
            for (let y = road.y; y < road.y + road.height; y += 8) {
                if (Math.random() > 0.7) {
                    ctx.fillRect(x - camera.x, y - camera.y, 2, 2);
                }
            }
        }
    },
    
    /**
     * Render detailed buildings with enhanced graphics
     */
    renderBuildings(ctx, camera) {
        this.buildings.forEach(building => {
            const screenX = building.x - camera.x;
            const screenY = building.y - camera.y;
            
            // Building shadow (more realistic)
            ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
            ctx.fillRect(screenX + 6, screenY + 6, building.width, building.height);
            
            // Building body with gradient
            const gradient = ctx.createLinearGradient(screenX, screenY, screenX + building.width, screenY);
            gradient.addColorStop(0, building.color);
            gradient.addColorStop(0.3, this.lightenColor(building.color, 20));
            gradient.addColorStop(0.7, building.color);
            gradient.addColorStop(1, this.darkenColor(building.color, 20));
            
            ctx.fillStyle = gradient;
            ctx.fillRect(screenX, screenY, building.width, building.height);
            
            // Building outline
            ctx.strokeStyle = this.darkenColor(building.color, 40);
            ctx.lineWidth = 2;
            ctx.strokeRect(screenX, screenY, building.width, building.height);
            
            // Enhanced building details
            this.renderEnhancedBuildingDetails(ctx, screenX, screenY, building);
            
            // Building label with better styling
            ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
            ctx.fillRect(screenX + 5, screenY + building.height - 25, building.width - 10, 20);
            
            ctx.fillStyle = '#000';
            ctx.font = 'bold 11px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(building.type, screenX + building.width / 2, screenY + building.height - 10);
        });
    },
    
    /**
     * Render enhanced building details
     */
    renderEnhancedBuildingDetails(ctx, x, y, building) {
        const floors = building.floors || 2;
        const floorHeight = building.height / floors;
        
        // Windows for each floor
        for (let floor = 0; floor < floors; floor++) {
            const floorY = y + floor * floorHeight;
            
            // Window rows
            for (let wx = x + 15; wx < x + building.width - 15; wx += 25) {
                for (let wy = floorY + 10; wy < floorY + floorHeight - 10; wy += 20) {
                    // Window frame
                    ctx.fillStyle = this.colors.buildingWindow;
                    ctx.fillRect(wx, wy, 15, 12);
                    
                    // Window glass reflection
                    ctx.fillStyle = 'rgba(135, 206, 235, 0.8)';
                    ctx.fillRect(wx + 1, wy + 1, 13, 10);
                    
                    // Window frame
                    ctx.strokeStyle = '#000';
                    ctx.lineWidth = 1;
                    ctx.strokeRect(wx, wy, 15, 12);
                    
                    // Window cross
                    ctx.beginPath();
                    ctx.moveTo(wx + 7.5, wy);
                    ctx.lineTo(wx + 7.5, wy + 12);
                    ctx.moveTo(wx, wy + 6);
                    ctx.lineTo(wx + 15, wy + 6);
                    ctx.stroke();
                }
            }
        }
        
        // Main entrance
        if (building.width > 80) {
            const doorX = x + building.width / 2 - 10;
            const doorY = y + building.height - 25;
            
            // Door frame
            ctx.fillStyle = this.darkenColor(building.color, 30);
            ctx.fillRect(doorX, doorY, 20, 25);
            
            // Door
            ctx.fillStyle = '#8D6E63';
            ctx.fillRect(doorX + 2, doorY + 2, 16, 21);
            
            // Door handle
            ctx.fillStyle = '#FFD700';
            ctx.beginPath();
            ctx.arc(doorX + 15, doorY + 12, 2, 0, Math.PI * 2);
            ctx.fill();
        }
    },
    
    /**
     * Render traffic lights and signs
     */
    renderTrafficElements(ctx, camera) {
        this.trafficLights.forEach(light => {
            const screenX = light.x - camera.x;
            const screenY = light.y - camera.y;
            
            // Traffic light pole
            ctx.fillStyle = '#666';
            ctx.fillRect(screenX - 3, screenY - 40, 6, 40);
            
            // Traffic light box
            ctx.fillStyle = '#333';
            ctx.fillRect(screenX - 15, screenY - 35, 30, 25);
            
            // Traffic light state
            const lightColors = {
                'RED': '#FF0000',
                'YELLOW': '#FFFF00',
                'GREEN': '#00FF00'
            };
            
            ctx.fillStyle = lightColors[light.state] || '#666';
            ctx.beginPath();
            ctx.arc(screenX, screenY - 22, 8, 0, Math.PI * 2);
            ctx.fill();
            
            // Light glow effect
            ctx.shadowColor = lightColors[light.state] || '#666';
            ctx.shadowBlur = 10;
            ctx.beginPath();
            ctx.arc(screenX, screenY - 22, 6, 0, Math.PI * 2);
            ctx.fill();
            ctx.shadowBlur = 0;
        });
    },
    
    /**
     * Render decorative elements
     */
    renderDecorations(ctx, camera) {
        this.decorations.forEach(decoration => {
            const screenX = decoration.x - camera.x;
            const screenY = decoration.y - camera.y;
            
            switch(decoration.type) {
                case 'tree':
                    this.renderTree(ctx, screenX, screenY, decoration.size);
                    break;
                case 'streetlight':
                    this.renderStreetlight(ctx, screenX, screenY);
                    break;
            }
        });
    },
    
    /**
     * Render a realistic tree
     */
    renderTree(ctx, x, y, size) {
        // Tree trunk
        ctx.fillStyle = '#8D6E63';
        ctx.fillRect(x - 3, y, 6, size * 0.6);
        
        // Tree canopy (multiple circles for natural look)
        ctx.fillStyle = this.colors.tree;
        ctx.beginPath();
        ctx.arc(x, y - size * 0.2, size * 0.8, 0, Math.PI * 2);
        ctx.fill();
        
        // Lighter green highlights
        ctx.fillStyle = '#66BB6A';
        ctx.beginPath();
        ctx.arc(x - size * 0.3, y - size * 0.4, size * 0.4, 0, Math.PI * 2);
        ctx.fill();
        
        ctx.beginPath();
        ctx.arc(x + size * 0.2, y - size * 0.1, size * 0.3, 0, Math.PI * 2);
        ctx.fill();
    },
    
    /**
     * Render a streetlight
     */
    renderStreetlight(ctx, x, y) {
        // Pole
        ctx.fillStyle = '#757575';
        ctx.fillRect(x - 2, y, 4, 30);
        
        // Light fixture
        ctx.fillStyle = '#424242';
        ctx.fillRect(x - 6, y - 8, 12, 8);
        
        // Light glow
        ctx.fillStyle = 'rgba(255, 255, 200, 0.3)';
        ctx.beginPath();
        ctx.arc(x, y - 4, 15, 0, Math.PI * 2);
        ctx.fill();
    },
    
    /**
     * Utility function to lighten a color
     */
    lightenColor(color, percent) {
        const num = parseInt(color.replace("#", ""), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) + amt;
        const G = (num >> 8 & 0x00FF) + amt;
        const B = (num & 0x0000FF) + amt;
        return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
            (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
            (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    },
    
    /**
     * Utility function to darken a color
     */
    darkenColor(color, percent) {
        const num = parseInt(color.replace("#", ""), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) - amt;
        const G = (num >> 8 & 0x00FF) - amt;
        const B = (num & 0x0000FF) - amt;
        return "#" + (0x1000000 + (R > 255 ? 255 : R < 0 ? 0 : R) * 0x10000 +
            (G > 255 ? 255 : G < 0 ? 0 : G) * 0x100 +
            (B > 255 ? 255 : B < 0 ? 0 : B)).toString(16).slice(1);
    },
    renderScenarioMarkers(ctx, camera) {
        this.scenarioMarkers.forEach(marker => {
            if (!marker.active) return;
            
            const screenX = marker.x - camera.x;
            const screenY = marker.y - camera.y;
            
            // Trigger zone (subtle)
            ctx.strokeStyle = 'rgba(255, 255, 0, 0.3)';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 5]);
            ctx.beginPath();
            ctx.arc(screenX, screenY, marker.triggerRadius, 0, Math.PI * 2);
            ctx.stroke();
            ctx.setLineDash([]);
            
            // Marker icon background
            ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
            ctx.beginPath();
            ctx.arc(screenX, screenY, 20, 0, Math.PI * 2);
            ctx.fill();
            
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(screenX, screenY, 20, 0, Math.PI * 2);
            ctx.stroke();
            
            // Marker icon
            ctx.font = '24px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#000';
            ctx.fillText(marker.icon, screenX, screenY);
        });
    },
    
    /**
     * Check if position is on road
     */
    isOnRoad(x, y) {
        return this.roads.some(road => 
            x >= road.x && x <= road.x + road.width &&
            y >= road.y && y <= road.y + road.height
        );
    },
    
    /**
     * Get scenario markers for collision detection
     */
    getScenarioMarkers() {
        return this.scenarioMarkers.filter(marker => marker.active);
    },
    
    /**
     * Get world dimensions
     */
    getDimensions() {
        return { width: this.width, height: this.height };
    },
    
    /**
     * Reset world state
     */
    reset() {
        // Reset traffic lights
        this.trafficLights.forEach((light, index) => {
            light.timer = index * 2000; // Stagger timing
            light.state = index % 3 === 0 ? 'RED' : (index % 3 === 1 ? 'GREEN' : 'YELLOW');
        });
        
        // Reactivate all scenario markers
        this.scenarioMarkers.forEach(marker => {
            marker.active = true;
        });
        
        console.log('🔄 World reset complete');
    }
};

// Export module
window.WorldModule = WorldModule;
