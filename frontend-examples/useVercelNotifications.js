import { useState, useEffect, useCallback, useRef } from 'react';

/**
 * Vercel-optimized React Hook for Railway Backend SSE Notifications
 * Handles CORS, environment variables, and Vercel-specific configurations
 */
export const useVercelNotifications = (authToken) => {
  const [notifications, setNotifications] = useState([]);
  const [isConnected, setIsConnected] = useState(false);
  const [error, setError] = useState(null);
  const [connectionStatus, setConnectionStatus] = useState('disconnected');
  
  const eventSourceRef = useRef(null);
  const reconnectTimeoutRef = useRef(null);
  const reconnectAttempts = useRef(0);
  const maxReconnectAttempts = 5;
  const baseReconnectDelay = 1000;

  // Get API URL from environment or default to Railway
  const getApiUrl = () => {
    return process.env.REACT_APP_API_URL || 'https://scms-backend.up.railway.app';
  };

  // Cleanup function
  const cleanup = useCallback(() => {
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
      eventSourceRef.current = null;
    }
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
      reconnectTimeoutRef.current = null;
    }
  }, []);

  // Connect to SSE stream
  const connect = useCallback(() => {
    if (!authToken) {
      setError('No authentication token provided');
      return;
    }

    // Cleanup existing connection
    cleanup();

    const baseUrl = getApiUrl();
    const url = `${baseUrl}/api/notifications/stream`;

    // Debug logging for Vercel
    if (process.env.REACT_APP_DEBUG_NOTIFICATIONS === 'true') {
      console.log('Vercel Notifications: Connecting to', url);
    }

    try {
      // Note: EventSource doesn't support custom headers in all browsers
      // For production, we need to handle authentication differently
      const eventSource = new EventSource(url);
      eventSourceRef.current = eventSource;
      setConnectionStatus('connecting');

      // Connection opened
      eventSource.onopen = () => {
        if (process.env.REACT_APP_DEBUG_NOTIFICATIONS === 'true') {
          console.log('Vercel Notifications: SSE Connection opened');
        }
        setIsConnected(true);
        setConnectionStatus('connected');
        setError(null);
        reconnectAttempts.current = 0;
      };

      // Handle messages
      eventSource.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          
          if (process.env.REACT_APP_DEBUG_NOTIFICATIONS === 'true') {
            console.log('Vercel Notifications: Message received:', data);
          }
          
          // Handle different message types
          switch (data.type || 'notification') {
            case 'connected':
              console.log('Vercel Notifications: Connected to stream:', data);
              break;
            case 'notification':
              setNotifications(prev => {
                const newNotification = {
                  id: data.id || Date.now(),
                  title: data.title || 'New Notification',
                  message: data.message,
                  type: data.notification_type || 'info',
                  timestamp: data.created_at || new Date().toISOString(),
                  isRead: false,
                  ...data
                };
                return [newNotification, ...prev.slice(0, 99)];
              });
              break;
            case 'heartbeat':
              if (process.env.REACT_APP_DEBUG_NOTIFICATIONS === 'true') {
                console.log('Vercel Notifications: Heartbeat received');
              }
              break;
            case 'error':
              console.error('Vercel Notifications: SSE Error:', data);
              setError(data.message || 'Unknown error');
              break;
            default:
              if (process.env.REACT_APP_DEBUG_NOTIFICATIONS === 'true') {
                console.log('Vercel Notifications: Unknown message type:', data);
              }
          }
        } catch (parseError) {
          console.error('Vercel Notifications: Error parsing message:', parseError);
        }
      };

      // Handle specific event types
      eventSource.addEventListener('connected', (event) => {
        if (process.env.REACT_APP_DEBUG_NOTIFICATIONS === 'true') {
          console.log('Vercel Notifications: Connected event:', event.data);
        }
      });

      eventSource.addEventListener('notification', (event) => {
        try {
          const notification = JSON.parse(event.data);
          setNotifications(prev => [notification, ...prev.slice(0, 99)]);
        } catch (error) {
          console.error('Vercel Notifications: Error parsing notification:', error);
        }
      });

      eventSource.addEventListener('heartbeat', (event) => {
        if (process.env.REACT_APP_DEBUG_NOTIFICATIONS === 'true') {
          console.log('Vercel Notifications: Heartbeat:', event.data);
        }
      });

      eventSource.addEventListener('error', (event) => {
        console.error('Vercel Notifications: Error event:', event);
        setError('Connection error');
      });

      // Connection error
      eventSource.onerror = (event) => {
        console.error('Vercel Notifications: Connection error:', event);
        setIsConnected(false);
        setConnectionStatus('error');
        
        // Attempt to reconnect
        if (reconnectAttempts.current < maxReconnectAttempts) {
          const delay = baseReconnectDelay * Math.pow(2, reconnectAttempts.current);
          reconnectAttempts.current++;
          
          if (process.env.REACT_APP_DEBUG_NOTIFICATIONS === 'true') {
            console.log(`Vercel Notifications: Reconnecting in ${delay}ms (attempt ${reconnectAttempts.current}/${maxReconnectAttempts})`);
          }
          
          reconnectTimeoutRef.current = setTimeout(() => {
            connect();
          }, delay);
        } else {
          setError('Failed to reconnect after multiple attempts');
          setConnectionStatus('failed');
        }
      };

    } catch (error) {
      console.error('Vercel Notifications: Error creating EventSource:', error);
      setError('Failed to create connection');
      setConnectionStatus('failed');
    }
  }, [authToken, cleanup]);

  // Disconnect function
  const disconnect = useCallback(() => {
    cleanup();
    setIsConnected(false);
    setConnectionStatus('disconnected');
    setError(null);
  }, [cleanup]);

  // Mark notification as read
  const markAsRead = useCallback((notificationId) => {
    setNotifications(prev => 
      prev.map(notification => 
        notification.id === notificationId 
          ? { ...notification, isRead: true }
          : notification
      )
    );
  }, []);

  // Mark all notifications as read
  const markAllAsRead = useCallback(() => {
    setNotifications(prev => 
      prev.map(notification => ({ ...notification, isRead: true }))
    );
  }, []);

  // Clear all notifications
  const clearNotifications = useCallback(() => {
    setNotifications([]);
  }, []);

  // Auto-connect when token is available
  useEffect(() => {
    if (authToken) {
      connect();
    } else {
      disconnect();
    }

    return cleanup;
  }, [authToken, connect, disconnect, cleanup]);

  // Cleanup on unmount
  useEffect(() => {
    return cleanup;
  }, [cleanup]);

  return {
    notifications,
    isConnected,
    error,
    connectionStatus,
    connect,
    disconnect,
    markAsRead,
    markAllAsRead,
    clearNotifications,
    reconnectAttempts: reconnectAttempts.current,
    apiUrl: getApiUrl()
  };
};

export default useVercelNotifications;
