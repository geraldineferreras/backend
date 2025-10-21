import { useState, useEffect, useCallback, useRef } from 'react';

/**
 * Custom React Hook for Server-Sent Events Notifications
 * Usage: const { notifications, isConnected, error } = useNotifications(token);
 */
export const useNotifications = (authToken) => {
  const [notifications, setNotifications] = useState([]);
  const [isConnected, setIsConnected] = useState(false);
  const [error, setError] = useState(null);
  const [connectionStatus, setConnectionStatus] = useState('disconnected');
  
  const eventSourceRef = useRef(null);
  const reconnectTimeoutRef = useRef(null);
  const reconnectAttempts = useRef(0);
  const maxReconnectAttempts = 5;
  const baseReconnectDelay = 1000; // 1 second

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

    const baseUrl = process.env.REACT_APP_API_URL || 'https://scms-backend.up.railway.app';
    const url = `${baseUrl}/api/notifications/stream`;

    try {
      const eventSource = new EventSource(url, {
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Accept': 'text/event-stream'
        }
      });

      eventSourceRef.current = eventSource;
      setConnectionStatus('connecting');

      // Connection opened
      eventSource.onopen = () => {
        console.log('SSE Connection opened');
        setIsConnected(true);
        setConnectionStatus('connected');
        setError(null);
        reconnectAttempts.current = 0;
      };

      // Handle different event types
      eventSource.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          console.log('SSE Message received:', data);
          
          // Handle different message types
          switch (data.type || 'notification') {
            case 'connected':
              console.log('Connected to notifications stream:', data);
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
                return [newNotification, ...prev.slice(0, 99)]; // Keep last 100 notifications
              });
              break;
            case 'heartbeat':
              console.log('Heartbeat received:', data);
              break;
            case 'error':
              console.error('SSE Error:', data);
              setError(data.message || 'Unknown error');
              break;
            default:
              console.log('Unknown SSE message type:', data);
          }
        } catch (parseError) {
          console.error('Error parsing SSE message:', parseError);
        }
      };

      // Handle specific event types
      eventSource.addEventListener('connected', (event) => {
        console.log('Connected event:', event.data);
      });

      eventSource.addEventListener('notification', (event) => {
        try {
          const notification = JSON.parse(event.data);
          setNotifications(prev => [notification, ...prev.slice(0, 99)]);
        } catch (error) {
          console.error('Error parsing notification:', error);
        }
      });

      eventSource.addEventListener('heartbeat', (event) => {
        console.log('Heartbeat:', event.data);
      });

      eventSource.addEventListener('error', (event) => {
        console.error('SSE Error event:', event);
        setError('Connection error');
      });

      // Connection error
      eventSource.onerror = (event) => {
        console.error('SSE Connection error:', event);
        setIsConnected(false);
        setConnectionStatus('error');
        
        // Attempt to reconnect
        if (reconnectAttempts.current < maxReconnectAttempts) {
          const delay = baseReconnectDelay * Math.pow(2, reconnectAttempts.current);
          reconnectAttempts.current++;
          
          console.log(`Attempting to reconnect in ${delay}ms (attempt ${reconnectAttempts.current}/${maxReconnectAttempts})`);
          
          reconnectTimeoutRef.current = setTimeout(() => {
            connect();
          }, delay);
        } else {
          setError('Failed to reconnect after multiple attempts');
          setConnectionStatus('failed');
        }
      };

    } catch (error) {
      console.error('Error creating EventSource:', error);
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

    // Cleanup on unmount
    return () => {
      cleanup();
    };
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
    reconnectAttempts: reconnectAttempts.current
  };
};

export default useNotifications;
