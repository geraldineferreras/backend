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
function map_program_name($program_name) {
    $program_name = strtoupper(trim($program_name));
    
    // Now the database stores shortcuts, so return as-is
    $known_programs = ['BSIT', 'BSIS', 'BSCS', 'ACT'];
    
    if (in_array($program_name, $known_programs)) {
        return $program_name; // Return the shortcut as stored in database
    }
    
    return $program_name; // Return as-is if not recognized
}

/**
 * Convert program shortcut to full name for display
 * 
 * @param string $shortcut The program shortcut (e.g., "BSIT", "BSIS", "BSCS", "ACT")
 * @return string The full program name for display
 */
function program_shortcut_to_full_name($shortcut) {
    $shortcut = strtoupper(trim($shortcut));
    
    $mapping = [
        'BSIT' => 'Bachelor of Science in Information Technology',
        'BSIS' => 'Bachelor of Science in Information Systems',
        'BSCS' => 'Bachelor of Science in Computer Science',
        'ACT' => 'Associate in Computer Technology'
    ];
    
    return isset($mapping[$shortcut]) ? $mapping[$shortcut] : $shortcut;
}
?>
