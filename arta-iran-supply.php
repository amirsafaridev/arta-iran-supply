<?php
/**
 * Plugin Name: افزونه پنل مدیریت قراردادها
 * Plugin URI: https://example.com/arta-iran-supply
 * Description: پنل مدیریت قراردادها برای سازمان‌ها با امکان مدیریت مراحل قرارداد و آپلود فایل
 * Version: 1.0.0
 * Author: Amir Safari
 * Author URI: https://profiles.wordpress.org/amirsafaridevs/
 * Text Domain: arta-iran-supply
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ARTA_IRAN_SUPPLY_VERSION', '1.0.0');
define('ARTA_IRAN_SUPPLY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ARTA_IRAN_SUPPLY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ARTA_IRAN_SUPPLY_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Arta_Iran_Supply {
    
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
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load plugin files
        $this->load_dependencies();
        
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize classes
        add_action('plugins_loaded', array($this, 'load_classes'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'includes/class-user-roles.php';
        require_once ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'includes/class-contract-post-type.php';
        require_once ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'includes/class-contract-stages.php';
        require_once ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'includes/class-organization-panel.php';
        require_once ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'includes/class-help-menu.php';
        require_once ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'includes/class-settings.php';
        require_once ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'includes/class-request-order.php';
        require_once ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'includes/class-order-received.php';
    }
    
    /**
     * Load plugin classes
     */
    public function load_classes() {
        // Initialize user roles
        Arta_Iran_Supply_User_Roles::get_instance();
        
        // Initialize contract post type
        Arta_Iran_Supply_Contract_Post_Type::get_instance();
        
        // Initialize contract stages
        Arta_Iran_Supply_Contract_Stages::get_instance();
        
        // Initialize AJAX handler
        Arta_Iran_Supply_Ajax_Handler::get_instance();
        
        // Initialize organization panel
        Arta_Iran_Supply_Organization_Panel::get_instance();
        
        // Initialize help menu
        Arta_Iran_Supply_Help_Menu::get_instance();
        
        // Initialize settings
        Arta_Iran_Supply_Settings::get_instance();
        
        // Initialize request order handler
        Arta_Iran_Supply_Request_Order::get_instance();
        
        // Initialize order received handler
        Arta_Iran_Supply_Order_Received::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Register user role
        Arta_Iran_Supply_User_Roles::add_role();
        
        // Register post type
        Arta_Iran_Supply_Contract_Post_Type::register_post_type();
        
        // Register rewrite rules
        Arta_Iran_Supply_Organization_Panel::add_rewrite_rules();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Remove user role
        Arta_Iran_Supply_User_Roles::remove_role();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
Arta_Iran_Supply::get_instance();

