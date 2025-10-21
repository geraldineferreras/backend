import React, { useState, useEffect } from 'react';
import NotificationBell from './NotificationComponent';
import './App.css';

function App() {
  const [authToken, setAuthToken] = useState(null);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [user, setUser] = useState(null);

  // Check for existing token on app load
  useEffect(() => {
    const token = localStorage.getItem('authToken');
    if (token) {
      setAuthToken(token);
      setIsLoggedIn(true);
      // You might want to validate the token here
    }
  }, []);

  const handleLogin = (token) => {
    localStorage.setItem('authToken', token);
    setAuthToken(token);
    setIsLoggedIn(true);
  };

  const handleLogout = () => {
    localStorage.removeItem('authToken');
    setAuthToken(null);
    setIsLoggedIn(false);
    setUser(null);
  };

  const handleNotificationClick = (notification) => {
    console.log('Notification clicked:', notification);
    
    // Handle different notification types
    switch (notification.type) {
      case 'assignment':
        // Navigate to assignment page
        console.log('Navigate to assignment:', notification.assignment_id);
        break;
      case 'grade':
        // Navigate to grades page
        console.log('Navigate to grades:', notification.grade_id);
        break;
      case 'announcement':
        // Navigate to announcement
        console.log('Navigate to announcement:', notification.announcement_id);
        break;
      default:
        console.log('Generic notification action');
    }
  };

  if (!isLoggedIn) {
    return <LoginForm onLogin={handleLogin} />;
  }

  return (
    <div className="App">
      <header className="app-header">
        <div className="header-content">
          <h1>SCMS Dashboard</h1>
          <div className="header-actions">
            <NotificationBell 
              authToken={authToken} 
              onNotificationClick={handleNotificationClick}
            />
            <button onClick={handleLogout} className="logout-btn">
              Logout
            </button>
          </div>
        </div>
      </header>

      <main className="app-main">
        <div className="dashboard-grid">
          <div className="dashboard-card">
            <h2>Welcome to SCMS</h2>
            <p>Your real-time notifications are now active!</p>
            <p>Click the notification bell to see incoming notifications.</p>
          </div>

          <div className="dashboard-card">
            <h3>Recent Activity</h3>
            <p>Real-time updates will appear here as notifications arrive.</p>
          </div>

          <div className="dashboard-card">
            <h3>Quick Actions</h3>
            <div className="action-buttons">
              <button className="action-btn">View Assignments</button>
              <button className="action-btn">Check Grades</button>
              <button className="action-btn">Read Announcements</button>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}

// Login Form Component
function LoginForm({ onLogin }) {
  const [token, setToken] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!token.trim()) {
      alert('Please enter a JWT token');
      return;
    }

    setIsLoading(true);
    
    try {
      // Validate token by making a test request
      const response = await fetch('https://scms-backend.up.railway.app/api/auth/validate-token', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (response.ok) {
        const data = await response.json();
        onLogin(token);
      } else {
        throw new Error('Invalid token');
      }
    } catch (error) {
      alert('Invalid token. Please check your JWT token and try again.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="login-container">
      <div className="login-form">
        <h2>SCMS Login</h2>
        <p>Enter your JWT token to connect to real-time notifications</p>
        
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="token">JWT Token:</label>
            <input
              type="password"
              id="token"
              value={token}
              onChange={(e) => setToken(e.target.value)}
              placeholder="Enter your JWT token"
              required
            />
          </div>
          
          <button 
            type="submit" 
            className="login-btn"
            disabled={isLoading}
          >
            {isLoading ? 'Validating...' : 'Connect'}
          </button>
        </form>

        <div className="login-help">
          <h4>How to get your JWT token:</h4>
          <ol>
            <li>Login to your SCMS account</li>
            <li>Open browser developer tools (F12)</li>
            <li>Go to Application/Storage tab</li>
            <li>Look for the stored JWT token</li>
            <li>Copy and paste it here</li>
          </ol>
        </div>
      </div>
    </div>
  );
}

export default App;
