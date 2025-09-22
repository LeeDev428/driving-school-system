// World/Environment Module
// Handles roads, buildings, traffic signs, and world generation

const World = {
    // World dimensions
    width: 4000,
    height: 4000,
    
    // World objects
    roadPoints: [],
    roadElements: [],
    buildings: [],
    pedestrians: [],
    roadSigns: [],

    // Initialize the world
    init() {
        this.generateRoadSystem();
        this.addSimpleSquareBuildings();
    },

    // Generate the road system with intersections
    generateRoadSystem() {
        const centerX = this.width / 2;
        const centerY = this.height / 2;
        
        // Create main intersection roads
        this.roadPoints = [
            // Horizontal road (west to east)
            { x: 0, y: centerY },
            { x: centerX - 200, y: centerY },
            { x: centerX, y: centerY },
            { x: centerX + 200, y: centerY },
            { x: this.width, y: centerY },
            
            // Vertical road (north to south)
            { x: centerX, y: 0 },
            { x: centerX, y: centerY - 200 },
            { x: centerX, y: centerY },
            { x: centerX, y: centerY + 200 },
            { x: centerX, y: this.height }
        ];

        // Add road elements (traffic lights, stop signs, etc.)
        const upperY = centerY - 200;
        const lowerY = centerY + 200;
        
        this.roadElements = [
            // Traffic lights at main intersection
            { type: 'TRAFFIC_LIGHT', x: centerX - 50, y: centerY - 50 },
            { type: 'TRAFFIC_LIGHT', x: centerX + 50, y: centerY + 50 },
            
            // Stop signs at secondary intersections
            { type: 'STOP_SIGN', x: centerX - 100, y: upperY - 50 },
            { type: 'STOP_SIGN', x: centerX + 100, y: lowerY + 50 },
            
            // Speed limit signs
            { type: 'SPEED_LIMIT', x: centerX - 80, y: centerY - 100, speedLimit: '30' },
            { type: 'SPEED_LIMIT', x: centerX + 80, y: centerY + 100, speedLimit: '25' }
        ];

        console.log('🛣️ Road system generated with', this.roadElements.length, 'elements');
    },

    // Add buildings around the roads
    addSimpleSquareBuildings() {
        const centerX = this.width / 2;
        const centerY = this.height / 2;
        const buildingOffset = 150; // Distance from road center
        
        this.buildings = [
            // Upper left quadrant
            { x: centerX - buildingOffset - 100, y: 100, width: 150, height: 120, type: 'school', color: '#FFD700', label: 'SCHOOL' },
            { x: centerX - buildingOffset - 300, y: 200, width: 120, height: 100, type: 'house', color: '#FF6B6B', label: '' },
            { x: centerX - buildingOffset - 250, y: 350, width: 100, height: 90, type: 'house', color: '#87CEEB', label: '' },
            { x: centerX - buildingOffset - 400, y: 450, width: 130, height: 110, type: 'house', color: '#98FB98', label: '' },
            { x: centerX - buildingOffset - 180, y: 600, width: 140, height: 100, type: 'shop', color: '#DDA0DD', label: 'SHOP' },
            
            // Upper right quadrant
            { x: centerX + buildingOffset + 100, y: 120, width: 140, height: 100, type: 'house', color: '#FFA07A', label: '' },
            { x: centerX + buildingOffset + 200, y: 180, width: 130, height: 110, type: 'house', color: '#90EE90', label: '' },
            { x: centerX + buildingOffset + 50, y: 330, width: 150, height: 100, type: 'shop', color: '#FFB6C1', label: 'MARKET' },
            { x: centerX + buildingOffset + 300, y: 400, width: 120, height: 120, type: 'house', color: '#F0E68C', label: '' },
            { x: centerX + buildingOffset + 180, y: 550, width: 110, height: 90, type: 'house', color: '#CD853F', label: '' },
            
            // Lower left quadrant
            { x: centerX - buildingOffset - 320, y: centerY + 350, width: 130, height: 100, type: 'shop', color: '#20B2AA', label: 'STORE' },
            { x: centerX - buildingOffset - 100, y: centerY + 500, width: 160, height: 120, type: 'school', color: '#FFD700', label: 'ELEMENTARY' },
            { x: centerX - buildingOffset - 400, y: centerY + 600, width: 140, height: 110, type: 'house', color: '#FFE4B5', label: '' },
            { x: centerX - buildingOffset - 250, y: centerY + 750, width: 120, height: 100, type: 'house', color: '#D2691E', label: '' },
            
            // Lower right quadrant
            { x: centerX + buildingOffset + 50, y: centerY + 220, width: 140, height: 110, type: 'house', color: '#DDA0DD', label: '' },
            { x: centerX + buildingOffset + 220, y: centerY + 350, width: 150, height: 100, type: 'shop', color: '#FFA07A', label: 'CAFE' },
            { x: centerX + buildingOffset + 120, y: centerY + 500, width: 130, height: 120, type: 'house', color: '#98FB98', label: '' },
            { x: centerX + buildingOffset + 350, y: centerY + 600, width: 110, height: 100, type: 'house', color: '#F5DEB3', label: '' },
            
            // Central area buildings
            { x: centerX - buildingOffset - 200, y: centerY - 100, width: 130, height: 120, type: 'house', color: '#FF69B4', label: '' },
            { x: centerX + buildingOffset + 80, y: centerY - 150, width: 120, height: 100, type: 'house', color: '#40E0D0', label: '' },
            { x: centerX - buildingOffset - 350, y: centerY - 250, width: 140, height: 110, type: 'shop', color: '#DA70D6', label: 'MALL' },
            { x: centerX + buildingOffset + 250, y: centerY + 30, width: 130, height: 100, type: 'shop', color: '#F5DEB3', label: 'BANK' },
            
            // Additional scattered buildings
            { x: centerX - 50, y: 50, width: 100, height: 80, type: 'house', color: '#FFB6C1', label: '' },
            { x: centerX + 100, y: 80, width: 120, height: 100, type: 'house', color: '#20B2AA', label: '' },
            { x: centerX - 150, y: this.height - 500, width: 160, height: 110, type: 'shop', color: '#00CED1', label: 'RESTAURANT' },
            { x: centerX + 200, y: this.height - 400, width: 140, height: 120, type: 'house', color: '#9370DB', label: '' },
            
            // Far buildings for depth
            { x: 200, y: 800, width: 120, height: 100, type: 'house', color: '#FF7F50', label: '' },
            { x: 400, y: 1200, width: 150, height: 130, type: 'shop', color: '#32CD32', label: 'DEPOT' },
            { x: centerX - 450, y: 1000, width: 140, height: 130, type: 'house', color: '#87CEEB', label: '' },
            { x: this.width - 300, y: 600, width: 130, height: 110, type: 'house', color: '#F4A460', label: '' },
            { x: this.width - 200, y: 1400, width: 160, height: 140, type: 'shop', color: '#BC8F8F', label: 'WAREHOUSE' }
        ];

        console.log('🏢 Generated', this.buildings.length, 'buildings');
    },

    // Render the entire world
    render(ctx, camera) {
        this.renderRoads(ctx, camera);
        this.renderBuildings(ctx, camera);
        this.renderRoadElements(ctx, camera);
    },

    // Render roads
    renderRoads(ctx, camera) {
        ctx.fillStyle = '#555';
        const centerX = this.width / 2;
        const centerY = this.height / 2;
        const roadWidth = 160;

        // Draw horizontal road
        ctx.fillRect(
            0 - camera.x, 
            centerY - roadWidth/2 - camera.y, 
            this.width, 
            roadWidth
        );

        // Draw vertical road
        ctx.fillRect(
            centerX - roadWidth/2 - camera.x, 
            0 - camera.y, 
            roadWidth, 
            this.height
        );
    },

    // Render buildings
    renderBuildings(ctx, camera) {
        this.buildings.forEach(building => {
            const screenX = building.x - camera.x;
            const screenY = building.y - camera.y;
            
            // Only render if visible on screen
            if (screenX > -building.width - 50 && screenX < ctx.canvas.width + 50 &&
                screenY > -building.height - 50 && screenY < ctx.canvas.height + 50) {
                
                // Draw building
                ctx.fillStyle = building.color;
                ctx.fillRect(screenX, screenY, building.width, building.height);
                
                // Draw building outline
                ctx.strokeStyle = '#333';
                ctx.lineWidth = 2;
                ctx.strokeRect(screenX, screenY, building.width, building.height);
                
                // Draw label if exists
                if (building.label) {
                    ctx.fillStyle = '#000';
                    ctx.font = '12px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText(
                        building.label, 
                        screenX + building.width/2, 
                        screenY + building.height/2 + 4
                    );
                }
            }
        });
    },

    // Render road elements (signs, lights, etc.)
    renderRoadElements(ctx, camera) {
        this.roadElements.forEach(element => {
            const screenX = element.x - camera.x;
            const screenY = element.y - camera.y;
            
            // Only render if visible
            if (screenX > -100 && screenX < ctx.canvas.width + 100 &&
                screenY > -100 && screenY < ctx.canvas.height + 100) {
                
                switch (element.type) {
                    case 'STOP_SIGN':
                        this.renderStopSign(ctx, screenX, screenY);
                        break;
                    case 'TRAFFIC_LIGHT':
                        this.renderTrafficLight(ctx, screenX, screenY);
                        break;
                    case 'SPEED_LIMIT':
                        this.renderSpeedLimit(ctx, screenX, screenY, element.speedLimit);
                        break;
                }
            }
        });
    },

    // Render stop sign
    renderStopSign(ctx, x, y) {
        ctx.fillStyle = '#FF0000';
        ctx.fillRect(x - 15, y - 15, 30, 30);
        ctx.fillStyle = '#FFFFFF';
        ctx.font = '10px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('STOP', x, y + 3);
    },

    // Render traffic light
    renderTrafficLight(ctx, x, y) {
        // Traffic light pole
        ctx.fillStyle = '#666';
        ctx.fillRect(x - 3, y - 20, 6, 40);
        
        // Light box
        ctx.fillStyle = '#333';
        ctx.fillRect(x - 12, y - 30, 24, 20);
        
        // Lights (simplified - always showing green for simulation)
        ctx.fillStyle = '#00FF00';
        ctx.beginPath();
        ctx.arc(x, y - 20, 4, 0, Math.PI * 2);
        ctx.fill();
    },

    // Render speed limit sign
    renderSpeedLimit(ctx, x, y, limit) {
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(x - 15, y - 20, 30, 40);
        ctx.strokeStyle = '#000';
        ctx.strokeRect(x - 15, y - 20, 30, 40);
        
        ctx.fillStyle = '#000';
        ctx.font = '8px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('SPEED', x, y - 10);
        ctx.fillText('LIMIT', x, y - 2);
        ctx.font = '12px Arial';
        ctx.fillText(limit, x, y + 10);
    },

    // Get world dimensions
    getDimensions() {
        return { width: this.width, height: this.height };
    },

    // Check if position is on road
    isOnRoad(x, y) {
        const centerX = this.width / 2;
        const centerY = this.height / 2;
        const roadWidth = 160;
        
        // Check horizontal road
        if (Math.abs(y - centerY) < roadWidth / 2) return true;
        
        // Check vertical road
        if (Math.abs(x - centerX) < roadWidth / 2) return true;
        
        return false;
    },

    // Get buildings array (for collision detection)
    getBuildings() {
        return [...this.buildings];
    },

    // Get road elements array
    getRoadElements() {
        return [...this.roadElements];
    }
};

// Export to global window object for browser use
window.WorldModule = World;

// Export for use in other modules (Node.js compatibility)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = World;
}