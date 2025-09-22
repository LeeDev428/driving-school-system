/**
 * Game Stats Module - Database Integration and Statistics
 * Handles saving simulation results to simulation_results table
 */

const GameStats = {
    // Current session data
    sessionData: {
        userId: null,
        startTime: null,
        scenarios: [],
        totalScore: 0,
        completed: false
    },
    
    // Database endpoints
    endpoints: {
        saveProgress: '../save_simulation.php',
        getStats: '../get_simulation_stats.php'
    },
    
    /**
     * Initialize game stats module
     */
    init() {
        console.log('📊 Initializing game statistics...');
        
        this.sessionData.userId = window.SimulationConfig?.userId || null;
        this.sessionData.startTime = new Date().toISOString();
        this.sessionData.scenarios = [];
        this.sessionData.totalScore = 0;
        this.sessionData.completed = false;
        
        if (!this.sessionData.userId) {
            console.error('❌ No user ID found for statistics');
            return;
        }
        
        console.log('✅ Game statistics ready');
    },
    
    /**
     * Save individual scenario result
     */
    saveScenarioResult(result) {
        if (!this.sessionData.userId) {
            console.warn('Cannot save: No user ID');
            return;
        }
        
        console.log(`💾 Saving scenario ${result.scenarioId} result...`);
        
        // Add to session data
        this.sessionData.scenarios.push({
            scenarioId: result.scenarioId,
            question: result.question,
            selectedOption: result.selectedOption,
            correctOption: result.correctOption,
            isCorrect: result.isCorrect,
            points: result.points,
            timestamp: new Date().toISOString()
        });
        
        this.sessionData.totalScore += result.points;
        
        // Save to database
        this.sendToDatabase('scenario', result);
    },
    
    /**
     * Save final simulation results
     */
    saveFinalResults(finalResults) {
        if (!this.sessionData.userId) {
            console.warn('Cannot save final results: No user ID');
            return;
        }
        
        console.log('💾 Saving final simulation results...');
        
        this.sessionData.completed = true;
        this.sessionData.endTime = new Date().toISOString();
        this.sessionData.totalTime = finalResults.totalTime;
        this.sessionData.finalScore = finalResults.score;
        this.sessionData.accuracy = finalResults.accuracy;
        
        // Prepare data for database
        const saveData = {
            type: 'final',
            userId: this.sessionData.userId,
            startTime: this.sessionData.startTime,
            endTime: this.sessionData.endTime,
            totalTime: Math.round(finalResults.totalTime / 1000), // Convert to seconds
            totalScore: finalResults.score,
            maxScore: finalResults.scenariosCompleted * 20,
            scenariosCompleted: finalResults.scenariosCompleted,
            accuracy: Math.round(finalResults.accuracy * 100) / 100, // Round to 2 decimals
            scenarios: this.sessionData.scenarios,
            grade: this.calculateGrade(finalResults.accuracy)
        };
        
        // Save to database
        this.sendToDatabase('final', saveData);
    },
    
    /**
     * Save progress checkpoint
     */
    saveProgress(progressData) {
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
    },
    
    /**
     * Send data to database
     */
    async sendToDatabase(type, data) {
        try {
            const response = await fetch(this.endpoints.saveProgress, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: type,
                    data: data
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                console.log(`✅ ${type} data saved successfully`);
                if (result.simulationId) {
                    this.sessionData.simulationId = result.simulationId;
                }
            } else {
                console.error(`❌ Failed to save ${type} data:`, result.message);
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
