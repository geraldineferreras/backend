/**
 * Frontend Implementation for Role-Based Admin Hierarchy
 * This file contains React components and utilities for the SCMS frontend
 */

// ========================================
// 1. AUTHENTICATION CONTEXT UPDATE
// ========================================

// Update your existing AuthContext to handle admin_type and program
const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [permissions, setPermissions] = useState(null);

  const login = async (email, password) => {
    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();
      
      if (data.status) {
        const userData = {
          ...data.data,
          // Store the new fields
          admin_type: data.data.admin_type,
          program: data.data.program,
        };
        
        setUser(userData);
        localStorage.setItem('token', data.data.token);
        localStorage.setItem('user', JSON.stringify(userData));
        
        // Fetch user permissions
        await fetchUserPermissions();
        
        return { success: true, user: userData };
      } else {
        return { success: false, message: data.message };
      }
    } catch (error) {
      return { success: false, message: 'Login failed' };
    }
  };

  const fetchUserPermissions = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await fetch('/api/admin/get_user_permissions', {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });

      const data = await response.json();
      if (data.status) {
        setPermissions(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch permissions:', error);
    }
  };

  const logout = () => {
    setUser(null);
    setPermissions(null);
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  };

  const isMainAdmin = () => {
    return user?.role === 'admin' && user?.admin_type === 'main_admin';
  };

  const isChairperson = () => {
    return user?.role === 'chairperson';
  };

  const canCreateUser = (role) => {
    if (!permissions) return false;
    
    switch (role) {
      case 'student':
        return permissions.can_create_students;
      case 'teacher':
        return permissions.can_create_teachers;
      case 'chairperson':
        return permissions.can_create_chairpersons;
      case 'admin':
        return permissions.can_create_admins;
      default:
        return false;
    }
  };

  return (
    <AuthContext.Provider value={{
      user,
      permissions,
      login,
      logout,
      fetchUserPermissions,
      isMainAdmin,
      isChairperson,
      canCreateUser,
    }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

// ========================================
// 2. USER MANAGEMENT COMPONENT
// ========================================

const UserManagement = () => {
  const { user, permissions, isMainAdmin, isChairperson, canCreateUser } = useAuth();
  const [users, setUsers] = useState([]);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [availablePrograms, setAvailablePrograms] = useState([]);

  useEffect(() => {
    fetchUsers();
    fetchAvailablePrograms();
  }, []);

  const fetchUsers = async () => {
    try {
      const token = localStorage.getItem('token');
      let endpoint = '/api/admin/get_students'; // Default to students
      
      // Main Admin can see all users, Chairperson only sees students
      if (isMainAdmin()) {
        // You might want to create separate endpoints for different user types
        // For now, we'll use the students endpoint
        endpoint = '/api/admin/get_students';
      } else if (isChairperson()) {
        endpoint = '/api/admin/get_students'; // Chairperson only sees students in their program
      }

      const response = await fetch(endpoint, {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });

      const data = await response.json();
      if (data.status) {
        setUsers(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch users:', error);
    }
  };

  const fetchAvailablePrograms = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await fetch('/api/admin/get_available_programs', {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });

      const data = await response.json();
      if (data.status) {
        setAvailablePrograms(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch programs:', error);
    }
  };

  const handleCreateUser = async (userData) => {
    try {
      const token = localStorage.getItem('token');
      
      // Create FormData for file uploads
      const formData = new FormData();
      
      // Add text fields
      formData.append('role', userData.role);
      formData.append('full_name', userData.full_name);
      formData.append('email', userData.email);
      formData.append('password', userData.password);
      
      // Add role-specific fields
      if (userData.program) {
        formData.append('program', userData.program);
      }
      if (userData.student_num) {
        formData.append('student_num', userData.student_num);
      }
      if (userData.section_id) {
        formData.append('section_id', userData.section_id);
      }
      
      // Add files if provided
      if (userData.profile_pic) {
        formData.append('profile_pic', userData.profile_pic);
      }
      if (userData.cover_pic) {
        formData.append('cover_pic', userData.cover_pic);
      }

      const response = await fetch('/api/admin/create_user', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          // Don't set Content-Type, let browser set it for FormData
        },
        body: formData,
      });

      const data = await response.json();
      if (data.status) {
        alert('User created successfully!');
        setShowCreateForm(false);
        fetchUsers(); // Refresh the list
      } else {
        alert(`Error: ${data.message}`);
      }
    } catch (error) {
      alert('Failed to create user');
    }
  };

  return (
    <div className="user-management">
      <div className="header">
        <h2>User Management</h2>
        <div className="user-info">
          <span>Logged in as: {user?.full_name}</span>
          <span>Role: {user?.role}</span>
          {user?.admin_type && <span>Type: {user?.admin_type}</span>}
          {user?.program && <span>Program: {user?.program}</span>}
        </div>
      </div>

      {/* Role-based action buttons */}
      <div className="actions">
        {canCreateUser('student') && (
          <button 
            onClick={() => setShowCreateForm(true)}
            className="btn btn-primary"
          >
            Add Student
          </button>
        )}
        
        {canCreateUser('teacher') && (
          <button 
            onClick={() => setShowCreateForm(true)}
            className="btn btn-primary"
          >
            Add Teacher
          </button>
        )}
        
        {canCreateUser('chairperson') && (
          <button 
            onClick={() => setShowCreateForm(true)}
            className="btn btn-primary"
          >
            Add Chairperson
          </button>
        )}
      </div>

      {/* Users list */}
      <div className="users-list">
        <h3>Users</h3>
        <table className="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Program</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {users.map((user) => (
              <tr key={user.user_id}>
                <td>{user.full_name}</td>
                <td>{user.email}</td>
                <td>{user.role}</td>
                <td>{user.program || 'N/A'}</td>
                <td>{user.status}</td>
                <td>
                  <button className="btn btn-sm btn-secondary">Edit</button>
                  <button className="btn btn-sm btn-danger">Delete</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Create user form modal */}
      {showCreateForm && (
        <CreateUserForm
          availablePrograms={availablePrograms}
          onSubmit={handleCreateUser}
          onCancel={() => setShowCreateForm(false)}
          userRole={user?.role}
          userProgram={user?.program}
        />
      )}
    </div>
  );
};

// ========================================
// 3. CREATE USER FORM COMPONENT
// ========================================

const CreateUserForm = ({ availablePrograms, onSubmit, onCancel, userRole, userProgram }) => {
  const [formData, setFormData] = useState({
    role: 'student',
    full_name: '',
    email: '',
    password: '',
    program: '',
    student_num: '',
    section_id: '',
    profile_pic: null,
    cover_pic: null,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    
    // Validate based on user role
    if (userRole === 'chairperson' && formData.role !== 'student') {
      alert('Chairpersons can only create students');
      return;
    }
    
    if (userRole === 'chairperson' && formData.program !== userProgram) {
      alert('Chairpersons can only create students in their program');
      return;
    }

    onSubmit(formData);
  };

  const handleRoleChange = (role) => {
    setFormData(prev => ({
      ...prev,
      role,
      program: role === 'student' ? (userProgram || '') : '',
    }));
  };

  return (
    <div className="modal-overlay">
      <div className="modal">
        <h3>Create New User</h3>
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Role</label>
            <select 
              value={formData.role} 
              onChange={(e) => handleRoleChange(e.target.value)}
              required
            >
              <option value="student">Student</option>
              {userRole === 'admin' && (
                <>
                  <option value="teacher">Teacher</option>
                  <option value="chairperson">Chairperson</option>
                </>
              )}
            </select>
          </div>

          <div className="form-group">
            <label>Full Name</label>
            <input
              type="text"
              value={formData.full_name}
              onChange={(e) => setFormData(prev => ({ ...prev, full_name: e.target.value }))}
              required
            />
          </div>

          <div className="form-group">
            <label>Email</label>
            <input
              type="email"
              value={formData.email}
              onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
              required
            />
          </div>

          <div className="form-group">
            <label>Password</label>
            <input
              type="password"
              value={formData.password}
              onChange={(e) => setFormData(prev => ({ ...prev, password: e.target.value }))}
              required
            />
          </div>

          {formData.role === 'student' && (
            <>
              <div className="form-group">
                <label>Program</label>
                <select
                  value={formData.program}
                  onChange={(e) => setFormData(prev => ({ ...prev, program: e.target.value }))}
                  required
                >
                  <option value="">Select Program</option>
                  {availablePrograms.map((program) => (
                    <option key={program} value={program}>
                      {program}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label>Student Number</label>
                <input
                  type="text"
                  value={formData.student_num}
                  onChange={(e) => setFormData(prev => ({ ...prev, student_num: e.target.value }))}
                />
              </div>

              <div className="form-group">
                <label>Profile Picture</label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => setFormData(prev => ({ ...prev, profile_pic: e.target.files[0] }))}
                />
              </div>

              <div className="form-group">
                <label>Cover Picture</label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => setFormData(prev => ({ ...prev, cover_pic: e.target.files[0] }))}
                />
              </div>
            </>
          )}

          {formData.role === 'teacher' && (
            <>
              <div className="form-group">
                <label>Profile Picture</label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => setFormData(prev => ({ ...prev, profile_pic: e.target.files[0] }))}
                />
              </div>

              <div className="form-group">
                <label>Cover Picture</label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => setFormData(prev => ({ ...prev, cover_pic: e.target.files[0] }))}
                />
              </div>
            </>
          )}

          {formData.role === 'chairperson' && (
            <>
              <div className="form-group">
                <label>Program</label>
                <select
                  value={formData.program}
                  onChange={(e) => setFormData(prev => ({ ...prev, program: e.target.value }))}
                  required
                >
                  <option value="">Select Program</option>
                  {availablePrograms.map((program) => (
                    <option key={program} value={program}>
                      {program}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label>Profile Picture</label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => setFormData(prev => ({ ...prev, profile_pic: e.target.files[0] }))}
                />
              </div>

              <div className="form-group">
                <label>Cover Picture</label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => setFormData(prev => ({ ...prev, cover_pic: e.target.files[0] }))}
                />
              </div>
            </>
          )}

          <div className="form-actions">
            <button type="submit" className="btn btn-primary">Create User</button>
            <button type="button" onClick={onCancel} className="btn btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  );
};

// ========================================
// 4. PERMISSION-BASED COMPONENT WRAPPER
// ========================================

const PermissionGate = ({ permission, children, fallback = null }) => {
  const { permissions } = useAuth();
  
  if (!permissions || !permissions[permission]) {
    return fallback;
  }
  
  return children;
};

// Usage example:
const AdminOnlySection = () => (
  <PermissionGate permission="can_create_chairpersons">
    <div>
      <h3>Admin Only Section</h3>
      <p>This section is only visible to Main Admins</p>
    </div>
  </PermissionGate>
);

// ========================================
// 5. CSS STYLES
// ========================================

const styles = `
.user-management {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.user-info {
  display: flex;
  gap: 15px;
  font-size: 14px;
  color: #666;
}

.actions {
  margin-bottom: 20px;
}

.btn {
  padding: 8px 16px;
  margin-right: 10px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-danger {
  background-color: #dc3545;
  color: white;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

.table th {
  background-color: #f8f9fa;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal {
  background: white;
  padding: 20px;
  border-radius: 8px;
  width: 500px;
  max-height: 80vh;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}
`;

// ========================================
// 6. USAGE INSTRUCTIONS
// ========================================

/*
IMPLEMENTATION STEPS:

1. Update your existing AuthContext:
   - Add admin_type and program fields
   - Add permission checking functions
   - Update login function to handle new fields

2. Replace your User Management component:
   - Use the new UserManagement component
   - Implement role-based access control
   - Add program-based filtering

3. Add the CreateUserForm:
   - Handle different user types
   - Implement program selection
   - Add validation based on user role

4. Use PermissionGate for conditional rendering:
   - Wrap components that need specific permissions
   - Hide/show features based on user role

5. Update your routing:
   - Add role-based route protection
   - Redirect users based on their permissions

EXAMPLE ROUTE PROTECTION:
```jsx
const ProtectedRoute = ({ permission, children }) => {
  const { permissions } = useAuth();
  
  if (!permissions || !permissions[permission]) {
    return <Navigate to="/unauthorized" />;
  }
  
  return children;
};

// Usage:
<Route path="/admin/users" element={
  <ProtectedRoute permission="can_create_students">
    <UserManagement />
  </ProtectedRoute>
} />
```

API ENDPOINTS TO USE:
- GET /api/admin/get_user_permissions - Get user permissions
- GET /api/admin/get_available_programs - Get available programs
- GET /api/admin/get_students - Get students (role-based)
- GET /api/admin/get_chairpersons - Get chairpersons (Main Admin only)
- POST /api/admin/create_user - Create new user
- PUT /api/admin/update_user/:id - Update user

TESTING:
1. Login as Main Admin - should see all options
2. Create a Chairperson
3. Login as Chairperson - should only see students in their program
4. Test creating users with different roles
5. Verify access control works correctly
*/
