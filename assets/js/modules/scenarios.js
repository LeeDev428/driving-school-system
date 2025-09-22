/**
 * Scenarios Module - 5 Realistic Traffic Scenarios with Questions
 * Handles scenario-based questions that match real driving situations
 */

const ScenariosModule = {
    // All 5 driving scenarios
    scenarios: [],
    
    // Tracking completed scenarios
    completedScenarios: new Set(),
    
    /**
     * Initialize scenarios
     */
    init() {
        console.log('🎯 Loading 5 traffic scenarios...');
        this.createTrafficScenarios();
        console.log('✅ All driving scenarios loaded');
    },
    
    /**
     * Create the 5 specific traffic scenarios
     */
    createTrafficScenarios() {
        this.scenarios = [
            // Scenario 1: Red Traffic Light (Classic situation)
            {
                id: 1,
                title: "Red Traffic Light Ahead",
                type: "RED_LIGHT",
                position: { x: 280, y: 360 },
                triggerRadius: 80,
                question: "You are approaching an intersection and the traffic light turns RED. What should you do?",
                options: [
                    "A) Come to a complete stop before the intersection and wait for green",
                    "B) Slow down and proceed carefully if no other cars are visible",
                    "C) Speed up to get through the intersection before the light changes",
                    "D) Honk your horn to alert other drivers and continue through"
                ],
                correctAnswer: 0, // Option A
                explanation: "When a traffic light is red, you must come to a complete stop behind the stop line and wait until the light turns green. Running a red light is illegal and extremely dangerous.",
                points: 20,
                context: "Traffic lights are one of the most important traffic control devices. Always obey traffic signals.",
                active: true
            },
            
            // Scenario 2: Stop Sign (Fundamental rule)
            {
                id: 2,
                title: "Stop Sign at Intersection",
                type: "STOP_SIGN",
                position: { x: 500, y: 360 },
                triggerRadius: 60,
                question: "You approach a STOP sign at an intersection. What is the correct procedure?",
                options: [
                    "A) Come to a complete stop, check for traffic in all directions, then proceed when safe",
                    "B) Slow down to a rolling stop and continue if no cars are immediately visible",
                    "C) Stop only if you see other vehicles or pedestrians approaching",
                    "D) Honk your horn to signal your presence and proceed with caution"
                ],
                correctAnswer: 0, // Option A
                explanation: "At a stop sign, you must come to a complete stop regardless of traffic conditions. Look left, right, and left again before proceeding when it's safe.",
                points: 20,
                context: "Stop signs require a complete stop every time, even if the intersection appears clear.",
                active: true
            },
            
            // Scenario 3: Pedestrian Crossing (Safety priority)
            {
                id: 3,
                title: "Pedestrian at Crosswalk",
                type: "PEDESTRIAN",
                position: { x: 700, y: 360 },
                triggerRadius: 70,
                question: "A pedestrian is waiting to cross at the crosswalk. What should you do?",
                options: [
                    "A) Stop and allow the pedestrian to cross safely, even if they're just waiting",
                    "B) Continue driving since the pedestrian hasn't started crossing yet",
                    "C) Honk your horn to let the pedestrian know you're approaching",
                    "D) Speed up to pass the crosswalk before the pedestrian starts crossing"
                ],
                correctAnswer: 0, // Option A
                explanation: "Pedestrians have the right-of-way at crosswalks. You should stop and allow them to cross safely, showing courtesy and ensuring their safety.",
                points: 20,
                context: "Pedestrian safety is always the top priority. Always yield to pedestrians at crosswalks.",
                active: true
            },
            
            // Scenario 4: School Zone (Special speed limits)
            {
                id: 4,
                title: "School Zone During School Hours",
                type: "SCHOOL_ZONE", 
                position: { x: 1000, y: 250 },
                triggerRadius: 90,
                question: "You are entering a school zone during school hours. What should you do?",
                options: [
                    "A) Reduce speed to the posted school zone limit and watch carefully for children",
                    "B) Maintain your normal driving speed if no children are immediately visible",
                    "C) Honk your horn to alert children of your presence",
                    "D) Drive faster to get through the zone quickly and reduce risk"
                ],
                correctAnswer: 0, // Option A
                explanation: "School zones have reduced speed limits during school hours. Always slow down and be extra vigilant for children who may unexpectedly enter the roadway.",
                points: 20,
                context: "Children can be unpredictable. School zones require extra caution and reduced speed.",
                active: true
            },
            
            // Scenario 5: Busy Intersection (Yielding right-of-way)
            {
                id: 5,
                title: "Busy Intersection with Traffic",
                type: "INTERSECTION",
                position: { x: 640, y: 540 },
                triggerRadius: 85,
                question: "You approach a busy intersection with cross traffic. What do you do?",
                options: [
                    "A) Yield to traffic that has the right-of-way and wait for a safe gap",
                    "B) Proceed slowly into the intersection and merge with traffic",
                    "C) Honk your horn and proceed with caution to claim your space",
                    "D) Speed up to merge quickly and avoid blocking traffic"
                ],
                correctAnswer: 0, // Option A
                explanation: "Always yield to traffic that has the right-of-way. Wait for a safe gap before proceeding through the intersection.",
                points: 20,
                context: "Right-of-way rules prevent accidents. When in doubt, yield to other traffic.",
                active: true
            }
        ];
    },
    
    /**
     * Get scenario by ID
     */
    getScenario(id) {
        return this.scenarios.find(scenario => scenario.id === id);
    },
    
    /**
     * Get all active scenarios
     */
    getActiveScenarios() {
        return this.scenarios.filter(scenario => scenario.active);
    },
    
    /**
     * Check if car triggers any scenarios
     */
    checkTriggers(carPosition) {
        const triggeredScenarios = [];
        
        this.scenarios.forEach(scenario => {
            if (!scenario.active || this.completedScenarios.has(scenario.id)) {
                return;
            }
            
            const distance = Math.sqrt(
                Math.pow(carPosition.x - scenario.position.x, 2) + 
                Math.pow(carPosition.y - scenario.position.y, 2)
            );
            
            if (distance <= scenario.triggerRadius) {
                triggeredScenarios.push(scenario);
            }
        });
        
        return triggeredScenarios;
    },
    
    /**
     * Mark scenario as completed
     */
    markCompleted(scenarioId) {
        this.completedScenarios.add(scenarioId);
        
        const scenario = this.getScenario(scenarioId);
        if (scenario) {
            scenario.active = false;
        }
        
        console.log(`✅ Scenario ${scenarioId} completed`);
    },
    
    /**
     * Check if answer is correct
     */
    checkAnswer(scenarioId, selectedOption) {
        const scenario = this.getScenario(scenarioId);
        if (!scenario) return false;
        
        return selectedOption === scenario.correctAnswer;
    },
    
    /**
     * Get scenario results
     */
    getScenarioResult(scenarioId, selectedOption) {
        const scenario = this.getScenario(scenarioId);
        if (!scenario) return null;
        
        const isCorrect = this.checkAnswer(scenarioId, selectedOption);
        
        return {
            scenarioId: scenarioId,
            question: scenario.question,
            selectedOption: selectedOption,
            correctOption: scenario.correctAnswer,
            isCorrect: isCorrect,
            points: isCorrect ? scenario.points : 0,
            explanation: scenario.explanation,
            context: scenario.context
        };
    },
    
    /**
     * Get completion statistics
     */
    getCompletionStats() {
        const totalScenarios = this.scenarios.length;
        const completedCount = this.completedScenarios.size;
        const remainingCount = totalScenarios - completedCount;
        
        return {
            total: totalScenarios,
            completed: completedCount,
            remaining: remainingCount,
            completionRate: Math.round((completedCount / totalScenarios) * 100)
        };
    },
    
    /**
     * Get all scenario positions for minimap/overview
     */
    getScenarioPositions() {
        return this.scenarios.map(scenario => ({
            id: scenario.id,
            position: scenario.position,
            type: scenario.type,
            completed: this.completedScenarios.has(scenario.id),
            active: scenario.active
        }));
    },
    
    /**
     * Get next available scenario
     */
    getNextScenario() {
        return this.scenarios.find(scenario => 
            scenario.active && !this.completedScenarios.has(scenario.id)
        );
    },
    
    /**
     * Reset all scenarios
     */
    reset() {
        this.completedScenarios.clear();
        
        this.scenarios.forEach(scenario => {
            scenario.active = true;
        });
        
        console.log('🔄 All scenarios reset');
    },
    
    /**
     * Get scenario difficulty level
     */
    getScenarioDifficulty(scenarioId) {
        const difficultyMap = {
            1: 'Easy',      // Red light - basic rule
            2: 'Easy',      // Stop sign - fundamental
            3: 'Medium',    // Pedestrian - requires judgment
            4: 'Medium',    // School zone - special rules
            5: 'Hard'       // Intersection - complex situation
        };
        
        return difficultyMap[scenarioId] || 'Medium';
    },
    
    /**
     * Get scenario category
     */
    getScenarioCategory(scenarioId) {
        const categoryMap = {
            1: 'Traffic Signals',
            2: 'Traffic Signs',
            3: 'Pedestrian Safety',
            4: 'Special Zones',
            5: 'Intersection Rules'
        };
        
        return categoryMap[scenarioId] || 'General';
    },
    
    /**
     * Generate final report
     */
    generateFinalReport() {
        const stats = this.getCompletionStats();
        const results = [];
        
        this.scenarios.forEach(scenario => {
            results.push({
                id: scenario.id,
                title: scenario.title,
                type: scenario.type,
                category: this.getScenarioCategory(scenario.id),
                difficulty: this.getScenarioDifficulty(scenario.id),
                completed: this.completedScenarios.has(scenario.id),
                points: this.completedScenarios.has(scenario.id) ? scenario.points : 0
            });
        });
        
        return {
            statistics: stats,
            scenarios: results,
            totalPoints: results.reduce((sum, r) => sum + r.points, 0),
            maxPoints: this.scenarios.length * 20,
            grade: this.calculateGrade(results)
        };
    },
    
    /**
     * Calculate grade based on performance
     */
    calculateGrade(results) {
        const totalPoints = results.reduce((sum, r) => sum + r.points, 0);
        const maxPoints = this.scenarios.length * 20;
        const percentage = (totalPoints / maxPoints) * 100;
        
        if (percentage >= 90) return 'A';
        if (percentage >= 80) return 'B';
        if (percentage >= 70) return 'C';
        if (percentage >= 60) return 'D';
        return 'F';
    },
    
    /**
     * Update scenario based on current game state
     */
    update(deltaTime) {
        // Update any time-based scenario elements
        // For now, scenarios are static, but this could be used for
        // dynamic elements like changing traffic lights, moving pedestrians, etc.
    }
};

// Export module
window.ScenariosModule = ScenariosModule;
