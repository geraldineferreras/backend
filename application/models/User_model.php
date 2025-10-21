<?php
class User_model extends CI_Model {
    public function get_by_email($email) {
        return $this->db->get_where('users', ['email' => $email])->row_array();
    }
    
    public function get_by_student_num($student_num) {
        return $this->db->get_where('users', ['student_num' => $student_num])->row_array();
    }

    public function insert($data) {
        return $this->db->insert('users', $data);
    }

    public function get_all($role = null) {
        if ($role) {
            $this->db->where('users.role', $role);
        }
        
        // For students, join with sections table to get section_name and year level
        if ($role === 'student') {
            $this->db->select('users.*, sections.section_name, sections.year_level')
                     ->from('users')
                     ->join('sections', 'users.section_id = sections.section_id', 'left');
        } else {
            $this->db->from('users');
        }
        
        return $this->db->get()->result_array();
    }

    public function get_by_id($user_id) {
        // Check if the user is a student by first getting the user data
        $user = $this->db->get_where('users', ['user_id' => $user_id])->row_array();
        
        if ($user && $user['role'] === 'student') {
            // For students, join with sections table to get section_name and year level
            return $this->db->select('users.*, sections.section_name, sections.year_level')
                           ->from('users')
                           ->join('sections', 'users.section_id = sections.section_id', 'left')
                           ->where('users.user_id', $user_id)
                           ->get()->row_array();
        }
        
        return $user;
    }

    public function update($user_id, $data) {
        $this->db->where('user_id', $user_id);
        return $this->db->update('users', $data);
    }

    public function delete($user_id) {
        $this->db->where('user_id', $user_id);
        return $this->db->delete('users');
    }

    // Google OAuth methods
    public function get_by_google_id($google_id) {
        return $this->db->get_where('users', ['google_id' => $google_id])->row_array();
    }

    public function get_by_email_or_google($email, $google_id = null) {
        $this->db->group_start();
        $this->db->where('email', $email);
        if ($google_id) {
            $this->db->or_where('google_id', $google_id);
        }
        $this->db->group_end();
        return $this->db->get('users')->row_array();
    }

    public function link_google_account($user_id, $google_id, $google_email) {
        $data = [
            'google_id' => $google_id,
            'account_type' => 'unified',
            'google_email_verified' => true,
            'oauth_provider' => 'google'
        ];
        return $this->update($user_id, $data);
    }

    public function unlink_google_account($user_id) {
        $data = [
            'google_id' => null,
            'account_type' => 'local',
            'google_email_verified' => false,
            'oauth_provider' => null
        ];
        return $this->update($user_id, $data);
    }

    public function get_account_status($email) {
        return $this->db->select('user_id, email, account_type, google_id, google_email_verified, oauth_provider')
                        ->from('users')
                        ->where('email', $email)
                        ->get()
                        ->row_array();
    }

    // Role-Based Admin Hierarchy Methods
    
    /**
     * Get students by specific program (for Chairperson access control)
     * @param string $program
     * @return array
     */
    public function get_students_by_program($program) {
        return $this->db->select('users.*, sections.section_name, sections.year_level')
                       ->from('users')
                       ->join('sections', 'users.section_id = sections.section_id', 'left')
                       ->where('users.role', 'student')
                       ->where('users.program', $program)
                       ->get()
                       ->result_array();
    }

    /**
     * Get all chairpersons
     * @return array
     */
    public function get_chairpersons() {
        return $this->db->where('role', 'chairperson')
                       ->get('users')
                       ->result_array();
    }

    /**
     * Get main admin user
     * @return array|null
     */
    public function get_main_admin() {
        return $this->db->where('role', 'admin')
                       ->where('admin_type', 'main_admin')
                       ->get('users')
                       ->row_array();
    }

    /**
     * Get users by admin type
     * @param string $admin_type (main_admin, chairperson)
     * @return array
     */
    public function get_by_admin_type($admin_type) {
        return $this->db->where('admin_type', $admin_type)
                       ->get('users')
                       ->result_array();
    }

    /**
     * Get users by program
     * @param string $program
     * @return array
     */
    public function get_by_program($program) {
        return $this->db->where('program', $program)
                       ->get('users')
                       ->result_array();
    }

    /**
     * Get students with program filtering for access control
     * @param string $user_program (optional - for Chairperson filtering)
     * @return array
     */
    public function get_students_with_program_filter($user_program = null) {
        $this->db->select('users.*, sections.section_name, sections.year_level')
                ->from('users')
                ->join('sections', 'users.section_id = sections.section_id', 'left')
                ->where('users.role', 'student');
        
        // If user_program is provided, filter by program (for Chairperson access)
        if ($user_program) {
            $this->db->where('users.program', $user_program);
        }
        
        return $this->db->get()->result_array();
    }

    /**
     * Get chairperson by program
     * @param string $program
     * @return array|null
     */
    public function get_chairperson_by_program($program) {
        return $this->db->where('role', 'chairperson')
                       ->where('program', $program)
                       ->get('users')
                       ->row_array();
    }

    /**
     * Check if user can manage another user based on role hierarchy
     * @param string $manager_user_id
     * @param string $target_user_id
     * @return bool
     */
    public function can_manage_user($manager_user_id, $target_user_id) {
        $manager = $this->get_by_id($manager_user_id);
        $target = $this->get_by_id($target_user_id);
        
        if (!$manager || !$target) {
            return false;
        }
        
        // Main Admin can manage all users
        if ($manager['role'] === 'admin' && $manager['admin_type'] === 'main_admin') {
            return true;
        }
        
        // Chairperson can only manage students in their program
        if ($manager['role'] === 'chairperson' && $target['role'] === 'student') {
            return $manager['program'] === $target['program'];
        }
        
        return false;
    }

    /**
     * Get available programs for user creation
     * @param string $user_role (admin, chairperson)
     * @param string $user_program (for Chairperson filtering)
     * @return array
     */
    public function get_available_programs($user_role, $user_program = null) {
        // For now, return hardcoded programs. You can later integrate with programs table
        $programs = [
            'Bachelor of Science in Computer Science',
            'Bachelor of Science in Information Systems', 
            'Bachelor of Science in Information Technology',
            'Associate in Computer Technology'
        ];
        
        // If user is Chairperson, only return their program
        if ($user_role === 'chairperson' && $user_program) {
            return [$user_program];
        }
        
        return $programs;
    }

    /**
     * Create user with role-based validation
     * @param array $user_data
     * @param string $creator_role
     * @param string $creator_program
     * @return bool
     */
    public function create_user_with_validation($user_data, $creator_role, $creator_program = null) {
        // Validate based on creator's role
        if ($creator_role === 'admin' && $user_data['admin_type'] === 'main_admin') {
            // Main Admin can create any role except another Main Admin
            if ($user_data['role'] === 'admin' && $user_data['admin_type'] === 'main_admin') {
                return false; // Cannot create another Main Admin
            }
        } elseif ($creator_role === 'chairperson') {
            // Chairperson can only create students in their program
            if ($user_data['role'] !== 'student' || $user_data['program'] !== $creator_program) {
                return false;
            }
        } else {
            return false; // Invalid creator role
        }
        
        return $this->insert($user_data);
    }
} 