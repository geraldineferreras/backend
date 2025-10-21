/**
 * Vanilla JavaScript Server-Sent Events Notifications
 * No framework dependencies - works with any frontend setup
 */

class NotificationManager {
  constructor(options = {}) {
    this.baseUrl = options.baseUrl || 'https://scms-backend.up.railway.app';
    this.authToken = options.authToken || null;
    this.eventSource = null;
    this.notifications = [];
    this.isConnected = false;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.baseReconnectDelay = 1000;
    this.reconnectTimeout = null;
    
    // Callbacks
    this.onNotification = options.onNotification || (() => {});
    this.onConnect = options.onConnect || (() => {});
    this.onDisconnect = options.onDisconnect || (() => {});
    this.onError = options.onError || (() => {});
    
    // Auto-connect if token is provided
    if (this.authToken) {
      this.connect();
    }
  }

  /**
   * Set authentication token
   */
  setAuthToken(token) {
    this.authToken = token;
    if (token) {
      this.connect();
    } else {
      this.disconnect();
    }
  }

  /**
   * Connect to the SSE stream
   */
  connect() {
    if (!this.authToken) {
      console.error('No authentication token provided');
      this.onError('No authentication token provided');
      return;
    }

    // Cleanup existing connection
    this.disconnect();

    const url = `${this.baseUrl}/api/notifications/stream`;
    
    try {
      // Note: EventSource doesn't support custom headers in all browsers
      // For production, you might need to use fetch with ReadableStream or
      // implement token in URL parameter
      this.eventSource = new EventSource(url);
      
      console.log('Connecting to SSE stream...');

      // Connection opened
      this.eventSource.onopen = (event) => {
        console.log('SSE Connection opened');
        this.isConnected = true;
        this.reconnectAttempts = 0;
        this.onConnect();
      };

      // Handle messages
      this.eventSource.onmessage = (event) => {
        this.handleMessage(event);
      };

      // Handle specific event types
      this.eventSource.addEventListener('connected', (event) => {
        console.log('Connected event:', event.data);
      });

      this.eventSource.addEventListener('notification', (event) => {
        try {
          const notification = JSON.parse(event.data);
          this.addNotification(notification);
        } catch (error) {
          console.error('Error parsing notification:', error);
        }
      });

      this.eventSource.addEventListener('heartbeat', (event) => {
        console.log('Heartbeat:', event.data);
      });

      this.eventSource.addEventListener('error', (event) => {
        console.error('SSE Error event:', event);
        this.handleError('Connection error');
      });

      // Connection error
      this.eventSource.onerror = (event) => {
        console.error('SSE Connection error:', event);
        this.isConnected = false;
        this.onDisconnect();
        this.handleReconnect();
      };

    } catch (error) {
      console.error('Error creating EventSource:', error);
      this.onError('Failed to create connection');
    }
  }

  /**
   * Handle incoming messages
   */
  handleMessage(event) {
    try {
      const data = JSON.parse(event.data);
      console.log('SSE Message received:', data);
      
      switch (data.type || 'notification') {
        case 'connected':
          console.log('Connected to notifications stream:', data);
          break;
        case 'notification':
          this.addNotification(data);
          break;
        case 'heartbeat':
          console.log('Heartbeat received:', data);
          break;
        case 'error':
          console.error('SSE Error:', data);
          this.onError(data.message || 'Unknown error');
          break;
        default:
          console.log('Unknown SSE message type:', data);
      }
    } catch (parseError) {
      console.error('Error parsing SSE message:', parseError);
    }
  }

  /**
   * Add notification to the list
   */
  addNotification(notificationData) {
    const notification = {
      id: notificationData.id || Date.now(),
      title: notificationData.title || 'New Notification',
      message: notificationData.message,
      type: notificationData.notification_type || 'info',
      timestamp: notificationData.created_at || new Date().toISOString(),
      isRead: false,
      ...notificationData
    };

    this.notifications.unshift(notification);
    
    // Keep only last 100 notifications
    if (this.notifications.length > 100) {
      this.notifications = this.notifications.slice(0, 100);
    }

    // Call the notification callback
    this.onNotification(notification);
  }

  /**
   * Handle reconnection logic
   */
  handleReconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      const delay = this.baseReconnectDelay * Math.pow(2, this.reconnectAttempts);
      this.reconnectAttempts++;
      
      console.log(`Attempting to reconnect in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
      
      this.reconnectTimeout = setTimeout(() => {
        this.connect();
      }, delay);
    } else {
      console.error('Failed to reconnect after multiple attempts');
      this.onError('Failed to reconnect after multiple attempts');
    }
  }

  /**
   * Handle errors
   */
  handleError(message) {
    console.error('NotificationManager Error:', message);
    this.onError(message);
  }

  /**
   * Disconnect from the SSE stream
   */
  disconnect() {
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = null;
    }
    
    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
      this.reconnectTimeout = null;
    }
    
    this.isConnected = false;
    this.onDisconnect();
  }

  /**
   * Mark notification as read
   */
  markAsRead(notificationId) {
    const notification = this.notifications.find(n => n.id === notificationId);
    if (notification) {
      notification.isRead = true;
    }
  }

  /**
   * Mark all notifications as read
   */
  markAllAsRead() {
    this.notifications.forEach(notification => {
      notification.isRead = true;
    });
  }

  /**
   * Get unread notifications count
   */
  getUnreadCount() {
    return this.notifications.filter(n => !n.isRead).length;
  }

  /**
   * Get all notifications
   */
  getNotifications() {
    return [...this.notifications];
  }

  /**
   * Clear all notifications
   */
  clearNotifications() {
    this.notifications = [];
  }

  /**
   * Destroy the notification manager
   */
  destroy() {
    this.disconnect();
    this.notifications = [];
    this.onNotification = null;
    this.onConnect = null;
    this.onDisconnect = null;
    this.onError = null;
  }
}

// Usage Examples:

// Example 1: Basic usage
function setupBasicNotifications() {
  const notificationManager = new NotificationManager({
    authToken: 'your-jwt-token-here',
    baseUrl: 'https://scms-backend.up.railway.app',
    onNotification: (notification) => {
      console.log('New notification:', notification);
      // Show browser notification
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(notification.title, {
          body: notification.message,
          icon: '/notification-icon.png'
        });
      }
    },
    onConnect: () => {
      console.log('Connected to notifications stream');
    },
    onDisconnect: () => {
      console.log('Disconnected from notifications stream');
    },
    onError: (error) => {
      console.error('Notifications error:', error);
    }
  });

  return notificationManager;
}

// Example 2: With UI integration
function setupNotificationsWithUI() {
  const notificationBell = document.getElementById('notification-bell');
  const notificationCount = document.getElementById('notification-count');
  const notificationList = document.getElementById('notification-list');

  const notificationManager = new NotificationManager({
    authToken: localStorage.getItem('authToken'),
    onNotification: (notification) => {
      // Update UI
      updateNotificationCount();
      addNotificationToUI(notification);
      
      // Show browser notification
      showBrowserNotification(notification);
    },
    onConnect: () => {
      notificationBell.classList.add('connected');
    },
    onDisconnect: () => {
      notificationBell.classList.remove('connected');
    },
    onError: (error) => {
      showError(error);
    }
  });

  function updateNotificationCount() {
    const count = notificationManager.getUnreadCount();
    notificationCount.textContent = count;
    notificationCount.style.display = count > 0 ? 'block' : 'none';
  }

  function addNotificationToUI(notification) {
    const notificationElement = document.createElement('div');
    notificationElement.className = `notification-item ${!notification.isRead ? 'unread' : ''}`;
    notificationElement.innerHTML = `
      <div class="notification-content">
        <h4>${notification.title}</h4>
        <p>${notification.message}</p>
        <span class="notification-time">${formatTime(notification.timestamp)}</span>
      </div>
    `;
    
    notificationElement.addEventListener('click', () => {
      notificationManager.markAsRead(notification.id);
      updateNotificationCount();
    });
    
    notificationList.insertBefore(notificationElement, notificationList.firstChild);
  }

  function showBrowserNotification(notification) {
    if ('Notification' in window) {
      if (Notification.permission === 'granted') {
        new Notification(notification.title, {
          body: notification.message,
          icon: '/notification-icon.png'
        });
      } else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(permission => {
          if (permission === 'granted') {
            new Notification(notification.title, {
              body: notification.message,
              icon: '/notification-icon.png'
            });
          }
        });
      }
    }
  }

  function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));
    
    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
    return date.toLocaleDateString();
  }

  function showError(error) {
    // Show error in UI
    const errorElement = document.createElement('div');
    errorElement.className = 'error-message';
    errorElement.textContent = error;
    document.body.appendChild(errorElement);
    
    setTimeout(() => {
      errorElement.remove();
    }, 5000);
  }

  return notificationManager;
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = NotificationManager;
}

// Global access
if (typeof window !== 'undefined') {
  window.NotificationManager = NotificationManager;
}
