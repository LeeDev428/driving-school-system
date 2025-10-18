/**
 * Scenarios Module - LTO-Based Traffic Scenarios
 * 10 Most Important Questions, randomly selects 5 for simulation
 */

const ScenariosModule = {
    // All available scenarios (10 most important)
    allScenarios: [],
    
    // Selected 5 scenarios for current simulation
    scenarios: [],
    
    // Tracking completed scenarios
    completedScenarios: new Set(),
    
    /**
     * Initialize scenarios
     */
    init() {
        console.log('🎯 Loading LTO-based traffic scenarios...');
        this.createAllScenarios();
        this.selectRandomScenarios();
        console.log('✅ 5 scenarios selected from 10 most important questions');
    },
    
    /**
     * Create all 10 most important scenarios
     */
    createAllScenarios() {
        this.allScenarios = [
            // 1. TRAFFIC LIGHT - Most fundamental rule
            {
                id: 1,
                title: "Traffic Light Colors",
                type: "RED_LIGHT",
                question: "What do the colors of the traffic light mean?",
                options: [
                    "🔴 Red – Stop. Vehicles must come to a complete halt and cannot move forward.",
                    "🟡 Yellow – Speed up quickly to avoid getting stuck at the light.",
                    "🟢 Green – Stop and wait before moving."
                ],
                correctAnswer: 0,
                explanation: "RED means STOP completely. YELLOW means prepare to stop (not speed up). GREEN means go when safe. Traffic lights are the most basic traffic control system.",
                points: 20
            },
            
            // 2. STOP SIGN - Critical safety rule
            {
                id: 2,
                title: "STOP Sign at Intersection",
                type: "STOP_SIGN",
                question: "You are approaching an intersection with a STOP sign. The road looks clear, and no vehicles are coming from either side. What is the correct action to take?",
                options: [
                    "🛑 Make a complete full stop, then proceed when safe.",
                    "🚗 Slow down only (rolling stop) and continue since the road looks clear.",
                    "⏩ Ignore the STOP sign if there are no enforcers watching."
                ],
                correctAnswer: 0,
                explanation: "STOP signs require a COMPLETE STOP every time, regardless of traffic conditions. A rolling stop is illegal. Look left-right-left before proceeding.",
                points: 20
            },
            
            // 3. ZEBRA CROSSING - Pedestrian safety is #1 priority
            {
                id: 3,
                title: "Zebra Crossing (Pedestrian)",
                type: "PEDESTRIAN",
                question: "As you approach a school area, you notice a Zebra Crossing sign ahead and see the white stripes painted across the road. Some pedestrians are waiting to cross. What should you do when you see this sign?",
                options: [
                    "✅ Slow down and give way to pedestrians crossing at the zebra lines.",
                    "🏎️ Speed up to pass before the pedestrians step onto the road.",
                    "🚫 Ignore the sign and continue driving without stopping."
                ],
                correctAnswer: 0,
                explanation: "Zebra crossings give pedestrians the RIGHT OF WAY. You MUST slow down and yield to anyone waiting or crossing. Pedestrian safety is always the top priority.",
                points: 20
            },
            
            // 4. SCHOOL ZONE - Children's safety
            {
                id: 4,
                title: "School Zone Ahead",
                type: "SCHOOL_ZONE",
                question: "While driving through a neighborhood, you see a School Ahead sign posted on the roadside. This means you are entering a school zone where children may be crossing. What should you do when you see this sign?",
                options: [
                    "🏫 Slow down, stay alert, and follow the reduced speed limit to ensure children's safety.",
                    "🏎️ Maintain your normal speed since children are unlikely to cross.",
                    "🚫 Ignore the sign and drive without caution."
                ],
                correctAnswer: 0,
                explanation: "School zones require REDUCED SPEED and EXTRA VIGILANCE. Children are unpredictable and may run into the road. Always slow down and stay alert near schools.",
                points: 20
            },
            
            // 5. GREEN LIGHT WITH PEDESTRIAN - Right of way priority
            {
                id: 5,
                title: "Green Light with Pedestrian",
                type: "INTERSECTION",
                question: "You are approaching an intersection with a green light. A pedestrian suddenly begins crossing the street. The LTO rule states: 'A driver should never insist on the right of way if it will endanger others.' What should you do in this situation?",
                options: [
                    "🚦 Yield the right of way and stop for the pedestrian, even if you have the green light.",
                    "🏎️ Continue driving since the green light gives you full right of way.",
                    "📢 Honk repeatedly to force the pedestrian to hurry and clear the lane."
                ],
                correctAnswer: 0,
                explanation: "SAFETY FIRST! Even with a green light, you must yield to pedestrians. Never insist on right of way if it endangers anyone. Human life is more important than traffic rules.",
                points: 20
            },
            
            // 6. SEATBELT LAW - Back seat passengers (RA 8750)
            {
                id: 6,
                title: "Seatbelt Law - Back Seat",
                type: "SAFETY_RULE",
                question: "You are traveling on a national highway, seated at the back seat of a car. The driver tells you it's fine not to wear a seatbelt since you're not in the front. What should you do?",
                options: [
                    "🚗 Wear your seatbelt, because it is mandatory even for back seat passengers under Republic Act 8750.",
                    "😎 Ignore the seatbelt law if you're at the back since enforcers usually check only the driver.",
                    "🛋️ Sit comfortably without a seatbelt because accidents rarely affect back seat passengers."
                ],
                correctAnswer: 0,
                explanation: "Republic Act 8750 (Seatbelt Use Act) requires ALL passengers, including back seat passengers, to wear seatbelts on national highways. This law saves lives.",
                points: 20
            },
            
            // 7. MOBILE PHONE USE WHILE DRIVING - Anti-Distracted Driving Act
            {
                id: 7,
                title: "Mobile Phone While Driving",
                type: "DISTRACTED_DRIVING",
                question: "You are driving along a busy road when your phone rings. You consider answering it quickly using loudspeaker mode while still holding the wheel. What is the correct action to take?",
                options: [
                    "📵 Do not use your phone at all while driving, since both texting and answering calls are prohibited under LTO A.O. 2017-013.",
                    "☎️ Answer the call quickly on loudspeaker while steering with one hand to save time.",
                    "😅 Text or call only at red lights since the car isn't moving."
                ],
                correctAnswer: 0,
                explanation: "Anti-Distracted Driving Act (RA 10913) and LTO A.O. 2017-013 prohibit ALL mobile phone use while driving - even hands-free at red lights. Pull over safely if you need to use your phone.",
                points: 20
            },
            
            // 8. NO PARKING ZONE - Traffic rules
            {
                id: 8,
                title: "No Parking Zone",
                type: "PARKING_RULE",
                question: "You stop your car in a 'No Parking' zone to wait for a friend, thinking it's fine since you'll only be there for a minute. What is the correct action according to LTO rules?",
                options: [
                    "🅿️ Do not park at all in prohibited zones, since even brief stops count as illegal parking.",
                    "😎 It's fine to park for a short time as long as you stay inside the car.",
                    "🤷 You can park if your hazard lights are on to signal it's temporary."
                ],
                correctAnswer: 0,
                explanation: "No Parking means NO PARKING - not even for 'just a minute'. Brief stops still count as illegal parking and can result in fines or towing. Find a legal parking spot.",
                points: 20
            },
            
            // 9. ANTI-CORRUPTION - Refusing bribes
            {
                id: 9,
                title: "Traffic Enforcer Bribe",
                type: "CORRUPTION",
                question: "A traffic enforcer stops you for a violation and asks for payment on the spot without issuing an official receipt. What is the correct action to take?",
                options: [
                    "📄 Refuse to pay immediately and settle the fine only at the office or through official payment channels.",
                    "💸 Pay the enforcer on the spot even without a receipt to avoid hassle.",
                    "🤝 Offer a smaller amount as a quick settlement."
                ],
                correctAnswer: 0,
                explanation: "Paying 'on the spot' without receipt is BRIBERY and illegal. Always demand proper documentation and pay only through official channels. Report corrupt enforcers to authorities.",
                points: 20
            },
            
            // 10. PRE-TRIP INSPECTION - Vehicle safety check
            {
                id: 10,
                title: "Pre-Trip Vehicle Inspection",
                type: "SAFETY_CHECK",
                question: "Before starting a long trip, a driver is about to leave immediately without checking the vehicle. What should the driver do first to ensure safety?",
                options: [
                    "🔧 Check brakes, lights, tires, mirrors, seatbelts, fuel, and documents before driving.",
                    "🕒 Skip inspection to save time since the car seems fine.",
                    "🎵 Just play music and start driving without checking anything."
                ],
                correctAnswer: 0,
                explanation: "Pre-trip inspection is MANDATORY for safety. Check B.L.O.W.B.A.G.E.T: Brakes, Lights, Oil, Water, Battery, Air (tires), Gas, Engine, Tools, and documents. Prevention is better than accidents.",
                points: 20
            }
        ];
    },
    
    /**
     * Randomly select 5 scenarios from the 10 available
     */
    selectRandomScenarios() {
        // Shuffle the array
        const shuffled = [...this.allScenarios].sort(() => 0.5 - Math.random());
        
        // Select first 5
        this.scenarios = shuffled.slice(0, 5);
        
        // Reassign IDs 1-5 for the selected scenarios
        this.scenarios.forEach((scenario, index) => {
            scenario.id = index + 1;
        });
        
        console.log('🎲 Randomly selected 5 scenarios:', this.scenarios.map(s => s.title));
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
     * Get next unasked scenario (for time-based random triggers)
     */
    getNextScenario() {
        // Find scenarios that haven't been completed yet
        const availableScenarios = this.scenarios.filter(scenario => 
            !this.completedScenarios.has(scenario.id)
        );
        
        if (availableScenarios.length === 0) {
            return null; // All scenarios completed
        }
        
        // Return the first available scenario (they're already randomized)
        return availableScenarios[0];
    },
    
    /**
     * Check if car triggers any scenarios (DEPRECATED - kept for compatibility)
     */
    checkTriggers(carPosition) {
        // This method is now deprecated - scenarios are triggered by time, not position
        return [];
    },
    
    /**
     * Mark scenario as completed
     */
    markCompleted(scenarioId) {
        this.completedScenarios.add(scenarioId);
        
        console.log(`✅ Scenario ${scenarioId} completed (${this.completedScenarios.size}/5)`);
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
     * Get all scenario positions for minimap/overview (DEPRECATED - no positions anymore)
     */
    getScenarioPositions() {
        return this.scenarios.map(scenario => ({
            id: scenario.id,
            type: scenario.type,
            completed: this.completedScenarios.has(scenario.id)
        }));
    },
    
    /**
     * Get next available scenario
     */
    getNextScenario() {
        // Find scenarios that haven't been completed yet
        const availableScenarios = this.scenarios.filter(scenario => 
            !this.completedScenarios.has(scenario.id)
        );
        
        if (availableScenarios.length === 0) {
            return null; // All scenarios completed
        }
        
        // Return the first available scenario (they're already randomized)
        return availableScenarios[0];
    },
    
    /**
     * Reset all scenarios
     */
    reset() {
        this.completedScenarios.clear();
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
