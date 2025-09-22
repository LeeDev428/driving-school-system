// UI Management Module
// Handles all user interface elements, panels, status displays, and interactions

const UI = {
    // UI state
    statusTimeout: null,
    
    // Initialize UI elements
    init() {
        this.setupEventListeners();
        this.setupButtonControls();
        this.updateStatsDisplay();
    },

    // Setup keyboard event listeners
    setupEventListeners() {
        document.addEventListener('keydown', this.onKeyDown);
        document.addEventListener('keyup', this.onKeyUp);
    },

    // Handle key down events
    onKeyDown(event) {
        if (typeof keys !== 'undefined') {
            keys[event.code] = true;
        }
        event.preventDefault();
    },

    // Handle key up events  
    onKeyUp(event) {
        if (typeof keys !== 'undefined') {
            keys[event.code] = false;
        }
        event.preventDefault();
    },

    // Setup button controls for mobile/touch devices
    setupButtonControls() {
        const buttons = [
            { id: 'forwardBtn', action: 'forward' },
            { id: 'reverseBtn', action: 'reverse' },
            { id: 'leftBtn', action: 'left' },
            { id: 'rightBtn', action: 'right' }
        ];

        buttons.forEach(button => {
            const element = document.getElementById(button.id);
            if (element && typeof buttonStates !== 'undefined') {
                element.addEventListener('mousedown', () => {
                    buttonStates[button.action] = true;
                });
                
                element.addEventListener('mouseup', () => {
                    buttonStates[button.action] = false;
                });
                
                element.addEventListener('mouseleave', () => {
                    buttonStates[button.action] = false;
                });
                
                // Touch events for mobile
                element.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    buttonStates[button.action] = true;
                });
                
                element.addEventListener('touchend', (e) => {
                    e.preventDefault();
                    buttonStates[button.action] = false;
                });
            }
        });
    },

    // Show status message
    showStatus(message, duration = 3000) {
        const statusElement = document.getElementById('gameStatus');
        if (statusElement) {
            statusElement.innerHTML = message;
            statusElement.style.display = 'block';
            
            // Clear existing timeout
            if (this.statusTimeout) {
                clearTimeout(this.statusTimeout);
            }
            
            // Hide after duration
            this.statusTimeout = setTimeout(() => {
                statusElement.style.display = 'none';
            }, duration);
        }
    },

    // Update statistics display
    updateStatsDisplay() {
        if (typeof gameStats === 'undefined') return;
        
        const correctElement = document.getElementById('correctAnswers');
        const wrongElement = document.getElementById('wrongAnswers');
        const totalElement = document.getElementById('totalAnswers');
        
        if (correctElement) correctElement.textContent = gameStats.correct || 0;
        if (wrongElement) wrongElement.textContent = gameStats.wrong || 0;
        if (totalElement) totalElement.textContent = gameStats.total || 0;
    },

    // Update speed display
    updateSpeedDisplay(speed) {
        const speedElement = document.getElementById('currentSpeed');
        if (speedElement) {
            speedElement.textContent = `${speed} km/h`;
        }
    },

    // Update scenario panel
    updateScenarioPanel(scenario, scenarioIndex) {
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
                button.onclick = () => this.selectOption(index);
                optionsContainer.appendChild(button);
            });
        }

        // Add "Go to Results" button if this is the 5th scenario
        if (scenarioIndex === 4) { // 0-based index, so 4 is the 5th scenario
            const resultsButton = document.createElement('button');
            resultsButton.textContent = 'Go to Results';
            resultsButton.className = 'results-btn';
            resultsButton.id = 'goToResultsBtn';
            resultsButton.style.display = 'none'; // Initially hidden
            resultsButton.onclick = () => this.goToResults();
            
            if (optionsContainer) {
                optionsContainer.appendChild(resultsButton);
            }
        }
        
        const scenarioPanel = document.getElementById('scenarioPanel');
        if (scenarioPanel) {
            scenarioPanel.style.display = 'block';
        }
    },

    // Handle option selection
    selectOption(optionIndex) {
        if (typeof currentScenario === 'undefined' || !currentScenario) return;
        
        const isCorrect = optionIndex === currentScenario.correctAnswer;
        
        // Update game stats (assuming gameStats is global)
        if (typeof gameStats !== 'undefined') {
            gameStats.total++;
            
            if (isCorrect) {
                gameStats.correct++;
                this.showStatus("✅ Correct! " + currentScenario.explanation, 4000);
            } else {
                gameStats.wrong++;
                this.showStatus("❌ Wrong. " + currentScenario.explanation, 4000);
            }
            
            // Save scenario result
            gameStats.scenarios.push({
                scenario: currentScenario.title,
                userAnswer: optionIndex,
                correctAnswer: currentScenario.correctAnswer,
                isCorrect: isCorrect,
                timestamp: Date.now()
            });
            
            this.updateStatsDisplay();
        }
        
        // If this is the 5th scenario, show the "Go to Results" button
        if (typeof scenarioIndex !== 'undefined' && scenarioIndex === 4) {
            const goToResultsBtn = document.getElementById('goToResultsBtn');
            if (goToResultsBtn) {
                goToResultsBtn.style.display = 'block';
            }
            // Don't move to next scenario, let user click "Go to Results"
            return;
        }
        
        // Move to next scenario after delay
        setTimeout(() => {
            this.hideScenarioPanel();
            this.showStatus("You may continue driving. Be safe!", 2000);
            
            // Notify game engine to continue
            if (typeof window.continueAfterScenario === 'function') {
                window.continueAfterScenario();
            }
        }, 2000);
    },

    // Hide scenario panel
    hideScenarioPanel() {
        const scenarioPanel = document.getElementById('scenarioPanel');
        if (scenarioPanel) {
            scenarioPanel.style.display = 'none';
        }
    },

    // Go to results page
    goToResults() {
        console.log('🎯 Go to Results button clicked - Final scenario completed');
        
        if (typeof gameStats === 'undefined') {
            console.error('❌ Game stats not available');
            return;
        }
        
        // Calculate final stats
        const completionTime = gameStats.startTime ? Math.floor((Date.now() - gameStats.startTime) / 1000) : 0;
        
        // Show loading message
        this.showStatus('Saving results...', 1000);
        
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
                this.showStatus(`Results saved! Redirecting...`, 2000);
                
                // Redirect to results page after 2 seconds
                setTimeout(() => {
                    window.location.href = 'simulation_result.php';
                }, 2000);
            } else {
                console.error('❌ Failed to save results:', result.message);
                this.showStatus('⚠️ Could not save results. Redirecting anyway...', 3000);
                
                // Still redirect even if save failed
                setTimeout(() => {
                    window.location.href = 'simulation_result.php';
                }, 3000);
            }
        })
        .catch(error => {
            console.error('❌ Error saving results:', error);
            this.showStatus('⚠️ Network error. Redirecting anyway...', 3000);
            
            // Still redirect even if network error
            setTimeout(() => {
                window.location.href = 'simulation_result.php';
            }, 3000);
        });
    },

    // Show end simulation screen
    showEndSimulation() {
        if (typeof gameStats === 'undefined') return;
        
        const completionTime = gameStats.startTime ? Math.floor((Date.now() - gameStats.startTime) / 1000) : 0;
        const scorePercentage = gameStats.total > 0 ? Math.round((gameStats.correct / gameStats.total) * 100) : 0;
        
        this.showStatus(`
            🎉 Simulation Complete!<br>
            Score: ${scorePercentage}% (${gameStats.correct}/${gameStats.total})<br>
            Time: ${completionTime}s<br>
            <button onclick="location.reload()" style="margin-top: 10px; padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Play Again
            </button>
        `, 10000);
    },

    // Update minimap (if exists)
    updateMiniMap(car, camera, world) {
        const miniMapCanvas = document.getElementById('miniMapCanvas');
        if (!miniMapCanvas) return;
        
        const miniMapCtx = miniMapCanvas.getContext('2d');
        const scale = 0.1; // Scale factor for minimap
        
        // Clear minimap
        miniMapCtx.clearRect(0, 0, miniMapCanvas.width, miniMapCanvas.height);
        
        // Draw world outline
        miniMapCtx.strokeStyle = '#666';
        miniMapCtx.strokeRect(0, 0, world.width * scale, world.height * scale);
        
        // Draw car position
        miniMapCtx.fillStyle = '#ff4444';
        const carX = car.properties.x * scale;
        const carY = car.properties.y * scale;
        miniMapCtx.fillRect(carX - 2, carY - 2, 4, 4);
        
        // Draw camera view area
        miniMapCtx.strokeStyle = '#00ff00';
        miniMapCtx.strokeRect(
            camera.x * scale,
            camera.y * scale,
            800 * scale, // Canvas width
            600 * scale  // Canvas height
        );
    },

    // Clean up UI resources
    cleanup() {
        if (this.statusTimeout) {
            clearTimeout(this.statusTimeout);
            this.statusTimeout = null;
        }
        
        // Remove event listeners
        document.removeEventListener('keydown', this.onKeyDown);
        document.removeEventListener('keyup', this.onKeyUp);
    }
};

// Export to global window object for browser use
window.UIModule = UI;

// Export for use in other modules (Node.js compatibility)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UI;
}