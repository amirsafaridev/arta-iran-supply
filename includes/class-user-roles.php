<?php
/**
 * User Roles Management
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_User_Roles {
    
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
     * Add organization role
     */
    public static function add_role() {
        $capabilities = array(
            'read' => true,
            'read_contracts' => true,
            'edit_own_contracts' => true,
            'upload_files' => true,
        );
        
        add_role('organization', __('سازمان', 'arta-iran-supply'), $capabilities);
    }
    
    /**
     * Remove organization role
     */
    public static function remove_role() {
        remove_role('organization');
    }
}

