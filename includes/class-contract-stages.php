<?php
/**
 * Contract Stages Management
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_Contract_Stages {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Constructor is empty as we use static methods
    }
    
    /**
     * Get stages for a contract
     *
     * @param int $contract_id Contract post ID
     * @return array Array of stages
     */
    public static function get_stages($contract_id) {
        $stages_data = get_post_meta($contract_id, '_contract_stages', true);
        
        if (empty($stages_data)) {
            return array();
        }
        
        // If it's already an array, return it directly
        if (is_array($stages_data)) {
            return $stages_data;
        }
        
        // If it's a string, try to decode it
        if (is_string($stages_data)) {
            $stages = json_decode($stages_data, true);
            
            if (!is_array($stages)) {
                return array();
            }
            
            return $stages;
        }
        
        // Fallback: return empty array
        return array();
    }
    
    /**
     * Add a stage to a contract
     *
     * @param int $contract_id Contract post ID
     * @param array $stage_data Stage data
     * @return bool|int Stage index on success, false on failure
     */
    public static function add_stage($contract_id, $stage_data) {
        // Validate contract exists
        if (get_post_type($contract_id) !== 'contract') {
            return false;
        }
        
        // Sanitize stage data
        $stage = array(
            'title' => sanitize_text_field($stage_data['title'] ?? ''),
            'status' => self::sanitize_stage_status($stage_data['status'] ?? 'pending'),
            'date' => sanitize_text_field($stage_data['date'] ?? ''),
            'description' => sanitize_textarea_field($stage_data['description'] ?? ''),
            'files' => array(),
        );
        
        // Get existing stages
        $stages = self::get_stages($contract_id);
        
        // Add new stage
        $stages[] = $stage;
        
        // Save stages
        return self::save_stages($contract_id, $stages) ? count($stages) - 1 : false;
    }
    
    /**
     * Update a stage
     *
     * @param int $contract_id Contract post ID
     * @param int $stage_index Stage index
     * @param array $stage_data Stage data
     * @return bool True on success, false on failure
     */
    public static function update_stage($contract_id, $stage_index, $stage_data) {
        $stages = self::get_stages($contract_id);
        
        if (!isset($stages[$stage_index])) {
            return false;
        }
        
        // Update stage fields
        if (isset($stage_data['title'])) {
            $stages[$stage_index]['title'] = sanitize_text_field($stage_data['title']);
        }
        
        if (isset($stage_data['status'])) {
            $stages[$stage_index]['status'] = self::sanitize_stage_status($stage_data['status']);
        }
        
        if (isset($stage_data['date'])) {
            $stages[$stage_index]['date'] = sanitize_text_field($stage_data['date']);
        }
        
        if (isset($stage_data['description'])) {
            $stages[$stage_index]['description'] = sanitize_textarea_field($stage_data['description']);
        }
        
        return self::save_stages($contract_id, $stages);
    }
    
    /**
     * Delete a stage
     *
     * @param int $contract_id Contract post ID
     * @param int $stage_index Stage index
     * @return bool True on success, false on failure
     */
    public static function delete_stage($contract_id, $stage_index) {
        $stages = self::get_stages($contract_id);
        
        if (!isset($stages[$stage_index])) {
            return false;
        }
        
        // Delete files associated with this stage
        if (!empty($stages[$stage_index]['files'])) {
            foreach ($stages[$stage_index]['files'] as $attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }
        }
        
        // Remove stage from array
        unset($stages[$stage_index]);
        
        // Re-index array
        $stages = array_values($stages);
        
        return self::save_stages($contract_id, $stages);
    }
    
    /**
     * Add file to stage
     *
     * @param int $contract_id Contract post ID
     * @param int $stage_index Stage index
     * @param int $attachment_id Attachment ID
     * @return bool True on success, false on failure
     */
    public static function add_file_to_stage($contract_id, $stage_index, $attachment_id) {
        // Validate attachment exists (can be image or any file)
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }
        
        $stages = self::get_stages($contract_id);
        
        if (!isset($stages[$stage_index])) {
            return false;
        }
        
        // Initialize files array if not exists
        if (!isset($stages[$stage_index]['files'])) {
            $stages[$stage_index]['files'] = array();
        }
        
        // Add attachment ID if not already exists
        if (!in_array($attachment_id, $stages[$stage_index]['files'])) {
            $stages[$stage_index]['files'][] = absint($attachment_id);
        }
        
        return self::save_stages($contract_id, $stages);
    }
    
    /**
     * Remove file from stage
     *
     * @param int $contract_id Contract post ID
     * @param int $stage_index Stage index
     * @param int $file_index File index in files array
     * @return bool True on success, false on failure
     */
    public static function remove_file_from_stage($contract_id, $stage_index, $file_index) {
        $stages = self::get_stages($contract_id);
        
        if (!isset($stages[$stage_index])) {
            return false;
        }
        
        if (!isset($stages[$stage_index]['files'][$file_index])) {
            return false;
        }
        
        // Get attachment ID before removing
        $attachment_id = $stages[$stage_index]['files'][$file_index];
        
        // Remove from array
        unset($stages[$stage_index]['files'][$file_index]);
        
        // Re-index array
        $stages[$stage_index]['files'] = array_values($stages[$stage_index]['files']);
        
        // Delete attachment
        wp_delete_attachment($attachment_id, true);
        
        return self::save_stages($contract_id, $stages);
    }
    
    /**
     * Save stages to post meta
     *
     * @param int $contract_id Contract post ID
     * @param array $stages Stages array
     * @return bool True on success, false on failure
     */
    private static function save_stages($contract_id, $stages) {
        $stages_json = wp_json_encode($stages, JSON_UNESCAPED_UNICODE);
        return update_post_meta($contract_id, '_contract_stages', $stages_json);
    }
    
    /**
     * Sanitize stage status
     *
     * @param string $status Status value
     * @return string Sanitized status
     */
    private static function sanitize_stage_status($status) {
        $allowed_statuses = array('pending', 'in_progress', 'completed');
        return in_array($status, $allowed_statuses) ? $status : 'pending';
    }
}

