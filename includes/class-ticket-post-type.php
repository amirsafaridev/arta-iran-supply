<?php
/**
 * Ticket Post Type
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_Ticket_Post_Type {
    
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
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_ticket_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_ticket_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('edit_form_after_title', array($this, 'render_ticket_status_header'));
        
        // AJAX handlers for messages
        add_action('wp_ajax_send_ticket_message', array($this, 'handle_send_ticket_message'));
        add_action('wp_ajax_upload_ticket_file', array($this, 'handle_upload_ticket_file'));
        add_action('wp_ajax_mark_message_read', array($this, 'handle_mark_message_read'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'ticket' && ($hook === 'post.php' || $hook === 'post-new.php')) {
            // Enqueue WordPress media uploader
            wp_enqueue_media();
            
            // Enqueue custom script for ticket messages
            wp_enqueue_script(
                'arta-ticket-messages',
                ARTA_IRAN_SUPPLY_PLUGIN_URL . 'assets/js/admin-ticket-messages.js',
                array('jquery'),
                ARTA_IRAN_SUPPLY_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('arta-ticket-messages', 'artaTicket', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('arta_ticket_ajax_nonce'),
                'postId' => get_the_ID(),
                'currentUserId' => get_current_user_id(),
            ));
        }
    }
    
    /**
     * Register ticket post type
     */
    public static function register_post_type() {
        $labels = array(
            'name' => __('تیکت‌ها', 'arta-iran-supply'),
            'singular_name' => __('تیکت', 'arta-iran-supply'),
            'menu_name' => __('تیکت‌ها', 'arta-iran-supply'),
            'add_new' => __('افزودن تیکت جدید', 'arta-iran-supply'),
            'add_new_item' => __('افزودن تیکت جدید', 'arta-iran-supply'),
            'edit_item' => __('ویرایش تیکت', 'arta-iran-supply'),
            'new_item' => __('تیکت جدید', 'arta-iran-supply'),
            'view_item' => __('مشاهده تیکت', 'arta-iran-supply'),
            'search_items' => __('جستجوی تیکت‌ها', 'arta-iran-supply'),
            'not_found' => __('تیکتی یافت نشد', 'arta-iran-supply'),
            'not_found_in_trash' => __('تیکتی در سطل زباله یافت نشد', 'arta-iran-supply'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 21,
            'menu_icon' => 'dashicons-tickets-alt',
            'supports' => array('title'),
            'show_in_rest' => false,
        );
        
        register_post_type('ticket', $args);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'ticket_info',
            __('اطلاعات تیکت', 'arta-iran-supply'),
            array($this, 'render_ticket_info_meta_box'),
            'ticket',
            'normal',
            'high'
        );
        
        add_meta_box(
            'ticket_messages',
            __('پیام‌های تیکت', 'arta-iran-supply'),
            array($this, 'render_ticket_messages_meta_box'),
            'ticket',
            'normal',
            'default'
        );
    }
    
    /**
     * Render ticket info meta box (combined user and status)
     */
    public function render_ticket_info_meta_box($post) {
        wp_nonce_field('arta_ticket_info_meta_box', 'arta_ticket_info_meta_box_nonce');
        
        $ticket_user_id = get_post_meta($post->ID, '_ticket_user_id', true);
        $status = get_post_meta($post->ID, '_ticket_status', true);
        if (empty($status)) {
            $status = 'open';
        }
        
        // Get all users for dropdown
        $users = get_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC',
        ));
        
        $statuses = array(
            'open' => __('باز', 'arta-iran-supply'),
            'in_progress' => __('در حال بررسی', 'arta-iran-supply'),
            'answered' => __('پاسخ داده شده', 'arta-iran-supply'),
            'closed' => __('بسته شده', 'arta-iran-supply'),
        );
        
        // Status colors
        $status_colors = array(
            'open' => '#2196F3',
            'in_progress' => '#FF9800',
            'answered' => '#4CAF50',
            'closed' => '#9E9E9E',
        );
        
        ?>
        <style>
            .ticket-info-box {
                background: #fff;
                border-radius: 8px;
                padding: 0;
            }
            .ticket-info-field {
                padding: 16px;
                border-bottom: 1px solid #f0f0f0;
            }
            .ticket-info-field:last-child {
                border-bottom: none;
            }
            .ticket-info-label {
                display: block;
                font-weight: 600;
                color: #333;
                margin-bottom: 8px;
                font-size: 13px;
            }
            .ticket-info-select {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 14px;
                background: #fff;
                transition: all 0.2s ease;
                box-sizing: border-box;
            }
            .ticket-info-select:focus {
                outline: none;
                border-color: #2196F3;
                box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
            }
            .ticket-status-badge {
                display: inline-block;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                color: #fff;
                text-align: center;
                min-width: 120px;
            }
        </style>
        
        <div class="ticket-info-box">
            <div class="ticket-info-field">
                <label for="ticket_user_id" class="ticket-info-label">
                    <span style="margin-left: 5px;">👤</span>
                    <?php _e('کاربر تیکت', 'arta-iran-supply'); ?>
                </label>
                <select name="ticket_user_id" id="ticket_user_id" class="ticket-info-select">
                    <option value=""><?php _e('-- انتخاب کاربر --', 'arta-iran-supply'); ?></option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($ticket_user_id, $user->ID); ?>>
                            <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="ticket-info-field">
                <label for="ticket_status" class="ticket-info-label">
                    <span style="margin-left: 5px;">📊</span>
                    <?php _e('وضعیت تیکت', 'arta-iran-supply'); ?>
                </label>
                <select name="ticket_status" id="ticket_status" class="ticket-info-select ticket-status-select">
                    <?php foreach ($statuses as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($status, $key); ?> data-color="<?php echo esc_attr($status_colors[$key] ?? '#2196F3'); ?>">
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var statusIcons = {
                'open': '🔵',
                'in_progress': '🟠',
                'answered': '🟢',
                'closed': '⚫'
            };
            
            var statusLabels = {
                'open': '<?php echo esc_js(__('باز', 'arta-iran-supply')); ?>',
                'in_progress': '<?php echo esc_js(__('در حال بررسی', 'arta-iran-supply')); ?>',
                'answered': '<?php echo esc_js(__('پاسخ داده شده', 'arta-iran-supply')); ?>',
                'closed': '<?php echo esc_js(__('بسته شده', 'arta-iran-supply')); ?>'
            };
            
            function updateStatusColor() {
                var select = $('#ticket_status');
                var selectedOption = select.find('option:selected');
                var color = selectedOption.data('color') || '#2196F3';
                
                select.css({
                    'background-color': color,
                    'color': '#fff',
                    'font-weight': '600'
                });
            }
            
            function showUnsavedChanges() {
                var headerBadge = $('.ticket-header-status-badge');
                if (headerBadge.length) {
                    // Add indicator that changes need to be saved
                    if (!headerBadge.find('.ticket-unsaved-indicator').length) {
                        headerBadge.append('<span class="ticket-unsaved-indicator" style="margin-right: 5px; animation: pulse 1.5s infinite;">⚠️</span>');
                    }
                    headerBadge.addClass('ticket-has-unsaved-changes');
                }
            }
            
            function hideUnsavedChanges() {
                var headerBadge = $('.ticket-header-status-badge');
                headerBadge.find('.ticket-unsaved-indicator').remove();
                headerBadge.removeClass('ticket-has-unsaved-changes');
            }
            
            // Initialize on page load
            updateStatusColor();
            
            // Track original status value
            var originalStatus = $('#ticket_status').val();
            
            $('#ticket_status').on('change', function() {
                updateStatusColor();
                var currentStatus = $(this).val();
                if (currentStatus !== originalStatus) {
                    showUnsavedChanges();
                } else {
                    hideUnsavedChanges();
                }
            });
            
            // Hide indicator when form is submitted
            $('#post').on('submit', function() {
                hideUnsavedChanges();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render ticket status and user info in header (after title)
     */
    public function render_ticket_status_header($post) {
        // Only show for ticket post type
        if (get_post_type($post) !== 'ticket') {
            return;
        }
        
        $status = get_post_meta($post->ID, '_ticket_status', true);
        if (empty($status)) {
            $status = 'open';
        }
        
        $ticket_user_id = get_post_meta($post->ID, '_ticket_user_id', true);
        $selected_user = $ticket_user_id ? get_user_by('ID', $ticket_user_id) : null;
        
        $statuses = array(
            'open' => __('باز', 'arta-iran-supply'),
            'in_progress' => __('در حال بررسی', 'arta-iran-supply'),
            'answered' => __('پاسخ داده شده', 'arta-iran-supply'),
            'closed' => __('بسته شده', 'arta-iran-supply'),
        );
        
        $status_colors = array(
            'open' => '#2196F3',
            'in_progress' => '#FF9800',
            'answered' => '#4CAF50',
            'closed' => '#9E9E9E',
        );
        
        $status_icons = array(
            'open' => '🔵',
            'in_progress' => '🟠',
            'answered' => '🟢',
            'closed' => '⚫',
        );
        
        ?>
        <style>
            .ticket-header-info-wrapper {
                margin: 15px 0;
                padding: 20px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                gap: 30px;
                flex-wrap: wrap;
            }
            .ticket-header-info-item {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .ticket-header-info-label {
                font-weight: 600;
                color: #555;
                font-size: 14px;
            }
            .ticket-header-user-info {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .ticket-header-user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 16px;
                flex-shrink: 0;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .ticket-header-user-details {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .ticket-header-user-name {
                font-weight: 600;
                color: #333;
                font-size: 14px;
            }
            .ticket-header-user-email {
                font-size: 12px;
                color: #666;
            }
            .ticket-header-status-badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
                color: #fff;
                background-color: <?php echo esc_attr($status_colors[$status] ?? '#2196F3'); ?>;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }
            .ticket-header-status-badge:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            .ticket-header-status-badge.ticket-has-unsaved-changes {
                border: 2px solid #FF9800;
                animation: borderPulse 2s infinite;
            }
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
            @keyframes borderPulse {
                0%, 100% { box-shadow: 0 2px 4px rgba(0,0,0,0.1), 0 0 0 0 rgba(255, 152, 0, 0.7); }
                50% { box-shadow: 0 2px 4px rgba(0,0,0,0.1), 0 0 0 4px rgba(255, 152, 0, 0); }
            }
            .ticket-unsaved-indicator {
                display: inline-block;
            }
            .ticket-header-no-user {
                color: #999;
                font-size: 13px;
                font-style: italic;
            }
        </style>
        <div class="ticket-header-info-wrapper">
            <?php if ($selected_user) : ?>
                <?php $initials = strtoupper(substr($selected_user->display_name, 0, 1)); ?>
                <div class="ticket-header-info-item">
                    <span class="ticket-header-info-label"><?php _e('کاربر تیکت:', 'arta-iran-supply'); ?></span>
                    <div class="ticket-header-user-info">
                        <div class="ticket-header-user-details">
                            <div class="ticket-header-user-name"><?php echo esc_html($selected_user->display_name); ?></div>
                            <div class="ticket-header-user-email"><?php echo esc_html($selected_user->user_email); ?></div>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="ticket-header-info-item">
                    <span class="ticket-header-info-label"><?php _e('کاربر تیکت:', 'arta-iran-supply'); ?></span>
                    <span class="ticket-header-no-user"><?php _e('کاربری انتخاب نشده است', 'arta-iran-supply'); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="ticket-header-info-item">
                <span class="ticket-header-info-label"><?php _e('وضعیت تیکت:', 'arta-iran-supply'); ?></span>
                <span class="ticket-header-status-badge" id="ticket-status-header-badge">
                    <span><?php echo esc_html($status_icons[$status] ?? '🔵'); ?></span>
                    <span><?php echo esc_html($statuses[$status]); ?></span>
                </span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render ticket messages meta box
     */
    public function render_ticket_messages_meta_box($post) {
        wp_nonce_field('arta_ticket_messages_meta_box', 'arta_ticket_messages_meta_box_nonce');
        
        $messages = $this->get_ticket_messages($post->ID);
        $current_user_id = get_current_user_id();
        $is_published = ($post->post_status === 'publish');
        
        ?>
        <style>
            .ticket-messages-container {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 0;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                height: auto;
                max-height: 100%;
            }
            .ticket-new-message-form {
                background: #fff;
                border-bottom: 2px solid #e9ecef;
                padding: 24px;
                margin: 0;
                flex-shrink: 0;
            }
            .ticket-messages-list {
                flex: 1;
                overflow-y: auto;
                overflow-x: hidden;
                padding: 24px;
                background: #fff;
                min-height: 200px;
                max-height: 500px;
            }
            .ticket-messages-list::-webkit-scrollbar {
                width: 8px;
            }
            .ticket-messages-list::-webkit-scrollbar-track {
                background: #f1f1f1;
            }
            .ticket-messages-list::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 4px;
            }
            .ticket-messages-list::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
            .ticket-new-message-form h3 {
                margin: 0 0 16px 0;
                font-size: 16px;
                font-weight: 600;
                color: #333;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .ticket-message-textarea {
                width: 100%;
                min-height: 120px;
                padding: 12px;
                border: 2px solid #e9ecef;
                border-radius: 8px;
                font-size: 14px;
                font-family: inherit;
                resize: vertical;
                transition: all 0.2s ease;
                box-sizing: border-box;
            }
            .ticket-message-textarea:focus {
                outline: none;
                border-color: #2196F3;
                box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
            }
            .ticket-files-preview {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin: 12px 0;
                min-height: 40px;
            }
            .ticket-file-preview-item {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                background: #f0f7ff;
                border: 1px solid #b3d9ff;
                border-radius: 6px;
                font-size: 13px;
            }
            .ticket-file-preview-item .remove-file-btn {
                background: #dc3545;
                color: white;
                border: none;
                padding: 2px 8px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                transition: background 0.2s;
            }
            .ticket-file-preview-item .remove-file-btn:hover {
                background: #c82333;
            }
            .ticket-message-actions {
                display: flex;
                gap: 12px;
                align-items: center;
                margin-top: 12px;
            }
            .ticket-upload-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 10px 16px;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 6px;
                color: #495057;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .ticket-upload-btn:hover {
                background: #e9ecef;
                border-color: #adb5bd;
            }
            .ticket-send-btn {
                padding: 10px 24px;
                background: #2196F3;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
            }
            .ticket-send-btn:hover {
                background: #1976D2;
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
            }
            .ticket-send-btn:disabled {
                background: #ccc;
                cursor: not-allowed;
                transform: none;
            }
            .ticket-messages-list {
                background: #fff;
                padding: 24px;
                max-height: 600px;
                overflow-y: auto;
            }
            .ticket-messages-list h3 {
                margin: 0 0 20px 0;
                font-size: 16px;
                font-weight: 600;
                color: #333;
                padding-bottom: 12px;
                border-bottom: 2px solid #e9ecef;
            }
            .ticket-message-item {
                margin-bottom: 20px;
                padding: 16px;
                border-radius: 12px;
                transition: all 0.2s ease;
                animation: fadeIn 0.3s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .ticket-message-item.sent {
                background: linear-gradient(135deg, #e3f2fd 0%, #f0f7ff 100%);
                border: 1px solid #b3d9ff;
                margin-right: 40px;
            }
            .ticket-message-item.received {
                background: #fff;
                border: 1px solid #e9ecef;
                margin-left: 40px;
            }
            .ticket-message-item.received.unread {
                border-right: 4px solid #2196F3;
                background: #f8f9ff;
            }
            .ticket-message-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 12px;
                gap: 12px;
            }
            .ticket-message-sender {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .ticket-message-avatar {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 14px;
                flex-shrink: 0;
            }
            .ticket-message-sender-info {
                flex: 1;
            }
            .ticket-message-sender-name {
                font-weight: 600;
                color: #333;
                font-size: 14px;
                margin-bottom: 2px;
            }
            .ticket-message-date {
                color: #6c757d;
                font-size: 12px;
            }
            .ticket-message-status {
                flex-shrink: 0;
            }
            .ticket-message-status-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }
            .ticket-message-status-badge.unread {
                background: #2196F3;
                color: white;
            }
            .ticket-message-status-badge.read {
                background: #4CAF50;
                color: white;
            }
            .ticket-message-content {
                color: #333;
                font-size: 14px;
                line-height: 1.6;
                margin-bottom: 12px;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .ticket-message-attachments {
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid #e9ecef;
            }
            .ticket-message-attachments-title {
                font-size: 12px;
                font-weight: 600;
                color: #6c757d;
                margin-bottom: 8px;
            }
            .ticket-attachment-item {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 6px;
                margin: 4px;
                transition: all 0.2s;
            }
            .ticket-attachment-item:hover {
                background: #e9ecef;
                border-color: #dee2e6;
            }
            .ticket-attachment-item img {
                width: 24px;
                height: 24px;
                object-fit: cover;
                border-radius: 4px;
            }
            .ticket-attachment-item a {
                color: #2196F3;
                text-decoration: none;
                font-size: 13px;
                font-weight: 500;
            }
            .ticket-attachment-item a:hover {
                text-decoration: underline;
            }
            .ticket-mark-read-btn {
                margin-top: 12px;
                padding: 6px 16px;
                background: #2196F3;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
            }
            .ticket-mark-read-btn:hover {
                background: #1976D2;
            }
            .ticket-no-messages {
                text-align: center;
                padding: 40px 20px;
                color: #6c757d;
                font-size: 14px;
            }
            .ticket-publish-notice {
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 8px;
                padding: 16px 20px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .ticket-publish-notice-icon {
                font-size: 24px;
            }
            .ticket-publish-notice-text {
                flex: 1;
                color: #856404;
                font-size: 14px;
                font-weight: 500;
            }
            .ticket-form-disabled {
                opacity: 0.6;
                pointer-events: none;
                position: relative;
            }
            .ticket-form-disabled::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.7);
                border-radius: 8px;
            }
        </style>
        
        <div id="ticket-messages-container" class="ticket-messages-container">
            <?php if (!$is_published) : ?>
                <div class="ticket-publish-notice">
                    <span class="ticket-publish-notice-icon">⚠️</span>
                    <span class="ticket-publish-notice-text">
                        <?php _e('برای ارسال پیام، ابتدا باید تیکت را منتشر کنید.', 'arta-iran-supply'); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <!-- New Message Form -->
            <div id="new-message-form" class="ticket-new-message-form <?php echo !$is_published ? 'ticket-form-disabled' : ''; ?>">
                <h3>
                    <span>💬</span>
                    <?php _e('ارسال پیام جدید', 'arta-iran-supply'); ?>
                </h3>
                <textarea id="ticket-message-content" name="ticket_message_content" class="ticket-message-textarea" rows="5" placeholder="<?php echo $is_published ? esc_attr__('متن پیام را وارد کنید...', 'arta-iran-supply') : esc_attr__('ابتدا تیکت را منتشر کنید...', 'arta-iran-supply'); ?>" <?php echo !$is_published ? 'disabled' : ''; ?>></textarea>
                
                <div id="ticket-files-preview" class="ticket-files-preview"></div>
                
                <div class="ticket-message-actions">
                    <button type="button" id="ticket-upload-file-btn" class="ticket-upload-btn" <?php echo !$is_published ? 'disabled' : ''; ?>>
                        <span>📎</span>
                        <?php _e('افزودن فایل', 'arta-iran-supply'); ?>
                    </button>
                    <input type="file" id="ticket-file-input" multiple style="display: none;" <?php echo !$is_published ? 'disabled' : ''; ?> />
                    <button type="button" id="ticket-send-message-btn" class="ticket-send-btn" <?php echo !$is_published ? 'disabled' : ''; ?>>
                        <?php _e('ارسال پیام', 'arta-iran-supply'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Messages List -->
            <div id="ticket-messages-list" class="ticket-messages-list">
                <h3>
                    <span>📋</span>
                    <?php _e('تاریخچه پیام‌ها', 'arta-iran-supply'); ?>
                </h3>
                <?php if (!empty($messages)) : ?>
                    <?php foreach ($messages as $message) : ?>
                        <?php
                        $sender = get_user_by('ID', $message['sender_id']);
                        $sender_name = $sender ? $sender->display_name : __('کاربر حذف شده', 'arta-iran-supply');
                        $is_read = isset($message['is_read']) ? $message['is_read'] : false;
                        $message_class = $message['sender_id'] == $current_user_id ? 'sent' : 'received';
                        $unread_class = !$is_read && $message_class === 'received' ? 'unread' : '';
                        $sender_initials = strtoupper(substr($sender_name, 0, 1));
                        ?>
                        <div class="ticket-message-item <?php echo esc_attr($message_class . ' ' . $unread_class); ?>" data-message-id="<?php echo esc_attr($message['id']); ?>">
                            <div class="ticket-message-header">
                                <div class="ticket-message-sender">
                                    <div class="ticket-message-avatar"><?php echo esc_html($sender_initials); ?></div>
                                    <div class="ticket-message-sender-info">
                                        <div class="ticket-message-sender-name"><?php echo esc_html($sender_name); ?></div>
                                        <div class="ticket-message-date"><?php echo esc_html($this->format_date($message['date'])); ?></div>
                                    </div>
                                </div>
                                <div class="ticket-message-status">
                                    <?php if ($message_class === 'received' && !$is_read) : ?>
                                        <span class="ticket-message-status-badge unread"><?php _e('خوانده نشده', 'arta-iran-supply'); ?></span>
                                    <?php elseif ($message_class === 'received' && $is_read) : ?>
                                        <span class="ticket-message-status-badge read"><?php _e('خوانده شده', 'arta-iran-supply'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="ticket-message-content">
                                <?php echo wp_kses_post(nl2br(esc_html($message['content']))); ?>
                            </div>
                            
                            <?php if (!empty($message['attachments'])) : ?>
                                <div class="ticket-message-attachments">
                                    <div class="ticket-message-attachments-title"><?php _e('فایل‌های پیوست:', 'arta-iran-supply'); ?></div>
                                    <div>
                                        <?php foreach ($message['attachments'] as $attachment_id) : ?>
                                            <?php
                                            $attachment = get_post($attachment_id);
                                            if ($attachment) {
                                                $file_url = wp_get_attachment_url($attachment_id);
                                                $file_name = get_the_title($attachment_id);
                                                $file_type = get_post_mime_type($attachment_id);
                                                $is_image = wp_attachment_is_image($attachment_id);
                                                $thumbnail = $is_image ? wp_get_attachment_image_url($attachment_id, 'thumbnail') : wp_mime_type_icon($file_type);
                                            ?>
                                            <div class="ticket-attachment-item">
                                                <?php if ($is_image) : ?>
                                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($file_name); ?>" />
                                                <?php else : ?>
                                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($file_name); ?>" />
                                                <?php endif; ?>
                                                <a href="<?php echo esc_url($file_url); ?>" target="_blank">
                                                    <?php echo esc_html($file_name); ?>
                                                </a>
                                            </div>
                                            <?php } ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($message_class === 'received' && !$is_read) : ?>
                                <button type="button" class="ticket-mark-read-btn mark-read-btn" data-message-id="<?php echo esc_attr($message['id']); ?>">
                                    <?php _e('✓ علامت‌گذاری به عنوان خوانده شده', 'arta-iran-supply'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="ticket-no-messages">
                        <p><?php _e('هنوز پیامی ارسال نشده است.', 'arta-iran-supply'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'ticket') {
            return;
        }
        
        // Save ticket info (user and status)
        if (isset($_POST['arta_ticket_info_meta_box_nonce']) && 
            wp_verify_nonce($_POST['arta_ticket_info_meta_box_nonce'], 'arta_ticket_info_meta_box')) {
            
            if (isset($_POST['ticket_user_id'])) {
                $user_id = absint($_POST['ticket_user_id']);
                update_post_meta($post_id, '_ticket_user_id', $user_id);
                
                // Also save user name for display
                if ($user_id > 0) {
                    $user = get_user_by('ID', $user_id);
                    if ($user) {
                        update_post_meta($post_id, '_ticket_user', $user->display_name);
                    }
                } else {
                    delete_post_meta($post_id, '_ticket_user');
                }
            }
            
            if (isset($_POST['ticket_status'])) {
                $status = sanitize_text_field($_POST['ticket_status']);
                $allowed_statuses = array('open', 'in_progress', 'answered', 'closed');
                if (in_array($status, $allowed_statuses)) {
                    update_post_meta($post_id, '_ticket_status', $status);
                }
            }
        }
    }
    
    /**
     * Get ticket messages
     */
    private function get_ticket_messages($ticket_id) {
        $messages_json = get_post_meta($ticket_id, '_ticket_messages', true);
        if (empty($messages_json)) {
            return array();
        }
        
        $messages = json_decode($messages_json, true);
        if (!is_array($messages)) {
            return array();
        }
        
        // Sort by date (newest first for display)
        usort($messages, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $messages;
    }
    
    /**
     * Format date for display
     */
    private function format_date($date) {
        $timestamp = strtotime($date);
        $date_format = get_option('date_format') . ' ' . get_option('time_format');
        return date_i18n($date_format, $timestamp);
    }
    
    /**
     * Handle send ticket message AJAX
     */
    public function handle_send_ticket_message() {
        check_ajax_referer('arta_ticket_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        $attachment_ids = isset($_POST['attachment_ids']) ? array_map('absint', $_POST['attachment_ids']) : array();
        
        if (!$ticket_id) {
            wp_send_json_error(array('message' => __('شناسه تیکت نامعتبر است.', 'arta-iran-supply')));
        }
        
        // Verify ticket exists
        $ticket = get_post($ticket_id);
        if (!$ticket || $ticket->post_type !== 'ticket') {
            wp_send_json_error(array('message' => __('تیکت یافت نشد.', 'arta-iran-supply')));
        }
        
        // Check if ticket is published
        if ($ticket->post_status !== 'publish') {
            wp_send_json_error(array('message' => __('برای ارسال پیام، ابتدا باید تیکت را منتشر کنید.', 'arta-iran-supply')));
        }
        
        if (empty($content)) {
            wp_send_json_error(array('message' => __('متن پیام نمی‌تواند خالی باشد.', 'arta-iran-supply')));
        }
        
        // Get existing messages
        $messages = $this->get_ticket_messages($ticket_id);
        
        // Create new message
        $new_message = array(
            'id' => uniqid('msg_'),
            'sender_id' => get_current_user_id(),
            'content' => $content,
            'date' => current_time('mysql'),
            'attachments' => $attachment_ids,
            'is_read' => false,
        );
        
        $messages[] = $new_message;
        
        // Save messages
        $messages_json = wp_json_encode($messages, JSON_UNESCAPED_UNICODE);
        update_post_meta($ticket_id, '_ticket_messages', $messages_json);
        
        // Update ticket status to "answered" if it was "open" or "in_progress"
        $current_status = get_post_meta($ticket_id, '_ticket_status', true);
        if (in_array($current_status, array('open', 'in_progress'))) {
            update_post_meta($ticket_id, '_ticket_status', 'answered');
        }
        
        // Format message for response
        $sender = get_user_by('ID', $new_message['sender_id']);
        $formatted_message = array(
            'id' => $new_message['id'],
            'sender_id' => $new_message['sender_id'],
            'sender_name' => $sender ? $sender->display_name : __('کاربر حذف شده', 'arta-iran-supply'),
            'content' => $new_message['content'],
            'date' => $new_message['date'],
            'formatted_date' => $this->format_date($new_message['date']),
            'attachments' => array(),
            'is_read' => false,
        );
        
        // Format attachments
        foreach ($new_message['attachments'] as $attachment_id) {
            $attachment = get_post($attachment_id);
            if ($attachment) {
                $formatted_message['attachments'][] = array(
                    'id' => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id),
                    'name' => get_the_title($attachment_id),
                    'type' => get_post_mime_type($attachment_id),
                    'is_image' => wp_attachment_is_image($attachment_id),
                );
            }
        }
        
        wp_send_json_success(array(
            'message' => __('پیام با موفقیت ارسال شد.', 'arta-iran-supply'),
            'ticket_message' => $formatted_message,
        ));
    }
    
    /**
     * Handle upload ticket file AJAX
     */
    public function handle_upload_ticket_file() {
        check_ajax_referer('arta_ticket_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        
        // Verify ticket exists
        $ticket = get_post($ticket_id);
        if (!$ticket || $ticket->post_type !== 'ticket') {
            wp_send_json_error(array('message' => __('تیکت یافت نشد.', 'arta-iran-supply')));
        }
        
        // Check if ticket is published
        if ($ticket->post_status !== 'publish') {
            wp_send_json_error(array('message' => __('برای آپلود فایل، ابتدا باید تیکت را منتشر کنید.', 'arta-iran-supply')));
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error(array('message' => __('فایلی ارسال نشده است.', 'arta-iran-supply')));
        }
        
        // Include WordPress file handling functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Validate file type
        $allowed_types = get_allowed_mime_types();
        $file_type = wp_check_filetype($_FILES['file']['name'], $allowed_types);
        
        if (empty($file_type['type']) || !in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('نوع فایل مجاز نیست.', 'arta-iran-supply')));
        }
        
        $upload = wp_handle_upload($_FILES['file'], array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        );
        
        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $ticket_id);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => __('خطا در ایجاد attachment.', 'arta-iran-supply')));
        }
        
        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);
        
        $mime_type = get_post_mime_type($attachment_id);
        $is_image = wp_attachment_is_image($attachment_id);
        $thumbnail = $is_image ? wp_get_attachment_image_url($attachment_id, 'thumbnail') : wp_mime_type_icon($mime_type);
        
        wp_send_json_success(array(
            'message' => __('فایل با موفقیت آپلود شد.', 'arta-iran-supply'),
            'attachment' => array(
                'id' => $attachment_id,
                'url' => wp_get_attachment_url($attachment_id),
                'name' => get_the_title($attachment_id),
                'type' => $mime_type,
                'is_image' => $is_image,
                'thumbnail' => $thumbnail,
            ),
        ));
    }
    
    /**
     * Handle mark message as read AJAX
     */
    public function handle_mark_message_read() {
        check_ajax_referer('arta_ticket_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
        
        if (!$ticket_id || !$message_id) {
            wp_send_json_error(array('message' => __('پارامترهای نامعتبر.', 'arta-iran-supply')));
        }
        
        // Verify ticket exists
        $ticket = get_post($ticket_id);
        if (!$ticket || $ticket->post_type !== 'ticket') {
            wp_send_json_error(array('message' => __('تیکت یافت نشد.', 'arta-iran-supply')));
        }
        
        // Get messages
        $messages = $this->get_ticket_messages($ticket_id);
        
        // Find and update message
        $found = false;
        foreach ($messages as &$message) {
            if ($message['id'] === $message_id) {
                $message['is_read'] = true;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            wp_send_json_error(array('message' => __('پیام یافت نشد.', 'arta-iran-supply')));
        }
        
        // Save updated messages
        $messages_json = wp_json_encode($messages, JSON_UNESCAPED_UNICODE);
        update_post_meta($ticket_id, '_ticket_messages', $messages_json);
        
        wp_send_json_success(array('message' => __('پیام به عنوان خوانده شده علامت‌گذاری شد.', 'arta-iran-supply')));
    }
    
    /**
     * Add custom columns
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['ticket_user'] = __('کاربر', 'arta-iran-supply');
        $new_columns['ticket_status'] = __('وضعیت', 'arta-iran-supply');
        $new_columns['ticket_messages_count'] = __('تعداد پیام‌ها', 'arta-iran-supply');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Render custom columns
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'ticket_user':
                $user_id = get_post_meta($post_id, '_ticket_user_id', true);
                if ($user_id) {
                    $user = get_user_by('ID', $user_id);
                    if ($user) {
                        echo esc_html($user->display_name);
                    } else {
                        echo esc_html(get_post_meta($post_id, '_ticket_user', true));
                    }
                } else {
                    echo '-';
                }
                break;
                
            case 'ticket_status':
                $status = get_post_meta($post_id, '_ticket_status', true);
                $statuses = array(
                    'open' => __('باز', 'arta-iran-supply'),
                    'in_progress' => __('در حال بررسی', 'arta-iran-supply'),
                    'answered' => __('پاسخ داده شده', 'arta-iran-supply'),
                    'closed' => __('بسته شده', 'arta-iran-supply'),
                );
                echo isset($statuses[$status]) ? esc_html($statuses[$status]) : '-';
                break;
                
            case 'ticket_messages_count':
                $messages = $this->get_ticket_messages($post_id);
                echo esc_html(count($messages));
                break;
        }
    }
}

