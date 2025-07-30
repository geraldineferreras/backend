// XMLHttpRequest Implementation for SCMS Section Management
// This file provides XHR-based functions for section management

class SectionXHR {
    constructor() {
        // Update base URL to match your server configuration
        this.baseURL = 'http://localhost/scms_new/index.php/api';
        this.token = localStorage.getItem('auth_token') || '';
    }

    // Set authentication token
    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    // Clear authentication token
    clearToken() {
        this.token = '';
        localStorage.removeItem('auth_token');
    }

    // Generic XHR request method
    makeRequest(method, endpoint, data = null, callback) {
        const xhr = new XMLHttpRequest();
        const url = this.baseURL + endpoint;

        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('Accept', 'application/json');
        
        // Add Authorization header with Bearer token (not as query parameter)
        if (this.token) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + this.token);
        }

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (xhr.status >= 200 && xhr.status < 300) {
                        // Success
                        if (callback && typeof callback.success === 'function') {
                            callback.success(response);
                        }
                    } else {
                        // Error
                        if (callback && typeof callback.error === 'function') {
                            callback.error({
                                status: xhr.status,
                                message: response.message || 'Request failed',
                                data: response
                            });
                        }
                    }
                } catch (e) {
                    // JSON parse error
                    if (callback && typeof callback.error === 'function') {
                        callback.error({
                            status: xhr.status,
                            message: 'Invalid JSON response',
                            data: xhr.responseText
                        });
                    }
                }
            }
        };

        xhr.onerror = function() {
            if (callback && typeof callback.error === 'function') {
                callback.error({
                    status: 0,
                    message: 'Network error',
                    data: null
                });
            }
        };

        if (data) {
            xhr.send(JSON.stringify(data));
        } else {
            xhr.send();
        }
    }

    // Get sections by program and year level
    getSectionsByProgramYear(program, yearLevel = null, callback) {
        let endpoint = `/admin/sections_by_program_year_specific?program=${encodeURIComponent(program)}`;
        if (yearLevel) {
            endpoint += `&year_level=${encodeURIComponent(yearLevel)}`;
        }
        
        this.makeRequest('GET', endpoint, null, callback);
    }

    // Get all sections
    getAllSections(callback) {
        this.makeRequest('GET', '/admin/sections', null, callback);
    }

    // Get section by ID
    getSection(sectionId, callback) {
        this.makeRequest('GET', `/admin/sections/${sectionId}`, null, callback);
    }

    // Create new section
    createSection(sectionData, callback) {
        this.makeRequest('POST', '/admin/sections', sectionData, callback);
    }

    // Update section
    updateSection(sectionId, sectionData, callback) {
        this.makeRequest('PUT', `/admin/sections/${sectionId}`, sectionData, callback);
    }

    // Delete section
    deleteSection(sectionId, callback) {
        this.makeRequest('DELETE', `/admin/sections/${sectionId}`, null, callback);
    }

    // Get sections by year level
    getSectionsByYear(yearLevel, callback) {
        this.makeRequest('GET', `/admin/sections/year/${encodeURIComponent(yearLevel)}`, null, callback);
    }

    // Get sections by program
    getSectionsByProgram(program, callback) {
        this.makeRequest('GET', `/admin/sections_by_program?program=${encodeURIComponent(program)}`, null, callback);
    }

    // Get available advisers
    getAdvisers(callback) {
        this.makeRequest('GET', '/admin/advisers', null, callback);
    }

    // Get programs
    getPrograms(callback) {
        this.makeRequest('GET', '/admin/programs', null, callback);
    }

    // Get year levels
    getYearLevels(callback) {
        this.makeRequest('GET', '/admin/year-levels', null, callback);
    }

    // Get semesters
    getSemesters(callback) {
        this.makeRequest('GET', '/admin/semesters', null, callback);
    }

    // Get academic years
    getAcademicYears(callback) {
        this.makeRequest('GET', '/admin/academic-years', null, callback);
    }

    // Get students in a section
    getSectionStudents(sectionId, callback) {
        this.makeRequest('GET', `/admin/sections/${sectionId}/students`, null, callback);
    }

    // Assign students to section
    assignStudents(sectionId, studentIds, callback) {
        this.makeRequest('POST', `/admin/sections/${sectionId}/assign-students`, {
            student_ids: studentIds
        }, callback);
    }

    // Remove students from section
    removeStudents(sectionId, studentIds, callback) {
        this.makeRequest('POST', `/admin/sections/${sectionId}/remove-students`, {
            student_ids: studentIds
        }, callback);
    }

    // Get available students
    getAvailableStudents(callback) {
        this.makeRequest('GET', '/admin/students/available', null, callback);
    }

    // Get all students with sections
    getAllStudents(callback) {
        this.makeRequest('GET', '/admin/students', null, callback);
    }
}

// Auth XHR class
class AuthXHR {
    constructor() {
        this.baseURL = 'http://localhost/scms_new/index.php/api';
    }

    makeRequest(method, endpoint, data = null, callback) {
        const xhr = new XMLHttpRequest();
        const url = this.baseURL + endpoint;

        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (xhr.status >= 200 && xhr.status < 300) {
                        if (callback && typeof callback.success === 'function') {
                            callback.success(response);
                        }
                    } else {
                        if (callback && typeof callback.error === 'function') {
                            callback.error({
                                status: xhr.status,
                                message: response.message || 'Request failed',
                                data: response
                            });
                        }
                    }
                } catch (e) {
                    if (callback && typeof callback.error === 'function') {
                        callback.error({
                            status: xhr.status,
                            message: 'Invalid JSON response',
                            data: xhr.responseText
                        });
                    }
                }
            }
        };

        xhr.onerror = function() {
            if (callback && typeof callback.error === 'function') {
                callback.error({
                    status: 0,
                    message: 'Network error',
                    data: null
                });
            }
        };

        if (data) {
            xhr.send(JSON.stringify(data));
        } else {
            xhr.send();
        }
    }

    // Login
    login(credentials, callback) {
        this.makeRequest('POST', '/login', credentials, callback);
    }

    // Register
    register(userData, callback) {
        this.makeRequest('POST', '/register', userData, callback);
    }

    // Logout
    logout(callback) {
        this.makeRequest('POST', '/logout', null, callback);
    }

    // Refresh token
    refreshToken(callback) {
        this.makeRequest('POST', '/refresh-token', null, callback);
    }

    // Validate token
    validateToken(callback) {
        this.makeRequest('GET', '/validate-token', null, callback);
    }
}

// Create global instances
const sectionXHR = new SectionXHR();
const authXHR = new AuthXHR();

// Example usage functions
function exampleUsage() {
    // Example 1: Get BSIT 3rd year sections
    sectionXHR.getSectionsByProgramYear('BSIT', '3rd', {
        success: function(response) {
            console.log('BSIT 3rd year sections:', response);
            if (response.success) {
                displaySections(response.data.sections);
            }
        },
        error: function(error) {
            console.error('Error fetching sections:', error);
        }
    });

    // Example 2: Get all BSIT sections
    sectionXHR.getSectionsByProgramYear('BSIT', null, {
        success: function(response) {
            console.log('All BSIT sections:', response);
        },
        error: function(error) {
            console.error('Error:', error);
        }
    });

    // Example 3: Create a new section
    const newSection = {
        section_name: 'BSIT-3A',
        program: 'Bachelor of Science in Information Technology',
        year_level: '3rd',
        adviser_id: 1,
        semester: '1st',
        academic_year: '2024-2025'
    };

    sectionXHR.createSection(newSection, {
        success: function(response) {
            console.log('Section created:', response);
        },
        error: function(error) {
            console.error('Error creating section:', error);
        }
    });

    // Example 4: Login
    authXHR.login({
        email: 'admin@example.com',
        password: 'password123'
    }, {
        success: function(response) {
            console.log('Login successful:', response);
            if (response.data && response.data.token) {
                sectionXHR.setToken(response.data.token);
            }
        },
        error: function(error) {
            console.error('Login failed:', error);
        }
    });
}

// Helper function to display sections
function displaySections(sections) {
    const container = document.getElementById('sections-container');
    if (!container) return;

    container.innerHTML = '';
    
    sections.forEach(section => {
        const sectionDiv = document.createElement('div');
        sectionDiv.className = 'section-item';
        sectionDiv.innerHTML = `
            <h3>${section.section_name}</h3>
            <p><strong>Program:</strong> ${section.program}</p>
            <p><strong>Year Level:</strong> ${section.year_level}</p>
            <p><strong>Semester:</strong> ${section.semester}</p>
            <p><strong>Academic Year:</strong> ${section.academic_year}</p>
            <p><strong>Adviser:</strong> ${section.adviser_name || 'Not assigned'}</p>
        `;
        container.appendChild(sectionDiv);
    });
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SectionXHR, AuthXHR, sectionXHR, authXHR };
}