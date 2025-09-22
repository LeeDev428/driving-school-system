// Advanced 2D Driving Simulation with Curved Roads
let canvas, ctx, miniMapCanvas, miniMapCtx;
let gameRunning = false;
let gameStats = { correct: 0, wrong: 0, total: 0, startTime: null, scenarios: [] };
let currentScenario = null;
let scenarioIndex = 0;

// Camera system for following car
let camera = {
    x: 0,
    y: 0,
    targetX: 0,
    targetY: 0,
    smoothing: 0.1
};

// Car properties with realistic physics
let car = {
    x: 0,
    y: 0,
    width: 20,
    height: 35,
    speed: 0,
    maxSpeed: 6, // Maximum 60 km/h (adjusted scale: 1 unit = 10 km/h)
    acceleration: 0.05, // Realistic acceleration - takes time to reach top speed
    deceleration: 0.25, // Realistic braking
    friction: 0.99, // Natural slowdown when not accelerating
    angle: 0,
    angularVelocity: 0,
    color: '#e74c3c'
};

// Road system with curves and intersections
let roadPoints = [];
let roadElements = [];
let buildings = [];
let pedestrians = [];
let roadSigns = [];
let worldWidth = 4000;
let worldHeight = 4000;

// Control states
let keys = {};
let buttonStates = {
    accelerate: false,
    brake: false,
    turnLeft: false,
    turnRight: false
};

// Game state
let isCarStopped = false; // New variable to control car stopping for scenarios

// Animation variables
let animationId;
let lastTime = 0;

// Road elements and scenarios
const roadElementTypes = {
    PEDESTRIAN_CROSSING: 'pedestrian_crossing',
    TRAFFIC_LIGHT: 'traffic_light',
    INTERSECTION: 'intersection',
    SCHOOL_ZONE: 'school_zone',
    SPEED_BUMP: 'speed_bump',
    STOP_SIGN: 'stop_sign'
};

// 20 SUPER EASY DRIVING SCENARIOS - Beginner Friendly Questions
const allDrivingScenarios = [
    // PEDESTRIAN CROSSING SCENARIOS (4 scenarios)
    {
        title: "See a Person Crossing",
        question: "A person wants to cross the street. What do you do?",
        triggerElement: roadElementTypes.PEDESTRIAN_CROSSING,
        options: [
            "Stop and let them cross",
            "Honk your horn",
            "Drive faster",
            "Turn around"
        ],
        correctAnswer: 0,
        explanation: "Always stop for people crossing the street. Be kind!",
        difficulty: "easy"
    },
    {
        title: "Kids Playing Near Road",
        question: "You see children playing near the road. What should you do?",
        triggerElement: roadElementTypes.PEDESTRIAN_CROSSING,
        options: [
            "Slow down and watch carefully",
            "Speed up to get past them",
            "Honk loudly",
            "Keep driving normally"
        ],
        correctAnswer: 0,
        explanation: "Kids might run into the street. Always slow down and be careful!",
        difficulty: "easy"
    },
    {
        title: "Helping People Cross",
        question: "An old person is crossing slowly. What do you do?",
        triggerElement: roadElementTypes.PEDESTRIAN_CROSSING,
        options: [
            "Wait patiently for them",
            "Honk to make them hurry",
            "Drive around them",
            "Flash your lights"
        ],
        correctAnswer: 0,
        explanation: "Be patient! Some people need more time to cross safely.",
        difficulty: "easy"
    },
    {
        title: "Rainy Day Safety",
        question: "It's raining and someone is crossing. What should you do?",
        triggerElement: roadElementTypes.PEDESTRIAN_CROSSING,
        options: [
            "Stop so you don't splash them",
            "Drive through the puddle",
            "Go really fast",
            "Honk at them"
        ],
        correctAnswer: 0,
        explanation: "Don't splash people with water! Stop and be nice.",
        difficulty: "easy"
    },

    // SCHOOL ZONE SCENARIOS (4 scenarios)
    {
        title: "Near a School",
        question: "You're driving near a school. What's the most important thing?",
        triggerElement: roadElementTypes.SCHOOL_ZONE,
        options: [
            "Drive very slowly",
            "Drive at normal speed",
            "Honk to warn kids",
            "Drive faster to get past"
        ],
        correctAnswer: 0,
        explanation: "Always drive slowly near schools. Kids are everywhere!",
        difficulty: "easy"
    },
    {
        title: "School Bus Safety",
        question: "A school bus stops with flashing red lights. What do you do?",
        triggerElement: roadElementTypes.SCHOOL_ZONE,
        options: [
            "Stop and wait until lights turn off",
            "Drive around the bus slowly",
            "Honk and keep going",
            "Speed up to pass quickly"
        ],
        correctAnswer: 0,
        explanation: "Red flashing lights mean STOP! Kids might be getting off the bus.",
        difficulty: "easy"
    },
    {
        title: "Children Everywhere",
        question: "You see lots of kids walking to school. What should you do?",
        triggerElement: roadElementTypes.SCHOOL_ZONE,
        options: [
            "Drive extra slowly and carefully",
            "Honk to get their attention",
            "Drive at normal speed",
            "Flash your headlights"
        ],
        correctAnswer: 0,
        explanation: "Kids don't always look before crossing. Be super careful!",
        difficulty: "easy"
    },
    {
        title: "School Time Rules",
        question: "When should you drive slowly in school zones?",
        triggerElement: roadElementTypes.SCHOOL_ZONE,
        options: [
            "When kids are going to or leaving school",
            "Only during the night",
            "Only on weekends",
            "Never - speed limits don't change"
        ],
        correctAnswer: 0,
        explanation: "School zones are extra safe during school times. Kids first!",
        difficulty: "easy"
    },

    // TRAFFIC LIGHT SCENARIOS (4 scenarios)
    {
        title: "Yellow Light Means...",
        question: "The traffic light turns yellow. What does this mean?",
        triggerElement: roadElementTypes.TRAFFIC_LIGHT,
        options: [
            "Get ready to stop",
            "Go faster",
            "Keep going at same speed",
            "Turn on your radio"
        ],
        correctAnswer: 0,
        explanation: "Yellow means 'get ready to stop'. Red is coming next!",
        difficulty: "easy"
    },
    {
        title: "Red Light Rule",
        question: "What should you do at a red light?",
        triggerElement: roadElementTypes.TRAFFIC_LIGHT,
        options: [
            "Stop completely",
            "Slow down a little",
            "Keep driving",
            "Honk your horn"
        ],
        correctAnswer: 0,
        explanation: "Red means STOP! Always stop completely at red lights.",
        difficulty: "easy"
    },
    {
        title: "Green Light Go",
        question: "The light is green. What can you do?",
        triggerElement: roadElementTypes.TRAFFIC_LIGHT,
        options: [
            "Go, but look both ways first",
            "Stop anyway",
            "Honk and go fast",
            "Wait for yellow"
        ],
        correctAnswer: 0,
        explanation: "Green means go, but always check that it's safe first!",
        difficulty: "easy"
    },
    {
        title: "Broken Traffic Light",
        question: "The traffic light is broken and not working. What do you do?",
        triggerElement: roadElementTypes.TRAFFIC_LIGHT,
        options: [
            "Treat it like a stop sign",
            "Drive through quickly",
            "Honk loudly",
            "Turn around and go back"
        ],
        correctAnswer: 0,
        explanation: "When lights are broken, treat them like stop signs. Stop and look!",
        difficulty: "easy"
    },

    // STOP SIGN AND INTERSECTION SCENARIOS (4 scenarios)
    {
        title: "Stop Sign Means",
        question: "You see a stop sign. What must you do?",
        triggerElement: roadElementTypes.STOP_SIGN,
        options: [
            "Come to a complete stop",
            "Slow down a lot",
            "Just look both ways",
            "Honk your horn"
        ],
        correctAnswer: 0,
        explanation: "Stop signs mean you must stop completely. Count to 3!",
        difficulty: "easy"
    },
    {
        title: "Who Goes First?",
        question: "At a 4-way stop, you and another car arrive at the same time. They're on your right. Who goes first?",
        triggerElement: roadElementTypes.STOP_SIGN,
        options: [
            "The car on your right goes first",
            "You go first",
            "Both go at the same time",
            "Whoever honks first"
        ],
        correctAnswer: 0,
        explanation: "Right has the right-of-way! Let the car on your right go first.",
        difficulty: "easy"
    },
    {
        title: "Emergency Vehicles",
        question: "You hear sirens behind you. What should you do?",
        triggerElement: roadElementTypes.INTERSECTION,
        options: [
            "Pull over safely and stop",
            "Speed up to get out of the way",
            "Keep driving normally",
            "Stop right where you are"
        ],
        correctAnswer: 0,
        explanation: "Emergency vehicles save lives! Pull over safely and let them pass.",
        difficulty: "easy"
    },
    {
        title: "Being Patient",
        question: "Traffic is moving slowly. What's the best thing to do?",
        triggerElement: roadElementTypes.INTERSECTION,
        options: [
            "Be patient and wait your turn",
            "Honk at everyone",
            "Change lanes constantly",
            "Drive on the shoulder"
        ],
        correctAnswer: 0,
        explanation: "Good drivers are patient drivers. Everyone gets where they're going!",
        difficulty: "easy"
    },

    // GENERAL SAFETY SCENARIOS (4 scenarios)
    {
        title: "Safe Following Distance",
        question: "How close should you follow the car in front of you?",
        triggerElement: roadElementTypes.INTERSECTION,
        options: [
            "Leave plenty of space",
            "Stay very close",
            "It doesn't matter",
            "Just don't hit them"
        ],
        correctAnswer: 0,
        explanation: "Leave space! You need room to stop if something happens.",
        difficulty: "easy"
    },
    {
        title: "Turn Signals",
        question: "When should you use your turn signals?",
        triggerElement: roadElementTypes.INTERSECTION,
        options: [
            "Every time you turn or change lanes",
            "Only when other cars are around",
            "Only on busy streets",
            "Never - they're not important"
        ],
        correctAnswer: 0,
        explanation: "Always use turn signals! They tell other people where you're going.",
        difficulty: "easy"
    },
    {
        title: "Seat Belt Safety",
        question: "When should you wear your seat belt?",
        triggerElement: roadElementTypes.INTERSECTION,
        options: [
            "Every time you drive",
            "Only on long trips",
            "Only when it's required",
            "Only if you're going fast"
        ],
        correctAnswer: 0,
        explanation: "Seat belts save lives! Always buckle up before you start driving.",
        difficulty: "easy"
    },
    {
        title: "Being a Good Driver",
        question: "What makes someone a good driver?",
        triggerElement: roadElementTypes.INTERSECTION,
        options: [
            "Being careful and following the rules",
            "Driving really fast",
            "Honking at everyone",
            "Never stopping for anyone"
        ],
        correctAnswer: 0,
        explanation: "Good drivers are safe, careful, and kind to others on the road!",
        difficulty: "easy"
    }
];

// Game state - selected scenarios for this run
let selectedScenarios = [];
let currentScenarioIndex = 0;

// NEW FUNCTION: Select 5 random scenarios from 20 available
function selectRandomScenarios() {
    const scenarios = [...allDrivingScenarios]; // Copy array
    const selected = [];
    
    // Randomly select 5 scenarios
    for (let i = 0; i < 5; i++) {
        const randomIndex = Math.floor(Math.random() * scenarios.length);
        selected.push(scenarios[randomIndex]);
        scenarios.splice(randomIndex, 1); // Remove selected to avoid duplicates
    }
    
    console.log(`🎯 Selected 5 random scenarios from 20 available:`, selected.map(s => s.title));
    return selected;
}

// Contextual scenarios based on road elements - EXACTLY 5 Core Driving Scenarios (LEGACY - KEEP FOR COMPATIBILITY)
const contextualScenarios = [
    {
        title: "Pedestrian Crossing Safety",
        question: "You see a pedestrian waiting to cross at a marked crosswalk ahead. Your speed is 45 km/h. What is the correct action?",
        triggerElement: roadElementTypes.PEDESTRIAN_CROSSING,
        options: [
            "Come to a complete stop and allow the pedestrian to cross safely",
            "Slow down and honk to warn the pedestrian you're approaching",
            "Maintain speed - pedestrians should wait for traffic",
            "Speed up to pass before the pedestrian steps into the road"
        ],
        correctAnswer: 0,
        explanation: "By law, drivers must yield to pedestrians in crosswalks. Come to a complete stop and ensure pedestrian safety.",
        speedRequirement: "complete_stop"
    },
    {
        title: "School Zone Protocol",
        question: "You enter a school zone with a 25 km/h speed limit. It's 3:30 PM on a weekday and you're going 40 km/h. What should you do?",
        triggerElement: roadElementTypes.SCHOOL_ZONE,
        options: [
            "Continue at 40 km/h since no children are visible",
            "Immediately reduce speed to 25 km/h and watch for children",
            "Slow down only if a crossing guard is present",
            "The speed limit only applies during school hours (8 AM-3 PM)"
        ],
        correctAnswer: 1,
        explanation: "School zone speed limits are strictly enforced during posted hours, often including after-school activities. Children's safety is paramount.",
        speedRequirement: "limit_25"
    },
    {
        title: "Traffic Light Decision Making",
        question: "You're 25 meters from an intersection when the light turns yellow. Your speed is 50 km/h. What is the safest action?",
        triggerElement: roadElementTypes.TRAFFIC_LIGHT,
        options: [
            "Accelerate to clear the intersection before it turns red",
            "Brake firmly but safely to stop before the stop line",
            "Maintain current speed and proceed through the intersection",
            "Flash your headlights and honk while proceeding"
        ],
        correctAnswer: 1,
        explanation: "Yellow light means prepare to stop. From 25m at 50 km/h, you have sufficient distance to stop safely. Don't rush through yellow lights.",
        speedRequirement: "complete_stop"
    },
    {
        title: "4-Way Stop Intersection",
        question: "At a 4-way stop, you and another vehicle arrive simultaneously. The other vehicle is to your right and also going straight. Who has the right-of-way?",
        triggerElement: roadElementTypes.STOP_SIGN,
        options: [
            "You have right-of-way since you're going straight",
            "The vehicle to your right has right-of-way",
            "Both vehicles should proceed at the same time carefully",
            "Whoever honks first gets to go"
        ],
        correctAnswer: 1,
        explanation: "At 4-way stops, when vehicles arrive simultaneously, yield to the vehicle on your right. Always come to a complete stop first.",
        speedRequirement: "complete_stop"
    },
    {
        title: "Emergency Vehicle Response",
        question: "You hear sirens approaching from behind while driving in the right lane. An ambulance with flashing lights is catching up. What should you do?",
        triggerElement: roadElementTypes.INTERSECTION,
        options: [
            "Maintain your lane and speed - they'll go around",
            "Safely pull to the right shoulder and stop",
            "Speed up to get out of their way quickly",
            "Stop immediately in your current lane"
        ],
        correctAnswer: 1,
        explanation: "Always yield right-of-way to emergency vehicles. Safely move to the right and stop to allow them to pass.",
        speedRequirement: "yield_emergency"
    }
];

// Initialize the simulation
function init() {
    showStatus("Initializing advanced driving simulation...");
    
    canvas = document.getElementById('simulationCanvas');
    miniMapCanvas = document.getElementById('miniMapCanvas');
    
    if (!canvas) {
        console.error('Canvas not found');
        return;
    }
    
    ctx = canvas.getContext('2d');
    miniMapCtx = miniMapCanvas.getContext('2d');
    
    // Set canvas size to full screen
    resizeCanvas();
    
    // Initialize game world
    generateRoadSystem();
    setupEventListeners();
    
    // Car position is already set correctly in generateRoadSystem() - AT THE BOTTOM
    // Don't override it here
    
    // Hide loading screen
    const loadingScreen = document.getElementById('loadingScreen');
    if (loadingScreen) {
        loadingScreen.style.display = 'none';
    }
    
    showStatus("Ready! Use arrow keys or buttons to drive. Follow traffic rules!", 4000);
    
    // Start the animation loop
    animate(0);
    
    // Auto-start simulation
    setTimeout(() => {
        startSimulation();
    }, 2000);
}

function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}

function generateRoadSystem() {
    // Reset road system
    roadPoints = [];
    roadElements = [];
    buildings = [];
    pedestrians = [];
    
    // SIMPLE ROAD NETWORK - 1 vertical + 2 horizontal roads
    const roadWidth = 80;
    const centerX = worldWidth / 2;
    const centerY = worldHeight / 2;
    
    // 1. MAIN VERTICAL ROAD (center, going up-down)
    for (let y = 50; y < worldHeight - 50; y += 30) {
        roadPoints.push({
            x: centerX,
            y: y,
            width: roadWidth,
            isMainRoad: true,
            isVertical: true
        });
    }
    
    // 2. FIRST HORIZONTAL ROAD (upper intersection)
    const upperY = centerY - 150;
    for (let x = 50; x < worldWidth - 50; x += 30) {
        roadPoints.push({
            x: x,
            y: upperY,
            width: roadWidth,
            isMainRoad: false,
            isHorizontal: true
        });
    }
    
    // 3. SECOND HORIZONTAL ROAD (lower intersection)
    const lowerY = centerY + 150;
    for (let x = 50; x < worldWidth - 50; x += 30) {
        roadPoints.push({
            x: x,
            y: lowerY,
            width: roadWidth,
            isMainRoad: false,
            isHorizontal: true
        });
    }
    
    // ADD MORE TRAFFIC LIGHTS at all intersections and key points
    roadElements.push(
        // Upper intersection traffic lights
        { type: roadElementTypes.TRAFFIC_LIGHT, x: centerX + 60, y: upperY, lightState: 'green' },
        { type: roadElementTypes.TRAFFIC_LIGHT, x: centerX - 60, y: upperY, lightState: 'red' },
        
        // Lower intersection traffic lights
        { type: roadElementTypes.TRAFFIC_LIGHT, x: centerX + 60, y: lowerY, lightState: 'yellow' },
        { type: roadElementTypes.TRAFFIC_LIGHT, x: centerX - 60, y: lowerY, lightState: 'green' },
        
        // Additional traffic lights along main road
        { type: roadElementTypes.TRAFFIC_LIGHT, x: centerX + 60, y: centerY - 300, lightState: 'red' },
        { type: roadElementTypes.TRAFFIC_LIGHT, x: centerX - 60, y: centerY, lightState: 'green' },
        { type: roadElementTypes.TRAFFIC_LIGHT, x: centerX + 60, y: centerY + 300, lightState: 'yellow' }
    );
    
    // ADD MORE PEDESTRIAN CROSSINGS
    roadElements.push(
        { type: roadElementTypes.PEDESTRIAN_CROSSING, x: centerX, y: upperY },
        { type: roadElementTypes.PEDESTRIAN_CROSSING, x: centerX, y: lowerY },
        { type: roadElementTypes.PEDESTRIAN_CROSSING, x: centerX, y: centerY - 250 },
        { type: roadElementTypes.PEDESTRIAN_CROSSING, x: centerX, y: centerY + 250 },
        // Horizontal crossings
        { type: roadElementTypes.PEDESTRIAN_CROSSING, x: centerX - 200, y: upperY },
        { type: roadElementTypes.PEDESTRIAN_CROSSING, x: centerX + 200, y: upperY },
        { type: roadElementTypes.PEDESTRIAN_CROSSING, x: centerX - 200, y: lowerY },
        { type: roadElementTypes.PEDESTRIAN_CROSSING, x: centerX + 200, y: lowerY }
    );
    
    // ADD TRAFFIC SIGNS
    roadElements.push(
        // Stop signs
        { type: roadElementTypes.STOP_SIGN, x: centerX - 100, y: upperY - 50 },
        { type: roadElementTypes.STOP_SIGN, x: centerX + 100, y: lowerY + 50 },
        
        // Speed limit signs
        { type: 'SPEED_LIMIT', x: centerX - 80, y: centerY - 100, speedLimit: '30' },
        { type: 'SPEED_LIMIT', x: centerX + 80, y: centerY + 100, speedLimit: '25' }
    );
    
    // Add simple square/rectangle buildings
    addSimpleSquareBuildings();
    
    // Set car starting position AT THE BOTTOM
    car.x = centerX;
    car.y = worldHeight - 50; // Near bottom of screen
    car.angle = -Math.PI / 2; // Point upward
}

function addSimpleSquareBuildings() {
    const centerX = worldWidth / 2;
    const centerY = worldHeight / 2;
    
    // Clear buildings array first
    buildings = [];
    
    // PLACE BUILDINGS AROUND THE ROADS (not on them)
    const roadWidth = 80;
    const buildingOffset = 120; // Distance from road edge
    
    // TOP LEFT QUADRANT - MUCH LARGER AND BRIGHTER BUILDINGS
    buildings.push(
        { x: centerX - buildingOffset - 100, y: 100, width: 150, height: 120, type: 'school', color: '#FFD700', label: 'SCHOOL' },
        { x: centerX - buildingOffset - 300, y: 200, width: 120, height: 100, type: 'house', color: '#FF6B6B', label: '' },
        { x: centerX - buildingOffset - 200, y: 350, width: 140, height: 90, type: 'shop', color: '#4ECDC4', label: 'SHOP' }
    );
    
    // TOP RIGHT QUADRANT - MUCH LARGER AND BRIGHTER BUILDINGS  
    buildings.push(
        { x: centerX + buildingOffset, y: 120, width: 180, height: 130, type: 'school', color: '#FFD700', label: 'HIGH SCHOOL' },
        { x: centerX + buildingOffset + 200, y: 180, width: 130, height: 110, type: 'house', color: '#90EE90', label: '' },
        { x: centerX + buildingOffset + 50, y: 330, width: 150, height: 100, type: 'shop', color: '#FFB6C1', label: 'MARKET' }
    );
    
    // BOTTOM LEFT QUADRANT - MUCH LARGER AND BRIGHTER BUILDINGS
    buildings.push(
        { x: centerX - buildingOffset - 150, y: centerY + 200, width: 140, height: 120, type: 'house', color: '#F0E68C', label: '' },
        { x: centerX - buildingOffset - 320, y: centerY + 350, width: 130, height: 100, type: 'shop', color: '#20B2AA', label: 'STORE' },
        { x: centerX - buildingOffset - 100, y: centerY + 500, width: 160, height: 120, type: 'school', color: '#FFD700', label: 'ELEMENTARY' }
    );
    
    // BOTTOM RIGHT QUADRANT - MUCH LARGER AND BRIGHTER BUILDINGS
    buildings.push(
        { x: centerX + buildingOffset + 50, y: centerY + 220, width: 140, height: 110, type: 'house', color: '#DDA0DD', label: '' },
        { x: centerX + buildingOffset + 220, y: centerY + 350, width: 150, height: 100, type: 'shop', color: '#FFA07A', label: 'CAFE' },
        { x: centerX + buildingOffset, y: centerY + 500, width: 160, height: 120, type: 'house', color: '#98FB98', label: '' }
    );
    
    // MIDDLE LEFT (between roads) - MUCH LARGER AND BRIGHTER BUILDINGS
    buildings.push(
        { x: centerX - buildingOffset - 200, y: centerY - 100, width: 130, height: 120, type: 'house', color: '#FF69B4', label: '' },
        { x: centerX - buildingOffset - 400, y: centerY + 50, width: 140, height: 90, type: 'shop', color: '#CD853F', label: 'OFFICE' }
    );
    
    // MIDDLE RIGHT (between roads) - MUCH LARGER AND BRIGHTER BUILDINGS
    buildings.push(
        { x: centerX + buildingOffset + 80, y: centerY - 120, width: 150, height: 130, type: 'house', color: '#87CEEB', label: '' },
        { x: centerX + buildingOffset + 250, y: centerY + 30, width: 130, height: 100, type: 'shop', color: '#F5DEB3', label: 'BANK' }
    );
    
    // ADD MORE LARGE VISIBLE BUILDINGS NEAR THE BOTTOM (where car starts)
    buildings.push(
        { x: centerX - 250, y: worldHeight - 300, width: 180, height: 140, type: 'school', color: '#FFD700', label: 'DRIVING SCHOOL' },
        { x: centerX + 200, y: worldHeight - 280, width: 150, height: 120, type: 'shop', color: '#FF1493', label: 'GAS STATION' },
        { x: centerX - 450, y: worldHeight - 350, width: 130, height: 120, type: 'house', color: '#32CD32', label: '' },
        { x: centerX + 380, y: worldHeight - 400, width: 140, height: 130, type: 'house', color: '#FF69B4', label: '' },
        { x: centerX - 150, y: worldHeight - 500, width: 160, height: 110, type: 'shop', color: '#00CED1', label: 'RESTAURANT' }
    );
    
    // ADD MORE LARGE BUILDINGS ALONG THE SIDES
    buildings.push(
        { x: centerX - 500, y: 500, width: 140, height: 120, type: 'house', color: '#FFE4B5', label: '' },
        { x: centerX + 450, y: 550, width: 150, height: 130, type: 'shop', color: '#20B2AA', label: 'PHARMACY' },
        { x: centerX - 480, y: 750, width: 130, height: 140, type: 'house', color: '#98FB98', label: '' },
        { x: centerX + 480, y: 800, width: 160, height: 120, type: 'shop', color: '#FFA500', label: 'BOOKSTORE' },
        { x: centerX - 450, y: 1000, width: 140, height: 130, type: 'house', color: '#87CEEB', label: '' },
        { x: centerX + 500, y: 1050, width: 150, height: 120, type: 'shop', color: '#CD853F', label: 'CLINIC' }
    );
}

function addVerticalStreetFurniture(x, y) {
    // Street lamps on both sides of vertical road
    buildings.push({
        x: x - 50,
        y: y - 20,
        width: 4,
        height: 25,
        type: 'streetlamp',
        color: '#696969'
    });
    
    buildings.push({
        x: x + 50,
        y: y + 20,
        width: 4,
        height: 25,
        type: 'streetlamp',
        color: '#696969'
    });
    
    // Bus stops occasionally
    if (Math.random() > 0.7) {
        buildings.push({
            x: x + 60,
            y: y - 10,
            width: 30,
            height: 20,
            type: 'busstop',
            color: '#4682B4'
        });
    }
    
    // Trees for green city feel along vertical road
    if (Math.random() > 0.5) {
        buildings.push({
            x: x - 70 + Math.random() * 15,
            y: y + 40 + Math.random() * 20,
            width: 15,
            height: 20,
            type: 'tree',
            color: '#228B22'
        });
        
        buildings.push({
            x: x + 70 + Math.random() * 15,
            y: y - 40 - Math.random() * 20,
            width: 15,
            height: 20,
            type: 'tree',
            color: '#228B22'
        });
    }
}

// Add pedestrians near crosswalks
function addPedestrians(crosswalkX, crosswalkY) {
    const numPedestrians = Math.floor(Math.random() * 4) + 1;
    
    for (let i = 0; i < numPedestrians; i++) {
        pedestrians.push({
            x: crosswalkX + (Math.random() - 0.5) * 100,
            y: crosswalkY + (Math.random() - 0.5) * 50,
            width: 8,
            height: 12,
            speed: 0.5 + Math.random() * 0.5,
            direction: Math.random() * Math.PI * 2,
            waiting: Math.random() > 0.5,
            color: ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7'][Math.floor(Math.random() * 5)]
        });
    }
}

function setupEventListeners() {
    // Keyboard controls
    document.addEventListener('keydown', onKeyDown);
    document.addEventListener('keyup', onKeyUp);
    
    // Window resize
    window.addEventListener('resize', resizeCanvas);
    
    // Button controls with proper state management
    setupButtonControls();
}

function onKeyDown(event) {
    keys[event.code] = true;
    
    if(['Space', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.code)) {
        event.preventDefault();
    }
}

function onKeyUp(event) {
    keys[event.code] = false;
}

function setupButtonControls() {
    const accelerateBtn = document.getElementById('accelerateBtn');
    const brakeBtn = document.getElementById('brakeBtn');
    const turnLeftBtn = document.getElementById('turnLeftBtn');
    const turnRightBtn = document.getElementById('turnRightBtn');
    
    // Accelerate button
    if (accelerateBtn) {
        accelerateBtn.addEventListener('mousedown', () => buttonStates.accelerate = true);
        accelerateBtn.addEventListener('mouseup', () => buttonStates.accelerate = false);
        accelerateBtn.addEventListener('mouseleave', () => buttonStates.accelerate = false);
        accelerateBtn.addEventListener('touchstart', (e) => { e.preventDefault(); buttonStates.accelerate = true; });
        accelerateBtn.addEventListener('touchend', (e) => { e.preventDefault(); buttonStates.accelerate = false; });
    }
    
    // Brake button
    if (brakeBtn) {
        brakeBtn.addEventListener('mousedown', () => buttonStates.brake = true);
        brakeBtn.addEventListener('mouseup', () => buttonStates.brake = false);
        brakeBtn.addEventListener('mouseleave', () => buttonStates.brake = false);
        brakeBtn.addEventListener('touchstart', (e) => { e.preventDefault(); buttonStates.brake = true; });
        brakeBtn.addEventListener('touchend', (e) => { e.preventDefault(); buttonStates.brake = false; });
    }
    
    // Turn left button
    if (turnLeftBtn) {
        turnLeftBtn.addEventListener('mousedown', () => buttonStates.turnLeft = true);
        turnLeftBtn.addEventListener('mouseup', () => buttonStates.turnLeft = false);
        turnLeftBtn.addEventListener('mouseleave', () => buttonStates.turnLeft = false);
        turnLeftBtn.addEventListener('touchstart', (e) => { e.preventDefault(); buttonStates.turnLeft = true; });
        turnLeftBtn.addEventListener('touchend', (e) => { e.preventDefault(); buttonStates.turnLeft = false; });
    }
    
    // Turn right button
    if (turnRightBtn) {
        turnRightBtn.addEventListener('mousedown', () => buttonStates.turnRight = true);
        turnRightBtn.addEventListener('mouseup', () => buttonStates.turnRight = false);
        turnRightBtn.addEventListener('mouseleave', () => buttonStates.turnRight = false);
        turnRightBtn.addEventListener('touchstart', (e) => { e.preventDefault(); buttonStates.turnRight = true; });
        turnRightBtn.addEventListener('touchend', (e) => { e.preventDefault(); buttonStates.turnRight = false; });
    }

    // Arrow controls (alternative control method)
    const arrowBtns = document.querySelectorAll('.arrow-btn');
    arrowBtns.forEach(btn => {
        const action = btn.dataset.action;
        if (action) {
            btn.addEventListener('mousedown', () => {
                if (action === 'accelerate') buttonStates.accelerate = true;
                else if (action === 'brake') buttonStates.brake = true;
                else if (action === 'turnLeft') buttonStates.turnLeft = true;
                else if (action === 'turnRight') buttonStates.turnRight = true;
            });
            
            btn.addEventListener('mouseup', () => {
                if (action === 'accelerate') buttonStates.accelerate = false;
                else if (action === 'brake') buttonStates.brake = false;
                else if (action === 'turnLeft') buttonStates.turnLeft = false;
                else if (action === 'turnRight') buttonStates.turnRight = false;
            });
            
            btn.addEventListener('mouseleave', () => {
                if (action === 'accelerate') buttonStates.accelerate = false;
                else if (action === 'brake') buttonStates.brake = false;
                else if (action === 'turnLeft') buttonStates.turnLeft = false;
                else if (action === 'turnRight') buttonStates.turnRight = false;
            });
            
            btn.addEventListener('touchstart', (e) => {
                e.preventDefault();
                if (action === 'accelerate') buttonStates.accelerate = true;
                else if (action === 'brake') buttonStates.brake = true;
                else if (action === 'turnLeft') buttonStates.turnLeft = true;
                else if (action === 'turnRight') buttonStates.turnRight = true;
            });
            
            btn.addEventListener('touchend', (e) => {
                e.preventDefault();
                if (action === 'accelerate') buttonStates.accelerate = false;
                else if (action === 'brake') buttonStates.brake = false;
                else if (action === 'turnLeft') buttonStates.turnLeft = false;
                else if (action === 'turnRight') buttonStates.turnRight = false;
            });
        }
    });
}

// Car movement and physics
function updateCar() {
    if (!gameRunning) return;
    
    // Handle input from keyboard and buttons with realistic acceleration
    if (keys['ArrowUp'] || keys['KeyW'] || buttonStates.accelerate) {
        car.speed = Math.min(car.speed + car.acceleration, car.maxSpeed);
    } else if (keys['ArrowDown'] || keys['KeyS'] || buttonStates.brake) {
        car.speed = Math.max(car.speed - car.deceleration, -5); // Allow slight reverse
    } else {
        car.speed *= car.friction; // Natural deceleration
    }
    
    // Steering (only works when moving)
    if (Math.abs(car.speed) > 1) {
        const steerFactor = Math.min(1, Math.abs(car.speed) / 20); // More realistic steering
        if (keys['ArrowLeft'] || keys['KeyA'] || buttonStates.turnLeft) {
            car.angle -= 0.03 * steerFactor;
        }
        if (keys['ArrowRight'] || keys['KeyD'] || buttonStates.turnRight) {
            car.angle += 0.03 * steerFactor;
        }
    }
    
    // Calculate next position
    const nextX = car.x + Math.sin(car.angle) * car.speed;
    const nextY = car.y - Math.cos(car.angle) * car.speed;
    
    // Check if next position is within road boundaries
    if (isCarOnRoad(nextX, nextY, car.width, car.height, car.angle)) {
        car.x = nextX;
        car.y = nextY;
    } else {
        // Stop the car if trying to go off road
        car.speed *= 0.3; // Emergency braking
        showTemporaryMessage("Stay on the road!");
    }
    
    // Update speed display
    updateSpeedDisplay();
    
    // Scroll the world
    scrollOffset += car.speed;
    
    // Check for scenario triggers
    checkScenarioTriggers();
}

function updateSpeedDisplay() {
    const speedElement = document.getElementById('speedDisplay');
    if (speedElement) {
        const kmh = Math.round(Math.abs(car.speed) * 10); // Convert to real km/h (1 unit = 10 km/h)
        speedElement.textContent = Math.min(kmh, 60); // Cap display at 60km/h
    }
}

// Check if car position is within road boundaries
function isCarOnRoad(x, y, width, height, angle) {
    // Get road path and check boundaries with all car corners
    if (roadPoints.length < 2) return true;
    
    // Calculate all four corners of the car considering rotation
    const corners = [
        { x: x - width/2, y: y - height/2 }, // Top-left
        { x: x + width/2, y: y - height/2 }, // Top-right
        { x: x - width/2, y: y + height/2 }, // Bottom-left
        { x: x + width/2, y: y + height/2 }  // Bottom-right
    ];
    
    // Rotate corners based on car angle
    const rotatedCorners = corners.map(corner => {
        const dx = corner.x - x;
        const dy = corner.y - y;
        return {
            x: x + dx * Math.cos(angle) - dy * Math.sin(angle),
            y: y + dx * Math.sin(angle) + dy * Math.cos(angle)
        };
    });
    
    // Check if ALL corners are within road boundaries
    const roadWidth = 80; // Slightly narrower for stricter boundaries
    
    for (let corner of rotatedCorners) {
        let closestDistance = Infinity;
        
        for (let i = 0; i < roadPoints.length; i++) {
            const point = roadPoints[i];
            const distance = Math.sqrt((corner.x - point.x) ** 2 + (corner.y - point.y) ** 2);
            if (distance < closestDistance) {
                closestDistance = distance;
            }
        }
        
        // If any corner is outside road width, car is off-road
        if (closestDistance > roadWidth / 2) {
            return false;
        }
    }
    
    return true;
}

// Show temporary message
function showTemporaryMessage(message) {
    const statusElement = document.getElementById('statusMessage');
    if (statusElement) {
        statusElement.textContent = message;
        statusElement.style.display = 'block';
        statusElement.style.background = 'rgba(231, 76, 60, 0.9)';
        setTimeout(() => {
            statusElement.style.display = 'none';
        }, 2000);
    }
}

// Drawing functions
function draw() {
    // Clear canvas with sky blue background
    ctx.fillStyle = '#87CEEB';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Draw buildings first (background) - DISABLED, using direct drawing in drawRoad instead
    // drawBuildings();
    
    // Draw road
    drawRoad();
    
    // Draw pedestrians
    drawPedestrians();
    
    // Draw road elements and signs
    drawRoadElements();
    
    // Draw car
    drawCar();
    
    // Draw UI elements
    drawUI();
}

// Removed drawDestinationTrackLine function - no broken lines needed

function drawRoad() {
    if (roadPoints.length < 2) return;
    
    ctx.save();
    
    // Calculate camera offset
    const cameraX = canvas.width / 2 - car.x;
    const cameraY = canvas.height / 2 - car.y;
    ctx.translate(cameraX, cameraY);
    
    // Draw green background
    ctx.fillStyle = '#90EE90';
    ctx.fillRect(0, 0, worldWidth, worldHeight);
    
    // Draw road surfaces (dark gray) - COMPLETELY SOLID
    ctx.fillStyle = '#404040';
    
    // 1. Draw VERTICAL ROAD (solid, no lines)
    const centerX = worldWidth / 2;
    const centerY = worldHeight / 2;
    const roadWidth = 80;
    
    ctx.fillRect(centerX - roadWidth/2, 0, roadWidth, worldHeight);
    
    // 2. Draw HORIZONTAL ROADS (solid, no lines)
    const upperY = centerY - 150;
    const lowerY = centerY + 150;
    
    ctx.fillRect(0, upperY - roadWidth/2, worldWidth, roadWidth);
    ctx.fillRect(0, lowerY - roadWidth/2, worldWidth, roadWidth);
    
    // ADD VISIBLE SIDEWALKS/PEDESTRIAN LANES
    ctx.fillStyle = '#D3D3D3'; // Light gray sidewalks
    
    // Sidewalks along vertical road
    const sidewalkWidth = 20;
    ctx.fillRect(centerX - roadWidth/2 - sidewalkWidth, 0, sidewalkWidth, worldHeight); // Left sidewalk
    ctx.fillRect(centerX + roadWidth/2, 0, sidewalkWidth, worldHeight); // Right sidewalk
    
    // Sidewalks along horizontal roads
    ctx.fillRect(0, upperY - roadWidth/2 - sidewalkWidth, worldWidth, sidewalkWidth); // Upper road top sidewalk
    ctx.fillRect(0, upperY + roadWidth/2, worldWidth, sidewalkWidth); // Upper road bottom sidewalk
    ctx.fillRect(0, lowerY - roadWidth/2 - sidewalkWidth, worldWidth, sidewalkWidth); // Lower road top sidewalk
    ctx.fillRect(0, lowerY + roadWidth/2, worldWidth, sidewalkWidth); // Lower road bottom sidewalk
    
    // NO CENTER LINES AT ALL - completely solid roads like in your image
    
    // DRAW BUILDINGS DIRECTLY HERE TO ENSURE VISIBILITY
    drawDirectBuildings(centerX, centerY, roadWidth);
    
    // Draw road elements
    drawAllTrafficElements();
    drawAllPedestrianCrossings();
    drawAllTrafficSigns();
    
    // Buildings are drawn separately in the main draw() function
    
    ctx.restore();
}

// NEW APPROACH: Draw buildings directly with bright colors and large sizes
function drawDirectBuildings(centerX, centerY, roadWidth) {
    const buildingOffset = 150;
    
    // BRIGHT COLORED BUILDINGS - TOP LEFT
    ctx.fillStyle = '#FF0000'; // Bright red
    ctx.fillRect(centerX - buildingOffset - 200, 200, 120, 100);
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 3;
    ctx.strokeRect(centerX - buildingOffset - 200, 200, 120, 100);
    ctx.fillStyle = '#FFFFFF';
    ctx.font = 'bold 16px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('SCHOOL', centerX - buildingOffset - 140, 250);
    
    // BRIGHT GREEN BUILDING - TOP RIGHT
    ctx.fillStyle = '#00FF00'; // Bright green
    ctx.fillRect(centerX + buildingOffset + 50, 180, 140, 120);
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 3;
    ctx.strokeRect(centerX + buildingOffset + 50, 180, 140, 120);
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 16px Arial';
    ctx.fillText('HOUSE', centerX + buildingOffset + 120, 240);
    
    // BRIGHT BLUE BUILDING - BOTTOM LEFT
    ctx.fillStyle = '#0000FF'; // Bright blue
    ctx.fillRect(centerX - buildingOffset - 180, centerY + 250, 130, 110);
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 3;
    ctx.strokeRect(centerX - buildingOffset - 180, centerY + 250, 130, 110);
    ctx.fillStyle = '#FFFFFF';
    ctx.font = 'bold 16px Arial';
    ctx.fillText('SHOP', centerX - buildingOffset - 115, centerY + 310);
    
    // BRIGHT YELLOW BUILDING - BOTTOM RIGHT
    ctx.fillStyle = '#FFFF00'; // Bright yellow
    ctx.fillRect(centerX + buildingOffset + 80, centerY + 300, 150, 130);
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 3;
    ctx.strokeRect(centerX + buildingOffset + 80, centerY + 300, 150, 130);
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 16px Arial';
    ctx.fillText('HOSPITAL', centerX + buildingOffset + 155, centerY + 370);
    
    // BRIGHT MAGENTA BUILDING - NEAR CAR START
    ctx.fillStyle = '#FF00FF'; // Bright magenta
    ctx.fillRect(centerX - 250, worldHeight - 400, 160, 140);
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 3;
    ctx.strokeRect(centerX - 250, worldHeight - 400, 160, 140);
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 16px Arial';
    ctx.fillText('DRIVING SCHOOL', centerX - 170, worldHeight - 330);
    
    // BRIGHT CYAN BUILDING - NEAR CAR START RIGHT
    ctx.fillStyle = '#00FFFF'; // Bright cyan
    ctx.fillRect(centerX + 200, worldHeight - 380, 140, 120);
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 3;
    ctx.strokeRect(centerX + 200, worldHeight - 380, 140, 120);
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 16px Arial';
    ctx.fillText('GAS STATION', centerX + 270, worldHeight - 320);
}

function drawAllTrafficElements() {
    // Draw all traffic lights
    roadElements.forEach(element => {
        if (element.type === roadElementTypes.TRAFFIC_LIGHT) {
            // Traffic light pole
            ctx.fillStyle = '#696969';
            ctx.fillRect(element.x - 3, element.y - 50, 6, 40);
            
            // Traffic light box
            ctx.fillStyle = '#2F2F2F';
            ctx.fillRect(element.x - 12, element.y - 55, 24, 30);
            
            // Lights
            const lightState = element.lightState || 'green';
            
            // Red light
            ctx.fillStyle = lightState === 'red' ? '#FF0000' : '#660000';
            ctx.beginPath();
            ctx.arc(element.x, element.y - 45, 5, 0, Math.PI * 2);
            ctx.fill();
            
            // Yellow light
            ctx.fillStyle = lightState === 'yellow' ? '#FFFF00' : '#666600';
            ctx.beginPath();
            ctx.arc(element.x, element.y - 35, 5, 0, Math.PI * 2);
            ctx.fill();
            
            // Green light
            ctx.fillStyle = lightState === 'green' ? '#00FF00' : '#006600';
            ctx.beginPath();
            ctx.arc(element.x, element.y - 25, 5, 0, Math.PI * 2);
            ctx.fill();
        }
    });
}

function drawAllPedestrianCrossings() {
    roadElements.forEach(element => {
        if (element.type === roadElementTypes.PEDESTRIAN_CROSSING) {
            // No zebra stripes - clean roads only as requested
            // Crossings are indicated by traffic lights and signs only
        }
    });
}

function drawAllTrafficSigns() {
    roadElements.forEach(element => {
        if (element.type === roadElementTypes.STOP_SIGN) {
            // Stop sign
            ctx.fillStyle = '#FF0000';
            ctx.beginPath();
            ctx.arc(element.x, element.y, 15, 0, Math.PI * 2);
            ctx.fill();
            
            // STOP text
            ctx.fillStyle = '#FFFFFF';
            ctx.font = 'bold 8px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('STOP', element.x, element.y + 3);
        }
        
        if (element.type === 'SPEED_LIMIT') {
            // Speed limit sign (white rectangle)
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(element.x - 15, element.y - 20, 30, 25);
            ctx.strokeStyle = '#000000';
            ctx.lineWidth = 2;
            ctx.strokeRect(element.x - 15, element.y - 20, 30, 25);
            
            // Speed limit text
            ctx.fillStyle = '#000000';
            ctx.font = 'bold 8px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('SPEED', element.x, element.y - 10);
            ctx.fillText('LIMIT', element.x, element.y - 2);
            ctx.fillText(element.speedLimit, element.x, element.y + 6);
        }
    });
}

// Car drawing and physics functions

function drawCar() {
    // Draw sidewalks along vertical roads
    ctx.strokeStyle = '#D3D3D3'; // Light gray for sidewalks
    ctx.lineWidth = 15;
    ctx.lineCap = 'round';
    
    // Main vertical road sidewalks
    const mainRoadPoints = roadPoints.filter(p => p.isMainRoad && p.isVertical);
    if (mainRoadPoints.length > 1) {
        // Left sidewalk (west side)
        ctx.beginPath();
        mainRoadPoints.forEach((point, i) => {
            const x = point.x - 50; // 50px left of road center
            if (i === 0) {
                ctx.moveTo(x, point.y);
            } else {
                ctx.lineTo(x, point.y);
            }
        });
        ctx.stroke();
        
        // Right sidewalk (east side)
        ctx.beginPath();
        mainRoadPoints.forEach((point, i) => {
            const x = point.x + 50; // 50px right of road center
            if (i === 0) {
                ctx.moveTo(x, point.y);
            } else {
                ctx.lineTo(x, point.y);
            }
        });
        ctx.stroke();
    }
}

// Removed drawVerticalCityLaneMarkings function - no broken lines needed

function drawVerticalPedestrianCrossings() {
    // Draw zebra crossings for vertical roads (horizontal crossings)
    roadElements.forEach(element => {
        if (element.type === roadElementTypes.PEDESTRIAN_CROSSING && element.isVertical) {
            ctx.fillStyle = '#FFFFFF';
            
            // Draw horizontal zebra stripes across vertical road
            for (let i = -35; i <= 35; i += 8) {
                ctx.fillRect(element.x + i - 2, element.y - 4, 4, 70);
            }
            
            // Pedestrian crossing signs on both sides
            ctx.fillStyle = '#FFD700';
            ctx.fillRect(element.x - 60, element.y - 40, 20, 30);
            ctx.fillRect(element.x + 60, element.y - 40, 20, 30);
            
            // Warning signs
            ctx.fillStyle = '#000000';
            ctx.font = '10px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('PED', element.x - 50, element.y - 30);
            ctx.fillText('XING', element.x - 50, element.y - 20);
            ctx.fillText('PED', element.x + 70, element.y - 30);
            ctx.fillText('XING', element.x + 70, element.y - 20);
        }
    });
}

function drawVerticalTrafficControlDevices() {
    roadElements.forEach(element => {
        if ((element.type === roadElementTypes.TRAFFIC_LIGHT || element.hasTrafficLight) && element.isVertical) {
            // Traffic light pole positioned for vertical road
            ctx.fillStyle = '#696969';
            ctx.fillRect(element.x + 50, element.y - 5, 60, 10);
            
            // Traffic light box
            ctx.fillStyle = '#2F2F2F';
            ctx.fillRect(element.x + 50, element.y - 15, 40, 30);
            
            // Traffic lights
            const lightState = element.lightState || 'green';
            
            // Red light
            ctx.fillStyle = lightState === 'red' ? '#FF0000' : '#660000';
            ctx.beginPath();
            ctx.arc(element.x + 55, element.y, 6, 0, Math.PI * 2);
            ctx.fill();
            
            // Yellow light
            ctx.fillStyle = lightState === 'yellow' ? '#FFFF00' : '#666600';
            ctx.beginPath();
            ctx.arc(element.x + 70, element.y, 6, 0, Math.PI * 2);
            ctx.fill();
            
            // Green light
            ctx.fillStyle = lightState === 'green' ? '#00FF00' : '#006600';
            ctx.beginPath();
            ctx.arc(element.x + 85, element.y, 6, 0, Math.PI * 2);
            ctx.fill();
        }
        
        if (element.type === roadElementTypes.STOP_SIGN && element.isVertical) {
            // Stop sign pole positioned for vertical road
            ctx.fillStyle = '#696969';
            ctx.fillRect(element.x + 50, element.y - 2, 40, 4);
            
            // Stop sign (octagon)
            ctx.fillStyle = '#FF0000';
            ctx.beginPath();
            ctx.arc(element.x + 70, element.y, 15, 0, Math.PI * 2);
            ctx.fill();
            
            // STOP text
            ctx.fillStyle = '#FFFFFF';
            ctx.font = 'bold 10px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('STOP', element.x + 70, element.y + 3);
        }
    });
}

function drawCar() {
    ctx.save();
    ctx.translate(car.x, car.y);
    ctx.rotate(car.angle);
    
    // Car body
    ctx.fillStyle = '#FF0000';
    ctx.fillRect(-15, -30, 30, 60);
    
    // Car windshield
    ctx.fillStyle = '#87CEEB';
    ctx.fillRect(-12, -25, 24, 15);
    
    // Car headlights
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(-8, -32, 6, 4);
    ctx.fillRect(2, -32, 6, 4);
    
    // Car taillights
    ctx.fillStyle = '#FF0000';
    ctx.fillRect(-8, 28, 6, 4);
    ctx.fillRect(2, 28, 6, 4);
    
    ctx.restore();
}

function drawTrafficControlDevices() {
    roadElements.forEach(element => {
        if (element.type === roadElementTypes.TRAFFIC_LIGHT || element.hasTrafficLight) {
            // Traffic light pole
            ctx.fillStyle = '#696969';
            ctx.fillRect(element.x - 5, element.y - 80, 10, 60);
            
            // Traffic light box
            ctx.fillStyle = '#2F2F2F';
            ctx.fillRect(element.x - 15, element.y - 85, 30, 40);
            
            // Traffic lights
            const lightState = element.lightState || 'green';
            
            // Red light
            ctx.fillStyle = lightState === 'red' ? '#FF0000' : '#660000';
            ctx.beginPath();
            ctx.arc(element.x, element.y - 75, 6, 0, Math.PI * 2);
            ctx.fill();
            
            // Yellow light
            ctx.fillStyle = lightState === 'yellow' ? '#FFFF00' : '#666600';
            ctx.beginPath();
            ctx.arc(element.x, element.y - 60, 6, 0, Math.PI * 2);
            ctx.fill();
            
            // Green light
            ctx.fillStyle = lightState === 'green' ? '#00FF00' : '#006600';
            ctx.beginPath();
            ctx.arc(element.x, element.y - 45, 6, 0, Math.PI * 2);
            ctx.fill();
        }
        
        if (element.type === roadElementTypes.STOP_SIGN) {
            // Stop sign pole
            ctx.fillStyle = '#696969';
            ctx.fillRect(element.x + 40, element.y - 60, 4, 40);
            
            // Stop sign (octagon)
            ctx.fillStyle = '#FF0000';
            ctx.beginPath();
            ctx.arc(element.x + 42, element.y - 65, 15, 0, Math.PI * 2);
            ctx.fill();
            
            // STOP text
            ctx.fillStyle = '#FFFFFF';
            ctx.font = 'bold 10px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('STOP', element.x + 42, element.y - 60);
        }
    });
}

function drawCar() {
    ctx.save();
    ctx.translate(car.x + car.width/2, car.y + car.height/2);
    ctx.rotate(car.angle);
    
    // Car body
    ctx.fillStyle = car.color;
    ctx.fillRect(-car.width/2, -car.height/2, car.width, car.height);
    
    // Car details
    ctx.fillStyle = '#000000';
    ctx.fillRect(-car.width/2 + 5, -car.height/2 + 5, car.width - 10, 8); // Front windshield
    ctx.fillRect(-car.width/2 + 5, car.height/2 - 13, car.width - 10, 8); // Rear windshield
    
    // Car wheels
    ctx.fillStyle = '#000000';
    ctx.fillRect(-car.width/2 - 2, -car.height/2 + 10, 4, 8);
    ctx.fillRect(car.width/2 - 2, -car.height/2 + 10, 4, 8);
    ctx.fillRect(-car.width/2 - 2, car.height/2 - 18, 4, 8);
    ctx.fillRect(car.width/2 - 2, car.height/2 - 18, 4, 8);
    
    // Visual indicator when car is stopped for scenario
    if (isCarStopped) {
        // Red "STOPPED" indicator above car
        ctx.fillStyle = '#FF0000';
        ctx.fillRect(-25, -45, 50, 15); // Red background
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 10px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('STOPPED', 0, -35);
        
        // Flashing red border around car
        const time = Date.now();
        if (Math.sin(time * 0.01) > 0) {
            ctx.strokeStyle = '#FF0000';
            ctx.lineWidth = 3;
            ctx.strokeRect(-car.width/2 - 3, -car.height/2 - 3, car.width + 6, car.height + 6);
        }
    }
    
    ctx.restore();
}

function drawRoadSigns() {
    roadSigns.forEach(sign => {
        const signY = sign.y + scrollOffset;
        
        if (signY > -50 && signY < canvas.height + 50) {
            // Sign post
            ctx.fillStyle = '#8B4513';
            ctx.fillRect(sign.x - 3, signY, 6, 40);
            
            // Sign board
            ctx.fillStyle = sign.color || '#FFD700';
            ctx.fillRect(sign.x - 25, signY - 30, 50, 30);
            ctx.strokeStyle = '#000000';
            ctx.strokeRect(sign.x - 25, signY - 30, 50, 30);
            
            // Sign text
            ctx.fillStyle = '#000000';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(sign.text, sign.x, signY - 10);
        }
    });
}

// Draw buildings along the roadside
function drawBuildings() {
    ctx.save();
    
    // Calculate camera offset
    const cameraX = canvas.width / 2 - car.x;
    const cameraY = canvas.height / 2 - car.y;
    
    ctx.translate(cameraX, cameraY);
    
    buildings.forEach(building => {
        if (building.type === 'school') {
            drawSchoolBuilding(building);
        } else if (building.type === 'playground') {
            drawPlayground(building);
        } else if (building.type === 'tree') {
            drawTree(building);
        } else if (building.type === 'streetlamp') {
            drawStreetLamp(building);
        } else if (building.type === 'busstop') {
            drawBusStop(building);
        } else {
            drawRegularBuilding(building);
        }
    });
    
    ctx.restore();
}

function drawSchoolBuilding(building) {
    // School building shadow for depth
    ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
    ctx.fillRect(building.x + 10, building.y + 10, building.width, building.height);
    
    // School building base - bright gold color
    ctx.fillStyle = building.color;
    ctx.fillRect(building.x, building.y, building.width, building.height);
    
    // School building outline - THICK BORDER
    ctx.strokeStyle = '#8B4513';
    ctx.lineWidth = 5; // Much thicker for visibility
    ctx.strokeRect(building.x, building.y, building.width, building.height);
    
    // School name plate
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(building.x + 10, building.y + 10, building.width - 20, 25);
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 1;
    ctx.strokeRect(building.x + 10, building.y + 10, building.width - 20, 25);
    
    // School name text
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 12px Arial';
    ctx.textAlign = 'center';
    ctx.fillText(building.label || building.name || 'SCHOOL', building.x + building.width/2, building.y + 27);
    
    // School windows - larger and more organized
    ctx.fillStyle = '#87CEEB';
    const windowSize = 12;
    const windowSpacing = 18;
    const startY = building.y + 45;
    
    for (let row = 0; row < 3; row++) {
        for (let col = 0; col < Math.floor(building.width / windowSpacing) - 1; col++) {
            const windowX = building.x + 15 + col * windowSpacing;
            const windowY = startY + row * 20;
            ctx.fillRect(windowX, windowY, windowSize, windowSize);
            
            // Window frames
            ctx.strokeStyle = '#000000';
            ctx.lineWidth = 1;
            ctx.strokeRect(windowX, windowY, windowSize, windowSize);
        }
    }
    
    // School entrance doors
    ctx.fillStyle = '#8B4513';
    const doorWidth = 20;
    const doorHeight = 30;
    const doorX = building.x + building.width/2 - doorWidth/2;
    const doorY = building.y + building.height - doorHeight;
    ctx.fillRect(doorX, doorY, doorWidth, doorHeight);
    
    // Door frame
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2;
    ctx.strokeRect(doorX, doorY, doorWidth, doorHeight);
    
    // Flag pole if specified
    if (building.hasFlag) {
        ctx.strokeStyle = '#696969';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(building.x + building.width + 10, building.y - 30);
        ctx.lineTo(building.x + building.width + 10, building.y + 20);
        ctx.stroke();
        
        // Flag
        ctx.fillStyle = '#FF0000';
        ctx.fillRect(building.x + building.width + 12, building.y - 25, 20, 15);
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(building.x + building.width + 12, building.y - 17, 20, 7);
    }
}

function drawPlayground(building) {
    // Playground base (grass)
    ctx.fillStyle = '#32CD32';
    ctx.fillRect(building.x, building.y, building.width, building.height);
    
    // Playground equipment
    ctx.fillStyle = '#FF6347';
    ctx.fillRect(building.x + 5, building.y + 5, 10, 20); // Slide
    
    ctx.fillStyle = '#4169E1';
    ctx.fillRect(building.x + 20, building.y + 10, 8, 15); // Swing set
    
    ctx.fillStyle = '#FFD700';
    ctx.beginPath();
    ctx.arc(building.x + building.width - 8, building.y + 8, 6, 0, Math.PI * 2);
    ctx.fill(); // Merry-go-round
}

function drawTree(building) {
    // Tree trunk
    ctx.fillStyle = '#8B4513';
    ctx.fillRect(building.x + building.width/2 - 2, building.y + building.height - 8, 4, 8);
    
    // Tree foliage
    ctx.fillStyle = building.color;
    ctx.beginPath();
    ctx.arc(building.x + building.width/2, building.y + building.height/2, building.width/2, 0, Math.PI * 2);
    ctx.fill();
}

function drawStreetLamp(building) {
    // Lamp post
    ctx.strokeStyle = building.color;
    ctx.lineWidth = building.width;
    ctx.beginPath();
    ctx.moveTo(building.x, building.y + building.height);
    ctx.lineTo(building.x, building.y);
    ctx.stroke();
    
    // Lamp light
    ctx.fillStyle = '#FFFF99';
    ctx.beginPath();
    ctx.arc(building.x, building.y, 6, 0, Math.PI * 2);
    ctx.fill();
}

function drawBusStop(building) {
    // Bus stop shelter
    ctx.fillStyle = building.color;
    ctx.fillRect(building.x, building.y, building.width, building.height);
    
    // Bus stop sign
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(building.x + 5, building.y - 10, building.width - 10, 8);
    
    ctx.fillStyle = '#000000';
    ctx.font = '8px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('BUS STOP', building.x + building.width/2, building.y - 4);
}

function drawRegularBuilding(building) {
    // Building shadow for depth
    ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
    ctx.fillRect(building.x + 8, building.y + 8, building.width, building.height);
    
    // Building base with bright colors
    ctx.fillStyle = building.color;
    ctx.fillRect(building.x, building.y, building.width, building.height);
    
    // Building outline - THICK BORDER for visibility
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 4; // Much thicker border
    ctx.strokeRect(building.x, building.y, building.width, building.height);
    
    // Windows if specified
    if (building.hasWindows) {
        const windowRows = Math.floor(building.height / 25);
        const windowCols = Math.floor(building.width / 20);
        
        ctx.fillStyle = '#87CEEB'; // Light blue windows
        for (let row = 1; row < windowRows; row++) {
            for (let col = 1; col < windowCols; col++) {
                const windowX = building.x + col * 20 - 8;
                const windowY = building.y + row * 25 - 10;
                ctx.fillRect(windowX, windowY, 10, 15);
                
                // Window frame
                ctx.strokeStyle = '#000000';
                ctx.lineWidth = 1;
                ctx.strokeRect(windowX, windowY, 10, 15);
            }
        }
    }
    
    // Door for houses and shops
    if (building.type === 'house' || building.type === 'shop') {
        ctx.fillStyle = '#8B4513'; // Brown door
        const doorX = building.x + building.width / 2 - 8;
        const doorY = building.y + building.height - 25;
        ctx.fillRect(doorX, doorY, 16, 25);
        
        // Door handle
        ctx.fillStyle = '#FFD700';
        ctx.fillRect(doorX + 12, doorY + 12, 2, 2);
    }
    
    // Roof for houses
    if (building.type === 'house') {
        ctx.fillStyle = '#8B0000'; // Dark red roof
        ctx.beginPath();
        ctx.moveTo(building.x - 5, building.y);
        ctx.lineTo(building.x + building.width / 2, building.y - 20);
        ctx.lineTo(building.x + building.width + 5, building.y);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
    }
    
    // Add labels for shops and other buildings
    if (building.label && building.label.trim() !== '') {
        // Draw label background
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(building.x + 5, building.y + 5, building.width - 10, 20);
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 1;
        ctx.strokeRect(building.x + 5, building.y + 5, building.width - 10, 20);
        
        // Draw label text
        ctx.fillStyle = '#000000';
        ctx.font = 'bold 10px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(building.label, building.x + building.width/2, building.y + 18);
    }
}

// Draw pedestrians and sidewalks
function drawPedestrians() {
    ctx.save();
    
    // Calculate camera offset
    const cameraX = canvas.width / 2 - car.x;
    const cameraY = canvas.height / 2 - car.y;
    
    ctx.translate(cameraX, cameraY);
    
    // First draw sidewalks along roads
    if (roadPoints.length > 1) {
        ctx.fillStyle = '#C0C0C0'; // Gray sidewalks
        
        for (let i = 0; i < roadPoints.length - 1; i++) {
            const current = roadPoints[i];
            const next = roadPoints[i + 1];
            
            // Calculate perpendicular for sidewalk positions
            const dx = next.x - current.x;
            const dy = next.y - current.y;
            const length = Math.sqrt(dx * dx + dy * dy);
            
            if (length > 0) {
                const perpX = -dy / length;
                const perpY = dx / length;
                
                // Left sidewalk
                const leftSidewalkX = current.x + perpX * 55;
                const leftSidewalkY = current.y + perpY * 55;
                const leftSidewalkX2 = next.x + perpX * 55;
                const leftSidewalkY2 = next.y + perpY * 55;
                
                ctx.fillRect(leftSidewalkX - 8, leftSidewalkY, 16, 
                           Math.sqrt((leftSidewalkX2 - leftSidewalkX) ** 2 + (leftSidewalkY2 - leftSidewalkY) ** 2));
                
                // Right sidewalk
                const rightSidewalkX = current.x - perpX * 55;
                const rightSidewalkY = current.y - perpY * 55;
                const rightSidewalkX2 = next.x - perpX * 55;
                const rightSidewalkY2 = next.y - perpY * 55;
                
                ctx.fillRect(rightSidewalkX - 8, rightSidewalkY, 16, 
                           Math.sqrt((rightSidewalkX2 - rightSidewalkX) ** 2 + (rightSidewalkY2 - rightSidewalkY) ** 2));
            }
        }
    }
    
    // Draw animated pedestrians
    pedestrians.forEach(pedestrian => {
        // Update pedestrian position (simple walking animation)
        if (!pedestrian.waiting) {
            pedestrian.x += Math.cos(pedestrian.direction) * pedestrian.speed;
            pedestrian.y += Math.sin(pedestrian.direction) * pedestrian.speed;
            
            // Change direction occasionally to simulate walking
            if (Math.random() > 0.98) {
                pedestrian.direction += (Math.random() - 0.5) * 0.3;
            }
            
            // Keep pedestrians on sidewalks (basic constraint)
            const distanceFromRoad = Math.min(
                ...roadPoints.map(point => 
                    Math.sqrt((pedestrian.x - point.x) ** 2 + (pedestrian.y - point.y) ** 2)
                )
            );
            
            if (distanceFromRoad < 50) { // Too close to road
                pedestrian.direction += Math.PI; // Turn around
            }
        }
        
        // Pedestrian body (rectangular)
        ctx.fillStyle = pedestrian.color;
        ctx.fillRect(pedestrian.x - pedestrian.width/2, pedestrian.y - pedestrian.height/2, 
                    pedestrian.width, pedestrian.height);
        
        // Pedestrian head (circle)
        ctx.fillStyle = '#FFDBAC'; // Skin color
        ctx.beginPath();
        ctx.arc(pedestrian.x, pedestrian.y - pedestrian.height/2 - 3, 3, 0, Math.PI * 2);
        ctx.fill();
        
        // Legs (simple animation)
        ctx.strokeStyle = pedestrian.color;
        ctx.lineWidth = 2;
        const legOffset = Math.sin(Date.now() * 0.01 + pedestrian.x) * 2;
        ctx.beginPath();
        ctx.moveTo(pedestrian.x - 2, pedestrian.y + pedestrian.height/2);
        ctx.lineTo(pedestrian.x - 2 + legOffset, pedestrian.y + pedestrian.height/2 + 4);
        ctx.moveTo(pedestrian.x + 2, pedestrian.y + pedestrian.height/2);
        ctx.lineTo(pedestrian.x + 2 - legOffset, pedestrian.y + pedestrian.height/2 + 4);
        ctx.stroke();
        
        // Arms (simple animation)
        const armOffset = Math.sin(Date.now() * 0.01 + pedestrian.x + Math.PI) * 1.5;
        ctx.beginPath();
        ctx.moveTo(pedestrian.x - pedestrian.width/2, pedestrian.y - 2);
        ctx.lineTo(pedestrian.x - pedestrian.width/2 - 3 + armOffset, pedestrian.y + 2);
        ctx.moveTo(pedestrian.x + pedestrian.width/2, pedestrian.y - 2);
        ctx.lineTo(pedestrian.x + pedestrian.width/2 + 3 - armOffset, pedestrian.y + 2);
        ctx.stroke();
    });
    
    ctx.restore();
}

// Draw enhanced road elements with camera offset
function drawRoadElements() {
    ctx.save();
    
    // Calculate camera offset
    const cameraX = canvas.width / 2 - car.x;
    const cameraY = canvas.height / 2 - car.y;
    
    ctx.translate(cameraX, cameraY);
    
    roadElements.forEach(element => {
        switch (element.type) {
            case roadElementTypes.PEDESTRIAN_CROSSING:
                // Zebra crossing stripes
                ctx.fillStyle = '#FFFFFF';
                for (let i = 0; i < 8; i++) {
                    ctx.fillRect(element.x - element.width/2 + i * 10, 
                               element.y, 8, element.height);
                }
                
                // Pedestrian crossing signs
                ctx.fillStyle = '#FFFF00';
                ctx.fillRect(element.x - 40, element.y - 30, 30, 25);
                ctx.fillStyle = '#000000';
                ctx.font = '8px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('PED', element.x - 25, element.y - 15);
                ctx.fillText('XING', element.x - 25, element.y - 8);
                break;
                
            case roadElementTypes.TRAFFIC_LIGHT:
                // Traffic light pole
                ctx.fillStyle = '#666666';
                ctx.fillRect(element.x - 3, element.y, 6, 50);
                
                // Traffic light box
                ctx.fillStyle = '#333333';
                ctx.fillRect(element.x - 15, element.y - 35, 30, 35);
                ctx.strokeStyle = '#222222';
                ctx.lineWidth = 2;
                ctx.strokeRect(element.x - 15, element.y - 35, 30, 35);
                
                // Lights with realistic timing
                const currentTime = Date.now();
                const cycle = (currentTime / 3000) % 3; // 3 second cycles
                let lightState = 'green';
                if (cycle < 1) lightState = 'red';
                else if (cycle < 1.5) lightState = 'yellow';
                
                // Red light
                ctx.fillStyle = lightState === 'red' ? '#FF0000' : '#660000';
                ctx.beginPath();
                ctx.arc(element.x, element.y - 28, 6, 0, Math.PI * 2);
                ctx.fill();
                
                // Yellow light
                ctx.fillStyle = lightState === 'yellow' ? '#FFFF00' : '#666600';
                ctx.beginPath();
                ctx.arc(element.x, element.y - 17, 6, 0, Math.PI * 2);
                ctx.fill();
                
                // Green light
                ctx.fillStyle = lightState === 'green' ? '#00FF00' : '#006600';
                ctx.beginPath();
                ctx.arc(element.x, element.y - 6, 6, 0, Math.PI * 2);
                ctx.fill();
                break;
                
            case roadElementTypes.STOP_SIGN:
                // Stop sign pole
                ctx.fillStyle = '#666666';
                ctx.fillRect(element.x - 2, element.y, 4, 30);
                
                // Octagonal stop sign
                ctx.fillStyle = '#FF0000';
                ctx.beginPath();
                const centerX = element.x;
                const centerY = element.y - 15;
                const radius = 12;
                ctx.moveTo(centerX + radius, centerY);
                for (let i = 1; i < 8; i++) {
                    const angle = (i * Math.PI) / 4;
                    ctx.lineTo(centerX + radius * Math.cos(angle), centerY + radius * Math.sin(angle));
                }
                ctx.closePath();
                ctx.fill();
                
                // White border
                ctx.strokeStyle = '#FFFFFF';
                ctx.lineWidth = 2;
                ctx.stroke();
                
                // STOP text
                ctx.fillStyle = '#FFFFFF';
                ctx.font = 'bold 8px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('STOP', centerX, centerY + 2);
                break;
                
            case roadElementTypes.SCHOOL_ZONE:
                // School zone sign
                ctx.fillStyle = '#FFFF00';
                ctx.fillRect(element.x - 25, element.y - 20, 50, 30);
                ctx.strokeStyle = '#000000';
                ctx.lineWidth = 2;
                ctx.strokeRect(element.x - 25, element.y - 20, 50, 30);
                
                ctx.fillStyle = '#000000';
                ctx.font = 'bold 10px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('SCHOOL', element.x, element.y - 8);
                ctx.font = '8px Arial';
                ctx.fillText('25 km/h', element.x, element.y + 5);
                
                // School zone pole
                ctx.fillStyle = '#666666';
                ctx.fillRect(element.x - 2, element.y + 10, 4, 25);
                break;
                
            case roadElementTypes.INTERSECTION:
                // No intersection markings - clean roads only
                break;
        }
    });
    
    ctx.restore();
}

function drawUI() {
    // Draw scenario panel if active
    if (currentScenario) {
        drawScenarioPanel();
    }
}

function drawScenarioPanel() {
    // This will be handled by the HTML UI panel
    // The canvas version would be too complex for this simple demo
}

// Scenario management
function checkScenarioTriggers() {
    if (currentScenario || !gameRunning) return;
    
    // Check for contextual scenarios based on nearby road elements
    for (let element of roadElements) {
        const distance = Math.sqrt((car.x - element.x) ** 2 + (car.y - element.y) ** 2);
        
        // Trigger scenario when approaching road element (within 100 pixels)
        if (distance < 100) {
            // Find matching scenario for this element type
            const matchingScenario = contextualScenarios.find(scenario => 
                scenario.triggerElement === element.type && !element.scenarioTriggered
            );
            
            if (matchingScenario) {
                element.scenarioTriggered = true; // Mark as triggered
                presentScenario(matchingScenario);
                break; // Only one scenario at a time
            }
        }
    }
}

function presentScenario(scenario) {
    currentScenario = scenario;
    
    // STOP THE CAR - Car must wait for user answer
    isCarStopped = true;
    car.speed = 0;
    car.angularVelocity = 0;
    
    // Update UI panel
    updateScenarioPanel(scenario);
    
    showStatus(`⚠️ ${scenario.title} - Car stopped for safety question!`, 3000);
}

function addRoadSign(scenario) {
    const sign = {
        x: road.x + road.width + 30, // Position to the right of the road
        y: car.y - 200, // Ahead of the car
        text: getSignText(scenario.signType),
        color: getSignColor(scenario.signType),
        type: scenario.signType
    };
    
    roadSigns.push(sign);
    currentSign = sign;
}

function getSignText(signType) {
    switch (signType) {
        case 'school': return 'SCHOOL';
        case 'stop': return 'STOP';
        case 'speed_limit': return '50';
        case 'yield': return 'YIELD';
        case 'no_parking': return 'NO PARK';
        default: return 'SIGN';
    }
}

function getSignColor(signType) {
    switch (signType) {
        case 'school': return '#FFD700'; // Yellow
        case 'stop': return '#FF0000'; // Red
        case 'speed_limit': return '#FFFFFF'; // White
        case 'yield': return '#FFFF00'; // Yellow
        case 'no_parking': return '#FF0000'; // Red
        default: return '#FFFFFF';
    }
}

function updateScenarioPanel(scenario) {
    // Update scenario number
    const scenarioNumberElement = document.getElementById('scenarioNumber');
    if (scenarioNumberElement) {
        scenarioNumberElement.textContent = scenarioIndex + 1;
    }
    
    // Update scenario description and question
    const descriptionElement = document.getElementById('scenarioDescription');
    if (descriptionElement) {
        descriptionElement.innerHTML = `<p>${scenario.title}</p>`;
    }
    
    const questionElement = document.getElementById('scenarioQuestion');
    if (questionElement) {
        questionElement.innerHTML = `<p>${scenario.question}</p>`;
    }
    
    const optionsContainer = document.getElementById('scenarioOptions');
    if (optionsContainer) {
        optionsContainer.innerHTML = '';
        
        scenario.options.forEach((option, index) => {
            const button = document.createElement('button');
            button.className = 'option-btn';
            button.textContent = option;
            button.onclick = () => selectOption(index);
            optionsContainer.appendChild(button);
        });
        
        // Add "Go to Results" button if this is the 5th scenario
        if (scenarioIndex === 4) { // 0-based index, so 4 is the 5th scenario
            const resultsButton = document.createElement('button');
            resultsButton.className = 'results-btn';
            resultsButton.textContent = 'Go to Results';
            resultsButton.style.cssText = `
                background: #27ae60;
                color: white;
                padding: 15px 30px;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                font-weight: bold;
                margin-top: 20px;
                cursor: pointer;
                width: 100%;
            `;
            resultsButton.onclick = () => goToResults();
            optionsContainer.appendChild(resultsButton);
        }
    }
    
    const scenarioPanel = document.getElementById('scenarioPanel');
    if (scenarioPanel) {
        scenarioPanel.style.display = 'block';
    }
}

function selectOption(optionIndex) {
    if (!currentScenario) return;
    
    const isCorrect = optionIndex === currentScenario.correctAnswer;
    gameStats.total++;
    
    if (isCorrect) {
        gameStats.correct++;
        showStatus("✅ Correct! Well done!", 2000);
    } else {
        gameStats.wrong++;
        showStatus("❌ Wrong answer. The correct answer was: " + currentScenario.options[currentScenario.correctAnswer], 4000);
    }
    
    // Save scenario result
    gameStats.scenarios.push({
        scenario: currentScenario.title,
        userAnswer: optionIndex,
        correctAnswer: currentScenario.correctAnswer,
        isCorrect: isCorrect,
        timestamp: Date.now()
    });
    
    updateStatsDisplay();
    
    // If this is the 5th scenario, show the "Go to Results" button
    if (scenarioIndex === 4) {
        const goToResultsBtn = document.getElementById('goToResultsBtn');
        if (goToResultsBtn) {
            goToResultsBtn.style.display = 'block';
        }
        // Don't move to next scenario, let user click "Go to Results"
        return;
    }
    
    // Move to next scenario
    setTimeout(() => {
        currentScenario = null;
        isCarStopped = false; // RESUME CAR MOVEMENT after scenario
        scenarioIndex++;
        
        const scenarioPanel = document.getElementById('scenarioPanel');
        if (scenarioPanel) {
            scenarioPanel.style.display = 'none';
        }
        
        showStatus("You may continue driving. Be safe!", 2000);
        
        if (scenarioIndex >= 5) { // End after EXACTLY 5 scenarios
            endSimulation();
        }
    }, 2000);
}

// Game state management
function startSimulation() {
    gameRunning = true;
    gameStats.startTime = Date.now();
    scenarioIndex = 0;
    currentScenario = null;
    
    // Reset car position to start at BOTTOM of screen
    if (roadPoints.length > 0) {
        car.x = worldWidth / 2; // Center horizontally
        car.y = worldHeight - 100; // Bottom of screen
    } else {
        car.x = worldWidth / 2;
        car.y = worldHeight - 100;
    }
    car.speed = 0;
    car.angle = -Math.PI / 2; // Point upward
    
    // Clear road signs
    roadSigns = [];
    scrollOffset = 0;
    
    showStatus("🚗 Simulation started! Drive safely and respond to road signs.", 3000);
}

function endSimulation() {
    gameRunning = false;
    
    const completionTime = Math.floor((Date.now() - gameStats.startTime) / 1000);
    const scorePercentage = gameStats.total > 0 ? Math.round((gameStats.correct / gameStats.total) * 100) : 0;
    const passed = scorePercentage >= 70;
    
    // Show results
    showResults(scorePercentage, passed);
    
    // Save to database
    saveSimulationResult(completionTime, scorePercentage);
}

function showResults(scorePercentage, passed) {
    const resultsScreen = document.getElementById('resultsScreen');
    const resultsTitle = document.getElementById('resultsTitle');
    const resultsScore = document.getElementById('resultsScore');
    const totalScenarios = document.getElementById('totalScenarios');
    const wrongAnswers = document.getElementById('wrongAnswers');
    
    if (resultsTitle) {
        resultsTitle.textContent = passed ? 'PASSED!' : 'FAILED';
        resultsTitle.style.color = passed ? '#4CAF50' : '#F44336';
    }
    
    if (resultsScore) {
        resultsScore.textContent = scorePercentage + '%';
    }
    
    if (totalScenarios) {
        totalScenarios.textContent = gameStats.total;
    }
    
    if (wrongAnswers) {
        wrongAnswers.textContent = gameStats.wrong;
    }
    
    if (resultsScreen) {
        resultsScreen.style.display = 'flex';
    }
    
    showStatus(passed ? "🎉 Congratulations! You passed!" : "📚 Keep practicing!", 5000);
}

function updateStatsDisplay() {
    const totalScenariosElement = document.getElementById('totalScenarios');
    const wrongAnswersElement = document.getElementById('wrongAnswers');
    
    if (totalScenariosElement) {
        totalScenariosElement.textContent = gameStats.total;
    }
    
    if (wrongAnswersElement) {
        wrongAnswersElement.textContent = gameStats.wrong;
    }
    
    const accuracy = gameStats.total > 0 ? Math.round((gameStats.correct / gameStats.total) * 100) : 0;
    const resultsScoreElement = document.getElementById('resultsScore');
    if (resultsScoreElement) {
        resultsScoreElement.textContent = accuracy + '%';
    }
}

// Animation loop
function animate() {
    updateCar();
    draw();
    animationId = requestAnimationFrame(animate);
}

// Utility functions
function showStatus(message, duration = 2000) {
    const statusElement = document.getElementById('statusMessage');
    if (statusElement) {
        statusElement.textContent = message;
        statusElement.style.display = 'block';
        
        setTimeout(() => {
            statusElement.style.display = 'none';
        }, duration);
    } else {
        console.log('Status:', message);
    }
}

function tryAgain() {
    const resultsScreen = document.getElementById('resultsScreen');
    if (resultsScreen) {
        resultsScreen.style.display = 'none';
    }
    
    // Reset stats
    gameStats = { correct: 0, wrong: 0, total: 0, startTime: null, scenarios: [] };
    
    // Restart simulation
    setTimeout(() => {
        startSimulation();
    }, 1000);
}

// Database integration - FIXED FOR SIMULATION.PHP
function saveSimulationResult(completionTime, scorePercentage) {
    const data = new FormData();
    data.append('action', 'save_simulation_result');
    data.append('simulation_type', 'driving_simulation_2d');
    data.append('total_scenarios', gameStats.total || 0);
    data.append('correct_answers', gameStats.correct || 0);
    data.append('wrong_answers', gameStats.wrong || 0);
    data.append('completion_time', completionTime);
    data.append('scenarios_data', JSON.stringify(gameStats.scenarios || []));
    
    fetch('simulation.php', { // Use simulation.php instead of save_simulation.php
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('✅ Simulation result saved successfully to database');
            showStatus(`Results saved! Score: ${scorePercentage}% (${scorePercentage >= 70 ? 'PASSED' : 'FAILED'})`, 4000);
        } else {
            console.error('❌ Failed to save simulation result:', result.message);
            showStatus('⚠️ Could not save results to database', 3000);
        }
    })
    .catch(error => {
        console.error('❌ Network error saving simulation result:', error);
        showStatus('⚠️ Network error - results not saved', 3000);
    });
}

// NEW FUNCTION: Go to Results - Save and Redirect
function goToResults() {
    console.log('🎯 Go to Results button clicked - Final scenario completed');
    
    // Calculate final stats
    const completionTime = gameStats.startTime ? Math.floor((Date.now() - gameStats.startTime) / 1000) : 0;
    const scorePercentage = gameStats.total > 0 ? Math.round((gameStats.correct / gameStats.total) * 100) : 0;
    
    // Show loading message
    showStatus('Saving results...', 1000);
    
    // Save to database
    const data = new FormData();
    data.append('action', 'save_simulation_result');
    data.append('simulation_type', 'driving_simulation_2d');
    data.append('total_scenarios', gameStats.total || 5); // Ensure 5 scenarios
    data.append('correct_answers', gameStats.correct || 0);
    data.append('wrong_answers', gameStats.wrong || 0);
    data.append('completion_time', completionTime);
    data.append('scenarios_data', JSON.stringify(gameStats.scenarios || []));
    
    fetch('simulation.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('✅ Results saved successfully - Redirecting to results page');
            showStatus(`Results saved! Redirecting...`, 2000);
            
            // Redirect to results page after 2 seconds
            setTimeout(() => {
                window.location.href = 'simulation_result.php';
            }, 2000);
        } else {
            console.error('❌ Failed to save results:', result.message);
            showStatus('⚠️ Could not save results. Redirecting anyway...', 3000);
            
            // Still redirect even if save failed
            setTimeout(() => {
                window.location.href = 'simulation_result.php';
            }, 3000);
        }
    })
    .catch(error => {
        console.error('❌ Network error:', error);
        showStatus('⚠️ Network error. Redirecting to results...', 3000);
        
        // Still redirect even if there's an error
        setTimeout(() => {
            window.location.href = 'simulation_result.php';
        }, 3000);
    });
}

// Initialize when page loads
window.addEventListener('load', () => {
    init();
    
    // Add event listeners for UI buttons
    const retryBtn = document.getElementById('retryBtn');
    if (retryBtn) {
        retryBtn.addEventListener('click', tryAgain);
    }
});

// Handle page visibility changes
document.addEventListener('visibilitychange', () => {
    if (document.hidden && gameRunning) {
        // Pause game when tab is not visible
        gameRunning = false;
        showStatus("Game paused. Click to resume.", 3000);
    }
});

// Road boundary checking functions
function isPositionOnRoad(x, y) {
    const roadWidth = 90; // Total road width (45 on each side from center)
    
    // Check against main road points
    for (let i = 0; i < roadPoints.length - 1; i++) {
        const current = roadPoints[i];
        const next = roadPoints[i + 1];
        
        // Calculate distance from point to road segment
        const dist = distanceToRoadSegment(x, y, current, next);
        if (dist <= roadWidth / 2) {
            return true;
        }
    }
    
    // Check intersections and their extended roads
    for (const element of roadElements) {
        if (element.type === roadElementTypes.INTERSECTION) {
            // Main intersection area
            const dx = x - element.x;
            const dy = y - element.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance <= 80) { // Larger area for intersections
                return true;
            }
            
            // Check extended horizontal roads (left-right) - LONGER DETECTION
            if (Math.abs(dy) <= 45 && Math.abs(dx) <= 500) { // Extended to 500px
                return true;
            }
            
            // Check extended vertical roads (up-down) - LONGER DETECTION  
            if (Math.abs(dx) <= 45 && Math.abs(dy) <= 400) { // Extended to 400px
                return true;
            }
        }
    }
    
    return false;
}

function distanceToRoadSegment(x, y, p1, p2) {
    const dx = p2.x - p1.x;
    const dy = p2.y - p1.y;
    const length = Math.sqrt(dx * dx + dy * dy);
    
    if (length === 0) {
        const dx2 = x - p1.x;
        const dy2 = y - p1.y;
        return Math.sqrt(dx2 * dx2 + dy2 * dy2);
    }
    
    // Calculate the projection of point onto the line segment
    const t = Math.max(0, Math.min(1, ((x - p1.x) * dx + (y - p1.y) * dy) / (length * length)));
    const projX = p1.x + t * dx;
    const projY = p1.y + t * dy;
    
    const dx3 = x - projX;
    const dy3 = y - projY;
    return Math.sqrt(dx3 * dx3 + dy3 * dy3);
}

function getClosestRoadPosition(x, y) {
    let closestPoint = null;
    let minDistance = Infinity;
    
    // Find closest point on main road
    for (let i = 0; i < roadPoints.length - 1; i++) {
        const current = roadPoints[i];
        const next = roadPoints[i + 1];
        
        // Find closest point on this road segment
        const dx = next.x - current.x;
        const dy = next.y - current.y;
        const length = Math.sqrt(dx * dx + dy * dy);
        
        if (length > 0) {
            const t = Math.max(0, Math.min(1, ((x - current.x) * dx + (y - current.y) * dy) / (length * length)));
            const projX = current.x + t * dx;
            const projY = current.y + t * dy;
            
            const distance = Math.sqrt((x - projX) * (x - projX) + (y - projY) * (y - projY));
            
            if (distance < minDistance) {
                minDistance = distance;
                closestPoint = { x: projX, y: projY };
            }
        }
    }
    
    return closestPoint;
}

// Car physics and movement
function updateCar(deltaTime) {
    if (!gameRunning) return;
    
    // If car is stopped for scenario, prevent all movement
    if (isCarStopped) {
        car.speed = 0;
        car.angularVelocity = 0;
        return; // Exit early, no movement allowed
    }
    
    // Handle input (keyboard + buttons)
    const accelerating = keys['ArrowUp'] || keys['KeyW'] || buttonStates.accelerate;
    const braking = keys['ArrowDown'] || keys['KeyS'] || buttonStates.brake;
    const turningLeft = keys['ArrowLeft'] || keys['KeyA'] || buttonStates.turnLeft;
    const turningRight = keys['ArrowRight'] || keys['KeyD'] || buttonStates.turnRight;
    
    // Acceleration and braking
    if (accelerating) {
        car.speed = Math.min(car.speed + car.acceleration, car.maxSpeed);
    } else if (braking) {
        car.speed = Math.max(car.speed - car.deceleration, -2);
    } else {
        // Natural deceleration (friction)
        car.speed *= 0.98;
    }
    
    // STRICT SPEED LIMIT ENFORCEMENT - Absolutely enforce 60km/h (6 units) maximum
    car.speed = Math.min(car.speed, 1); // Cannot exceed 60km/h under any circumstances
    car.speed = Math.max(car.speed, -0.5); // Limit reverse speed too
    
    // Steering (only when moving)
    if (Math.abs(car.speed) > 0.5) {
        const steeringSensitivity = 0.03 * Math.min(Math.abs(car.speed), 4);
        
        if (turningLeft) {
            car.angularVelocity = Math.max(car.angularVelocity - 0.02, -steeringSensitivity);
        } else if (turningRight) {
            car.angularVelocity = Math.min(car.angularVelocity + 0.02, steeringSensitivity);
        } else {
            car.angularVelocity *= 0.9; // Damping
        }
        
        car.angle += car.angularVelocity;
    } else {
        car.angularVelocity *= 0.8;
    }
    
    // Move car based on angle and speed
    const velocityX = Math.sin(car.angle) * car.speed;
    const velocityY = -Math.cos(car.angle) * car.speed;
    
    // Calculate new position
    const newX = car.x + velocityX;
    const newY = car.y + velocityY;
    
    // Check if new position is within road boundaries
    if (isPositionOnRoad(newX, newY)) {
        car.x = newX;
        car.y = newY;
    } else {
        // Stop the car and apply slight bounce back
        car.speed *= 0.3;
        car.angularVelocity *= 0.3;
        
        // Find closest valid road position
        const validPos = getClosestRoadPosition(car.x, car.y);
        if (validPos) {
            car.x = validPos.x;
            car.y = validPos.y;
        }
    }
    
    // Keep car within world bounds as fallback
    car.x = Math.max(50, Math.min(car.x, worldWidth - 50));
    car.y = Math.max(50, Math.min(car.y, worldHeight - 50));
    
    // Update camera to follow car
    updateCamera();
    
    // Update speed display
    updateSpeedDisplay();
    
    // Check for scenario triggers
    checkContextualScenarios();
}

function updateCamera() {
    // Smooth camera following
    camera.targetX = car.x - canvas.width / 2;
    camera.targetY = car.y - canvas.height / 2;
    
    camera.x += (camera.targetX - camera.x) * camera.smoothing;
    camera.y += (camera.targetY - camera.y) * camera.smoothing;
}

function updateSpeedDisplay() {
    const speedElement = document.getElementById('speedDisplay');
    if (speedElement) {
        const kmh = Math.round(Math.abs(car.speed) * 10); // Convert to real km/h (1 unit = 10 km/h)
        speedElement.textContent = Math.min(kmh, 60); // Cap display at 60km/h
    }
}

// Drawing functions
function draw() {
    // Clear canvas with sky color
    ctx.fillStyle = '#87CEEB';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Save context for camera transform
    ctx.save();
    ctx.translate(-camera.x, -camera.y);
    
    // Draw world elements
    drawGround();
    drawRoadSystem();
    drawRoadElements();
    drawCar();
    
    ctx.restore();
    
    // Draw UI elements (not affected by camera)
    drawMiniMap();
}

function drawGround() {
    // Draw grass background
    ctx.fillStyle = '#90EE90';
    ctx.fillRect(0, 0, worldWidth, worldHeight);
    
    // Add some texture with random grass patches
    ctx.fillStyle = '#8FBC8F';
    for (let i = 0; i < 50; i++) {
        const x = (i * 137) % worldWidth;
        const y = (i * 211) % worldHeight;
        ctx.fillRect(x, y, 20, 20);
    }
}

function drawRoadSystem() {
    if (roadPoints.length < 2) return;
    
    // Draw road surface - SOLID ONLY (no dashed lines)
    ctx.strokeStyle = '#404040';
    ctx.lineWidth = 100;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    ctx.beginPath();
    ctx.moveTo(roadPoints[0].x, roadPoints[0].y);
    
    for (let i = 1; i < roadPoints.length; i++) {
        ctx.lineTo(roadPoints[i].x, roadPoints[i].y);
    }
    ctx.stroke();
    
    // NO LANE MARKINGS - completely solid roads as requested
    // NO ROAD EDGES - completely clean solid roads like in the image
}

function drawRoadElements() {
    roadElements.forEach(element => {
        switch (element.type) {
            case roadElementTypes.PEDESTRIAN_CROSSING:
                drawPedestrianCrossing(element);
                break;
            case roadElementTypes.TRAFFIC_LIGHT:
                drawTrafficLight(element);
                break;
            case roadElementTypes.INTERSECTION:
                drawIntersection(element);
                break;
            case roadElementTypes.SCHOOL_ZONE:
                drawSchoolZone(element);
                break;
            case roadElementTypes.STOP_SIGN:
                drawStopSign(element);
                break;
        }
    });
}

function drawPedestrianCrossing(element) {
    // No white stripes - clean roads only as requested
    
    // Pedestrian figure only (for identification)
    ctx.fillStyle = '#FF6B6B';
    ctx.fillRect(element.x + 35, element.y - 20, 8, 15); // Body
    ctx.beginPath();
    ctx.arc(element.x + 39, element.y - 28, 4, 0, Math.PI * 2); // Head
    ctx.fill();
}

function drawTrafficLight(element) {
    // Pole
    ctx.fillStyle = '#444444';
    ctx.fillRect(element.x - 2, element.y, 4, element.height);
    
    // Light box
    ctx.fillStyle = '#333333';
    ctx.fillRect(element.x - 8, element.y - 30, 16, 25);
    
    // Lights (simple version - always showing green)
    ctx.fillStyle = '#4CAF50';
    ctx.beginPath();
    ctx.arc(element.x, element.y - 15, 4, 0, Math.PI * 2);
    ctx.fill();
}

function drawIntersection(element) {
    // Draw intersection marking
    ctx.strokeStyle = '#FFFFFF';
    ctx.lineWidth = 2;
    ctx.strokeRect(
        element.x - element.width/2,
        element.y - element.height/2,
        element.width,
        element.height
    );
}

function drawSchoolZone(element) {
    // School zone sign
    ctx.fillStyle = '#FFD700';
    ctx.fillRect(element.x + 60, element.y - 40, 40, 30);
    ctx.strokeStyle = '#000000';
    ctx.strokeRect(element.x + 60, element.y - 40, 40, 30);
    
    // Text
    ctx.fillStyle = '#000000';
    ctx.font = '12px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('SCHOOL', element.x + 80, element.y - 20);
    
    // Speed limit
    ctx.fillText('25', element.x + 80, element.y - 8);
}

function drawStopSign(element) {
    // Octagonal stop sign
    ctx.fillStyle = '#FF0000';
    ctx.beginPath();
    const centerX = element.x + 40;
    const centerY = element.y - 25;
    const radius = 15;
    
    for (let i = 0; i < 8; i++) {
        const angle = (i * Math.PI * 2) / 8;
        const x = centerX + Math.cos(angle) * radius;
        const y = centerY + Math.sin(angle) * radius;
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    }
    ctx.closePath();
    ctx.fill();
    
    // STOP text
    ctx.fillStyle = '#FFFFFF';
    ctx.font = 'bold 10px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('STOP', centerX, centerY + 3);
    
    // Post
    ctx.fillStyle = '#888888';
    ctx.fillRect(centerX - 2, centerY + 15, 4, 25);
}

function drawCar() {
    ctx.save();
    ctx.translate(car.x, car.y);
    ctx.rotate(car.angle);
    
    // Car shadow
    ctx.fillStyle = 'rgba(0, 0, 0, 0.3)';
    ctx.fillRect(-car.width/2 + 2, -car.height/2 + 2, car.width, car.height);
    
    // Car body
    ctx.fillStyle = car.color;
    ctx.fillRect(-car.width/2, -car.height/2, car.width, car.height);
    
    // Car outline
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 1;
    ctx.strokeRect(-car.width/2, -car.height/2, car.width, car.height);
    
    // Windshields
    ctx.fillStyle = '#87CEEB';
    ctx.fillRect(-car.width/2 + 3, -car.height/2 + 3, car.width - 6, 8); // Front
    ctx.fillRect(-car.width/2 + 3, car.height/2 - 11, car.width - 6, 8); // Rear
    
    // Wheels
    ctx.fillStyle = '#000000';
    ctx.fillRect(-car.width/2 - 3, -car.height/2 + 8, 4, 6);  // Front left
    ctx.fillRect(car.width/2 - 1, -car.height/2 + 8, 4, 6);   // Front right
    ctx.fillRect(-car.width/2 - 3, car.height/2 - 14, 4, 6);  // Rear left
    ctx.fillRect(car.width/2 - 1, car.height/2 - 14, 4, 6);   // Rear right
    
    // Headlights
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(-car.width/2 + 5, -car.height/2 - 2, 6, 2);
    ctx.fillRect(car.width/2 - 11, -car.height/2 - 2, 6, 2);
    
    ctx.restore();
}

function drawMiniMap() {
    if (!miniMapCtx) return;
    
    // Clear mini map
    miniMapCtx.fillStyle = '#2c3e50';
    miniMapCtx.fillRect(0, 0, 150, 150);
    
    // Draw road on mini map
    const scale = 0.03;
    miniMapCtx.strokeStyle = '#7f8c8d';
    miniMapCtx.lineWidth = 2;
    miniMapCtx.beginPath();
    
    roadPoints.forEach((point, index) => {
        const x = point.x * scale;
        const y = point.y * scale;
        if (index === 0) miniMapCtx.moveTo(x, y);
        else miniMapCtx.lineTo(x, y);
    });
    miniMapCtx.stroke();
    
    // Draw car on mini map
    miniMapCtx.fillStyle = '#e74c3c';
    miniMapCtx.beginPath();
    miniMapCtx.arc(car.x * scale, car.y * scale, 3, 0, Math.PI * 2);
    miniMapCtx.fill();
}

// Contextual scenario system
function checkContextualScenarios() {
    if (currentScenario || !gameRunning) return;
    
    // Check if car is near any road elements
    roadElements.forEach(element => {
        const distance = Math.sqrt(
            Math.pow(car.x - element.x, 2) + Math.pow(car.y - element.y, 2)
        );
        
        if (distance < 100) { // Trigger zone
            const scenario = contextualScenarios.find(s => s.triggerElement === element.type);
            if (scenario && !element.triggered) {
                element.triggered = true;
                presentContextualScenario(scenario);
            }
        }
    });
}

function presentContextualScenario(scenario) {
    currentScenario = scenario;
    updateScenarioPanel(scenario);
    showStatus(`New scenario: ${scenario.title}`, 3000);
}

function updateScenarioPanel(scenario) {
    // Update scenario number
    const scenarioNumberElement = document.getElementById('scenarioNumber');
    if (scenarioNumberElement) {
        scenarioNumberElement.textContent = scenarioIndex + 1;
    }
    
    // Update scenario description and question
    const descriptionElement = document.getElementById('scenarioDescription');
    if (descriptionElement) {
        descriptionElement.innerHTML = `<p><strong>${scenario.title}</strong></p>`;
    }
    
    const questionElement = document.getElementById('scenarioQuestion');
    if (questionElement) {
        questionElement.innerHTML = `<p>${scenario.question}</p>`;
    }
    
    const optionsContainer = document.getElementById('scenarioOptions');
    if (optionsContainer) {
        optionsContainer.innerHTML = '';
        
        scenario.options.forEach((option, index) => {
            const button = document.createElement('button');
            button.className = 'option-btn';
            button.textContent = option;
            button.onclick = () => selectOption(index);
            optionsContainer.appendChild(button);
        });
        
        // Add "Go to Results" button if this is the 5th scenario
        if (scenarioIndex === 4) {
            const resultsButton = document.createElement('button');
            resultsButton.id = 'goToResultsBtn';
            resultsButton.textContent = 'Go to Results';
            resultsButton.className = 'results-btn';
            resultsButton.onclick = goToResults;
            resultsButton.style.display = 'none'; // Initially hidden, shown after answering
            optionsContainer.appendChild(resultsButton);
        }
    }
    
    const scenarioPanel = document.getElementById('scenarioPanel');
    if (scenarioPanel) {
        scenarioPanel.style.display = 'block';
    }
}

function selectOption(optionIndex) {
    if (!currentScenario) return;
    
    const isCorrect = optionIndex === currentScenario.correctAnswer;
    gameStats.total++;
    
    if (isCorrect) {
        gameStats.correct++;
        showStatus("✅ Correct! " + currentScenario.explanation, 4000);
    } else {
        gameStats.wrong++;
        showStatus("❌ Wrong. " + currentScenario.explanation, 4000);
    }
    
    // Save scenario result
    gameStats.scenarios.push({
        scenario: currentScenario.title,
        userAnswer: optionIndex,
        correctAnswer: currentScenario.correctAnswer,
        isCorrect: isCorrect,
        timestamp: Date.now()
    });
    
    updateStatsDisplay();
    
    // If this is the 5th scenario, show the "Go to Results" button
    if (scenarioIndex === 4) {
        const goToResultsBtn = document.getElementById('goToResultsBtn');
        if (goToResultsBtn) {
            goToResultsBtn.style.display = 'block';
        }
        // Don't move to next scenario, let user click "Go to Results"
        return;
    }
    
    // Move to next scenario
    setTimeout(() => {
        currentScenario = null;
        isCarStopped = false; // RESUME CAR MOVEMENT after scenario
        scenarioIndex++;
        
        const scenarioPanel = document.getElementById('scenarioPanel');
        if (scenarioPanel) {
            scenarioPanel.style.display = 'none';
        }
        
        showStatus("You may continue driving. Be safe!", 2000);
        
        if (scenarioIndex >= 5) { // End after EXACTLY 5 scenarios
            endSimulation();
        }
    }, 3000);
}

// Game state management
function startSimulation() {
    gameRunning = true;
    gameStats.startTime = Date.now();
    scenarioIndex = 0;
    currentScenario = null;
    isCarStopped = false; // Make sure car can move at start
    
    // Reset car position to BOTTOM of screen
    car.x = worldWidth / 2; // Center horizontally
    car.y = worldHeight - 100; // Bottom of screen
    car.speed = 0;
    car.angle = -Math.PI / 2; // Point upward
    car.angularVelocity = 0;
    
    // Reset road elements
    roadElements.forEach(element => element.triggered = false);
    
    showStatus("🚗 Simulation started! Drive carefully and respond to road situations.", 3000);
}

function endSimulation() {
    gameRunning = false;
    
    const completionTime = Math.floor((Date.now() - gameStats.startTime) / 1000);
    const scorePercentage = gameStats.total > 0 ? Math.round((gameStats.correct / gameStats.total) * 100) : 0;
    const passed = scorePercentage >= 70;
    
    // Show results
    showResults(scorePercentage, passed);
    
    // Save to database
    saveSimulationResult(completionTime, scorePercentage);
}

function showResults(scorePercentage, passed) {
    const resultsScreen = document.getElementById('resultsScreen');
    const resultsTitle = document.getElementById('resultsTitle');
    const resultsScore = document.getElementById('resultsScore');
    const totalScenarios = document.getElementById('totalScenarios');
    const wrongAnswers = document.getElementById('wrongAnswers');
    
    if (resultsTitle) {
        resultsTitle.textContent = passed ? 'PASSED!' : 'FAILED';
        resultsTitle.style.color = passed ? '#4CAF50' : '#F44336';
    }
    
    if (resultsScore) {
        resultsScore.textContent = scorePercentage + '%';
    }
    
    if (totalScenarios) {
        totalScenarios.textContent = gameStats.total;
    }
    
    if (wrongAnswers) {
        wrongAnswers.textContent = gameStats.wrong;
    }
    
    if (resultsScreen) {
        resultsScreen.style.display = 'flex';
    }
    
    showStatus(passed ? "🎉 Congratulations! You passed!" : "📚 Keep practicing!", 5000);
}

function updateStatsDisplay() {
    const totalScenariosElement = document.getElementById('totalScenarios');
    const wrongAnswersElement = document.getElementById('wrongAnswers');
    
    if (totalScenariosElement) {
        totalScenariosElement.textContent = gameStats.total;
    }
    
    if (wrongAnswersElement) {
        wrongAnswersElement.textContent = gameStats.wrong;
    }
    
    const accuracy = gameStats.total > 0 ? Math.round((gameStats.correct / gameStats.total) * 100) : 0;
    const resultsScoreElement = document.getElementById('resultsScore');
    if (resultsScoreElement) {
        resultsScoreElement.textContent = accuracy + '%';
    }
}

// Animation loop with delta time
function animate(currentTime) {
    const deltaTime = currentTime - lastTime;
    lastTime = currentTime;
    
    updateCar(deltaTime);
    draw();
    
    animationId = requestAnimationFrame(animate);
}

// Utility functions
function showStatus(message, duration = 2000) {
    const statusElement = document.getElementById('statusMessage');
    if (statusElement) {
        statusElement.textContent = message;
        statusElement.style.display = 'block';
        
        setTimeout(() => {
            statusElement.style.display = 'none';
        }, duration);
    } else {
        console.log('Status:', message);
    }
}

function tryAgain() {
    const resultsScreen = document.getElementById('resultsScreen');
    if (resultsScreen) {
        resultsScreen.style.display = 'none';
    }
    
    // Reset stats
    gameStats = { correct: 0, wrong: 0, total: 0, startTime: null, scenarios: [] };
    
    // Restart simulation
    setTimeout(() => {
        startSimulation();
    }, 1000);
}

// Database integration (Updated)
// Database integration (Updated - second instance)
function saveSimulationResult(completionTime, scorePercentage) {
    const data = new FormData();
    data.append('action', 'save_simulation_result');
    data.append('simulation_type', 'driving_simulation_2d');
    data.append('total_scenarios', gameStats.total || 0);
    data.append('correct_answers', gameStats.correct || 0);
    data.append('wrong_answers', gameStats.wrong || 0);
    data.append('completion_time', completionTime);
    data.append('scenarios_data', JSON.stringify(gameStats.scenarios || []));
    
    fetch('simulation.php', { // Use simulation.php instead of save_simulation.php
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('✅ Simulation result saved successfully to database');
            showStatus(`Results saved! Score: ${scorePercentage}% (${scorePercentage >= 70 ? 'PASSED' : 'FAILED'})`, 4000);
        } else {
            console.error('❌ Failed to save simulation result:', result.message);
            showStatus('⚠️ Could not save results to database', 3000);
        }
    })
    .catch(error => {
        console.error('❌ Network error saving simulation result:', error);
        showStatus('⚠️ Network error - results not saved', 3000);
    });
}