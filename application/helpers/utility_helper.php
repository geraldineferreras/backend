<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Utility Helper Functions
 */

/**
 * Format year level from numeric to ordinal format
 * 
 * @param string|int $year_level The year level (e.g., "1", "2", "3", "4")
 * @return string Formatted year level (e.g., "1st Year", "2nd Year", "3rd Year", "4th Year")
 */
function format_year_level_display($year_level) {
    if (empty($year_level)) {
        return '';
    }
    
    $year = intval($year_level);
    
    if ($year < 1 || $year > 10) {
        return $year_level; // Return as-is if out of expected range
    }
    
    $suffix = 'th';
    if ($year == 1) {
        $suffix = 'st';
    } elseif ($year == 2) {
        $suffix = 'nd';
    } elseif ($year == 3) {
        $suffix = 'rd';
    }
    
    return $year . $suffix . ' Year';
}

/**
 * Map program names to what's actually stored in the database
 * 
 * @param string $program_name The program name (e.g., "BSIS", "BSCS", "BSIT", "ACT")
 * @return string The program name as stored in database
 */
function map_program_name($program_name, $allow_archived = false) {
    if (empty($program_name)) {
        return null;
    }

    $CI =& get_instance();
    if (!isset($CI->Program_model)) {
        $CI->load->model('Program_model');
    }

    $program = $CI->Program_model->normalize_program_input($program_name, $allow_archived);
    return $program ? $program['code'] : null;
}

/**
 * Convert program shortcut to full name for display
 * 
 * @param string $shortcut The program shortcut (e.g., "BSIT", "BSIS", "BSCS", "ACT")
 * @return string The full program name for display
 */
function program_shortcut_to_full_name($shortcut) {
    if (empty($shortcut)) {
        return '';
    }

    $CI =& get_instance();
    if (!isset($CI->Program_model)) {
        $CI->load->model('Program_model');
    }

    $program = $CI->Program_model->get_by_code($shortcut, true);
    return $program ? $program['name'] : strtoupper(trim($shortcut));
}

/**
 * Generate full_name from atomic name fields with middle name as initial
 * 
 * @param string $first_name First name
 * @param string $middle_name Middle name (will be converted to initial)
 * @param string $last_name Last name
 * @param bool $last_name_first Whether to format as "Last, First M." (true) or "First M. Last" (false)
 * @return string Formatted full name
 */
function generate_full_name($first_name, $middle_name = null, $last_name = null, $last_name_first = false) {
    $first_name = trim($first_name ?? '');
    $middle_name = trim($middle_name ?? '');
    $last_name = trim($last_name ?? '');
    
    // Convert middle name to initial if it exists
    $middle_initial = '';
    if (!empty($middle_name)) {
        // Get first letter and capitalize
        $middle_initial = strtoupper(substr($middle_name, 0, 1)) . '.';
    }
    
    // Build the name
    if (empty($last_name)) {
        // If no last name, just return first name
        return $first_name;
    }
    
    if ($last_name_first) {
        // Format: "Last, First M."
        $name_parts = [$last_name];
        if (!empty($first_name)) {
            $name_parts[] = $first_name . ($middle_initial ? ' ' . $middle_initial : '');
        }
        return implode(', ', $name_parts);
    } else {
        // Format: "First M. Last"
        $name_parts = [];
        if (!empty($first_name)) {
            $name_parts[] = $first_name;
        }
        if ($middle_initial) {
            $name_parts[] = $middle_initial;
        }
        if (!empty($last_name)) {
            $name_parts[] = $last_name;
        }
        return implode(' ', $name_parts);
    }
}

/**
 * Generate full_name from user array with atomic name fields
 * Falls back to existing full_name if atomic fields are not available
 * 
 * @param array $user User data array with first_name, middle_name, last_name, or full_name
 * @param bool $last_name_first Whether to format as "Last, First M." (true) or "First M. Last" (false)
 * @return string Formatted full name
 */
function get_user_full_name($user, $last_name_first = false) {
    // If atomic fields exist, use them
    if (isset($user['first_name']) || isset($user['middle_name']) || isset($user['last_name'])) {
        $first_name = $user['first_name'] ?? null;
        $middle_name = $user['middle_name'] ?? null;
        $last_name = $user['last_name'] ?? null;
        
        // If at least first_name or last_name is available, use atomic fields
        if (!empty($first_name) || !empty($last_name)) {
            return generate_full_name($first_name, $middle_name, $last_name, $last_name_first);
        }
    }
    
    // Fall back to existing full_name if atomic fields are not available
    return $user['full_name'] ?? '';
}
?>
