/**
 * Message System Module - Real-time AJAX Message Display
 * Handles violation messages and real-time notifications
 */

const MessageSystem = {
    messageContainer: null,
    activeMessages: [],
    messageId: 0,
    
    /**
     * Initialize the message system
     */
    init() {
        this.createMessageContainer();
        console.log('📢 Message system initialized');
    },
    
    /**
     * Create the message container
     */
    createMessageContainer() {
        // Remove existing container if any
        const existing = document.getElementById('message-container');
        if (existing) {
            existing.remove();
        }
        
        // Create new message container
        this.messageContainer = document.createElement('div');
        this.messageContainer.id = 'message-container';
        this.messageContainer.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            pointer-events: none;
        `;
        
        document.body.appendChild(this.messageContainer);
    },
    
    /**
     * Show a violation message
     */
    showViolationMessage(type, message, severity = 'warning') {
        const messageData = {
            id: ++this.messageId,
            type: type,
            message: message,
            severity: severity,
            timestamp: Date.now()
        };
        
        this.displayMessage(messageData);
        this.logViolation(messageData);
        
        // Auto-remove after 4 seconds
        setTimeout(() => {
            this.removeMessage(messageData.id);
        }, 4000);
    },
    
    /**
     * Display a message visually
     */
    displayMessage(messageData) {
        const messageElement = document.createElement('div');
        messageElement.id = `message-${messageData.id}`;
        messageElement.className = `violation-message ${messageData.severity}`;
        
        // Set styling based on severity
        let backgroundColor, borderColor, icon;
        switch(messageData.severity) {
            case 'error':
                backgroundColor = '#F44336';
                borderColor = '#D32F2F';
                icon = '⚠️';
                break;
            case 'warning':
                backgroundColor = '#FF9800';
                borderColor = '#F57C00';
                icon = '⚠️';
                break;
            case 'info':
                backgroundColor = '#2196F3';
                borderColor = '#1976D2';
                icon = 'ℹ️';
                break;
            default:
                backgroundColor = '#FF9800';
                borderColor = '#F57C00';
                icon = '⚠️';
        }
        
        messageElement.style.cssText = `
            background: ${backgroundColor};
            color: white;
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid ${borderColor};
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            animation: slideInRight 0.3s ease-out;
            pointer-events: auto;
            cursor: pointer;
        `;
        
        messageElement.innerHTML = `
            <div style="display: flex; align-items: center;">
                <span style="font-size: 18px; margin-right: 8px;">${icon}</span>
                <div>
                    <div style="font-weight: bold; margin-bottom: 2px;">${messageData.type}</div>
                    <div style="font-weight: normal; opacity: 0.9;">${messageData.message}</div>
                </div>
                <span style="margin-left: auto; font-size: 18px; cursor: pointer;" onclick="MessageSystem.removeMessage(${messageData.id})">×</span>
            </div>
        `;
        
        // Add animation CSS if not already added
        if (!document.getElementById('message-animations')) {
            const style = document.createElement('style');
            style.id = 'message-animations';
            style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        this.messageContainer.appendChild(messageElement);
        this.activeMessages.push(messageData);
        
        // Add click to dismiss
        messageElement.addEventListener('click', () => {
            this.removeMessage(messageData.id);
        });
    },
    
    /**
     * Remove a message
     */
    removeMessage(messageId) {
        const messageElement = document.getElementById(`message-${messageId}`);
        if (messageElement) {
            messageElement.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (messageElement.parentNode) {
                    messageElement.parentNode.removeChild(messageElement);
                }
            }, 300);
        }
        
        // Remove from active messages
        this.activeMessages = this.activeMessages.filter(msg => msg.id !== messageId);
    },
    
    /**
     * Log violation to server via AJAX
     */
    logViolation(messageData) {
        // Send violation to server for tracking
        fetch('../log_violation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: messageData.type,
                message: messageData.message,
                severity: messageData.severity,
                timestamp: messageData.timestamp,
                session_id: this.getSessionId()
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('📝 Violation logged:', data);
        })
        .catch(error => {
            console.warn('⚠️ Failed to log violation:', error);
        });
    },
    
    /**
     * Get session ID for tracking
     */
    getSessionId() {
        // Try to get session ID from existing session or generate one
        let sessionId = sessionStorage.getItem('driving_session_id');
        if (!sessionId) {
            sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('driving_session_id', sessionId);
        }
        return sessionId;
    },
    
    /**
     * Clear all messages
     */
    clearAllMessages() {
        this.activeMessages.forEach(msg => {
            this.removeMessage(msg.id);
        });
    }
};

// Make it globally available
window.MessageSystem = MessageSystem;