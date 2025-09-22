// Game Statistics Module
// Handles tracking, storage, and management of game statistics

const GameStats = {
    // Statistics data
    data: {
        correct: 0,
        wrong: 0,
        total: 0,
        startTime: null,
        scenarios: [],
        sessionId: null
    },

    // Initialize statistics
    init() {
        this.reset();
        this.data.sessionId = this.generateSessionId();
        console.log('📊 Game statistics initialized with session ID:', this.data.sessionId);
    },

    // Reset all statistics
    reset() {
        this.data.correct = 0;
        this.data.wrong = 0;
        this.data.total = 0;
        this.data.startTime = null;
        this.data.scenarios = [];
        console.log('🔄 Game statistics reset');
    },

    // Start tracking time
    startSession() {
        this.data.startTime = Date.now();
        console.log('⏱️ Session timer started');
    },

    // Generate unique session ID
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },

    // Record a scenario result
    recordScenario(scenarioData) {
        const result = {
            scenario: scenarioData.scenario || 'Unknown',
            userAnswer: scenarioData.userAnswer || 0,
            correctAnswer: scenarioData.correctAnswer || 0,
            isCorrect: scenarioData.isCorrect || false,
            timestamp: Date.now(),
            timeFromStart: this.data.startTime ? Date.now() - this.data.startTime : 0
        };

        this.data.scenarios.push(result);
        this.data.total++;

        if (result.isCorrect) {
            this.data.correct++;
        } else {
            this.data.wrong++;
        }

        console.log('📝 Recorded scenario result:', result);
        return result;
    },

    // Get current statistics
    getStats() {
        return {
            correct: this.data.correct,
            wrong: this.data.wrong,
            total: this.data.total,
            percentage: this.data.total > 0 ? Math.round((this.data.correct / this.data.total) * 100) : 0,
            completionTime: this.getCompletionTime(),
            sessionId: this.data.sessionId
        };
    },

    // Get completion time in seconds
    getCompletionTime() {
        return this.data.startTime ? Math.floor((Date.now() - this.data.startTime) / 1000) : 0;
    },

    // Get detailed scenario results
    getScenarioResults() {
        return [...this.data.scenarios];
    },

    // Calculate performance metrics
    getPerformanceMetrics() {
        const stats = this.getStats();
        const scenarios = this.getScenarioResults();
        
        return {
            accuracy: stats.percentage,
            averageTimePerScenario: scenarios.length > 0 ? stats.completionTime / scenarios.length : 0,
            fastestScenario: this.getFastestScenario(),
            slowestScenario: this.getSlowestScenario(),
            correctStreaks: this.getCorrectStreaks(),
            totalTime: stats.completionTime
        };
    },

    // Get fastest scenario completion
    getFastestScenario() {
        if (this.data.scenarios.length === 0) return null;
        
        let fastest = null;
        let minTime = Infinity;
        
        for (let i = 1; i < this.data.scenarios.length; i++) {
            const timeDiff = this.data.scenarios[i].timestamp - this.data.scenarios[i-1].timestamp;
            if (timeDiff < minTime) {
                minTime = timeDiff;
                fastest = this.data.scenarios[i];
            }
        }
        
        return fastest ? { scenario: fastest.scenario, time: Math.floor(minTime / 1000) } : null;
    },

    // Get slowest scenario completion
    getSlowestScenario() {
        if (this.data.scenarios.length === 0) return null;
        
        let slowest = null;
        let maxTime = 0;
        
        for (let i = 1; i < this.data.scenarios.length; i++) {
            const timeDiff = this.data.scenarios[i].timestamp - this.data.scenarios[i-1].timestamp;
            if (timeDiff > maxTime) {
                maxTime = timeDiff;
                slowest = this.data.scenarios[i];
            }
        }
        
        return slowest ? { scenario: slowest.scenario, time: Math.floor(maxTime / 1000) } : null;
    },

    // Calculate correct answer streaks
    getCorrectStreaks() {
        const streaks = [];
        let currentStreak = 0;
        
        this.data.scenarios.forEach(scenario => {
            if (scenario.isCorrect) {
                currentStreak++;
            } else {
                if (currentStreak > 0) {
                    streaks.push(currentStreak);
                    currentStreak = 0;
                }
            }
        });
        
        if (currentStreak > 0) {
            streaks.push(currentStreak);
        }
        
        return {
            longest: streaks.length > 0 ? Math.max(...streaks) : 0,
            total: streaks.length,
            average: streaks.length > 0 ? streaks.reduce((a, b) => a + b, 0) / streaks.length : 0
        };
    },

    // Export data for saving
    exportForSave() {
        const stats = this.getStats();
        return {
            simulation_type: 'driving_simulation_2d',
            total_scenarios: stats.total,
            correct_answers: stats.correct,
            wrong_answers: stats.wrong,
            score_percentage: stats.percentage,
            completion_time_seconds: stats.completionTime,
            scenarios_data: JSON.stringify(this.data.scenarios),
            session_id: stats.sessionId,
            performance_metrics: JSON.stringify(this.getPerformanceMetrics())
        };
    },

    // Save to database
    async saveToDatabase() {
        try {
            console.log('💾 Saving statistics to database...');
            
            const exportData = this.exportForSave();
            const formData = new FormData();
            
            formData.append('action', 'save_simulation_result');
            Object.keys(exportData).forEach(key => {
                formData.append(key, exportData[key]);
            });
            
            const response = await fetch('simulation.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Statistics saved successfully:', result);
                return { success: true, data: result };
            } else {
                console.error('❌ Failed to save statistics:', result.message);
                return { success: false, error: result.message };
            }
        } catch (error) {
            console.error('❌ Error saving statistics:', error);
            return { success: false, error: error.message };
        }
    },

    // Get summary for display
    getSummary() {
        const stats = this.getStats();
        const metrics = this.getPerformanceMetrics();
        
        return {
            title: 'Driving Simulation Complete!',
            score: `${stats.correct}/${stats.total} (${stats.percentage}%)`,
            time: `${stats.completionTime} seconds`,
            accuracy: `${stats.percentage}% accuracy`,
            averageTime: `${metrics.averageTimePerScenario.toFixed(1)}s per scenario`,
            longestStreak: `${metrics.correctStreaks.longest} correct in a row`
        };
    },

    // Local storage backup
    saveToLocalStorage() {
        try {
            const data = {
                stats: this.data,
                timestamp: Date.now()
            };
            localStorage.setItem('drivingSimulationStats', JSON.stringify(data));
            console.log('💾 Statistics backed up to local storage');
        } catch (error) {
            console.warn('⚠️ Could not save to local storage:', error);
        }
    },

    // Load from local storage
    loadFromLocalStorage() {
        try {
            const saved = localStorage.getItem('drivingSimulationStats');
            if (saved) {
                const data = JSON.parse(saved);
                this.data = { ...this.data, ...data.stats };
                console.log('📂 Statistics loaded from local storage');
                return true;
            }
        } catch (error) {
            console.warn('⚠️ Could not load from local storage:', error);
        }
        return false;
    },

    // Clear local storage
    clearLocalStorage() {
        try {
            localStorage.removeItem('drivingSimulationStats');
            console.log('🗑️ Local storage cleared');
        } catch (error) {
            console.warn('⚠️ Could not clear local storage:', error);
        }
    },

    // Validate data integrity
    validate() {
        const issues = [];
        
        if (this.data.correct + this.data.wrong !== this.data.total) {
            issues.push('Correct + Wrong answers do not equal total');
        }
        
        if (this.data.scenarios.length !== this.data.total) {
            issues.push('Scenario count does not match total answers');
        }
        
        if (this.data.startTime && this.data.startTime > Date.now()) {
            issues.push('Start time is in the future');
        }
        
        return {
            valid: issues.length === 0,
            issues: issues
        };
    }
};

// Export to global window object for browser use
window.GameStatsModule = GameStats;

// Export for use in other modules (Node.js compatibility)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GameStats;
}