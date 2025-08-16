// Authentication Helper for SCMS API
class AuthHelper {
    constructor() {
        this.baseURL = 'http://localhost/scms_new/index.php/api';
        this.tokenKey = 'auth_token';
    }

    // Get stored token
    getToken() {
        return localStorage.getItem(this.tokenKey) || sessionStorage.getItem(this.tokenKey);
    }

    // Set token
    setToken(token) {
        localStorage.setItem(this.tokenKey, token);
    }

    // Remove token
    removeToken() {
        localStorage.removeItem(this.tokenKey);
        sessionStorage.removeItem(this.tokenKey);
    }

    // Check if user is authenticated
    isAuthenticated() {
        return !!this.getToken();
    }

    // Login function
    async login(email, password) {
        try {
            const response = await fetch(`${this.baseURL}/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (data.success && data.data.token) {
                this.setToken(data.data.token);
                return {
                    success: true,
                    user: data.data
                };
            } else {
                return {
                    success: false,
                    message: data.message || 'Login failed'
                };
            }
        } catch (error) {
            return {
                success: false,
                message: error.message || 'Network error'
            };
        }
    }

    // Logout function
    async logout() {
        try {
            const token = this.getToken();
            if (token) {
                await fetch(`${this.baseURL}/logout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
            }
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.removeToken();
        }
    }

    // Make authenticated API request
    async makeRequest(endpoint, options = {}) {
        const token = this.getToken();
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers
        };

        // Add Authorization header if token exists
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const config = {
            ...options,
            headers
        };

        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, config);
            
            if (response.status === 401) {
                // Token expired or invalid
                this.removeToken();
                throw new Error('Authentication required. Please log in again.');
            }

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            throw error;
        }
    }

    // Section management methods
    async getSectionsByProgramYear(program, yearLevel = null) {
        let endpoint = `/admin/sections_by_program_year_specific?program=${encodeURIComponent(program)}`;
        if (yearLevel) {
            endpoint += `&year_level=${encodeURIComponent(yearLevel)}`;
        }
        
        return this.makeRequest(endpoint);
    }

    async getAllSections() {
        return this.makeRequest('/admin/sections');
    }

    async getSection(sectionId) {
        return this.makeRequest(`/admin/sections/${sectionId}`);
    }

    async createSection(sectionData) {
        return this.makeRequest('/admin/sections', {
            method: 'POST',
            body: JSON.stringify(sectionData)
        });
    }

    async updateSection(sectionId, sectionData) {
        return this.makeRequest(`/admin/sections/${sectionId}`, {
            method: 'PUT',
            body: JSON.stringify(sectionData)
        });
    }

    async deleteSection(sectionId, { force = false } = {}) {
        const query = force ? '?force=true' : '';
        return this.makeRequest(`/admin/sections/${sectionId}${query}`, {
            method: 'DELETE'
        });
    }

    async getPrograms() {
        return this.makeRequest('/admin/programs');
    }

    async getYearLevels() {
        return this.makeRequest('/admin/year-levels');
    }

    async getAdvisers() {
        return this.makeRequest('/admin/advisers');
    }

    async getSectionStudents(sectionId) {
        return this.makeRequest(`/admin/sections/${sectionId}/students`);
    }

    async assignStudents(sectionId, studentIds) {
        return this.makeRequest(`/admin/sections/${sectionId}/assign-students`, {
            method: 'POST',
            body: JSON.stringify({ student_ids: studentIds })
        });
    }

    async removeStudents(sectionId, studentIds) {
        return this.makeRequest(`/admin/sections/${sectionId}/remove-students`, {
            method: 'POST',
            body: JSON.stringify({ student_ids: studentIds })
        });
    }
}

// Create global instance
const authHelper = new AuthHelper();

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AuthHelper, authHelper };
}

// Example usage:
/*
// Login
const loginResult = await authHelper.login('admin@example.com', 'password');
if (loginResult.success) {
    console.log('Login successful');
}

// Get sections
try {
    const sections = await authHelper.getSectionsByProgramYear('BSIT', '3rd');
    console.log('Sections:', sections);
} catch (error) {
    console.error('Error:', error.message);
}

// Logout
await authHelper.logout();
*/