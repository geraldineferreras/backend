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
} 