<?php
/**
 * Organization Panel
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_Organization_Panel {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Query var name
     */
    const QUERY_VAR = 'contracts_panel';
    
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
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_filter('template_include', array($this, 'template_include'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_head', array($this, 'add_dynamic_css'), 999);
    }
    
    /**
     * Add rewrite rules
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^contracts-panel/?$',
            'index.php?' . self::QUERY_VAR . '=1',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }
    
    /**
     * Template include
     */
    public function template_include($template) {
        if (get_query_var(self::QUERY_VAR)) {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                // Show login page (not redirect)
                return ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'templates/panel-template.php';
            }
            
            // Check if user has organization role
            $current_user = wp_get_current_user();
            if (!in_array('organization', $current_user->roles)) {
                wp_die(__('شما دسترسی به این پنل را ندارید.', 'arta-iran-supply'), __('خطای دسترسی', 'arta-iran-supply'), array('response' => 403));
            }
            
            // Load panel template
            return ARTA_IRAN_SUPPLY_PLUGIN_DIR . 'templates/panel-template.php';
        }
        
        return $template;
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (!get_query_var(self::QUERY_VAR)) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'arta-panel-css',
            ARTA_IRAN_SUPPLY_PLUGIN_URL . 'assets/css/panel.css',
            array(),
            ARTA_IRAN_SUPPLY_VERSION
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'arta-panel-js',
            ARTA_IRAN_SUPPLY_PLUGIN_URL . 'assets/js/panel.js',
            array('jquery'),
            ARTA_IRAN_SUPPLY_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('arta-panel-js', 'artaPanel', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arta_ajax_nonce'),
            'logoutUrl' => wp_logout_url(home_url('/contracts-panel')),
        ));
    }
    
    /**
     * Add dynamic CSS based on settings
     */
    public function add_dynamic_css() {
        // Only add CSS on panel page
        if (!get_query_var(self::QUERY_VAR)) {
            return;
        }
        $settings = Arta_Iran_Supply_Settings::get_settings();
        
        $login_bg_color = isset($settings['login_bg_color']) ? $settings['login_bg_color'] : '#667eea';
        $login_primary_color = isset($settings['login_primary_color']) ? $settings['login_primary_color'] : '#667eea';
        $login_secondary_color = isset($settings['login_secondary_color']) ? $settings['login_secondary_color'] : '#764ba2';
        $login_bg_type = isset($settings['login_bg_type']) ? $settings['login_bg_type'] : 'gradient';
        $login_bg_image = isset($settings['login_bg_image']) ? $settings['login_bg_image'] : 0;
        $login_bg_animation = isset($settings['login_bg_animation']) ? $settings['login_bg_animation'] : 'shapes';
        $login_button_color = isset($settings['login_button_color']) ? $settings['login_button_color'] : '#667eea';
        $panel_primary_color = isset($settings['panel_primary_color']) ? $settings['panel_primary_color'] : '#0066ff';
        $panel_secondary_color = isset($settings['panel_secondary_color']) ? $settings['panel_secondary_color'] : '#00d4ff';
        $sidebar_bg_color = isset($settings['sidebar_bg_color']) ? $settings['sidebar_bg_color'] : '#ffffff';
        
        // Get background image URL
        $bg_image_url = '';
        if ($login_bg_type === 'image' && $login_bg_image) {
            $bg_image_url = wp_get_attachment_image_url($login_bg_image, 'full');
        }
        
        // Build background CSS based on type
        $bg_css = '';
        if ($login_bg_type === 'image' && $bg_image_url) {
            $bg_css = "background-image: url('{$bg_image_url}'); background-size: cover; background-position: center; background-repeat: no-repeat;";
        } elseif ($login_bg_type === 'solid') {
            $bg_css = "background: {$login_bg_color};";
        } else {
            $bg_css = "background: linear-gradient(135deg, {$login_bg_color} 0%, {$login_secondary_color} 100%);";
        }
        
        // Animation CSS
        $animation_css = '';
        if ($login_bg_animation === 'none') {
            $animation_css = '.login-shapes { display: none !important; }';
        } elseif ($login_bg_animation === 'wave') {
            $animation_css = '.login-shapes .shape { animation: wave 8s ease-in-out infinite !important; }';
        } elseif ($login_bg_animation === 'particles') {
            $animation_css = '.login-shapes .shape { animation: float 6s ease-in-out infinite !important; }';
        }
        
        // Disable gradient animation if not using gradient
        $gradient_animation = '';
        if ($login_bg_type !== 'gradient') {
            $gradient_animation = '.login-background { animation: none !important; }';
        }
        
        $css = "
        <style id='arta-panel-dynamic-css'>
        /* Login Page Background */
        .login-background {
            {$bg_css} !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
        }
        {$gradient_animation}
        {$animation_css}
        
        /* Login Page Colors */
        .login-card {
            border-top: 4px solid {$login_primary_color} !important;
        }
        .btn-login {
            background: {$login_button_color} !important;
            box-shadow: 0 8px 24px " . $this->hex_to_rgba($login_button_color, 0.4) . " !important;
        }
        .btn-login:hover {
            background: " . $this->darken_color($login_button_color, 10) . " !important;
            box-shadow: 0 12px 32px " . $this->hex_to_rgba($login_button_color, 0.5) . " !important;
        }
        .input-container:focus-within {
            border-color: {$login_primary_color} !important;
        }
        
        /* Panel Colors */
        .logo {
        }
        .sidebar {
            background: linear-gradient(180deg, {$sidebar_bg_color} 0%, #fafbfc 100%);
        }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(" . $this->hex_to_rgb($panel_primary_color) . ", 0.1) 0%, transparent 100%);
            border-right-color: {$panel_primary_color};
        }
        .menu-item:hover {
            background: rgba(" . $this->hex_to_rgb($panel_primary_color) . ", 0.05);
        }
        .stat-card {
            border-top: 4px solid {$panel_primary_color};
        }
        .btn-primary {
            background: linear-gradient(135deg, {$panel_primary_color} 0%, {$panel_secondary_color} 100%);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, {$panel_secondary_color} 0%, {$panel_primary_color} 100%);
        }
        </style>
        ";
        
        echo $css;
    }
    
    /**
     * Convert hex color to RGB
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "$r, $g, $b";
    }
    
    /**
     * Convert hex color to RGBA
     */
    private function hex_to_rgba($hex, $alpha = 1) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba($r, $g, $b, $alpha)";
    }
    
    /**
     * Darken hex color
     */
    private function darken_color($hex, $percent = 10) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));
        
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
                   str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
                   str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}

