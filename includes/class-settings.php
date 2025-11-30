<?php
/**
 * Settings Menu
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_Settings {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Option name
     */
    const OPTION_NAME = 'arta_panel_settings';
    
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
        add_action('admin_menu', array($this, 'add_settings_submenu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_settings_scripts'));
    }
    
    /**
     * Add settings submenu under contracts menu
     */
    public function add_settings_submenu() {
        add_submenu_page(
            'edit.php?post_type=contract',
            __('تنظیمات', 'arta-iran-supply'),
            __('تنظیمات', 'arta-iran-supply'),
            'manage_options',
            'arta-contracts-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('arta_panel_settings', self::OPTION_NAME, array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['panel_title'])) {
            $sanitized['panel_title'] = sanitize_text_field($input['panel_title']);
        }
        
        if (isset($input['panel_logo'])) {
            $sanitized['panel_logo'] = absint($input['panel_logo']);
        }
        
        if (isset($input['login_bg_color'])) {
            $sanitized['login_bg_color'] = sanitize_hex_color($input['login_bg_color']);
        }
        
        if (isset($input['login_primary_color'])) {
            $sanitized['login_primary_color'] = sanitize_hex_color($input['login_primary_color']);
        }
        
        if (isset($input['login_secondary_color'])) {
            $sanitized['login_secondary_color'] = sanitize_hex_color($input['login_secondary_color']);
        }
        
        if (isset($input['panel_primary_color'])) {
            $sanitized['panel_primary_color'] = sanitize_hex_color($input['panel_primary_color']);
        }
        
        if (isset($input['panel_secondary_color'])) {
            $sanitized['panel_secondary_color'] = sanitize_hex_color($input['panel_secondary_color']);
        }
        
        if (isset($input['sidebar_bg_color'])) {
            $sanitized['sidebar_bg_color'] = sanitize_hex_color($input['sidebar_bg_color']);
        }
        
        if (isset($input['login_title'])) {
            $sanitized['login_title'] = sanitize_text_field($input['login_title']);
        }
        
        if (isset($input['login_subtitle'])) {
            $sanitized['login_subtitle'] = sanitize_text_field($input['login_subtitle']);
        }
        
        if (isset($input['login_bg_type'])) {
            $allowed_types = array('gradient', 'image', 'solid');
            $sanitized['login_bg_type'] = in_array($input['login_bg_type'], $allowed_types) ? $input['login_bg_type'] : 'gradient';
        }
        
        if (isset($input['login_bg_image'])) {
            $sanitized['login_bg_image'] = absint($input['login_bg_image']);
        }
        
        if (isset($input['login_bg_animation'])) {
            $allowed_animations = array('none', 'wave', 'particles', 'shapes');
            $sanitized['login_bg_animation'] = in_array($input['login_bg_animation'], $allowed_animations) ? $input['login_bg_animation'] : 'shapes';
        }
        
        if (isset($input['login_button_color'])) {
            $sanitized['login_button_color'] = sanitize_hex_color($input['login_button_color']);
        }
        
        // Handle request_order_enabled checkbox
        // Checkbox sends value only when checked, so we check if it's set and equals '1'
        if (isset($input['request_order_enabled']) && $input['request_order_enabled'] == '1') {
            $sanitized['request_order_enabled'] = 1;
        } else {
            $sanitized['request_order_enabled'] = 0;
        }
        
        if (isset($input['request_order_received_message'])) {
            $sanitized['request_order_received_message'] = wp_kses_post($input['request_order_received_message']);
        }
        
        return $sanitized;
    }
    
    /**
     * Enqueue settings page scripts and styles
     */
    public function enqueue_settings_scripts($hook) {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'contract_page_arta-contracts-settings') {
            return;
        }
        
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue media uploader
        wp_enqueue_media();
        
        // Enqueue custom CSS
        wp_enqueue_style(
            'arta-settings-css',
            ARTA_IRAN_SUPPLY_PLUGIN_URL . 'assets/css/settings-page.css',
            array(),
            ARTA_IRAN_SUPPLY_VERSION
        );
        
        // Enqueue custom JS
        wp_enqueue_script(
            'arta-settings-js',
            ARTA_IRAN_SUPPLY_PLUGIN_URL . 'assets/js/settings-page.js',
            array('jquery', 'wp-color-picker'),
            ARTA_IRAN_SUPPLY_VERSION,
            true
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما دسترسی به این صفحه را ندارید.', 'arta-iran-supply'));
        }
        
        $settings = get_option(self::OPTION_NAME, array());
        
        // Handle form submission
        if (isset($_POST['arta_save_settings']) && check_admin_referer('arta_save_settings', 'arta_settings_nonce')) {
            $new_settings = array(
                'panel_title' => isset($_POST['panel_title']) ? sanitize_text_field($_POST['panel_title']) : '',
                'panel_logo' => isset($_POST['panel_logo']) ? absint($_POST['panel_logo']) : 0,
                'login_bg_color' => isset($_POST['login_bg_color']) ? sanitize_hex_color($_POST['login_bg_color']) : '#667eea',
                'login_primary_color' => isset($_POST['login_primary_color']) ? sanitize_hex_color($_POST['login_primary_color']) : '#667eea',
                'login_secondary_color' => isset($_POST['login_secondary_color']) ? sanitize_hex_color($_POST['login_secondary_color']) : '#764ba2',
                'panel_primary_color' => isset($_POST['panel_primary_color']) ? sanitize_hex_color($_POST['panel_primary_color']) : '#0066ff',
                'panel_secondary_color' => isset($_POST['panel_secondary_color']) ? sanitize_hex_color($_POST['panel_secondary_color']) : '#00d4ff',
                'sidebar_bg_color' => isset($_POST['sidebar_bg_color']) ? sanitize_hex_color($_POST['sidebar_bg_color']) : '#ffffff',
                'login_title' => isset($_POST['login_title']) ? sanitize_text_field($_POST['login_title']) : 'خوش آمدید',
                'login_subtitle' => isset($_POST['login_subtitle']) ? sanitize_text_field($_POST['login_subtitle']) : 'لطفاً اطلاعات خود را وارد کنید',
                'login_bg_type' => isset($_POST['login_bg_type']) ? sanitize_text_field($_POST['login_bg_type']) : 'gradient',
                'login_bg_image' => isset($_POST['login_bg_image']) ? absint($_POST['login_bg_image']) : 0,
                'login_bg_animation' => isset($_POST['login_bg_animation']) ? sanitize_text_field($_POST['login_bg_animation']) : 'shapes',
                'login_button_color' => isset($_POST['login_button_color']) ? sanitize_hex_color($_POST['login_button_color']) : '#667eea',
                'request_order_enabled' => (isset($_POST['request_order_enabled']) && $_POST['request_order_enabled'] == '1') ? 1 : 0,
                'request_order_received_message' => isset($_POST['request_order_received_message']) ? wp_kses_post($_POST['request_order_received_message']) : '',
            );
            
            update_option(self::OPTION_NAME, $new_settings);
            $settings = $new_settings;
            
            echo '<div class="notice notice-success is-dismissible"><p>' . __('تنظیمات با موفقیت ذخیره شد.', 'arta-iran-supply') . '</p></div>';
        }
        
        // Default values
        $panel_title = isset($settings['panel_title']) ? $settings['panel_title'] : 'پنل مدیریت';
        $panel_logo = isset($settings['panel_logo']) ? $settings['panel_logo'] : 0;
        $login_bg_color = isset($settings['login_bg_color']) ? $settings['login_bg_color'] : '#667eea';
        $login_primary_color = isset($settings['login_primary_color']) ? $settings['login_primary_color'] : '#667eea';
        $login_secondary_color = isset($settings['login_secondary_color']) ? $settings['login_secondary_color'] : '#764ba2';
        $panel_primary_color = isset($settings['panel_primary_color']) ? $settings['panel_primary_color'] : '#0066ff';
        $panel_secondary_color = isset($settings['panel_secondary_color']) ? $settings['panel_secondary_color'] : '#00d4ff';
        $sidebar_bg_color = isset($settings['sidebar_bg_color']) ? $settings['sidebar_bg_color'] : '#ffffff';
        $login_title = isset($settings['login_title']) ? $settings['login_title'] : 'خوش آمدید';
        $login_subtitle = isset($settings['login_subtitle']) ? $settings['login_subtitle'] : 'لطفاً اطلاعات خود را وارد کنید';
        $login_bg_type = isset($settings['login_bg_type']) ? $settings['login_bg_type'] : 'gradient';
        $login_bg_image = isset($settings['login_bg_image']) ? $settings['login_bg_image'] : 0;
        $login_bg_animation = isset($settings['login_bg_animation']) ? $settings['login_bg_animation'] : 'shapes';
        $login_button_color = isset($settings['login_button_color']) ? $settings['login_button_color'] : '#667eea';
        $request_order_enabled = isset($settings['request_order_enabled']) ? $settings['request_order_enabled'] : 0;
        $request_order_received_message = isset($settings['request_order_received_message']) ? $settings['request_order_received_message'] : __('درخواست شما ثبت شد و پس از بررسی توسط تیم ما در اسرع وقت با شما تماس گرفته خواهد شد', 'arta-iran-supply');
        
        $logo_url = $panel_logo ? wp_get_attachment_image_url($panel_logo, 'full') : '';
        $bg_image_url = $login_bg_image ? wp_get_attachment_image_url($login_bg_image, 'full') : '';
        ?>
        <div class="wrap arta-settings-page">
            <h1 class="arta-settings-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('تنظیمات ', 'arta-iran-supply'); ?>
            </h1>
            
            <form method="post" action="" class="arta-settings-form">
                <?php wp_nonce_field('arta_save_settings', 'arta_settings_nonce'); ?>
                
                <div class="arta-settings-tabs">
                    <div class="arta-tab-nav">
                        <button type="button" class="arta-tab-btn active" data-tab="general">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php _e('عمومی', 'arta-iran-supply'); ?>
                        </button>
                        <button type="button" class="arta-tab-btn" data-tab="login">
                            <span class="dashicons dashicons-lock"></span>
                            <?php _e('صفحه ورود', 'arta-iran-supply'); ?>
                        </button>
                        <button type="button" class="arta-tab-btn" data-tab="panel">
                            <span class="dashicons dashicons-admin-appearance"></span>
                            <?php _e('پنل', 'arta-iran-supply'); ?>
                        </button>
                        <button type="button" class="arta-tab-btn" data-tab="request">
                            <span class="dashicons dashicons-cart"></span>
                            <?php _e('درخواست', 'arta-iran-supply'); ?>
                        </button>
                    </div>
                    
                    <!-- General Tab -->
                    <div class="arta-tab-content active" id="tab-general">
                        <div class="arta-settings-section">
                            <h2 class="arta-section-title">
                                <span class="dashicons dashicons-info"></span>
                                <?php _e('تنظیمات عمومی', 'arta-iran-supply'); ?>
                            </h2>
                            
                            <div class="arta-form-group">
                                <label for="panel_title">
                                    <strong><?php _e('عنوان پنل', 'arta-iran-supply'); ?></strong>
                                    <span class="description"><?php _e('عنوانی که در بالای پنل نمایش داده می‌شود', 'arta-iran-supply'); ?></span>
                                </label>
                                <input type="text" id="panel_title" name="panel_title" value="<?php echo esc_attr($panel_title); ?>" class="regular-text" />
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="panel_logo">
                                    <strong><?php _e('لوگو پنل', 'arta-iran-supply'); ?></strong>
                                    <span class="description"><?php _e('لوگوی پنل که در سایدبار نمایش داده می‌شود', 'arta-iran-supply'); ?></span>
                                </label>
                                <div class="arta-logo-upload">
                                    <div class="arta-logo-preview" id="logo-preview">
                                        <?php if ($logo_url) : ?>
                                            <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" />
                                            <button type="button" class="arta-remove-logo" id="remove-logo">×</button>
                                        <?php else : ?>
                                            <div class="arta-logo-placeholder">
                                                <span class="dashicons dashicons-format-image"></span>
                                                <p><?php _e('هیچ لوگویی انتخاب نشده', 'arta-iran-supply'); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="button button-secondary" id="upload-logo">
                                        <?php _e('انتخاب لوگو', 'arta-iran-supply'); ?>
                                    </button>
                                    <input type="hidden" id="panel_logo" name="panel_logo" value="<?php echo esc_attr($panel_logo); ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Login Tab -->
                    <div class="arta-tab-content" id="tab-login">
                        <div class="arta-settings-section">
                            <h2 class="arta-section-title">
                                <span class="dashicons dashicons-lock"></span>
                                <?php _e('تنظیمات صفحه ورود', 'arta-iran-supply'); ?>
                            </h2>
                            
                            <div class="arta-form-group">
                                <label for="login_title">
                                    <strong><?php _e('عنوان صفحه ورود', 'arta-iran-supply'); ?></strong>
                                </label>
                                <input type="text" id="login_title" name="login_title" value="<?php echo esc_attr($login_title); ?>" class="regular-text" />
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="login_subtitle">
                                    <strong><?php _e('زیرعنوان صفحه ورود', 'arta-iran-supply'); ?></strong>
                                </label>
                                <input type="text" id="login_subtitle" name="login_subtitle" value="<?php echo esc_attr($login_subtitle); ?>" class="regular-text" />
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="login_bg_color">
                                    <strong><?php _e('رنگ پس‌زمینه صفحه ورود', 'arta-iran-supply'); ?></strong>
                                </label>
                                <input type="text" id="login_bg_color" name="login_bg_color" value="<?php echo esc_attr($login_bg_color); ?>" class="arta-color-picker" />
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="login_primary_color">
                                    <strong><?php _e('رنگ اصلی صفحه ورود', 'arta-iran-supply'); ?></strong>
                                </label>
                                <input type="text" id="login_primary_color" name="login_primary_color" value="<?php echo esc_attr($login_primary_color); ?>" class="arta-color-picker" />
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="login_secondary_color">
                                    <strong><?php _e('رنگ ثانویه صفحه ورود', 'arta-iran-supply'); ?></strong>
                                </label>
                                <input type="text" id="login_secondary_color" name="login_secondary_color" value="<?php echo esc_attr($login_secondary_color); ?>" class="arta-color-picker" />
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="login_button_color">
                                    <strong><?php _e('رنگ دکمه ورود', 'arta-iran-supply'); ?></strong>
                                    <span class="description"><?php _e('رنگ اصلی دکمه ورود', 'arta-iran-supply'); ?></span>
                                </label>
                                <input type="text" id="login_button_color" name="login_button_color" value="<?php echo esc_attr($login_button_color); ?>" class="arta-color-picker" />
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="login_bg_type">
                                    <strong><?php _e('نوع پس‌زمینه صفحه ورود', 'arta-iran-supply'); ?></strong>
                                    <span class="description"><?php _e('نوع پس‌زمینه صفحه ورود را انتخاب کنید', 'arta-iran-supply'); ?></span>
                                </label>
                                <select id="login_bg_type" name="login_bg_type" class="regular-text">
                                    <option value="gradient" <?php selected($login_bg_type, 'gradient'); ?>><?php _e('گرادیان', 'arta-iran-supply'); ?></option>
                                    <option value="image" <?php selected($login_bg_type, 'image'); ?>><?php _e('تصویر', 'arta-iran-supply'); ?></option>
                                    <option value="solid" <?php selected($login_bg_type, 'solid'); ?>><?php _e('رنگ ساده', 'arta-iran-supply'); ?></option>
                                </select>
                            </div>
                            
                            <div class="arta-form-group" id="bg-image-group" style="<?php echo $login_bg_type !== 'image' ? 'display: none;' : ''; ?>">
                                <label for="login_bg_image">
                                    <strong><?php _e('تصویر پس‌زمینه', 'arta-iran-supply'); ?></strong>
                                    <span class="description"><?php _e('تصویر پس‌زمینه صفحه ورود', 'arta-iran-supply'); ?></span>
                                </label>
                                <div class="arta-logo-upload">
                                    <div class="arta-logo-preview" id="bg-image-preview">
                                        <?php if ($bg_image_url) : ?>
                                            <img src="<?php echo esc_url($bg_image_url); ?>" alt="Background" />
                                            <button type="button" class="arta-remove-logo" id="remove-bg-image">×</button>
                                        <?php else : ?>
                                            <div class="arta-logo-placeholder">
                                                <span class="dashicons dashicons-format-image"></span>
                                                <p><?php _e('هیچ تصویری انتخاب نشده', 'arta-iran-supply'); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="button button-secondary" id="upload-bg-image">
                                        <?php _e('انتخاب تصویر', 'arta-iran-supply'); ?>
                                    </button>
                                    <input type="hidden" id="login_bg_image" name="login_bg_image" value="<?php echo esc_attr($login_bg_image); ?>" />
                                </div>
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="login_bg_animation">
                                    <strong><?php _e('انیمیشن پس‌زمینه', 'arta-iran-supply'); ?></strong>
                                    <span class="description"><?php _e('نوع انیمیشن پس‌زمینه صفحه ورود', 'arta-iran-supply'); ?></span>
                                </label>
                                <select id="login_bg_animation" name="login_bg_animation" class="regular-text">
                                    <option value="none" <?php selected($login_bg_animation, 'none'); ?>><?php _e('بدون انیمیشن', 'arta-iran-supply'); ?></option>
                                    <option value="shapes" <?php selected($login_bg_animation, 'shapes'); ?>><?php _e('اشکال متحرک', 'arta-iran-supply'); ?></option>
                                    <option value="wave" <?php selected($login_bg_animation, 'wave'); ?>><?php _e('موج', 'arta-iran-supply'); ?></option>
                                    <option value="particles" <?php selected($login_bg_animation, 'particles'); ?>><?php _e('ذرات', 'arta-iran-supply'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel Tab -->
                    <div class="arta-tab-content" id="tab-panel">
                        <div class="arta-settings-section">
                            <h2 class="arta-section-title">
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <?php _e('تنظیمات رنگ‌بندی پنل', 'arta-iran-supply'); ?>
                            </h2>
                            
                            <div class="arta-form-group">
                                <label for="panel_primary_color">
                                    <strong><?php _e('رنگ اصلی پنل', 'arta-iran-supply'); ?></strong>
                                </label>
                                <input type="text" id="panel_primary_color" name="panel_primary_color" value="<?php echo esc_attr($panel_primary_color); ?>" class="arta-color-picker" />
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="panel_secondary_color">
                                    <strong><?php _e('رنگ ثانویه پنل', 'arta-iran-supply'); ?></strong>
                                </label>
                                <input type="text" id="panel_secondary_color" name="panel_secondary_color" value="<?php echo esc_attr($panel_secondary_color); ?>" class="arta-color-picker" />
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="sidebar_bg_color">
                                    <strong><?php _e('رنگ پس‌زمینه سایدبار', 'arta-iran-supply'); ?></strong>
                                </label>
                                <input type="text" id="sidebar_bg_color" name="sidebar_bg_color" value="<?php echo esc_attr($sidebar_bg_color); ?>" class="arta-color-picker" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Request Tab -->
                    <div class="arta-tab-content" id="tab-request">
                        <div class="arta-settings-section">
                            <h2 class="arta-section-title">
                                <span class="dashicons dashicons-cart"></span>
                                <?php _e('تنظیمات ثبت درخواست', 'arta-iran-supply'); ?>
                            </h2>
                            
                            <div class="arta-form-group">
                                <label for="request_order_enabled" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" id="request_order_enabled" name="request_order_enabled" value="1" <?php checked($request_order_enabled, 1); ?> />
                                    <strong><?php _e('فعال‌سازی ثبت درخواست', 'arta-iran-supply'); ?></strong>
                                </label>
                                <span class="description" style="display: block; margin-top: 10px; margin-right: 30px;">
                                    <?php _e('با فعال‌سازی این گزینه، دکمه‌های "افزودن به سبد خرید" به "ثبت درخواست" تغییر می‌کنند و کاربران مستقیماً به صفحه تسویه حساب هدایت می‌شوند. در صفحه تسویه حساب، روش‌های پرداخت نمایش داده نمی‌شوند و سفارش در حالت "در انتظار بررسی" ثبت می‌شود.', 'arta-iran-supply'); ?>
                                </span>
                            </div>
                            
                            <div class="arta-form-group">
                                <label for="request_order_received_message">
                                    <strong><?php _e('پیام صفحه دریافت سفارش', 'arta-iran-supply'); ?></strong>
                                    <span class="description" style="display: block; margin-top: 5px;">
                                        <?php _e('این پیام در صفحه دریافت سفارش (order-received) برای سفارشاتی که با روش پرداخت "ثبت درخواست" ثبت شده‌اند نمایش داده می‌شود.', 'arta-iran-supply'); ?>
                                    </span>
                                </label>
                                <textarea 
                                    id="request_order_received_message" 
                                    name="request_order_received_message" 
                                    rows="5" 
                                    class="large-text"
                                    style="width: 100%; min-height: 120px; padding: 10px; font-family: inherit;"
                                ><?php echo esc_textarea($request_order_received_message); ?></textarea>
                                <p class="description" style="margin-top: 5px;">
                                    <?php _e('می‌توانید از خطوط جدید برای شکستن متن استفاده کنید.', 'arta-iran-supply'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="arta-settings-footer">
                    <button type="submit" name="arta_save_settings" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('ذخیره تنظیمات', 'arta-iran-supply'); ?>
                    </button>
                    <button type="button" class="button button-secondary button-large" id="reset-settings">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('بازنشانی به پیش‌فرض', 'arta-iran-supply'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get settings
     */
    public static function get_settings() {
        return get_option(self::OPTION_NAME, array());
    }
    
    /**
     * Get setting value
     */
    public static function get_setting($key, $default = '') {
        $settings = self::get_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
}

