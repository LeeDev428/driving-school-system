/**
 * Game Stats Module - Enhanced Database Integration with localStorage
 * Handles temporary localStorage storage and final database saving
 */

const GameStats = {
    // Current session data
    sessionData: {
        userId: null,
        sessionId: null,
        startTime: null,
        scenarios: [],
        totalScore: 0,
        completed: false
    },
    
    // Initialization flag to prevent duplicates
    initialized: false,
    
    // Database endpoints
    endpoints: {
        saveQuizResponses: '../save_quiz_responses.php',
        saveSimulation: '../save_simulation.php'
    },
    
    // LocalStorage keys
    storageKeys: {
        sessionId: 'quiz_session_id',
        responses: 'quiz_responses',
        startTime: 'quiz_start_time'
    },
    
    /**
     * Initialize game stats module
     */
    init() {
        // Prevent duplicate initialization
        if (this.initialized) {
            console.warn('⚠️ GameStats already initialized, skipping duplicate init');
            return;
        }
        
        console.log('📊 Initializing enhanced game statistics...');
        
        this.sessionData.userId = window.SimulationConfig?.userId || null;
        
        if (!this.sessionData.userId) {
            console.error('❌ No user ID found for statistics');
            return;
        }
        
        // Start new quiz session
        this.startNewQuizSession();
        
        // Mark as initialized
        this.initialized = true;
        
        console.log('✅ Game statistics ready with localStorage backup');
    },
    
    /**
     * Start a new quiz session
     */
    async startNewQuizSession() {
        try {
            // Clear any existing localStorage data
            this.clearLocalStorage();
            
            // Start session in database first to get proper session_id
            const response = await fetch(this.endpoints.saveQuizResponses, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'start_session',
                    user_id: this.sessionData.userId
                })
            });
            
            const result = await response.json();
            if (result.success && result.session_id) {
                // Use the database-generated session ID
                this.sessionData.sessionId = result.session_id;
                this.sessionData.startTime = new Date().toISOString();
                
                // Save to localStorage with the database session ID
                localStorage.setItem(this.storageKeys.sessionId, this.sessionData.sessionId);
                localStorage.setItem(this.storageKeys.startTime, this.sessionData.startTime);
                localStorage.setItem(this.storageKeys.responses, JSON.stringify([]));
                
                console.log('✅ Quiz session started with database sync:', this.sessionData.sessionId);
            } else {
                throw new Error('Failed to create database session');
            }
            
        } catch (error) {
            console.error('❌ Error starting quiz session:', error);
            // Fallback: Create client-side session only
            this.sessionData.sessionId = `quiz_${this.sessionData.userId}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            this.sessionData.startTime = new Date().toISOString();
            
            localStorage.setItem(this.storageKeys.sessionId, this.sessionData.sessionId);
            localStorage.setItem(this.storageKeys.startTime, this.sessionData.startTime);
            localStorage.setItem(this.storageKeys.responses, JSON.stringify([]));
            
            console.warn('⚠️ Using client-side session only');
        }
    },
    
    /**
     * Save individual scenario result to localStorage and database
     */
    async saveScenarioResult(result) {
        if (!this.sessionData.userId || !this.sessionData.sessionId) {
            console.warn('Cannot save: No user ID or session ID');
            return;
        }
        
        // Prevent duplicate saves for the same scenario
        const existingScenario = this.sessionData.scenarios.find(s => s.scenarioId === result.scenarioId);
        if (existingScenario) {
            console.warn(`⚠️ Scenario ${result.scenarioId} already saved, skipping duplicate`);
            return;
        }
        
        console.log(`💾 Saving scenario ${result.scenarioId} result to localStorage...`);
        
        const scenarioData = {
            scenarioId: result.scenarioId,
            question: result.question,
            selectedOption: result.selectedOption,
            correctOption: result.correctOption,
            isCorrect: result.isCorrect,
            points: result.points,
            timestamp: new Date().toISOString(),
            timeTaken: this.calculateTimeTaken()
        };
        
        // Add to session data
        this.sessionData.scenarios.push(scenarioData);
        this.sessionData.totalScore += result.points;
        
        // Save to localStorage (primary storage)
        try {
            const existingResponses = JSON.parse(localStorage.getItem(this.storageKeys.responses) || '[]');
            existingResponses.push(scenarioData);
            localStorage.setItem(this.storageKeys.responses, JSON.stringify(existingResponses));
            
            console.log(`✅ Scenario ${result.scenarioId} saved to localStorage`);
        } catch (error) {
            console.error('❌ Failed to save to localStorage:', error);
        }
        
        // Try to save to database immediately (backup)
        try {
            const response = await fetch(this.endpoints.saveQuizResponses, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_single_response',
                    session_id: this.sessionData.sessionId,
                    scenario_id: result.scenarioId,
                    question_text: result.question,
                    selected_option: result.selectedOption,
                    correct_option: result.correctOption,
                    is_correct: result.isCorrect,
                    points_earned: result.points,
                    time_taken_seconds: scenarioData.timeTaken
                })
            });

            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Check content type before parsing JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.warn(`⚠️ Expected JSON but got: ${text.substring(0, 100)}`);
                throw new Error('Server returned non-JSON response');
            }

            const dbResult = await response.json();
            if (dbResult.success) {
                console.log(`✅ Scenario ${result.scenarioId} also saved to database`);
            } else {
                console.warn(`⚠️ Database save failed for scenario ${result.scenarioId}:`, dbResult.error);
            }
        } catch (error) {
            console.warn(`⚠️ Database save failed for scenario ${result.scenarioId}:`, error.message);
        }
    },
    
    /**
     * Calculate time taken for current scenario
     */
    calculateTimeTaken() {
        // Simple calculation - in real implementation, you'd track per-scenario timing
        const totalTime = Date.now() - new Date(this.sessionData.startTime).getTime();
        return Math.round(totalTime / 1000); // Convert to seconds
    },
    
    /**
     * Save final simulation results from localStorage to database
     */
    async saveFinalResults(finalResults) {
        if (!this.sessionData.userId) {
            console.warn('Cannot save final results: No user ID');
            return;
        }
        
        console.log('💾 Saving final simulation results from localStorage to database...');
        
        // Get responses from localStorage
        let responses = [];
        try {
            responses = JSON.parse(localStorage.getItem(this.storageKeys.responses) || '[]');
        } catch (error) {
            console.error('❌ Failed to read from localStorage:', error);
            return;
        }
        
        // Ensure all 5 scenarios are completed
        if (responses.length < 5) {
            console.warn(`Cannot save final results: Only ${responses.length}/5 scenarios completed`);
            return;
        }
        
        this.sessionData.completed = true;
        this.sessionData.endTime = new Date().toISOString();
        
        // Calculate completion time
        const startTime = new Date(localStorage.getItem(this.storageKeys.startTime) || this.sessionData.startTime);
        const completionTimeSeconds = Math.round((Date.now() - startTime.getTime()) / 1000);
        
        try {
            // Save complete quiz to database
            const response = await fetch(this.endpoints.saveQuizResponses, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'complete_quiz',
                    session_id: this.sessionData.sessionId,
                    completion_time_seconds: completionTimeSeconds
                })
            });
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Check content type
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('❌ Expected JSON response but got:', text.substring(0, 200));
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            }
            
            const result = await response.json();
            
            if (result.success) {
                console.log(`🎉 Final results saved successfully!`);
                console.log(`📊 Results: ${result.correct_answers}/5 correct (${result.score_percentage}%), Status: ${result.status}`);
                
                // Clear localStorage after successful save
                this.clearLocalStorage();
                
                return {
                    success: true,
                    simulationId: result.simulation_id,
                    sessionId: result.session_id,
                    scorePercentage: result.score_percentage,
                    correctAnswers: result.correct_answers,
                    wrongAnswers: result.wrong_answers,
                    totalPoints: result.total_points,
                    status: result.status
                };
            } else {
                console.error('❌ Failed to save final results:', result.error);
                return { success: false, error: result.error };
            }
            
        } catch (error) {
            console.error('❌ Error saving final results:', error);
            
            // Keep localStorage data if database save fails
            console.log('💿 Keeping data in localStorage due to database error');
            return { success: false, error: error.message };
        }
    },
    
    /**
     * Clear localStorage data
     */
    clearLocalStorage() {
        try {
            localStorage.removeItem(this.storageKeys.sessionId);
            localStorage.removeItem(this.storageKeys.responses);
            localStorage.removeItem(this.storageKeys.startTime);
            console.log('🧹 LocalStorage cleared');
        } catch (error) {
            console.error('❌ Failed to clear localStorage:', error);
        }
    },
    
    /**
     * Get responses from localStorage (for debugging/recovery)
     */
    getLocalStorageData() {
        try {
            return {
                sessionId: localStorage.getItem(this.storageKeys.sessionId),
                responses: JSON.parse(localStorage.getItem(this.storageKeys.responses) || '[]'),
                startTime: localStorage.getItem(this.storageKeys.startTime)
            };
        } catch (error) {
            console.error('❌ Failed to read localStorage:', error);
            return null;
        }
    },
    
    /**
     * Save progress checkpoint (DEPRECATED - Use saveScenarioResult instead)
     * This method is disabled to prevent duplicate saves
     */
    saveProgress(progressData) {
        console.warn('⚠️ saveProgress is deprecated - duplicate saves prevented');
        console.warn('💡 Use saveScenarioResult instead for proper database integration');
        // Method disabled to prevent duplicate database entries
        return;
        
        /*
        // OLD CODE - REMOVED TO PREVENT DUPLICATES
        if (!this.sessionData.userId) return;
        
        const saveData = {
            type: 'progress',
            userId: this.sessionData.userId,
            scenarioId: progressData.scenarioId,
            answer: progressData.answer,
            correct: progressData.correct,
            score: progressData.score,
            timestamp: new Date().toISOString()
        };
        
        this.sendToDatabase('progress', saveData);
        */
    },
    
    /**
     * Send data to database
     */
    async sendToDatabase(type, data) {
        try {
            let requestData;
            
            if (type === 'final') {
                // Format data for save_simulation.php requirements
                const correctAnswers = this.sessionData.scenarios.filter(s => s.isCorrect).length;
                const wrongAnswers = this.sessionData.scenarios.length - correctAnswers;
                
                requestData = {
                    simulation_type: 'driving_scenarios',
                    total_scenarios: this.sessionData.scenarios.length,
                    correct_answers: correctAnswers,
                    wrong_answers: wrongAnswers,
                    completion_time_seconds: Math.round(data.totalTime),
                    scenarios_data: this.sessionData.scenarios.map(s => ({
                        scenario_id: s.scenarioId,
                        question: s.question,
                        selected_option: s.selectedOption,
                        correct_option: s.correctOption,
                        is_correct: s.isCorrect,
                        points_earned: s.points,
                        timestamp: s.timestamp
                    }))
                };
                
                console.log('💾 Sending final results to database:', requestData);
            } else {
                // For other types (progress, scenario), keep existing format
                requestData = {
                    type: type,
                    data: data
                };
            }
            
            const response = await fetch(this.endpoints.saveProgress, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                console.log(`✅ ${type} data saved successfully to database`);
                if (result.simulation_id) {
                    this.sessionData.simulationId = result.simulation_id;
                }
                
                // Show success message for final results
                if (type === 'final') {
                    console.log(`🎉 Final simulation results saved! ID: ${result.simulation_id}, Score: ${result.score_percentage}%`);
                }
            } else {
                console.error(`❌ Failed to save ${type} data:`, result.error || result.message);
            }
            
        } catch (error) {
            console.error(`❌ Error saving ${type} data:`, error);
            
            // Fallback: store in localStorage
            this.saveToLocalStorage(type, data);
        }
    },
    
    /**
     * Fallback: save to localStorage if database fails
     */
    saveToLocalStorage(type, data) {
        try {
            const key = `simulation_${type}_${Date.now()}`;
            localStorage.setItem(key, JSON.stringify(data));
            console.log('💿 Data saved to localStorage as fallback');
        } catch (error) {
            console.error('❌ Failed to save to localStorage:', error);
        }
    },
    
    /**
     * Calculate letter grade from accuracy percentage
     */
    calculateGrade(accuracy) {
        if (accuracy >= 90) return 'A';
        if (accuracy >= 80) return 'B';
        if (accuracy >= 70) return 'C';
        if (accuracy >= 60) return 'D';
        return 'F';
    },
    
    /**
     * Get current session statistics
     */
    getSessionStats() {
        return {
            userId: this.sessionData.userId,
            startTime: this.sessionData.startTime,
            totalScore: this.sessionData.totalScore,
            scenariosCompleted: this.sessionData.scenarios.length,
            completed: this.sessionData.completed,
            scenarios: this.sessionData.scenarios.map(s => ({
                id: s.scenarioId,
                correct: s.isCorrect,
                points: s.points
            }))
        };
    },
    
    /**
     * Get user's historical statistics
     */
    async getUserStats() {
        if (!this.sessionData.userId) {
            console.warn('Cannot get stats: No user ID');
            return null;
        }
        
        try {
            const response = await fetch(`${this.endpoints.getStats}?userId=${this.sessionData.userId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const stats = await response.json();
            return stats;
            
        } catch (error) {
            console.error('❌ Error fetching user stats:', error);
            return null;
        }
    },
    
    /**
     * Export session data for debugging
     */
    exportSessionData() {
        const exportData = {
            ...this.sessionData,
            exportTime: new Date().toISOString(),
            userAgent: navigator.userAgent,
            screenSize: {
                width: window.screen.width,
                height: window.screen.height
            }
        };
        
        // Create download link
        const dataStr = JSON.stringify(exportData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `simulation_session_${this.sessionData.userId}_${Date.now()}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
    },
    
    /**
     * Generate performance report
     */
    generatePerformanceReport() {
        const scenarios = this.sessionData.scenarios;
        if (scenarios.length === 0) {
            return null;
        }
        
        const correctAnswers = scenarios.filter(s => s.isCorrect).length;
        const totalQuestions = scenarios.length;
        const accuracy = (correctAnswers / totalQuestions) * 100;
        
        // Analyze performance by scenario type
        const scenarioTypes = {
            'RED_LIGHT': [],
            'STOP_SIGN': [],
            'PEDESTRIAN': [],
            'SCHOOL_ZONE': [],
            'INTERSECTION': []
        };
        
        scenarios.forEach(scenario => {
            const type = this.getScenarioType(scenario.scenarioId);
            if (scenarioTypes[type]) {
                scenarioTypes[type].push(scenario);
            }
        });
        
        const typePerformance = {};
        Object.entries(scenarioTypes).forEach(([type, typeScenarios]) => {
            if (typeScenarios.length > 0) {
                const correct = typeScenarios.filter(s => s.isCorrect).length;
                typePerformance[type] = {
                    total: typeScenarios.length,
                    correct: correct,
                    accuracy: Math.round((correct / typeScenarios.length) * 100)
                };
            }
        });
        
        return {
            overall: {
                totalQuestions,
                correctAnswers,
                accuracy: Math.round(accuracy * 100) / 100,
                totalScore: this.sessionData.totalScore,
                maxScore: totalQuestions * 20,
                grade: this.calculateGrade(accuracy)
            },
            byType: typePerformance,
            timeline: scenarios.map(s => ({
                scenarioId: s.scenarioId,
                timestamp: s.timestamp,
                correct: s.isCorrect,
                points: s.points
            }))
        };
    },
    
    /**
     * Get scenario type by ID
     */
    getScenarioType(scenarioId) {
        const typeMap = {
            1: 'RED_LIGHT',
            2: 'STOP_SIGN',
            3: 'PEDESTRIAN',
            4: 'SCHOOL_ZONE',
            5: 'INTERSECTION'
        };
        
        return typeMap[scenarioId] || 'UNKNOWN';
    },
    
    /**
     * Reset session data
     */
    reset() {
        this.sessionData = {
            userId: window.SimulationConfig?.userId || null,
            startTime: new Date().toISOString(),
            scenarios: [],
            totalScore: 0,
            completed: false
        };
        
        console.log('🔄 Game statistics reset');
    },
    
    /**
     * Validate data before saving
     */
    validateData(data) {
        // Basic validation
        if (!data || typeof data !== 'object') {
            return false;
        }
        
        // Check required fields based on type
        if (data.type === 'scenario') {
            return data.scenarioId && 
                   typeof data.isCorrect === 'boolean' && 
                   typeof data.points === 'number';
        }
        
        if (data.type === 'final') {
            return data.userId && 
                   data.totalScore !== undefined && 
                   data.accuracy !== undefined;
        }
        
        return true;
    }
};

// Export module
window.GameStats = GameStats;
