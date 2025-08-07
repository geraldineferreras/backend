// React Task Submission Example
// This shows how to properly submit tasks with file uploads in React

import React, { useState } from 'react';
import axios from 'axios';

const TaskSubmissionExample = () => {
  const [files, setFiles] = useState([]);
  const [submissionContent, setSubmissionContent] = useState('');
  const [classCode, setClassCode] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [response, setResponse] = useState(null);

  // Handle file selection
  const handleFileChange = (event) => {
    const selectedFiles = Array.from(event.target.files);
    setFiles(selectedFiles);
  };

  // Handle form submission
  const handleSubmit = async (event) => {
    event.preventDefault();
    setIsSubmitting(true);
    setResponse(null);

    try {
      // Create FormData
      const formData = new FormData();
      
      // Add text content if provided
      if (submissionContent.trim()) {
        formData.append('submission_content', submissionContent);
      }
      
      // Add class code
      formData.append('class_code', classCode);
      
      // Add files
      files.forEach((file, index) => {
        formData.append('attachment', file);
      });

      // Get token from localStorage or your auth system
      const token = localStorage.getItem('token'); // Adjust based on your auth system

      // Make the request
      const response = await axios.post('/api/tasks/23/submit', formData, {
        headers: {
          'Authorization': `Bearer ${token}`,
          // Don't set Content-Type - let the browser set it for FormData
        },
        timeout: 30000, // 30 seconds
      });

      setResponse({
        success: true,
        data: response.data,
        status: response.status
      });

      console.log('Submission successful:', response.data);
      
    } catch (error) {
      console.error('Submission error:', error);
      
      setResponse({
        success: false,
        error: error.response?.data || error.message,
        status: error.response?.status
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  // Debug function to check what's being sent
  const debugFormData = () => {
    const formData = new FormData();
    
    if (submissionContent.trim()) {
      formData.append('submission_content', submissionContent);
    }
    
    formData.append('class_code', classCode);
    
    files.forEach((file, index) => {
      formData.append('attachment', file);
    });

    console.log('=== DEBUG FORM DATA ===');
    console.log('Files:', files);
    console.log('Submission Content:', submissionContent);
    console.log('Class Code:', classCode);
    
    // Log FormData entries
    for (let [key, value] of formData.entries()) {
      console.log(`${key}:`, value);
    }
    
    console.log('=== END DEBUG ===');
  };

  return (
    <div style={{ maxWidth: '600px', margin: '0 auto', padding: '20px' }}>
      <h1>Task Submission Example</h1>
      
      <form onSubmit={handleSubmit}>
        <div style={{ marginBottom: '20px' }}>
          <label>
            <strong>Submission Content (Optional):</strong>
            <textarea
              value={submissionContent}
              onChange={(e) => setSubmissionContent(e.target.value)}
              placeholder="Enter your submission content..."
              style={{
                width: '100%',
                minHeight: '100px',
                padding: '10px',
                border: '1px solid #ddd',
                borderRadius: '5px'
              }}
            />
          </label>
        </div>

        <div style={{ marginBottom: '20px' }}>
          <label>
            <strong>Class Code:</strong>
            <input
              type="text"
              value={classCode}
              onChange={(e) => setClassCode(e.target.value)}
              placeholder="e.g., MATH101"
              required
              style={{
                width: '100%',
                padding: '10px',
                border: '1px solid #ddd',
                borderRadius: '5px'
              }}
            />
          </label>
        </div>

        <div style={{ marginBottom: '20px' }}>
          <label>
            <strong>Files to Upload:</strong>
            <input
              type="file"
              onChange={handleFileChange}
              multiple
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.mp4,.mp3"
              style={{
                width: '100%',
                padding: '10px',
                border: '1px solid #ddd',
                borderRadius: '5px'
              }}
            />
          </label>
          
          {files.length > 0 && (
            <div style={{ marginTop: '10px', padding: '10px', backgroundColor: '#f8f9fa', borderRadius: '5px' }}>
              <strong>Selected Files:</strong>
              <ul style={{ margin: '5px 0', paddingLeft: '20px' }}>
                {files.map((file, index) => (
                  <li key={index}>
                    {file.name} ({(file.size / 1024 / 1024).toFixed(2)} MB)
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>

        <div style={{ marginBottom: '20px' }}>
          <button
            type="button"
            onClick={debugFormData}
            style={{
              padding: '10px 20px',
              backgroundColor: '#6c757d',
              color: 'white',
              border: 'none',
              borderRadius: '5px',
              marginRight: '10px'
            }}
          >
            Debug Form Data
          </button>
          
          <button
            type="submit"
            disabled={isSubmitting || !classCode || files.length === 0}
            style={{
              padding: '10px 20px',
              backgroundColor: isSubmitting ? '#6c757d' : '#007bff',
              color: 'white',
              border: 'none',
              borderRadius: '5px'
            }}
          >
            {isSubmitting ? 'Submitting...' : 'Submit Task'}
          </button>
        </div>
      </form>

      {response && (
        <div style={{
          padding: '15px',
          borderRadius: '5px',
          backgroundColor: response.success ? '#d4edda' : '#f8d7da',
          color: response.success ? '#155724' : '#721c24',
          marginTop: '20px'
        }}>
          <strong>Response:</strong>
          <pre style={{ whiteSpace: 'pre-wrap', fontSize: '12px' }}>
            {JSON.stringify(response, null, 2)}
          </pre>
        </div>
      )}

      <div style={{ marginTop: '30px', padding: '15px', backgroundColor: '#fff3cd', borderRadius: '5px' }}>
        <h3>Troubleshooting Tips:</h3>
        <ul>
          <li><strong>File not being sent:</strong> Make sure you're using FormData and not setting Content-Type header</li>
          <li><strong>Wrong field name:</strong> The API expects 'attachment' as the field name</li>
          <li><strong>File size:</strong> Maximum 10MB per file</li>
          <li><strong>File types:</strong> Only specific file types are allowed</li>
          <li><strong>Authentication:</strong> Make sure your JWT token is valid</li>
        </ul>
      </div>
    </div>
  );
};

export default TaskSubmissionExample; 