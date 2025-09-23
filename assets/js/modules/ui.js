/**
 * UI Module - User Interface Management
 * Handles question display, scoring, controls, and user interactions
 */

const UIModule = {
    // UI Elements
    questionModal: null,
    currentQuestion: null,
    questionCallback: null,
    
    // UI State
    isQuestionVisible: false,
    selectedOption: -1,
    
    // UI Components
    elements: {},
    
    /**
     * Initialize UI module
     */
    init() {
        console.log('🎨 Initializing user interface...');
        this.setupUIElements();
        this.setupEventListeners();
        console.log('✅ UI ready for interaction');
    },
    
    /**
     * Setup UI element references
     */
    setupUIElements() {
        this.elements = {
            questionModal: document.getElementById('questionModal'),
            questionTitle: document.getElementById('questionTitle'),
            questionText: document.getElementById('questionText'),
            questionOptions: document.getElementById('questionOptions'),
            questionNumber: document.getElementById('questionNumber'),
            submitButton: document.getElementById('submitAnswer'),
            questionFeedback: document.getElementById('questionFeedback'),
            scoreDisplay: document.getElementById('scoreDisplay'),
            scenarioDisplay: document.getElementById('scenarioDisplay'),
            timeDisplay: document.getElementById('timeDisplay'),
            speedDisplay: document.getElementById('speedDisplay')
        };
        
        // Verify essential elements exist
        Object.entries(this.elements).forEach(([name, element]) => {
            if (!element) {
                console.warn(`⚠️ UI element '${name}' not found`);
            }
        });
    },
    
    /**
     * Setup UI event listeners
     */
    setupEventListeners() {
        // Submit button
        if (this.elements.submitButton) {
            this.elements.submitButton.addEventListener('click', () => {
                this.handleSubmitAnswer();
            });
        }
        
        // Close modal on outside click
        if (this.elements.questionModal) {
            this.elements.questionModal.addEventListener('click', (e) => {
                if (e.target === this.elements.questionModal) {
                    // Don't allow closing by clicking outside during question
                    // this.hideQuestion();
                }
            });
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (this.isQuestionVisible) {
                this.handleQuestionKeyboard(e);
            }
        });
    },
    
    /**
     * Show scenario question
     */
    showScenarioQuestion(scenario) {
        if (!scenario || !this.elements.questionModal) {
            console.error('Cannot show question: missing scenario or modal');
            return;
        }
        
        console.log(`📝 Showing question for scenario ${scenario.id}`);
        
        this.currentQuestion = scenario;
        this.selectedOption = -1;
        this.isQuestionVisible = true;
        
        // Update question content
        this.updateQuestionContent(scenario);
        
        // Show modal
        this.elements.questionModal.style.display = 'flex';
        
        // Reset submit button
        if (this.elements.submitButton) {
            this.elements.submitButton.disabled = true;
            this.elements.submitButton.textContent = 'Submit Answer';
        }
        
        // Hide feedback
        if (this.elements.questionFeedback) {
            this.elements.questionFeedback.style.display = 'none';
        }
    },
    
    /**
     * Update question content
     */
    updateQuestionContent(scenario) {
        // Set title
        if (this.elements.questionTitle) {
            this.elements.questionTitle.textContent = scenario.title || 'Traffic Scenario';
        }
        
        // Set question text
        if (this.elements.questionText) {
            this.elements.questionText.innerHTML = `
                <p style="font-size: 18px; margin-bottom: 20px; line-height: 1.4;">
                    ${scenario.question}
                </p>
            `;
        }
        
        // Set question number
        if (this.elements.questionNumber) {
            this.elements.questionNumber.textContent = scenario.id;
        }
        
        // Create option buttons
        this.createOptionButtons(scenario.options);
    },
    
    /**
     * Create option buttons
     */
    createOptionButtons(options) {
        if (!this.elements.questionOptions || !options) return;
        
        // Clear existing options
        this.elements.questionOptions.innerHTML = '';
        
        // Create option buttons
        options.forEach((option, index) => {
            const button = document.createElement('button');
            button.className = 'option-btn';
            button.textContent = option;
            button.dataset.optionIndex = index;
            
            // Add click handler
            button.addEventListener('click', () => {
                this.selectOption(index);
            });
            
            this.elements.questionOptions.appendChild(button);
        });
    },
    
    /**
     * Handle option selection
     */
    selectOption(optionIndex) {
        // Remove previous selection
        const allOptions = this.elements.questionOptions.querySelectorAll('.option-btn');
        allOptions.forEach(btn => btn.classList.remove('selected'));
        
        // Select new option
        const selectedButton = this.elements.questionOptions.querySelector(`[data-option-index="${optionIndex}"]`);
        if (selectedButton) {
            selectedButton.classList.add('selected');
            this.selectedOption = optionIndex;
            
            // Enable submit button
            if (this.elements.submitButton) {
                this.elements.submitButton.disabled = false;
            }
        }
    },
    
    /**
     * Handle submit answer
     */
    handleSubmitAnswer() {
        if (this.selectedOption === -1 || !this.currentQuestion) {
            return;
        }
        
        // Get scenario result
        const result = window.ScenariosModule?.getScenarioResult(
            this.currentQuestion.id, 
            this.selectedOption
        );
        
        if (!result) {
            console.error('Could not get scenario result');
            return;
        }
        
        // Show feedback
        this.showAnswerFeedback(result);
        
        // Change submit button to Next button
        if (this.elements.submitButton) {
            this.elements.submitButton.textContent = 'Next Question';
            this.elements.submitButton.className = 'next-btn';
            this.elements.submitButton.onclick = () => this.proceedToNext(result);
        }
        
        // Mark as completed in scenarios module (prevent duplicates)
        if (window.ScenariosModule && !window.ScenariosModule.completedScenarios.has(this.currentQuestion.id)) {
            window.ScenariosModule.markCompleted(this.currentQuestion.id);
        }
        
        // Save to database
        this.saveQuestionResult(result);
    },
    
    /**
     * Proceed to next question or complete quiz
     */
    proceedToNext(result) {
        this.hideQuestion();
        this.resumeSimulation(result);
    },
    
    /**
     * Show answer feedback
     */
    showAnswerFeedback(result) {
        if (!this.elements.questionFeedback) return;
        
        // Style feedback based on correctness
        const isCorrect = result.isCorrect;
        const feedbackClass = isCorrect ? 'correct' : 'incorrect';
        const icon = isCorrect ? '✅' : '❌';
        const title = isCorrect ? 'Correct!' : 'Incorrect';
        
        this.elements.questionFeedback.innerHTML = `
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 24px; margin-right: 10px;">${icon}</span>
                <strong style="font-size: 18px; color: ${isCorrect ? '#28a745' : '#dc3545'};">${title}</strong>
            </div>
            <p style="margin-bottom: 10px; font-weight: bold;">
                Correct Answer: ${this.currentQuestion.options[result.correctOption]}
            </p>
            <p style="margin-bottom: 10px; line-height: 1.4;">
                ${result.explanation}
            </p>
            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #007bff;">
                <small><strong>Remember:</strong> ${result.context}</small>
            </div>
            <div style="text-align: center; margin-top: 15px; font-weight: bold; color: #28a745;">
                Points Earned: ${result.points}
            </div>
        `;
        
        this.elements.questionFeedback.style.display = 'block';
        this.elements.questionFeedback.className = `question-feedback ${feedbackClass}`;
        
        // Update option button styles
        this.updateOptionStyles(result);
    },
    
    /**
     * Update option button styles after answer
     */
    updateOptionStyles(result) {
        const allOptions = this.elements.questionOptions.querySelectorAll('.option-btn');
        
        allOptions.forEach((btn, index) => {
            btn.disabled = true;
            
            if (index === result.correctOption) {
                btn.classList.add('correct');
            } else if (index === result.selectedOption && !result.isCorrect) {
                btn.classList.add('incorrect');
            }
        });
    },
    
    /**
     * Hide question modal (with loop prevention and proper cleanup)
     */
    hideQuestion() {
        if (this.isQuestionVisible && this.elements.questionModal) {
            console.log('🔚 Hiding question modal...');
            this.elements.questionModal.style.display = 'none';
            
            // Reset button state for next question
            if (this.elements.submitButton) {
                this.elements.submitButton.textContent = 'Submit Answer';
                this.elements.submitButton.className = 'submit-btn';
                this.elements.submitButton.disabled = true;
                this.elements.submitButton.onclick = null;
            }
            
            // Hide feedback
            if (this.elements.questionFeedback) {
                this.elements.questionFeedback.style.display = 'none';
            }
            
            // Clean up state in proper order
            this.isQuestionVisible = false;
            this.selectedOption = -1;
            
            // Keep currentQuestion for a moment to allow proper cleanup
            setTimeout(() => {
                this.currentQuestion = null;
                console.log('✅ Question modal hidden successfully');
            }, 100);
        }
    },
    
    /**
     * Resume simulation after question with error handling
     */
    resumeSimulation(result) {
        // Validate that we have a current question
        if (!this.currentQuestion || !this.currentQuestion.id) {
            console.error('❌ No valid current question when resuming simulation:', this.currentQuestion);
            return;
        }
        
        // Notify main simulation
        if (window.SimulationMain && window.SimulationMain.handleQuestionAnswered) {
            window.SimulationMain.handleQuestionAnswered(
                this.currentQuestion, 
                result.selectedOption, 
                result.isCorrect
            );
        }
        
        // Resume game engine
        if (window.GameEngine) {
            window.GameEngine.resumeFromScenario(this.currentQuestion.id);
        }
    },
    
    /**
     * Handle keyboard input during questions
     */
    handleQuestionKeyboard(e) {
        if (!this.isQuestionVisible) return;
        
        // Number keys 1-4 for options
        const keyNumber = parseInt(e.key);
        if (keyNumber >= 1 && keyNumber <= 4) {
            const optionIndex = keyNumber - 1;
            if (optionIndex < this.currentQuestion.options.length) {
                this.selectOption(optionIndex);
                e.preventDefault();
            }
        }
        
        // Enter to submit
        if (e.key === 'Enter' && this.selectedOption !== -1) {
            this.handleSubmitAnswer();
            e.preventDefault();
        }
    },
    
    /**
     * Update score display
     */
    updateScore(score) {
        if (this.elements.scoreDisplay) {
            this.elements.scoreDisplay.textContent = score;
        }
    },
    
    /**
     * Update scenario progress display
     */
    updateScenarioProgress(completed, total) {
        if (this.elements.scenarioDisplay) {
            this.elements.scenarioDisplay.textContent = `${completed}/${total}`;
        }
    },
    
    /**
     * Update speed display
     */
    updateSpeed(speed) {
        if (this.elements.speedDisplay) {
            this.elements.speedDisplay.textContent = Math.round(speed);
        }
    },
    
    /**
     * Save question result to database
     */
    saveQuestionResult(result) {
        if (!window.GameStats) {
            console.warn('GameStats module not available for saving');
            return;
        }
        
        window.GameStats.saveScenarioResult(result);
    },
    
    /**
     * Show completion screen with Proceed button
     */
    showCompletionScreen(finalResults) {
        console.log('🏁 Showing completion screen with Proceed option');
        
        // Create completion modal
        const completionHTML = `
            <div class="completion-modal" id="completionModal" style="
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.9); display: flex; justify-content: center; align-items: center;
                z-index: 3000;
            ">
                <div class="completion-content" style="
                    background: white; border-radius: 15px; padding: 40px; max-width: 600px; width: 90%;
                    text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                ">
                    <h2 style="color: #28a745; margin-bottom: 20px;">🎉 All 5 Scenarios Complete!</h2>
                    <div style="font-size: 18px; margin-bottom: 30px;">
                        <p><strong>Quiz Responses:</strong> ${finalResults.scenariosCompleted}/5 scenarios answered</p>
                        <p><strong>Estimated Score:</strong> ${finalResults.score} points</p>
                        <p><strong>Time Taken:</strong> ${this.formatTime(finalResults.totalTime)}</p>
                    </div>
                    <div style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <p style="margin: 0; color: #495057;">
                            <i class="fas fa-info-circle"></i> Your responses are saved temporarily. 
                            Click <strong>Proceed</strong> to save your results to the database and view detailed results.
                        </p>
                    </div>
                    <div id="loadingIndicator" style="display: none; margin: 20px 0;">
                        <div style="color: #007bff;">
                            <i class="fas fa-spinner fa-spin"></i> Saving your results to database...
                        </div>
                    </div>
                    <div id="buttonContainer">
                        <button id="proceedBtn" onclick="window.UIModule.proceedToResults()" style="
                            background: linear-gradient(45deg, #28a745, #1e7e34); color: white; border: none;
                            padding: 15px 40px; border-radius: 8px; font-size: 18px; cursor: pointer;
                            margin: 10px; font-weight: bold; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
                        ">Proceed to Results</button>
                        <button onclick="location.reload()" style="
                            background: linear-gradient(45deg, #6c757d, #545b62); color: white; border: none;
                            padding: 15px 30px; border-radius: 8px; font-size: 16px; cursor: pointer;
                            margin: 10px;
                        ">Retake Quiz</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', completionHTML);
        
        // Store final results for later use
        this.pendingResults = finalResults;
    },

    /**
     * Proceed to save results and redirect to results page
     */
    async proceedToResults() {
        console.log('🚀 User clicked Proceed - saving to database...');
        
        // Show loading indicator
        const loadingIndicator = document.getElementById('loadingIndicator');
        const buttonContainer = document.getElementById('buttonContainer');
        const proceedBtn = document.getElementById('proceedBtn');
        
        if (loadingIndicator) loadingIndicator.style.display = 'block';
        if (proceedBtn) proceedBtn.disabled = true;
        
        try {
            // Save final results to database
            if (window.GameStats && this.pendingResults) {
                const saveResult = await window.GameStats.saveFinalResults(this.pendingResults);
                
                if (saveResult && saveResult.success) {
                    console.log('✅ Results saved to database successfully!');
                    
                    // Show success message briefly
                    if (loadingIndicator) {
                        loadingIndicator.innerHTML = '<div style="color: #28a745;"><i class="fas fa-check-circle"></i> Results saved successfully!</div>';
                    }
                    
                    // Redirect to results page after short delay
                    setTimeout(() => {
                        window.location.href = 'simulation_result.php';
                    }, 1500);
                } else {
                    console.error('❌ Database save failed:', saveResult?.error);
                    this.showSaveError('Failed to save results to database. Please try again.');
                }
            } else {
                console.error('❌ No GameStats module or pending results available');
                this.showSaveError('Unable to save results. Missing required data.');
            }
        } catch (error) {
            console.error('❌ Error during save process:', error);
            this.showSaveError('An error occurred while saving. Please try again.');
        }
    },

    /**
     * Show save error message
     */
    showSaveError(message) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        const proceedBtn = document.getElementById('proceedBtn');
        
        if (loadingIndicator) {
            loadingIndicator.style.display = 'block';
            loadingIndicator.innerHTML = `<div style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> ${message}</div>`;
        }
        
        if (proceedBtn) {
            proceedBtn.disabled = false;
            proceedBtn.textContent = 'Try Again';
        }
    },
    
    /**
     * Get grade display based on accuracy
     */
    getGradeDisplay(accuracy) {
        let grade, color, message;
        
        if (accuracy >= 90) {
            grade = 'A'; color = '#28a745'; message = 'Excellent driving knowledge!';
        } else if (accuracy >= 80) {
            grade = 'B'; color = '#17a2b8'; message = 'Good understanding of traffic rules!';
        } else if (accuracy >= 70) {
            grade = 'C'; color = '#ffc107'; message = 'Fair performance, keep practicing!';
        } else if (accuracy >= 60) {
            grade = 'D'; color = '#fd7e14'; message = 'Needs improvement, review traffic rules!';
        } else {
            grade = 'F'; color = '#dc3545'; message = 'Please study traffic rules more carefully!';
        }
        
        return `
            <div style="font-size: 48px; font-weight: bold; color: ${color}; margin-bottom: 10px;">
                Grade: ${grade}
            </div>
            <p style="color: ${color}; font-weight: bold;">${message}</p>
        `;
    },
    
    /**
     * Format time display
     */
    formatTime(milliseconds) {
        const seconds = Math.floor(milliseconds / 1000);
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    },
    
    /**
     * Render game UI elements on canvas
     */
    renderGame(ctx, camera) {
        // This can be used for any in-game UI overlays
        // For now, most UI is handled via HTML elements
        
        if (window.SimulationConfig?.debug) {
            this.renderDebugOverlay(ctx);
        }
    },
    
    /**
     * Render debug overlay
     */
    renderDebugOverlay(ctx) {
        ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
        ctx.fillRect(10, 10, 200, 100);
        
        ctx.fillStyle = '#00FF00';
        ctx.font = '12px monospace';
        ctx.textAlign = 'left';
        
        const debugInfo = [
            `Question Visible: ${this.isQuestionVisible}`,
            `Selected Option: ${this.selectedOption}`,
            `Current Question: ${this.currentQuestion?.id || 'None'}`
        ];
        
        debugInfo.forEach((info, index) => {
            ctx.fillText(info, 15, 30 + index * 15);
        });
    },
    
    /**
     * Reset UI state
     */
    reset() {
        this.hideQuestion();
        this.selectedOption = -1;
        this.currentQuestion = null;
        this.isQuestionVisible = false;
        
        // Reset displays
        this.updateScore(0);
        this.updateScenarioProgress(0, 5);
        this.updateSpeed(0);
        
        console.log('🔄 UI reset complete');
    }
};

// Export module
window.UIModule = UIModule;
