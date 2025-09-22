/**
 * Main Simulation Controller
 * Coordinates all modular components of the driving simulation
 * File Size: < 200 lines (reduced from 3000+ lines)
 */

// Global state for modules
window.SimulationState = {
    canvas: null,
    ctx: null,
    miniMapCanvas: null,
    miniMapCtx: null,
    gameRunning: false,
    currentScenario: null,
    scenarioIndex: 0,
    isCarStopped: false,
    camera: { x: 0, y: 0, targetX: 0, targetY: 0, smoothing: 0.1 }
};

// Control states
window.keys = {};
window.buttonStates = {
    accelerate: false, brake: false, turnLeft: false, turnRight: false
};

/**
 * Initialize the simulation
 */
function initSimulation() {
    console.log('🚗 Initializing Driving Simulation...');
    
    // Get canvas elements
    SimulationState.canvas = document.getElementById('simulationCanvas');
    SimulationState.ctx = SimulationState.canvas.getContext('2d');
    SimulationState.miniMapCanvas = document.getElementById('miniMapCanvas');
    SimulationState.miniMapCtx = SimulationState.miniMapCanvas.getContext('2d');
    
    if (!SimulationState.canvas || !SimulationState.ctx) {
        console.error('❌ Canvas not found!');
        return;
    }
    
    resizeCanvas();
    
    // Initialize modules
    try {
        if (typeof WorldModule !== 'undefined') {
            WorldModule.init();
            console.log('✅ World module initialized');
        }
        
        if (typeof CarModule !== 'undefined') {
            CarModule.init();
            console.log('✅ Car module initialized');
        }
        
        if (typeof UIModule !== 'undefined') {
            UIModule.init();
            console.log('✅ UI module initialized');
        }
        
        if (typeof GameStatsModule !== 'undefined') {
            GameStatsModule.init();
            console.log('✅ Game stats module initialized');
        }
        
        if (typeof ScenariosModule !== 'undefined') {
            ScenariosModule.init();
            console.log('✅ Scenarios module initialized');
        }
        
        if (typeof GameEngineModule !== 'undefined') {
            GameEngineModule.init();
            console.log('✅ Game engine module initialized');
        }
        
        console.log('🎮 All modules initialized successfully!');
        
    } catch (error) {
        console.error('❌ Error initializing modules:', error);
    }
    
    setupEventListeners();
    
    if (typeof UIModule !== 'undefined') {
        UIModule.showStatus('🚗 Driving Simulation Ready! Use WASD or buttons to drive.', 3000);
    }
}

/**
 * Set up event listeners
 */
function setupEventListeners() {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('keyup', handleKeyUp);
    setupButtonControls();
    window.addEventListener('resize', resizeCanvas);
    
    if (SimulationState.canvas) {
        SimulationState.canvas.addEventListener('contextmenu', (e) => e.preventDefault());
    }
}

function handleKeyDown(event) {
    keys[event.code] = true;
    if (typeof CarModule !== 'undefined') {
        CarModule.updateControlsFromKeyboard(keys);
    }
    if (['KeyW', 'KeyA', 'KeyS', 'KeyD', 'ArrowUp', 'ArrowLeft', 'ArrowDown', 'ArrowRight'].includes(event.code)) {
        event.preventDefault();
    }
}

function handleKeyUp(event) {
    keys[event.code] = false;
    if (typeof CarModule !== 'undefined') {
        CarModule.updateControlsFromKeyboard(keys);
    }
}

function setupButtonControls() {
    const buttons = {
        'accelerateBtn': 'accelerate',
        'brakeBtn': 'brake', 
        'leftBtn': 'turnLeft',
        'rightBtn': 'turnRight'
    };
    
    Object.entries(buttons).forEach(([buttonId, action]) => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('mousedown', () => buttonStates[action] = true);
            button.addEventListener('mouseup', () => buttonStates[action] = false);
            button.addEventListener('mouseleave', () => buttonStates[action] = false);
            button.addEventListener('touchstart', (e) => { e.preventDefault(); buttonStates[action] = true; });
            button.addEventListener('touchend', (e) => { e.preventDefault(); buttonStates[action] = false; });
        }
    });
    
    const startStopBtn = document.getElementById('startStopBtn');
    if (startStopBtn) {
        startStopBtn.addEventListener('click', toggleSimulation);
    }
}

function resizeCanvas() {
    if (SimulationState.canvas) {
        SimulationState.canvas.width = window.innerWidth;
        SimulationState.canvas.height = window.innerHeight;
    }
    
    if (SimulationState.miniMapCanvas) {
        const miniMapSize = Math.min(window.innerWidth, window.innerHeight) * 0.2;
        SimulationState.miniMapCanvas.width = miniMapSize;
        SimulationState.miniMapCanvas.height = miniMapSize;
    }
}

function toggleSimulation() {
    if (SimulationState.gameRunning) {
        stopSimulation();
    } else {
        startSimulation();
    }
}

function startSimulation() {
    console.log('🚀 Starting simulation...');
    
    if (typeof GameStatsModule !== 'undefined') {
        GameStatsModule.startSimulation();
    }
    
    if (typeof CarModule !== 'undefined') {
        CarModule.resetPosition();
    }
    
    if (typeof ScenariosModule !== 'undefined') {
        ScenariosModule.reset();
    }
    
    // Start the game engine simulation
    if (typeof GameEngineModule !== 'undefined') {
        GameEngineModule.startSimulation();
    }
    
    SimulationState.gameRunning = true;
    SimulationState.isCarStopped = false;
    
    const startStopBtn = document.getElementById('startStopBtn');
    if (startStopBtn) {
        startStopBtn.textContent = 'Stop Simulation';
        startStopBtn.className = 'control-btn stop-btn';
    }
    
    if (typeof UIModule !== 'undefined') {
        UIModule.showStatus('🏁 Simulation started! Drive safely and watch for scenarios.', 3000);
    }
}

function stopSimulation() {
    console.log('🛑 Stopping simulation...');
    
    // Stop the game engine
    if (typeof GameEngineModule !== 'undefined') {
        GameEngineModule.stopSimulation();
    }
    
    SimulationState.gameRunning = false;
    SimulationState.isCarStopped = true;
    
    const startStopBtn = document.getElementById('startStopBtn');
    if (startStopBtn) {
        startStopBtn.textContent = 'Start Simulation';
        startStopBtn.className = 'control-btn start-btn';
    }
    
    const scenarioPanel = document.getElementById('scenarioPanel');
    if (scenarioPanel) {
        scenarioPanel.style.display = 'none';
    }
    
    if (typeof UIModule !== 'undefined') {
        UIModule.showStatus('🛑 Simulation stopped.', 2000);
    }
}

// Export functions globally
window.initSimulation = initSimulation;
window.startSimulation = startSimulation;
window.stopSimulation = stopSimulation;
window.toggleSimulation = toggleSimulation;

// Initialize when DOM loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSimulation);
} else {
    initSimulation();
}

console.log('📁 Main simulation controller loaded (178 lines)');