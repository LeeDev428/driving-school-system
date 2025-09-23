/**
 * World Module - Enhanced Organized 2D City Environment
 * Creates a beautiful top-down city view with organized grid layout
 */

const WorldModule = {
    // World dimensions (WILL BE CALCULATED DYNAMICALLY)
    width: 4800,   // Default fallback
    height: 2000,  // Default fallback
    
    // Visual elements
    roads: [],
    buildings: [],
    trafficLights: [],
    scenarioMarkers: [],
    decorations: [], // Trees, streetlights, etc.
    pedestrianLanes: [], // Pedestrian crossing lanes (zebra crossings)
    
    // Enhanced colors for organized city look
    colors: {
        grass: '#4CAF50',           // Brighter green
        grassDark: '#388E3C',       // Darker green for contrast
        road: '#37474F',            // Darker road
        roadLine: '#FFEB3B',        // Bright yellow lines
        sidewalk: '#E0E0E0',        // Light gray sidewalks
        building: '#8D6E63',        // Default building
        buildingWindow: '#1565C0',  // Blue windows
        tree: '#2E7D32',            // Tree color
        roadBorder: '#263238'       // Road borders
    },
    
    // Grid system for organized layout (2-way roads)
    gridSize: 280,
    blockWidth: 220,
    blockHeight: 180,
    streetWidth: 120,  // Much wider roads for proper 2-way traffic
    
    // Traffic light timing
    lightTimer: 0,
    lightCycle: 8000, // 8 seconds per light cycle
    
    /**
     * Initialize the world environment
     */
    init() {
        console.log('🌍 Building realistic city world...');
        
        // Calculate world dimensions dynamically
        this.calculateWorldDimensions();
        
        this.createCityLayout();
        this.createTrafficElements();
        console.log(`✅ City world ready: ${this.width}x${this.height} with traffic infrastructure`);
    },
    
    /**
     * Calculate world dimensions based on screen size
     */
    calculateWorldDimensions() {
        // Use SimulationConfig dimensions if available, otherwise fallback to window dimensions
        if (window.SimulationConfig) {
            this.width = window.SimulationConfig.worldWidth;
            this.height = window.SimulationConfig.worldHeight;
            console.log(`🌍 World dimensions from SimulationConfig: ${this.width}x${this.height}`);
        } else {
            // Fallback to window dimensions
            const screenWidth = window.innerWidth || 1920;
            const screenHeight = window.innerHeight || 1080;
            
            // Make world significantly larger than screen
            this.width = Math.max(screenWidth * 3, 5760);   // 3x screen width, minimum 5760
            this.height = Math.max(screenHeight * 2, 2160); // 2x screen height, minimum 2160
            
            console.log(`🌍 World dimensions calculated (fallback): ${this.width}x${this.height} (Screen: ${screenWidth}x${screenHeight})`);
        }
    },
    
    /**
     * Create the main city layout with enhanced graphics
     */
    createCityLayout() {
        this.createEnhancedRoadNetwork();
        this.createDetailedBuildings();
        this.createDecorations();
        this.createPedestrianLanes();
        this.createScenarioMarkers();
    },
    
    /**
     * Create enhanced road network with organized grid pattern (FULLSCREEN)
     */
    createEnhancedRoadNetwork() {
        // Clear existing roads
        this.roads = [];
        
        console.log(`Creating road network for world: ${this.width}x${this.height}`);
        
        // Create organized grid of roads spanning the full world
        
        // Horizontal roads (running left to right) - spanning full world width
        const horizontalRoadSpacing = Math.max(200, this.height / 8); // Distribute evenly
        const horizontalRoads = [];
        
        for (let i = 0; i < 8; i++) {
            const y = 150 + (i * horizontalRoadSpacing);
            if (y < this.height - 150) {
                horizontalRoads.push({
                    y: y,
                    name: `Avenue ${i + 1}`
                });
            }
        }
        
        horizontalRoads.forEach(road => {
            this.roads.push({
                x: 0,
                y: road.y,
                width: this.width, // Use FULL world width
                height: this.streetWidth,
                type: 'horizontal_street',
                name: road.name,
                lanes: 2,
                markings: true,
                sidewalks: true
            });
        });
        
        // Vertical roads (running top to bottom) - spanning full world height
        const verticalRoadSpacing = Math.max(200, this.width / 20); // More roads for wider world
        const verticalRoads = [];
        
        for (let i = 0; i < 20; i++) { // Create 20 vertical roads
            const x = 150 + (i * verticalRoadSpacing);
            if (x < this.width - 150) {
                verticalRoads.push({
                    x: x,
                    name: `Street ${i + 1}`
                });
            }
        }
        
        verticalRoads.forEach(road => {
            this.roads.push({
                x: road.x,
                y: 0,
                width: this.streetWidth,
                height: this.height, // Use FULL world height
                type: 'vertical_street',
                name: road.name,
                lanes: 2,
                markings: true,
                sidewalks: true
            });
        });
        
        console.log(`🛣️ Created road network: ${horizontalRoads.length} horizontal + ${verticalRoads.length} vertical = ${this.roads.length} total roads`);
    },
    
    /**
     * Create detailed buildings with organized city block layout
     */
    createDetailedBuildings() {
        // Clear existing buildings
        this.buildings = [];
        
        // Create organized city blocks in grid pattern with no overlaps
        this.createOrganizedCityBlocks();
        
        console.log(`🏢 Created ${this.buildings.length} buildings in organized city blocks`);
    },
    
    /**
     * Create organized city blocks with consistent spacing
     */
    createOrganizedCityBlocks() {
        // Clear existing buildings first
        this.buildings = [];
        
        // Define building colors for variety
        const buildingColors = [
            '#D32F2F', '#1976D2', '#388E3C', '#F57C00', 
            '#7B1FA2', '#5D4037', '#455A64', '#C62828',
            '#303F9F', '#689F38', '#F9A825', '#8E24AA'
        ];
        
        const buildingTypes = [
            'House', 'House', 'School', 'House', 
            'House', 'Shop', 'House', 'School',
            'House', 'House', 'Shop', 'House'
        ];
        
        // Get actual road positions
        const horizontalRoads = this.roads.filter(road => road.type === 'horizontal_street');
        const verticalRoads = this.roads.filter(road => road.type === 'vertical_street');
        
        console.log(`Creating buildings between ${horizontalRoads.length} horizontal and ${verticalRoads.length} vertical roads`);
        
        // Create building blocks between roads (avoid overlaps)
        for (let hIndex = 0; hIndex < horizontalRoads.length - 1; hIndex++) {
            const topRoad = horizontalRoads[hIndex];
            const bottomRoad = horizontalRoads[hIndex + 1];
            
            // Calculate safe building area between roads
            const buildingAreaY = topRoad.y + topRoad.height + 10; // 10px margin from road
            const buildingAreaHeight = bottomRoad.y - buildingAreaY - 10; // 10px margin to next road
            
            if (buildingAreaHeight > 60) { // Only create buildings if there's enough space
                for (let vIndex = 0; vIndex < verticalRoads.length - 1; vIndex++) {
                    const leftRoad = verticalRoads[vIndex];
                    const rightRoad = verticalRoads[vIndex + 1];
                    
                    // Calculate safe building area between roads
                    const buildingAreaX = leftRoad.x + leftRoad.width + 10; // 10px margin from road
                    const buildingAreaWidth = rightRoad.x - buildingAreaX - 10; // 10px margin to next road
                    
                    if (buildingAreaWidth > 80) { // Only create buildings if there's enough space
                        const colorIndex = (hIndex * verticalRoads.length + vIndex) % buildingColors.length;
                        const typeIndex = (hIndex * verticalRoads.length + vIndex) % buildingTypes.length;
                        
                        // Create building with safe positioning
                        this.createBuildingBlock(
                            buildingAreaX + 5, // Small additional margin
                            buildingAreaY + 5, // Small additional margin
                            Math.min(buildingAreaWidth - 10, 120), // Cap width and leave margins
                            Math.min(buildingAreaHeight - 10, 80), // Cap height and leave margins
                            buildingColors[colorIndex], 
                            buildingTypes[typeIndex], 
                            Math.floor(Math.random() * 3) + 1 // 1-3 floors
                        );
                    }
                }
            }
        }
        
        console.log(`✅ Created ${this.buildings.length} buildings without overlaps`);
    },
    
    /**
     * Helper function to create a building with collision bounds
     */
    createBuildingBlock(x, y, width, height, color, type, floors) {
        this.buildings.push({
            x: x,
            y: y,
            width: width,
            height: height,
            color: color,
            type: type,
            floors: floors,
            // Add collision detection bounds (slightly larger than visual bounds)
            collisionBounds: {
                x: x - 5,
                y: y - 5,
                width: width + 10,
                height: height + 10
            }
        });
    },
    
    /**
     * Create decorative elements (trees, grass patches, streetlights)
     */
    createDecorations() {
        this.decorations = [];
        
        // Only add trees strategically around the city (NO grass patches)
        this.createTreesAroundCity();
        
        // Add streetlights on roads
        this.createStreetlights();
        
        console.log(`🌳 Created ${this.decorations.length} decorative elements (no bushes/grass)`);
    },
    
    /**
     * Create grass patches around buildings and empty areas
     */
    createGrassPatches() {
        // Create small, strategic grass areas only in non-intrusive locations
        const grassAreas = [
            // Small grass areas at world edges only
            { x: 50, y: 50, width: 80, height: 60 },
            { x: this.width * 0.5, y: 50, width: 80, height: 60 },
            { x: this.width - 150, y: 50, width: 80, height: 60 },
            
            // Grass areas at world edges
            { x: 50, y: this.height - 100, width: 100, height: 60 },
            { x: this.width - 150, y: this.height - 100, width: 100, height: 60 },
        ];
        
        grassAreas.forEach(area => {
            this.decorations.push({
                type: 'grass_patch',
                x: area.x,
                y: area.y,
                width: area.width,
                height: area.height,
                color: '#4CAF50'
            });
        });
    },
    
    /**
     * Create trees around the city
     */
    createTreesAroundCity() {
        // Trees placed strategically away from roads and buildings
        const treePositions = [];
        
        // Get road positions for safe tree placement
        const horizontalRoads = this.roads.filter(road => road.type === 'horizontal_street');
        const verticalRoads = this.roads.filter(road => road.type === 'vertical_street');
        
        // Place trees in safe zones between roads
        for (let h = 0; h < horizontalRoads.length - 1; h++) {
            for (let v = 0; v < verticalRoads.length - 1; v++) {
                const topRoad = horizontalRoads[h];
                const bottomRoad = horizontalRoads[h + 1];
                const leftRoad = verticalRoads[v];
                const rightRoad = verticalRoads[v + 1];
                
                // Calculate safe zone between roads (LARGER MARGINS)
                const safeX = leftRoad.x + leftRoad.width + 40; // Increased from 20 to 40
                const safeY = topRoad.y + topRoad.height + 40; // Increased from 20 to 40
                const safeWidth = rightRoad.x - safeX - 40; // Increased margins
                const safeHeight = bottomRoad.y - safeY - 40; // Increased margins
                
                // Only place trees if there's enough safe space (more restrictive)
                if (safeWidth > 120 && safeHeight > 120) { // Increased minimum space requirement
                    // Place only 1 tree maximum in this safe zone (reduced from 1-2)
                    if (Math.random() > 0.6) { // Only 40% chance to place a tree
                        const treeX = safeX + 50 + Math.random() * (safeWidth - 100); // More margin
                        const treeY = safeY + 50 + Math.random() * (safeHeight - 100); // More margin
                        
                        treePositions.push({ x: treeX, y: treeY });
                    }
                }
            }
        }
        
        // Add trees at world edges for decoration
        const edgeTreeSpacing = 200;
        for (let x = 100; x < this.width - 100; x += edgeTreeSpacing) {
            treePositions.push({ x: x, y: 50 }); // Top edge
            if (this.height > 1000) {
                treePositions.push({ x: x, y: this.height - 50 }); // Bottom edge
            }
        }
        
        for (let y = 100; y < this.height - 100; y += edgeTreeSpacing) {
            treePositions.push({ x: 50, y: y }); // Left edge
            treePositions.push({ x: this.width - 50, y: y }); // Right edge
        }
        
        // Create trees with collision checking
        treePositions.forEach(pos => {
            // Only place tree if it's not on a road or too close to buildings
            if (!this.isOnRoad(pos.x, pos.y) && !this.isTooCloseToBuilding(pos.x, pos.y)) {
                this.decorations.push({
                    type: 'tree',
                    x: pos.x,
                    y: pos.y,
                    size: Math.random() * 6 + 8, // MUCH smaller trees: 8-14 size (was 12-20)
                    color: '#2E7D32'
                });
            }
        });
        
        console.log(`🌳 Placed ${this.decorations.filter(d => d.type === 'tree').length} trees safely`);
    },
    
    /**
     * Create streetlights along roads
     */
    createStreetlights() {
        // Add streetlights at intersections and along roads
        const streetlightPositions = [
            // At major intersections
            { x: 190, y: 150 }, { x: 440, y: 150 }, { x: 690, y: 150 },
            { x: 190, y: 350 }, { x: 440, y: 350 }, { x: 690, y: 350 },
            { x: 190, y: 550 }, { x: 440, y: 550 }, { x: 690, y: 550 },
        ];
        
        streetlightPositions.forEach(pos => {
            this.decorations.push({
                type: 'streetlight',
                x: pos.x,
                y: pos.y,
                height: 30
            });
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
     * Create pedestrian crossing lanes (zebra crossings)
     */
    createPedestrianLanes() {
        this.pedestrianLanes = [];
        
        // Get road positions for reference
        const horizontalRoads = this.roads.filter(road => road.type === 'horizontal_street');
        const verticalRoads = this.roads.filter(road => road.type === 'vertical_street');
        
        console.log(`Creating SIMPLE pedestrian crossings in green areas only`);
        
        let crossingId = 0;
        
        // Create SPECIFIC pedestrian lanes in predetermined green areas
        // NO overlapping, NO random placement - ONLY strategic green area crossings
        
        if (horizontalRoads.length >= 2 && verticalRoads.length >= 3) {
            // Place pedestrian crossings in green areas between specific building blocks
            
            // Crossing 1: Between first two buildings horizontally
            this.pedestrianLanes.push({
                id: crossingId++,
                x: verticalRoads[1].x + verticalRoads[1].width + 40, // In green area after 2nd vertical road
                y: horizontalRoads[0].y + horizontalRoads[0].height + 60, // In green area below 1st horizontal road
                width: 60, // Short crossing in green area
                height: 12,
                direction: 'horizontal',
                street: 'Green Area Crossing 1',
                alertRadius: 30,
                active: true
            });
            
            // Crossing 2: Between buildings vertically (if there are enough roads)
            if (horizontalRoads.length >= 3) {
                this.pedestrianLanes.push({
                    id: crossingId++,
                    x: verticalRoads[0].x + verticalRoads[0].width + 60, // In green area after 1st vertical road
                    y: horizontalRoads[1].y + horizontalRoads[1].height + 40, // In green area below 2nd horizontal road
                    width: 12,
                    height: 50, // Short vertical crossing in green area
                    direction: 'vertical',
                    street: 'Green Area Crossing 2',
                    alertRadius: 30,
                    active: true
                });
            }
        }
        
        console.log(`🚶 Created ${this.pedestrianLanes.length} pedestrian crossing lanes in green areas (NO road overlaps)`);
    },
    
    /**
     * Get street name based on position and direction
     */
    getStreetName(position, direction) {
        if (direction === 'vertical') {
            const verticalNames = ['West Ave', '1st St', '2nd Ave', 'Main St', '4th Ave', '5th St', '6th Ave', '7th St', '8th Ave', 'East Blvd'];
            const verticalPositions = [200, 500, 800, 1100, 1400, 1700, 2000, 2300, 2600, 2900];
            const index = verticalPositions.indexOf(position);
            return index >= 0 ? verticalNames[index] : 'Unknown St';
        } else {
            const horizontalNames = ['North Ave', 'First St', 'Main Blvd', 'Central St', 'South Ave'];
            const horizontalPositions = [150, 400, 650, 900, 1150];
            const index = horizontalPositions.indexOf(position);
            return index >= 0 ? horizontalNames[index] : 'Unknown Ave';
        }
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
        
        // Render pedestrian crossing lanes
        this.renderPedestrianLanes(ctx, camera);
        
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
     * Render enhanced roads with clean organized look
     */
    renderRoads(ctx, camera) {
        this.roads.forEach(road => {
            const screenX = road.x - camera.x;
            const screenY = road.y - camera.y;
            
            // Road border (darker outline)
            ctx.fillStyle = this.colors.roadBorder;
            ctx.fillRect(screenX - 2, screenY - 2, road.width + 4, road.height + 4);
            
            // Road surface (clean flat look)
            ctx.fillStyle = this.colors.road;
            ctx.fillRect(screenX, screenY, road.width, road.height);
            
            // Lane markings
            if (road.markings) {
                ctx.fillStyle = this.colors.roadLine;
                ctx.lineWidth = 2;
                
                if (road.type.includes('horizontal')) {
                    // Horizontal road markings
                    const centerY = screenY + road.height / 2;
                    
                    // Center line dashes
                    for (let x = screenX; x < screenX + road.width; x += 30) {
                        ctx.fillRect(x, centerY - 1, 15, 2);
                    }
                    
                    // Side lines
                    ctx.fillRect(screenX, screenY + 5, road.width, 2);
                    ctx.fillRect(screenX, screenY + road.height - 7, road.width, 2);
                    
                } else {
                    // Vertical road markings
                    const centerX = screenX + road.width / 2;
                    
                    // Center line dashes
                    for (let y = screenY; y < screenY + road.height; y += 30) {
                        ctx.fillRect(centerX - 1, y, 2, 15);
                    }
                    
                    // Side lines
                    ctx.fillRect(screenX + 5, screenY, 2, road.height);
                    ctx.fillRect(screenX + road.width - 7, screenY, 2, road.height);
                }
            }
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
     * Render detailed buildings with organized clean look
     */
    renderBuildings(ctx, camera) {
        this.buildings.forEach(building => {
            const screenX = building.x - camera.x;
            const screenY = building.y - camera.y;
            
            // Only render if visible on screen
            if (screenX < -building.width || screenX > ctx.canvas.width + building.width || 
                screenY < -building.height || screenY > ctx.canvas.height + building.height) {
                return;
            }
            
            // Render different building types with unique shapes
            switch(building.type) {
                case 'House':
                    this.renderHouse(ctx, screenX, screenY, building);
                    break;
                case 'School':
                    this.renderSchool(ctx, screenX, screenY, building);
                    break;
                default:
                    this.renderGenericBuilding(ctx, screenX, screenY, building);
                    break;
            }
        });
    },
    
    /**
     * Render a house with triangular roof and home-like features
     */
    renderHouse(ctx, x, y, building) {
        // Building shadow
        ctx.fillStyle = 'rgba(0, 0, 0, 0.2)';
        ctx.fillRect(x + 3, y + 3, building.width, building.height);
        
        // House body (rectangular base)
        ctx.fillStyle = building.color;
        ctx.fillRect(x, y + 20, building.width, building.height - 20);
        
        // Triangular roof
        ctx.fillStyle = '#8B4513'; // Brown roof
        ctx.beginPath();
        ctx.moveTo(x - 5, y + 20); // Left base of roof
        ctx.lineTo(x + building.width + 5, y + 20); // Right base of roof
        ctx.lineTo(x + building.width / 2, y); // Peak of roof
        ctx.closePath();
        ctx.fill();
        
        // Roof outline
        ctx.strokeStyle = '#654321';
        ctx.lineWidth = 2;
        ctx.stroke();
        
        // House body outline
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 1;
        ctx.strokeRect(x, y + 20, building.width, building.height - 20);
        
        // Front door
        const doorWidth = 18;
        const doorHeight = 35;
        const doorX = x + building.width / 2 - doorWidth / 2;
        const doorY = y + building.height - doorHeight;
        
        ctx.fillStyle = '#8B4513'; // Brown door
        ctx.fillRect(doorX, doorY, doorWidth, doorHeight);
        ctx.strokeStyle = '#000000';
        ctx.strokeRect(doorX, doorY, doorWidth, doorHeight);
        
        // Door knob
        ctx.fillStyle = '#FFD700'; // Gold knob
        ctx.beginPath();
        ctx.arc(doorX + doorWidth - 4, doorY + doorHeight / 2, 2, 0, Math.PI * 2);
        ctx.fill();
        
        // Windows
        const windowSize = 12;
        const windowY = y + 35;
        
        // Left window
        ctx.fillStyle = '#87CEEB'; // Sky blue window
        ctx.fillRect(x + 15, windowY, windowSize, windowSize);
        ctx.strokeStyle = '#000000';
        ctx.strokeRect(x + 15, windowY, windowSize, windowSize);
        
        // Right window
        ctx.fillRect(x + building.width - 15 - windowSize, windowY, windowSize, windowSize);
        ctx.strokeRect(x + building.width - 15 - windowSize, windowY, windowSize, windowSize);
        
        // Window cross frames
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 1;
        // Left window cross
        ctx.beginPath();
        ctx.moveTo(x + 15 + windowSize/2, windowY);
        ctx.lineTo(x + 15 + windowSize/2, windowY + windowSize);
        ctx.moveTo(x + 15, windowY + windowSize/2);
        ctx.lineTo(x + 15 + windowSize, windowY + windowSize/2);
        ctx.stroke();
        
        // Right window cross
        ctx.beginPath();
        ctx.moveTo(x + building.width - 15 - windowSize/2, windowY);
        ctx.lineTo(x + building.width - 15 - windowSize/2, windowY + windowSize);
        ctx.moveTo(x + building.width - 15 - windowSize, windowY + windowSize/2);
        ctx.lineTo(x + building.width - 15, windowY + windowSize/2);
        ctx.stroke();
        
        // House label
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 8px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('🏠', x + building.width / 2, y + building.height - 3);
    },
    
    /**
     * Render a school with institutional architecture
     */
    renderSchool(ctx, x, y, building) {
        // Building shadow
        ctx.fillStyle = 'rgba(0, 0, 0, 0.2)';
        ctx.fillRect(x + 3, y + 3, building.width, building.height);
        
        // School main building
        ctx.fillStyle = building.color;
        ctx.fillRect(x, y, building.width, building.height);
        
        // School entrance (stepped entrance)
        const entranceWidth = building.width * 0.4;
        const entranceHeight = 15;
        const entranceX = x + (building.width - entranceWidth) / 2;
        const entranceY = y + building.height - entranceHeight;
        
        ctx.fillStyle = '#D3D3D3'; // Light gray entrance
        ctx.fillRect(entranceX, entranceY, entranceWidth, entranceHeight);
        
        // School flag pole
        ctx.strokeStyle = '#8B4513'; // Brown pole
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(x + building.width - 10, y);
        ctx.lineTo(x + building.width - 10, y - 25);
        ctx.stroke();
        
        // Flag
        ctx.fillStyle = '#FF0000'; // Red flag
        ctx.fillRect(x + building.width - 10, y - 20, 15, 10);
        
        // Building outline
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 1;
        ctx.strokeRect(x, y, building.width, building.height);
        ctx.strokeRect(entranceX, entranceY, entranceWidth, entranceHeight);
        
        // Multiple rows of institutional windows
        const windowWidth = 8;
        const windowHeight = 12;
        const windowSpacing = 15;
        const startX = x + 10;
        const startY = y + 15;
        
        // Top row of windows
        for (let i = 0; i < Math.floor((building.width - 20) / windowSpacing); i++) {
            const windowX = startX + i * windowSpacing;
            
            // First floor windows
            ctx.fillStyle = '#87CEEB'; // Sky blue
            ctx.fillRect(windowX, startY, windowWidth, windowHeight);
            ctx.strokeStyle = '#000000';
            ctx.strokeRect(windowX, startY, windowWidth, windowHeight);
            
            // Second floor windows (if building is tall enough)
            if (building.height > 80) {
                ctx.fillStyle = '#87CEEB';
                ctx.fillRect(windowX, startY + 35, windowWidth, windowHeight);
                ctx.strokeRect(windowX, startY + 35, windowWidth, windowHeight);
            }
        }
        
        // School sign
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 8px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('🏫', x + building.width / 2, y + building.height - 3);
    },
    
    /**
     * Render generic building (fallback)
     */
    renderGenericBuilding(ctx, x, y, building) {
        // Building shadow
        ctx.fillStyle = 'rgba(0, 0, 0, 0.2)';
        ctx.fillRect(x + 3, y + 3, building.width, building.height);
        
        // Building body
        ctx.fillStyle = building.color;
        ctx.fillRect(x, y, building.width, building.height);
        
        // Building outline
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 1;
        ctx.strokeRect(x, y, building.width, building.height);
        
        // Simple windows grid
        const windowSize = 8;
        const windowSpacing = 12;
        const windowsPerRow = Math.floor((building.width - 20) / windowSpacing);
        const windowRows = Math.floor((building.height - 30) / windowSpacing);
        
        for (let row = 0; row < windowRows; row++) {
            for (let col = 0; col < windowsPerRow; col++) {
                const windowX = x + 10 + col * windowSpacing;
                const windowY = y + 15 + row * windowSpacing;
                
                ctx.fillStyle = '#87CEEB';
                ctx.fillRect(windowX, windowY, windowSize, windowSize);
                ctx.strokeStyle = '#000000';
                ctx.strokeRect(windowX, windowY, windowSize, windowSize);
            }
        }
        
        // Building label
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 8px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(building.type, x + building.width / 2, y + building.height - 5);
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
     * Check if a position would collide with any building
     * @param {number} x - X coordinate to check
     * @param {number} y - Y coordinate to check
     * @param {number} width - Width of object checking collision
     * @param {number} height - Height of object checking collision
     * @returns {boolean} - True if collision detected
     */
    checkBuildingCollision(x, y, width, height) {
        for (const building of this.buildings) {
            const bounds = building.collisionBounds;
            
            // Check if rectangles overlap
            if (x < bounds.x + bounds.width &&
                x + width > bounds.x &&
                y < bounds.y + bounds.height &&
                y + height > bounds.y) {
                return true;
            }
        }
        return false;
    },
    
    /**
     * Get the closest valid position to move to when collision is detected
     * @param {number} currentX - Current X position
     * @param {number} currentY - Current Y position  
     * @param {number} targetX - Desired X position
     * @param {number} targetY - Desired Y position
     * @param {number} width - Width of moving object
     * @param {number} height - Height of moving object
     * @returns {object} - Valid position {x, y}
     */
    resolveCollision(currentX, currentY, targetX, targetY, width, height) {
        // If target position is valid, use it
        if (!this.checkBuildingCollision(targetX, targetY, width, height)) {
            return { x: targetX, y: targetY };
        }
        
        // Try moving only in X direction
        if (!this.checkBuildingCollision(targetX, currentY, width, height)) {
            return { x: targetX, y: currentY };
        }
        
        // Try moving only in Y direction
        if (!this.checkBuildingCollision(currentX, targetY, width, height)) {
            return { x: currentX, y: targetY };
        }
        
        // If all movement would cause collision, stay at current position
        return { x: currentX, y: currentY };
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
     * Render pedestrian crossing lanes (zebra crossings)
     */
    renderPedestrianLanes(ctx, camera) {
        this.pedestrianLanes.forEach(lane => {
            if (!lane.active) return;
            
            const screenX = lane.x - camera.x;
            const screenY = lane.y - camera.y;
            
            // Only render if visible on screen
            if (screenX < -lane.width || screenX > ctx.canvas.width + lane.width || 
                screenY < -lane.height || screenY > ctx.canvas.height + lane.height) {
                return;
            }
            
            // Draw zebra crossing stripes
            ctx.fillStyle = '#FFFFFF';
            
            if (lane.direction === 'horizontal') {
                // Horizontal zebra stripes
                for (let i = 0; i < lane.width; i += 10) {
                    if (Math.floor(i / 10) % 2 === 0) {
                        ctx.fillRect(screenX + i, screenY, 8, lane.height);
                    }
                }
            } else {
                // Vertical zebra stripes
                for (let i = 0; i < lane.height; i += 10) {
                    if (Math.floor(i / 10) % 2 === 0) {
                        ctx.fillRect(screenX, screenY + i, lane.width, 8);
                    }
                }
            }
            
            // Debug: Alert radius (when car is nearby)
            if (window.gameDebug) {
                ctx.strokeStyle = 'rgba(255, 255, 0, 0.3)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.arc(screenX + lane.width/2, screenY + lane.height/2, lane.alertRadius, 0, Math.PI * 2);
                ctx.stroke();
            }
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
        // Ensure size is within reasonable bounds
        const treeSize = Math.min(size, 20); // Cap at size 20
        
        // Tree trunk (smaller)
        ctx.fillStyle = '#8D6E63';
        ctx.fillRect(x - 2, y, 4, treeSize * 0.5);
        
        // Tree canopy (smaller, single circle for cleaner look)
        ctx.fillStyle = this.colors.tree;
        ctx.beginPath();
        ctx.arc(x, y - treeSize * 0.1, treeSize * 0.6, 0, Math.PI * 2);
        ctx.fill();
        
        // Small highlight (reduced complexity)
        ctx.fillStyle = '#66BB6A';
        ctx.beginPath();
        ctx.arc(x - treeSize * 0.2, y - treeSize * 0.2, treeSize * 0.3, 0, Math.PI * 2);
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
     * Check if position is too close to buildings
     */
    isTooCloseToBuilding(x, y) {
        const minDistance = 25; // Minimum distance from buildings
        return this.buildings.some(building => {
            const distance = Math.sqrt(
                Math.pow(x - (building.x + building.width/2), 2) + 
                Math.pow(y - (building.y + building.height/2), 2)
            );
            return distance < minDistance;
        });
    },
    
    /**
     * Get scenario markers for collision detection
     */
    getScenarioMarkers() {
        return this.scenarioMarkers.filter(marker => marker.active);
    },
    
    /**
     * Get pedestrian lanes for collision detection
     */
    getPedestrianLanes() {
        return this.pedestrianLanes.filter(lane => lane.active);
    },
    
    /**
     * Get intersections for warning detection
     */
    getIntersections() {
        // Define major intersections in the landscape city
        return [
            { x: 300, y: 300, radius: 80, name: 'Main & First St' },
            { x: 580, y: 300, radius: 80, name: 'Main & Second St' },
            { x: 860, y: 300, radius: 80, name: 'Main & Third St' },
            { x: 1140, y: 300, radius: 80, name: 'Main & Fourth St' },
            { x: 1420, y: 300, radius: 80, name: 'Main & Fifth St' },
            
            { x: 300, y: 520, radius: 80, name: 'Center & First St' },
            { x: 580, y: 520, radius: 80, name: 'Center & Second St' },
            { x: 860, y: 520, radius: 80, name: 'Center & Third St' },
            { x: 1140, y: 520, radius: 80, name: 'Center & Fourth St' },
            { x: 1420, y: 520, radius: 80, name: 'Center & Fifth St' },
            
            { x: 300, y: 740, radius: 80, name: 'Park & First St' },
            { x: 580, y: 740, radius: 80, name: 'Park & Second St' },
            { x: 860, y: 740, radius: 80, name: 'Park & Third St' },
            { x: 1140, y: 740, radius: 80, name: 'Park & Fourth St' },
            { x: 1420, y: 740, radius: 80, name: 'Park & Fifth St' },
        ];
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
