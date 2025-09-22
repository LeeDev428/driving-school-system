/**
 * World Module - Enhanced Organized 2D City Environment
 * Creates a beautiful top-down city view with organized grid layout
 */

const WorldModule = {
    // World dimensions (FORCED LANDSCAPE - much wider than tall)
    width: 3200,   // Increased from 2400 to 3200 for true landscape
    height: 1600,  // Increased from 1400 to 1600 but kept ratio wide
    
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
        this.createPedestrianLanes();
        this.createScenarioMarkers();
    },
    
    /**
     * Create enhanced road network with organized grid pattern (LANDSCAPE)
     */
    createEnhancedRoadNetwork() {
        // Clear existing roads
        this.roads = [];
        
        // Create organized grid of wide roads for true landscape layout
        
        // Horizontal roads (running left to right) - wider spacing for landscape
        const horizontalRoads = [
            { y: 150, name: 'North Avenue' },
            { y: 400, name: 'First Street' },
            { y: 650, name: 'Main Boulevard' },
            { y: 900, name: 'Central Street' },
            { y: 1150, name: 'South Avenue' }
        ];
        
        horizontalRoads.forEach(road => {
            this.roads.push({
                x: 0,
                y: road.y,
                width: this.width,
                height: this.streetWidth,
                type: 'horizontal_street',
                name: road.name,
                lanes: road.name === 'Main Boulevard' ? 4 : 2,
                markings: true,
                sidewalks: true
            });
        });
        
        // Vertical roads (running top to bottom) - more roads for landscape
        const verticalRoads = [
            { x: 200, name: 'West Avenue' },
            { x: 500, name: '1st Street' },
            { x: 800, name: '2nd Avenue' },
            { x: 1100, name: 'Main Street' },
            { x: 1400, name: '4th Avenue' },
            { x: 1700, name: '5th Street' },
            { x: 2000, name: '6th Avenue' },
            { x: 2300, name: '7th Street' },
            { x: 2600, name: '8th Avenue' },
            { x: 2900, name: 'East Boulevard' }
        ];
        
        verticalRoads.forEach(road => {
            this.roads.push({
                x: road.x,
                y: 0,
                width: this.streetWidth,
                height: this.height,
                type: 'vertical_street',
                name: road.name,
                lanes: road.name === 'Main Street' ? 4 : 2,
                markings: true,
                sidewalks: true
            });
        });
        
        console.log(`🛣️ Created LANDSCAPE road network: ${this.roads.length} roads`);
    },
    
    /**
     * Create detailed buildings with organized city block layout
     */
    createDetailedBuildings() {
        // Clear existing buildings
        this.buildings = [];
        
        // Create organized city blocks in grid pattern matching reference image
        this.createOrganizedCityBlocks();
        
        console.log(`🏢 Created ${this.buildings.length} buildings in organized city blocks`);
    },
    
    /**
     * Create organized city blocks with consistent spacing
     */
    createOrganizedCityBlocks() {
        // Define building colors for variety
        const buildingColors = [
            '#D32F2F', '#1976D2', '#388E3C', '#F57C00', 
            '#7B1FA2', '#5D4037', '#455A64', '#C62828',
            '#303F9F', '#689F38', '#F9A825', '#8E24AA'
        ];
        
        const buildingTypes = [
            'House', 'House', 'School', 'House', 
            'House', 'School', 'House', 'Shop',
            'House', 'House', 'School', 'House'
        ];
        
        // Fixed building rows to avoid road overlaps
        // Roads are at y: 150, 400, 650, 900, 1150 (height 120 each)
        
        // Row 1: Top row (y: 20-130) - above first road at y:150
        this.createBuildingRow(20, 110, buildingColors, buildingTypes, 0);
        
        // Row 2: Between 1st and 2nd roads (y: 290-380) - between y:150+120=270 and y:400
        this.createBuildingRow(290, 90, buildingColors, buildingTypes, 1);
        
        // Row 3: Between 2nd and 3rd roads (y: 540-630) - between y:400+120=520 and y:650
        this.createBuildingRow(540, 90, buildingColors, buildingTypes, 2);
        
        // Row 4: Between 3rd and 4th roads (y: 790-880) - between y:650+120=770 and y:900
        this.createBuildingRow(790, 90, buildingColors, buildingTypes, 3);
        
        // Row 5: Between 4th and 5th roads (y: 1040-1130) - between y:900+120=1020 and y:1150
        this.createBuildingRow(1040, 90, buildingColors, buildingTypes, 4);
        
        // Row 6: Bottom row (y: 1290-1380) - below last road at y:1150+120=1270
        this.createBuildingRow(1290, 90, buildingColors, buildingTypes, 5);
    },
    
    /**
     * Create a row of buildings with consistent spacing
     */
    createBuildingRow(yPos, height, colors, types, rowIndex) {
        // Building positions based on vertical road spacing
        // Vertical roads are at x: 200, 500, 800, 1100, 1400, 1700, 2000, 2300, 2600, 2900 (width 120 each)
        // Buildings should be placed BETWEEN roads, not on them
        
        const buildingXPositions = [
            { x: 20, width: 160 },      // Before 1st vertical road (x: 20-180, road at 200)
            { x: 340, width: 140 },     // Between 1st and 2nd roads (x: 340-480, roads at 200 and 500)
            { x: 640, width: 140 },     // Between 2nd and 3rd roads (x: 640-780, roads at 500 and 800)
            { x: 940, width: 140 },     // Between 3rd and 4th roads (x: 940-1080, roads at 800 and 1100)
            { x: 1240, width: 140 },    // Between 4th and 5th roads (x: 1240-1380, roads at 1100 and 1400)
            { x: 1540, width: 140 },    // Between 5th and 6th roads (x: 1540-1680, roads at 1400 and 1700)
            { x: 1840, width: 140 },    // Between 6th and 7th roads (x: 1840-1980, roads at 1700 and 2000)
            { x: 2140, width: 140 },    // Between 7th and 8th roads (x: 2140-2280, roads at 2000 and 2300)
            { x: 2440, width: 140 },    // Between 8th and 9th roads (x: 2440-2580, roads at 2300 and 2600)
            { x: 2740, width: 140 }     // Between 9th and 10th roads (x: 2740-2880, roads at 2600 and 2900)
        ];
        
        buildingXPositions.forEach((pos, index) => {
            const colorIndex = (rowIndex * buildingXPositions.length + index) % colors.length;
            const typeIndex = (rowIndex * buildingXPositions.length + index) % types.length;
            
            this.createBuildingBlock(
                pos.x, 
                yPos, 
                pos.width, 
                height, 
                colors[colorIndex], 
                types[typeIndex], 
                Math.floor(Math.random() * 3) + 1 // 1-3 floors
            );
        });
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
        
        // Add grass patches around buildings
        this.createGrassPatches();
        
        // Add trees strategically around the city
        this.createTreesAroundCity();
        
        // Add streetlights on roads
        this.createStreetlights();
        
        console.log(`🌳 Created ${this.decorations.length} decorative elements`);
    },
    
    /**
     * Create grass patches around buildings and empty areas
     */
    createGrassPatches() {
        // Create larger grass areas between building blocks
        const grassAreas = [
            // Around scenario areas (more grass for test scenes)
            { x: 50, y: 50, width: 100, height: 80 },
            { x: 300, y: 50, width: 120, height: 80 },
            { x: 550, y: 50, width: 120, height: 80 },
            
            // Between building rows
            { x: 50, y: 200, width: 140, height: 40 },
            { x: 270, y: 200, width: 150, height: 40 },
            { x: 520, y: 200, width: 150, height: 40 },
            { x: 770, y: 200, width: 150, height: 40 },
            
            // More grass near scenario test scenes
            { x: 50, y: 400, width: 140, height: 40 },
            { x: 270, y: 400, width: 150, height: 40 },
            { x: 520, y: 400, width: 150, height: 40 },
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
        // Trees around building blocks (more near scenario areas)
        const treePositions = [
            // Around first row (scenario area)
            { x: 80, y: 80 }, { x: 120, y: 90 }, { x: 160, y: 85 },
            { x: 330, y: 80 }, { x: 370, y: 90 }, { x: 410, y: 85 },
            { x: 580, y: 80 }, { x: 620, y: 90 }, { x: 660, y: 85 },
            
            // Between roads
            { x: 100, y: 230 }, { x: 150, y: 240 }, { x: 200, y: 235 },
            { x: 300, y: 230 }, { x: 350, y: 240 }, { x: 400, y: 235 },
            { x: 550, y: 230 }, { x: 600, y: 240 }, { x: 650, y: 235 },
            
            // Near scenario test areas
            { x: 80, y: 430 }, { x: 130, y: 440 }, { x: 180, y: 435 },
            { x: 300, y: 430 }, { x: 350, y: 440 }, { x: 400, y: 435 },
            
            // Scattered throughout city
            { x: 800, y: 100 }, { x: 850, y: 110 }, { x: 900, y: 105 },
            { x: 1100, y: 100 }, { x: 1150, y: 110 }, { x: 1200, y: 105 },
            { x: 1400, y: 100 }, { x: 1450, y: 110 }, { x: 1500, y: 105 },
        ];
        
        treePositions.forEach(pos => {
            this.decorations.push({
                type: 'tree',
                x: pos.x,
                y: pos.y,
                size: Math.random() * 15 + 20, // 20-35 size
                color: '#2E7D32'
            });
        });
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
        
        // Define road positions for proper alignment
        const horizontalRoads = [150, 400, 650, 900, 1150]; // y positions
        const verticalRoads = [200, 500, 800, 1100, 1400, 1700, 2000, 2300, 2600, 2900]; // x positions
        const roadWidth = 120; // Street width
        
        // Create zebra crossings at major intersections
        const crossings = [];
        
        // Horizontal crossings (crossing vertical roads) - zebra stripes going across vertical roads
        horizontalRoads.forEach((roadY, hIndex) => {
            verticalRoads.forEach((roadX, vIndex) => {
                // Only create crossings at major intersections (not all)
                if (hIndex < 3 && vIndex < 5) { // Limit to first 3 horizontal and 5 vertical roads
                    crossings.push({
                        x: roadX - roadWidth/2 + 20, // Center on vertical road
                        y: roadY + roadWidth/2 - 10, // Just after the horizontal road
                        width: roadWidth - 40, // Zebra stripe width
                        height: 20, // Zebra stripe height
                        direction: 'horizontal',
                        street: `Crossing at ${this.getStreetName(roadX, 'vertical')} & ${this.getStreetName(roadY, 'horizontal')}`
                    });
                }
            });
        });
        
        // Vertical crossings (crossing horizontal roads) - zebra stripes going across horizontal roads
        verticalRoads.forEach((roadX, vIndex) => {
            horizontalRoads.forEach((roadY, hIndex) => {
                // Only create crossings at major intersections (not all)
                if (vIndex < 5 && hIndex < 3) { // Limit to first 5 vertical and 3 horizontal roads
                    crossings.push({
                        x: roadX + roadWidth/2 - 10, // Just after the vertical road
                        y: roadY - roadWidth/2 + 20, // Center on horizontal road
                        width: 20, // Zebra stripe width
                        height: roadWidth - 40, // Zebra stripe height
                        direction: 'vertical',
                        street: `Crossing at ${this.getStreetName(roadX, 'vertical')} & ${this.getStreetName(roadY, 'horizontal')}`
                    });
                }
            });
        });
        
        crossings.forEach((crossing, index) => {
            this.pedestrianLanes.push({
                id: index,
                x: crossing.x,
                y: crossing.y,
                width: crossing.width,
                height: crossing.height,
                direction: crossing.direction,
                street: crossing.street,
                alertRadius: 80, // Distance to trigger slow-down warning
                active: true
            });
        });
        
        console.log(`🚶 Created ${this.pedestrianLanes.length} pedestrian crossing lanes`);
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
