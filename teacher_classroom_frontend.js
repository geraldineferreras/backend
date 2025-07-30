// Teacher Classroom Frontend Implementation
// This file demonstrates how to implement filtered subject and section dropdowns
// based on teacher's assigned offerings

class TeacherClassroomManager {
    constructor() {
        this.baseURL = 'http://localhost/scms_new/index.php/api';
        this.authToken = null;
        this.availableSubjects = [];
        this.availableSections = {};
    }

    // Set authentication token
    setAuthToken(token) {
        this.authToken = token;
    }

    // Get authentication headers
    getAuthHeaders() {
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.authToken}`
        };
    }

    // Make API request
    async makeRequest(endpoint, options = {}) {
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, {
                ...options,
                headers: {
                    ...this.getAuthHeaders(),
                    ...options.headers
                }
            });
            
            const data = await response.json();
            return { success: response.ok, data, status: response.status };
        } catch (error) {
            return { success: false, data: { message: error.message }, status: 0 };
        }
    }

    // Load available subjects for the teacher
    async loadAvailableSubjects() {
        const result = await this.makeRequest('/teacher/available-subjects');
        if (result.success) {
            this.availableSubjects = result.data.data || [];
            return this.availableSubjects;
        } else {
            console.error('Failed to load subjects:', result.data.message);
            return [];
        }
    }

    // Load available sections for a specific subject
    async loadAvailableSections(subjectId) {
        const result = await this.makeRequest(`/teacher/available-sections/${subjectId}`);
        if (result.success) {
            this.availableSections[subjectId] = result.data.data || [];
            return this.availableSections[subjectId];
        } else {
            console.error('Failed to load sections:', result.data.message);
            return [];
        }
    }

    // Populate subject dropdown
    populateSubjectDropdown(selectElement) {
        // Clear existing options
        selectElement.innerHTML = '<option value="">Select subject</option>';
        
        // Add available subjects
        this.availableSubjects.forEach(subject => {
            const option = document.createElement('option');
            option.value = subject.id;
            option.textContent = subject.subject_name;
            selectElement.appendChild(option);
        });
    }

    // Populate section dropdown
    populateSectionDropdown(selectElement, subjectId) {
        // Clear existing options
        selectElement.innerHTML = '<option value="">Select section</option>';
        
        if (!subjectId) {
            return;
        }

        const sections = this.availableSections[subjectId] || [];
        sections.forEach(section => {
            const option = document.createElement('option');
            option.value = section.section_id;
            option.textContent = section.section_name;
            selectElement.appendChild(option);
        });
    }

    // Create classroom
    async createClassroom(classroomData) {
        const result = await this.makeRequest('/teacher/classrooms', {
            method: 'POST',
            body: JSON.stringify(classroomData)
        });
        return result;
    }

    // Get teacher's classrooms
    async getClassrooms() {
        const result = await this.makeRequest('/teacher/classrooms');
        return result;
    }
}

// Example usage and implementation
document.addEventListener('DOMContentLoaded', function() {
    const teacherManager = new TeacherClassroomManager();
    
    // Example: Initialize the create class form
    function initializeCreateClassForm() {
        const subjectSelect = document.getElementById('subjectSelect');
        const sectionSelect = document.getElementById('sectionSelect');
        const semesterSelect = document.getElementById('semesterSelect');
        const schoolYearInput = document.getElementById('schoolYearInput');
        const createButton = document.getElementById('createClassButton');
        
        // Load available subjects when form initializes
        teacherManager.loadAvailableSubjects().then(() => {
            teacherManager.populateSubjectDropdown(subjectSelect);
        });
        
        // When subject changes, load available sections
        subjectSelect.addEventListener('change', function() {
            const selectedSubjectId = this.value;
            if (selectedSubjectId) {
                teacherManager.loadAvailableSections(selectedSubjectId).then(() => {
                    teacherManager.populateSectionDropdown(sectionSelect, selectedSubjectId);
                });
            } else {
                // Clear section dropdown if no subject selected
                sectionSelect.innerHTML = '<option value="">Select section</option>';
            }
        });
        
        // Handle form submission
        createButton.addEventListener('click', async function() {
            const subjectId = subjectSelect.value;
            const sectionId = sectionSelect.value;
            const semester = semesterSelect.value;
            const schoolYear = schoolYearInput.value;
            
            if (!subjectId || !sectionId || !semester || !schoolYear) {
                alert('Please fill in all required fields');
                return;
            }
            
            const classroomData = {
                subject_id: parseInt(subjectId),
                section_id: parseInt(sectionId),
                semester: semester,
                school_year: schoolYear
            };
            
            const result = await teacherManager.createClassroom(classroomData);
            if (result.success) {
                alert('Classroom created successfully!');
                // Optionally refresh the classrooms list
                loadClassrooms();
            } else {
                alert('Failed to create classroom: ' + result.data.message);
            }
        });
    }
    
    // Example: Load and display classrooms
    async function loadClassrooms() {
        const result = await teacherManager.getClassrooms();
        const classroomsContainer = document.getElementById('classroomsContainer');
        
        if (result.success) {
            const classrooms = result.data.data || [];
            displayClassrooms(classrooms, classroomsContainer);
        } else {
            classroomsContainer.innerHTML = '<p>Error loading classrooms: ' + result.data.message + '</p>';
        }
    }
    
    // Example: Display classrooms in a table
    function displayClassrooms(classrooms, container) {
        if (classrooms.length === 0) {
            container.innerHTML = '<p>No classrooms found.</p>';
            return;
        }
        
        let html = `
            <table border="1" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Class Code</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>School Year</th>
                        <th>Students</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        classrooms.forEach(classroom => {
            html += `
                <tr>
                    <td>${classroom.class_code}</td>
                    <td>${classroom.subject_name}</td>
                    <td>${classroom.section_name}</td>
                    <td>${classroom.semester}</td>
                    <td>${classroom.school_year}</td>
                    <td>${classroom.student_count}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    }
    
    // Example: Set up authentication
    function setupAuthentication() {
        const authTokenInput = document.getElementById('authTokenInput');
        const setAuthButton = document.getElementById('setAuthButton');
        
        setAuthButton.addEventListener('click', function() {
            const token = authTokenInput.value.trim();
            if (token) {
                teacherManager.setAuthToken(token);
                alert('Authentication token set successfully!');
                // Initialize form after authentication
                initializeCreateClassForm();
                loadClassrooms();
            } else {
                alert('Please enter a valid authentication token');
            }
        });
    }
    
    // Initialize everything when page loads
    setupAuthentication();
});

// Example HTML structure that would work with this JavaScript:
/*
<div>
    <h2>Authentication</h2>
    <input type="text" id="authTokenInput" placeholder="Enter Bearer token">
    <button id="setAuthButton">Set Token</button>
</div>

<div>
    <h2>Create New Class</h2>
    <select id="subjectSelect">
        <option value="">Select subject</option>
    </select>
    
    <select id="sectionSelect">
        <option value="">Select section</option>
    </select>
    
    <select id="semesterSelect">
        <option value="">Select semester</option>
        <option value="1ST">1ST</option>
        <option value="2ND">2ND</option>
    </select>
    
    <input type="text" id="schoolYearInput" placeholder="School Year" value="2024-2025">
    
    <button id="createClassButton">Create Class</button>
</div>

<div>
    <h2>My Classrooms</h2>
    <div id="classroomsContainer"></div>
</div>
*/ 