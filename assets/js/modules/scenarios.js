// Scenarios Module
// Handles all driving scenarios, selection, and management

// Road element types for scenario triggers
const roadElementTypes = {
    STOP_SIGN: 'STOP_SIGN',
    TRAFFIC_LIGHT: 'TRAFFIC_LIGHT',
    PEDESTRIAN_CROSSING: 'PEDESTRIAN_CROSSING',
    SCHOOL_ZONE: 'SCHOOL_ZONE',
    INTERSECTION: 'INTERSECTION'
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

// Scenario Management System
const ScenarioManager = {
    selectedScenarios: [],
    currentScenario: null,
    scenarioIndex: 0,
    maxScenarios: 5,

    // Initialize scenario system
    init() {
        this.selectRandomScenarios();
        this.scenarioIndex = 0;
        this.currentScenario = null;
    },

    // Select 5 random scenarios from the available pool
    selectRandomScenarios() {
        console.log('🎯 Selecting 5 random scenarios from', allDrivingScenarios.length, 'available scenarios');
        
        const availableScenarios = [...allDrivingScenarios];
        this.selectedScenarios = [];
        
        // Select exactly 5 random scenarios
        for (let i = 0; i < this.maxScenarios && availableScenarios.length > 0; i++) {
            const randomIndex = Math.floor(Math.random() * availableScenarios.length);
            this.selectedScenarios.push(availableScenarios[randomIndex]);
            availableScenarios.splice(randomIndex, 1); // Remove to avoid duplicates
        }
        
        console.log('✅ Selected scenarios:', this.selectedScenarios.map(s => s.title));
    },

    // Get current scenario
    getCurrentScenario() {
        if (this.scenarioIndex < this.selectedScenarios.length) {
            return this.selectedScenarios[this.scenarioIndex];
        }
        return null;
    },

    // Get next scenario
    getNextScenario() {
        if (this.scenarioIndex < this.selectedScenarios.length - 1) {
            this.scenarioIndex++;
            this.currentScenario = this.getCurrentScenario();
            return this.currentScenario;
        }
        return null;
    },

    // Check if all scenarios are completed
    isCompleted() {
        return this.scenarioIndex >= this.maxScenarios;
    },

    // Get current scenario number (1-based)
    getCurrentScenarioNumber() {
        return this.scenarioIndex + 1;
    },

    // Get total number of scenarios
    getTotalScenarios() {
        return this.maxScenarios;
    },

    // Get scenario progress as string
    getProgressString() {
        return `${this.getCurrentScenarioNumber()}/${this.getTotalScenarios()}`;
    },

    // Reset scenario system
    reset() {
        this.scenarioIndex = 0;
        this.currentScenario = null;
        this.selectRandomScenarios();
    },

    // Get all selected scenarios (for debugging)
    getSelectedScenarios() {
        return [...this.selectedScenarios];
    }
};

// Export to global window object for browser use
window.ScenariosModule = ScenarioManager;
window.roadElementTypes = roadElementTypes;
window.allDrivingScenarios = allDrivingScenarios;

// Export for use in other modules (Node.js compatibility)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ScenarioManager, roadElementTypes, allDrivingScenarios };
}