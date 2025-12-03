/**
 * Dashboard Auto-Refresh Module
 * 
 * Polls the monitors API endpoint every 30 seconds to update monitor statuses
 * without requiring a full page reload. Preserves scroll position and pauses
 * polling when the tab is not visible.
 */

class DashboardRefresh {
    constructor() {
        this.pollingInterval = 30000; // 30 seconds
        this.intervalId = null;
        this.isPolling = false;
        this.apiEndpoint = '/api/monitors';
        
        this.init();
    }
    
    /**
     * Initialize the dashboard refresh functionality
     */
    init() {
        // Only run on dashboard page
        if (!this.isDashboardPage()) {
            return;
        }
        
        // Set up Page Visibility API to pause polling when tab is hidden
        this.setupVisibilityListener();
        
        // Start polling
        this.startPolling();
    }
    
    /**
     * Check if we're on the dashboard page
     */
    isDashboardPage() {
        return window.location.pathname === '/dashboard';
    }
    
    /**
     * Set up listener for page visibility changes
     */
    setupVisibilityListener() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pausePolling();
            } else {
                this.resumePolling();
            }
        });
    }
    
    /**
     * Start polling for monitor updates
     */
    startPolling() {
        if (this.isPolling) {
            return;
        }
        
        this.isPolling = true;
        this.intervalId = setInterval(() => {
            this.fetchAndUpdateMonitors();
        }, this.pollingInterval);
        
        console.log('Dashboard auto-refresh started (30s interval)');
    }
    
    /**
     * Pause polling (when tab is hidden)
     */
    pausePolling() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
            this.isPolling = false;
            console.log('Dashboard auto-refresh paused (tab hidden)');
        }
    }
    
    /**
     * Resume polling (when tab becomes visible)
     */
    resumePolling() {
        if (!this.isPolling) {
            this.startPolling();
            // Fetch immediately when resuming
            this.fetchAndUpdateMonitors();
            console.log('Dashboard auto-refresh resumed (tab visible)');
        }
    }
    
    /**
     * Fetch monitor data from API and update the DOM
     */
    async fetchAndUpdateMonitors() {
        try {
            // Store scroll position before update
            const scrollPosition = window.scrollY;
            
            const response = await fetch(this.apiEndpoint, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Update monitors in the DOM
            this.updateMonitors(data.monitors);
            
            // Restore scroll position after update
            window.scrollTo(0, scrollPosition);
            
        } catch (error) {
            // Log error but continue polling
            console.error('Error fetching monitor updates:', error);
        }
    }
    
    /**
     * Update monitor elements in the DOM
     * 
     * @param {Array} monitors - Array of monitor objects from API
     */
    updateMonitors(monitors) {
        monitors.forEach(monitor => {
            const monitorCard = document.querySelector(`[data-monitor-id="${monitor.id}"]`);
            
            if (!monitorCard) {
                return;
            }
            
            // Update status badge
            this.updateStatusBadge(monitorCard, monitor);
            
            // Update border color
            this.updateBorderColor(monitorCard, monitor);
            
            // Update last checked timestamp
            this.updateLastChecked(monitorCard, monitor);
            
            // Update response time if available
            this.updateResponseTime(monitorCard, monitor);
        });
    }
    
    /**
     * Update the status badge for a monitor
     * 
     * @param {HTMLElement} card - Monitor card element
     * @param {Object} monitor - Monitor data
     */
    updateStatusBadge(card, monitor) {
        const badge = card.querySelector('[data-status-badge]');
        
        if (!badge) {
            return;
        }
        
        // Remove all status classes
        badge.classList.remove(
            'bg-green-100', 'text-green-800',
            'bg-red-100', 'text-red-800',
            'bg-yellow-100', 'text-yellow-800'
        );
        
        // Add appropriate status class
        if (monitor.status === 'up') {
            badge.classList.add('bg-green-100', 'text-green-800');
        } else if (monitor.status === 'down') {
            badge.classList.add('bg-red-100', 'text-red-800');
        } else {
            badge.classList.add('bg-yellow-100', 'text-yellow-800');
        }
        
        // Update badge text
        badge.textContent = monitor.status.charAt(0).toUpperCase() + monitor.status.slice(1);
    }
    
    /**
     * Update the border color for a monitor card
     * 
     * @param {HTMLElement} card - Monitor card element
     * @param {Object} monitor - Monitor data
     */
    updateBorderColor(card, monitor) {
        // Remove all border color classes
        card.classList.remove(
            'border-green-500',
            'border-red-500', 'bg-red-50',
            'border-yellow-500'
        );
        
        // Add appropriate border color
        if (monitor.status === 'up') {
            card.classList.add('border-green-500');
        } else if (monitor.status === 'down') {
            card.classList.add('border-red-500', 'bg-red-50');
        } else {
            card.classList.add('border-yellow-500');
        }
    }
    
    /**
     * Update the last checked timestamp
     * 
     * @param {HTMLElement} card - Monitor card element
     * @param {Object} monitor - Monitor data
     */
    updateLastChecked(card, monitor) {
        const timestamp = card.querySelector('[data-last-checked]');
        
        if (!timestamp) {
            return;
        }
        
        if (monitor.last_checked_at) {
            timestamp.textContent = monitor.last_checked_at_human;
        } else {
            timestamp.textContent = 'Not checked yet';
            timestamp.classList.add('text-gray-400');
        }
    }
    
    /**
     * Update the response time display
     * 
     * @param {HTMLElement} card - Monitor card element
     * @param {Object} monitor - Monitor data
     */
    updateResponseTime(card, monitor) {
        const responseTime = card.querySelector('[data-response-time]');
        
        if (!responseTime) {
            return;
        }
        
        if (monitor.latest_response_time_ms) {
            responseTime.textContent = `${monitor.latest_response_time_ms}ms`;
            responseTime.parentElement.style.display = '';
        } else {
            responseTime.parentElement.style.display = 'none';
        }
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new DashboardRefresh();
    });
} else {
    new DashboardRefresh();
}

export default DashboardRefresh;
