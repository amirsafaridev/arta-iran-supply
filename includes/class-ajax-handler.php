<?php
/**
 * AJAX Handler
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_Ajax_Handler {
    
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
        // User login (public)
        add_action('wp_ajax_user_login', array($this, 'handle_user_login'));
        add_action('wp_ajax_nopriv_user_login', array($this, 'handle_user_login'));
        
        // Contract operations (authenticated)
        add_action('wp_ajax_get_contracts', array($this, 'handle_get_contracts'));
        add_action('wp_ajax_get_contract_detail', array($this, 'handle_get_contract_detail'));
        add_action('wp_ajax_get_dashboard_stats', array($this, 'handle_get_dashboard_stats'));
        
        // Stage operations
        add_action('wp_ajax_add_contract_stage', array($this, 'handle_add_contract_stage'));
        add_action('wp_ajax_update_stage_status', array($this, 'handle_update_stage_status'));
        add_action('wp_ajax_update_stage_title', array($this, 'handle_update_stage_title'));
        add_action('wp_ajax_delete_stage', array($this, 'handle_delete_stage'));
        
        // File operations
        add_action('wp_ajax_upload_stage_file', array($this, 'handle_upload_stage_file'));
        add_action('wp_ajax_delete_stage_file', array($this, 'handle_delete_stage_file'));
        
        // Recent activities
        add_action('wp_ajax_get_recent_activities', array($this, 'handle_get_recent_activities'));
        
        // Ticket operations
        add_action('wp_ajax_get_user_tickets', array($this, 'handle_get_user_tickets'));
        add_action('wp_ajax_create_ticket', array($this, 'handle_create_ticket'));
        add_action('wp_ajax_get_ticket_detail', array($this, 'handle_get_ticket_detail'));
        add_action('wp_ajax_send_ticket_message_panel', array($this, 'handle_send_ticket_message_panel'));
        add_action('wp_ajax_upload_ticket_file_panel', array($this, 'handle_upload_ticket_file_panel'));
        add_action('wp_ajax_get_tickets_notifications', array($this, 'handle_get_tickets_notifications'));
    }
    
    /**
     * Handle user login
     */
    public function handle_user_login() {
        // Verify nonce (for logged out users, we use wp_verify_nonce)
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'arta_ajax_nonce')) {
            wp_send_json_error(array('message' => __('خطای امنیتی. لطفاً صفحه را رفرش کنید.', 'arta-iran-supply')));
        }
        
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']) ? (bool) $_POST['remember'] : false;
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => __('نام کاربری و رمز عبور الزامی است.', 'arta-iran-supply')));
        }
        
        $credentials = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember,
        );
        
        $user = wp_signon($credentials, false);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
        }
        
        // Check if user has organization role
        if (!in_array('organization', $user->roles)) {
            wp_logout();
            wp_send_json_error(array('message' => __('شما دسترسی به این پنل را ندارید.', 'arta-iran-supply')));
        }
        
        wp_send_json_success(array(
            'message' => __('ورود موفقیت‌آمیز بود.', 'arta-iran-supply'),
            'user' => array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
            ),
        ));
    }
    
    /**
     * Handle get contracts
     */
    public function handle_get_contracts() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!current_user_can('read_contracts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $current_user_id = get_current_user_id();
        
        // Get contracts where current user is the client
        $args = array(
            'post_type' => 'contract',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_contract_client_user_id',
                    'value' => $current_user_id,
                    'compare' => '=',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        $contracts = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $contracts[] = $this->format_contract_data(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array('contracts' => $contracts));
    }
    
    /**
     * Handle get contract detail
     */
    public function handle_get_contract_detail() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!current_user_can('read_contracts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $contract_id = isset($_POST['contract_id']) ? absint($_POST['contract_id']) : 0;
        
        if (!$contract_id) {
            wp_send_json_error(array('message' => __('شناسه قرارداد نامعتبر است.', 'arta-iran-supply')));
        }
        
        // Verify contract belongs to current user (as client)
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
            wp_send_json_error(array('message' => __('قرارداد یافت نشد.', 'arta-iran-supply')));
        }
        
        $client_user_id = get_post_meta($contract_id, '_contract_client_user_id', true);
        if ($client_user_id != get_current_user_id()) {
            wp_send_json_error(array('message' => __('شما دسترسی به این قرارداد را ندارید.', 'arta-iran-supply')));
        }
        
        $contract_data = $this->format_contract_data($contract_id, true);
        
        wp_send_json_success(array('contract' => $contract_data));
    }
    
    /**
     * Handle get dashboard stats
     */
    public function handle_get_dashboard_stats() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!current_user_can('read_contracts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $current_user_id = get_current_user_id();
        
        // Get contracts where current user is the client
        $args = array(
            'post_type' => 'contract',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_contract_client_user_id',
                    'value' => $current_user_id,
                    'compare' => '=',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        $total = $query->post_count;
        $completed = 0;
        $in_progress = 0;
        $total_value = 0;
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $status = get_post_meta($post_id, '_contract_status', true);
                
                if ($status === 'completed') {
                    $completed++;
                } elseif ($status === 'in_progress') {
                    $in_progress++;
                }
                
                $value = get_post_meta($post_id, '_contract_value', true);
                if ($value) {
                    // Remove non-numeric characters for calculation
                    $numeric_value = preg_replace('/[^0-9]/', '', $value);
                    $total_value += floatval($numeric_value);
                }
            }
            wp_reset_postdata();
        }
        
        // Get open tickets count for current user
        $ticket_args = array(
            'post_type' => 'ticket',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_ticket_user_id',
                    'value' => $current_user_id,
                    'compare' => '=',
                ),
                array(
                    'key' => '_ticket_status',
                    'value' => 'open',
                    'compare' => '=',
                ),
            ),
        );
        
        $ticket_query = new WP_Query($ticket_args);
        $open_tickets_count = $ticket_query->post_count;
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $in_progress,
            'open_tickets' => $open_tickets_count,
        ));
    }
    
    /**
     * Handle add contract stage (Admin only)
     */
    public function handle_add_contract_stage() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_contracts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $contract_id = isset($_POST['contract_id']) ? absint($_POST['contract_id']) : 0;
        
        // Verify contract exists
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
            wp_send_json_error(array('message' => __('قرارداد یافت نشد.', 'arta-iran-supply')));
        }
        
        $stage_data = array(
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'pending',
            'date' => isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        );
        
        $stage_index = Arta_Iran_Supply_Contract_Stages::add_stage($contract_id, $stage_data);
        
        if ($stage_index !== false) {
            wp_send_json_success(array(
                'message' => __('مرحله با موفقیت اضافه شد.', 'arta-iran-supply'),
                'stage_index' => $stage_index,
            ));
        } else {
            wp_send_json_error(array('message' => __('خطا در افزودن مرحله.', 'arta-iran-supply')));
        }
    }
    
    /**
     * Handle update stage status (Admin only)
     */
    public function handle_update_stage_status() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_contracts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $contract_id = isset($_POST['contract_id']) ? absint($_POST['contract_id']) : 0;
        $stage_index = isset($_POST['stage_index']) ? absint($_POST['stage_index']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // Verify contract exists
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
            wp_send_json_error(array('message' => __('قرارداد یافت نشد.', 'arta-iran-supply')));
        }
        
        $result = Arta_Iran_Supply_Contract_Stages::update_stage($contract_id, $stage_index, array('status' => $status));
        
        if ($result) {
            wp_send_json_success(array('message' => __('وضعیت مرحله به‌روزرسانی شد.', 'arta-iran-supply')));
        } else {
            wp_send_json_error(array('message' => __('خطا در به‌روزرسانی وضعیت.', 'arta-iran-supply')));
        }
    }
    
    /**
     * Handle update stage title
     */
    public function handle_update_stage_title() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_own_contracts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $contract_id = isset($_POST['contract_id']) ? absint($_POST['contract_id']) : 0;
        $stage_index = isset($_POST['stage_index']) ? absint($_POST['stage_index']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        
        // Verify contract belongs to current user
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract' || $contract->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('شما دسترسی به این قرارداد را ندارید.', 'arta-iran-supply')));
        }
        
        $result = Arta_Iran_Supply_Contract_Stages::update_stage($contract_id, $stage_index, array('title' => $title));
        
        if ($result) {
            wp_send_json_success(array('message' => __('عنوان مرحله به‌روزرسانی شد.', 'arta-iran-supply')));
        } else {
            wp_send_json_error(array('message' => __('خطا در به‌روزرسانی عنوان.', 'arta-iran-supply')));
        }
    }
    
    /**
     * Handle delete stage (Admin only)
     */
    public function handle_delete_stage() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_contracts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $contract_id = isset($_POST['contract_id']) ? absint($_POST['contract_id']) : 0;
        $stage_index = isset($_POST['stage_index']) ? absint($_POST['stage_index']) : 0;
        
        // Verify contract exists
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
            wp_send_json_error(array('message' => __('قرارداد یافت نشد.', 'arta-iran-supply')));
        }
        
        $result = Arta_Iran_Supply_Contract_Stages::delete_stage($contract_id, $stage_index);
        
        if ($result) {
            wp_send_json_success(array('message' => __('مرحله حذف شد.', 'arta-iran-supply')));
        } else {
            wp_send_json_error(array('message' => __('خطا در حذف مرحله.', 'arta-iran-supply')));
        }
    }
    
    /**
     * Handle upload stage file (Admin only)
     */
    public function handle_upload_stage_file() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_contracts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $contract_id = isset($_POST['contract_id']) ? absint($_POST['contract_id']) : 0;
        $stage_index = isset($_POST['stage_index']) ? absint($_POST['stage_index']) : 0;
        
        // Verify contract exists
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
            wp_send_json_error(array('message' => __('قرارداد یافت نشد.', 'arta-iran-supply')));
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
        
        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $contract_id);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => __('خطا در ایجاد attachment.', 'arta-iran-supply')));
        }
        
        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);
        
        // Add file to stage
        $result = Arta_Iran_Supply_Contract_Stages::add_file_to_stage($contract_id, $stage_index, $attachment_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('فایل با موفقیت آپلود شد.', 'arta-iran-supply'),
                'attachment_id' => $attachment_id,
                'url' => wp_get_attachment_url($attachment_id),
            ));
        } else {
            wp_send_json_error(array('message' => __('خطا در افزودن فایل به مرحله.', 'arta-iran-supply')));
        }
    }
    
    /**
     * Handle delete stage file (Admin only)
     */
    public function handle_delete_stage_file() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_contracts')) {
            wp_send_json_error(array('message' => __('شما دسترسی به این عملیات را ندارید.', 'arta-iran-supply')));
        }
        
        $contract_id = isset($_POST['contract_id']) ? absint($_POST['contract_id']) : 0;
        $stage_index = isset($_POST['stage_index']) ? absint($_POST['stage_index']) : 0;
        $file_index = isset($_POST['file_index']) ? absint($_POST['file_index']) : 0;
        
        // Verify contract exists
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
            wp_send_json_error(array('message' => __('قرارداد یافت نشد.', 'arta-iran-supply')));
        }
        
        $result = Arta_Iran_Supply_Contract_Stages::remove_file_from_stage($contract_id, $stage_index, $file_index);
        
        if ($result) {
            wp_send_json_success(array('message' => __('فایل حذف شد.', 'arta-iran-supply')));
        } else {
            wp_send_json_error(array('message' => __('خطا در حذف فایل.', 'arta-iran-supply')));
        }
    }
    
    /**
     * Format contract data for JSON response
     *
     * @param int $contract_id Contract post ID
     * @param bool $include_stages Whether to include stages
     * @return array Formatted contract data
     */
    private function format_contract_data($contract_id, $include_stages = false) {
        $contract = get_post($contract_id);
        
        if (!$contract) {
            return array();
        }
        
        $status = get_post_meta($contract_id, '_contract_status', true);
        if (empty($status)) {
            $status = 'in_progress';
        }
        
        $data = array(
            'id' => $contract_id,
            'contract_id' => get_post_meta($contract_id, '_contract_id', true),
            'title' => $contract->post_title,
            'description' => $contract->post_content,
            'start_date' => get_post_meta($contract_id, '_contract_start_date', true),
            'end_date' => get_post_meta($contract_id, '_contract_end_date', true),
            'status' => $status,
            'progress' => absint(get_post_meta($contract_id, '_contract_progress', true)),
            'client' => $this->get_contract_client_name($contract_id),
            'value' => get_post_meta($contract_id, '_contract_value', true),
            'created_at' => $contract->post_date,
        );
        
        if ($include_stages) {
            $stages = Arta_Iran_Supply_Contract_Stages::get_stages($contract_id);
            $formatted_stages = array();
            
            foreach ($stages as $index => $stage) {
                $formatted_stage = array(
                    'index' => $index,
                    'title' => $stage['title'] ?? '',
                    'status' => $stage['status'] ?? 'pending',
                    'date' => $stage['date'] ?? '',
                    'description' => $stage['description'] ?? '',
                    'files' => array(),
                );
                
                    if (!empty($stage['files'])) {
                        foreach ($stage['files'] as $attachment_id) {
                            $mime_type = get_post_mime_type($attachment_id);
                            $is_image = wp_attachment_is_image($attachment_id);
                            $icon = $is_image ? '' : wp_mime_type_icon($mime_type);
                            
                            $formatted_stage['files'][] = array(
                                'id' => $attachment_id,
                                'url' => wp_get_attachment_url($attachment_id),
                                'name' => get_the_title($attachment_id),
                                'type' => $mime_type,
                                'icon' => $icon,
                                'is_image' => $is_image,
                            );
                        }
                    }
                
                $formatted_stages[] = $formatted_stage;
            }
            
            $data['stages'] = $formatted_stages;
        }
        
        return $data;
    }
    
    /**
     * Get contract client name
     *
     * @param int $contract_id Contract post ID
     * @return string Client name
     */
    private function get_contract_client_name($contract_id) {
        $client_user_id = get_post_meta($contract_id, '_contract_client_user_id', true);
        if ($client_user_id) {
            $client_user = get_user_by('ID', $client_user_id);
            if ($client_user) {
                return $client_user->display_name;
            }
        }
        return get_post_meta($contract_id, '_contract_client', true);
    }
    
    /**
     * Handle get recent activities
     */
    public function handle_get_recent_activities() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'arta_ajax_nonce')) {
            wp_send_json_error(array('message' => __('خطای امنیتی.', 'arta-iran-supply')));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('لطفاً وارد شوید.', 'arta-iran-supply')));
        }
        
        $current_user = wp_get_current_user();
        $activities = array();
        
        // Get user's contracts
        $args = array(
            'post_type' => 'contract',
            'post_status' => 'publish',
            'posts_per_page' => 50, // Limit to 50 most recent contracts
            'orderby' => 'modified',
            'order' => 'DESC',
        );
        
        // Filter by author if user has organization role
        if (in_array('organization', $current_user->roles)) {
            $args['author'] = $current_user->ID;
        }
        
        $contracts = get_posts($args);
        
        // If no contracts, return empty activities
        if (empty($contracts)) {
            wp_send_json_success(array('activities' => array()));
        }
        
        foreach ($contracts as $contract) {
            $contract_id = $contract->ID;
            $contract_title = $contract->post_title;
            $contract_modified = $contract->post_modified;
            $contract_date = $contract->post_date;
            
            // Activity: Contract created (show all contracts, prioritize recent ones)
            $days_since_created = (time() - strtotime($contract_date)) / (60 * 60 * 24);
            // Always show contract creation, but prioritize recent ones
            $activities[] = array(
                'type' => 'contract_created',
                'icon' => 'success',
                'title' => sprintf(__('قرارداد "%s" ایجاد شد', 'arta-iran-supply'), $contract_title),
                'time' => $contract_date,
                'timestamp' => strtotime($contract_date),
            );
            
            // Activity: Contract modified (if different from created date)
            $days_since_modified = (time() - strtotime($contract_modified)) / (60 * 60 * 24);
            if ($contract_modified !== $contract_date && $days_since_modified <= 90) {
                $activities[] = array(
                    'type' => 'contract_updated',
                    'icon' => 'info',
                    'title' => sprintf(__('قرارداد "%s" به‌روزرسانی شد', 'arta-iran-supply'), $contract_title),
                    'time' => $contract_modified,
                    'timestamp' => strtotime($contract_modified),
                );
            }
            
            // Get contract stages
            $stages = Arta_Iran_Supply_Contract_Stages::get_stages($contract_id);
            
            // Activity: Stages added or updated (if contract has stages)
            if (!empty($stages) && $days_since_modified <= 90) {
                $stages_count = count($stages);
                $completed_stages = 0;
                $in_progress_stages = 0;
                
                foreach ($stages as $stage) {
                    if (isset($stage['status'])) {
                        if ($stage['status'] === 'completed') {
                            $completed_stages++;
                        } elseif ($stage['status'] === 'in_progress') {
                            $in_progress_stages++;
                        }
                    }
                }
                
                // Show progress if there are completed or in-progress stages
                if ($completed_stages > 0 || $in_progress_stages > 0) {
                    $activities[] = array(
                        'type' => 'stages_progress',
                        'icon' => $completed_stages === $stages_count ? 'success' : 'info',
                        'title' => sprintf(__('قرارداد "%s": %d از %d مرحله تکمیل شده', 'arta-iran-supply'), $contract_title, $completed_stages, $stages_count),
                        'time' => $contract_modified,
                        'timestamp' => strtotime($contract_modified),
                    );
                }
            }
            
            // Check if stage has files (show files from last 30 days)
            foreach ($stages as $index => $stage) {
                if (!empty($stage['files'])) {
                    foreach ($stage['files'] as $file_id) {
                        $attachment = get_post($file_id);
                        if ($attachment) {
                            $file_date = $attachment->post_date;
                            $days_since_file = (time() - strtotime($file_date)) / (60 * 60 * 24);
                            if ($days_since_file <= 90) {
                                $activities[] = array(
                                    'type' => 'file_uploaded',
                                    'icon' => 'info',
                                    'title' => sprintf(__('فایل به مرحله "%s" قرارداد "%s" اضافه شد', 'arta-iran-supply'), $stage['title'] ?: __('مرحله', 'arta-iran-supply'), $contract_title),
                                    'time' => $file_date,
                                    'timestamp' => strtotime($file_date),
                                );
                            }
                        }
                    }
                }
            }
            
            // Activity: Contract progress updated (if progress exists and contract was modified recently)
            $progress = get_post_meta($contract_id, '_contract_progress', true);
            if ($progress && $progress > 0 && $days_since_modified <= 90 && $contract_modified !== $contract_date) {
                $activities[] = array(
                    'type' => 'progress_updated',
                    'icon' => 'info',
                    'title' => sprintf(__('پیشرفت قرارداد "%s" به %s%% به‌روزرسانی شد', 'arta-iran-supply'), $contract_title, $progress),
                    'time' => $contract_modified,
                    'timestamp' => strtotime($contract_modified),
                );
            }
            
            // Activity: Contract status (show status if exists and contract was modified recently)
            $status = get_post_meta($contract_id, '_contract_status', true);
            if ($status && $days_since_modified <= 90 && $contract_modified !== $contract_date) {
                $status_labels = array(
                    'in_progress' => __('در حال انجام', 'arta-iran-supply'),
                    'completed' => __('انجام شده', 'arta-iran-supply'),
                    'cancelled' => __('لغو شده', 'arta-iran-supply'),
                );
                
                if (isset($status_labels[$status])) {
                    $activities[] = array(
                        'type' => 'contract_status',
                        'icon' => $status === 'completed' ? 'success' : ($status === 'cancelled' ? 'warning' : 'info'),
                        'title' => sprintf(__('وضعیت قرارداد "%s" به "%s" تغییر کرد', 'arta-iran-supply'), $contract_title, $status_labels[$status]),
                        'time' => $contract_modified,
                        'timestamp' => strtotime($contract_modified),
                    );
                }
            }
        }
        
        // If no activities found but contracts exist, show at least the most recent contracts
        if (empty($activities) && !empty($contracts)) {
            // Show the 5 most recent contracts as activities
            $recent_contracts = array_slice($contracts, 0, 5);
            foreach ($recent_contracts as $contract) {
                $activities[] = array(
                    'type' => 'contract_created',
                    'icon' => 'success',
                    'title' => sprintf(__('قرارداد "%s"', 'arta-iran-supply'), $contract->post_title),
                    'time' => $contract->post_date,
                    'timestamp' => strtotime($contract->post_date),
                );
            }
        }
        
        // Sort activities by timestamp (newest first)
        usort($activities, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Limit to 10 most recent
        $activities = array_slice($activities, 0, 10);
        
        // Format time ago for each activity
        foreach ($activities as &$activity) {
            $activity['time_ago'] = $this->get_time_ago_persian($activity['time']);
        }
        
        wp_send_json_success(array('activities' => $activities));
    }
    
    /**
     * Get time ago in Persian
     *
     * @param string $date Date string
     * @return string Time ago in Persian
     */
    private function get_time_ago_persian($date) {
        $time = time() - strtotime($date);
        
        if ($time < 60) {
            return __('چند لحظه پیش', 'arta-iran-supply');
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return sprintf(__('%d دقیقه پیش', 'arta-iran-supply'), $minutes);
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return sprintf(__('%d ساعت پیش', 'arta-iran-supply'), $hours);
        } elseif ($time < 604800) {
            $days = floor($time / 86400);
            return sprintf(__('%d روز پیش', 'arta-iran-supply'), $days);
        } elseif ($time < 2592000) {
            $weeks = floor($time / 604800);
            return sprintf(__('%d هفته پیش', 'arta-iran-supply'), $weeks);
        } elseif ($time < 31536000) {
            $months = floor($time / 2592000);
            return sprintf(__('%d ماه پیش', 'arta-iran-supply'), $months);
        } else {
            $years = floor($time / 31536000);
            return sprintf(__('%d سال پیش', 'arta-iran-supply'), $years);
        }
    }
    
    /**
     * Handle get user tickets
     */
    public function handle_get_user_tickets() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('لطفاً وارد شوید.', 'arta-iran-supply')));
        }
        
        $current_user_id = get_current_user_id();
        
        // Get tickets where current user is the ticket user
        $args = array(
            'post_type' => 'ticket',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_ticket_user_id',
                    'value' => $current_user_id,
                    'compare' => '=',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        $tickets = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $ticket_id = get_the_ID();
                
                // Get messages count
                $messages_json = get_post_meta($ticket_id, '_ticket_messages', true);
                $messages = array();
                if (!empty($messages_json)) {
                    $messages = json_decode($messages_json, true);
                    if (!is_array($messages)) {
                        $messages = array();
                    }
                }
                
                $status = get_post_meta($ticket_id, '_ticket_status', true);
                if (empty($status)) {
                    $status = 'open';
                }
                
                $tickets[] = array(
                    'id' => $ticket_id,
                    'title' => get_the_title(),
                    'date' => get_the_date('Y/m/d H:i'),
                    'status' => $status,
                    'messages_count' => count($messages),
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array('tickets' => $tickets));
    }
    
    /**
     * Handle create ticket
     */
    public function handle_create_ticket() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('لطفاً وارد شوید.', 'arta-iran-supply')));
        }
        
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (empty($title)) {
            wp_send_json_error(array('message' => __('عنوان تیکت الزامی است.', 'arta-iran-supply')));
        }
        
        if (empty($message)) {
            wp_send_json_error(array('message' => __('پیام اولیه الزامی است.', 'arta-iran-supply')));
        }
        
        $current_user_id = get_current_user_id();
        
        // Create ticket post
        $ticket_data = array(
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'ticket',
            'post_author' => get_current_user_id(),
        );
        
        $ticket_id = wp_insert_post($ticket_data);
        
        if (is_wp_error($ticket_id)) {
            wp_send_json_error(array('message' => __('خطا در ایجاد تیکت.', 'arta-iran-supply')));
        }
        
        // Set ticket user
        update_post_meta($ticket_id, '_ticket_user_id', $current_user_id);
        update_post_meta($ticket_id, '_ticket_status', 'open');
        
        // Create initial message
        $initial_message = array(
            'id' => uniqid('msg_'),
            'sender_id' => $current_user_id,
            'content' => $message,
            'date' => current_time('mysql'),
            'attachments' => array(),
            'is_read' => false,
        );
        
        $messages = array($initial_message);
        $messages_json = wp_json_encode($messages, JSON_UNESCAPED_UNICODE);
        update_post_meta($ticket_id, '_ticket_messages', $messages_json);
        
        wp_send_json_success(array(
            'message' => __('تیکت با موفقیت ایجاد شد.', 'arta-iran-supply'),
            'ticket_id' => $ticket_id,
        ));
    }
    
    /**
     * Handle get ticket detail
     */
    public function handle_get_ticket_detail() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('لطفاً وارد شوید.', 'arta-iran-supply')));
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        $current_user_id = get_current_user_id();
        
        if (!$ticket_id) {
            wp_send_json_error(array('message' => __('شناسه تیکت نامعتبر است.', 'arta-iran-supply')));
        }
        
        // Verify ticket exists and belongs to user
        $ticket = get_post($ticket_id);
        if (!$ticket || $ticket->post_type !== 'ticket') {
            wp_send_json_error(array('message' => __('تیکت یافت نشد.', 'arta-iran-supply')));
        }
        
        $ticket_user_id = get_post_meta($ticket_id, '_ticket_user_id', true);
        if ($ticket_user_id != $current_user_id) {
            wp_send_json_error(array('message' => __('شما دسترسی به این تیکت را ندارید.', 'arta-iran-supply')));
        }
        
        // Get messages
        $messages_json = get_post_meta($ticket_id, '_ticket_messages', true);
        $messages = array();
        if (!empty($messages_json)) {
            $messages = json_decode($messages_json, true);
            if (!is_array($messages)) {
                $messages = array();
            }
        }
        
        // Mark messages as read when user views the ticket
        $messages_updated = false;
        foreach ($messages as $key => $message) {
            $sender_id = isset($message['sender_id']) ? $message['sender_id'] : 0;
            $is_read = isset($message['is_read']) ? $message['is_read'] : false;
            
            // If message is from someone else (not current user) and not read, mark as read
            if ($sender_id != $current_user_id && !$is_read) {
                $messages[$key]['is_read'] = true;
                $messages_updated = true;
            }
        }
        
        // Save updated messages if any were marked as read
        if ($messages_updated) {
            $messages_json = wp_json_encode($messages, JSON_UNESCAPED_UNICODE);
            update_post_meta($ticket_id, '_ticket_messages', $messages_json);
        }
        
        // Sort messages by date (newest first)
        usort($messages, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Format messages
        $formatted_messages = array();
        foreach ($messages as $message) {
            $sender = get_user_by('ID', $message['sender_id']);
            $sender_name = $sender ? $sender->display_name : __('کاربر حذف شده', 'arta-iran-supply');
            
            $formatted_message = array(
                'id' => $message['id'],
                'sender_id' => $message['sender_id'],
                'sender_name' => $sender_name,
                'content' => $message['content'],
                'date' => $message['date'],
                'formatted_date' => $this->format_ticket_date($message['date']),
                'attachments' => array(),
            );
            
            // Format attachments
            if (!empty($message['attachments'])) {
                foreach ($message['attachments'] as $attachment_id) {
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
            }
            
            $formatted_messages[] = $formatted_message;
        }
        
        $status = get_post_meta($ticket_id, '_ticket_status', true);
        if (empty($status)) {
            $status = 'open';
        }
        
        wp_send_json_success(array(
            'ticket' => array(
                'id' => $ticket_id,
                'title' => $ticket->post_title,
                'date' => get_the_date('Y/m/d H:i', $ticket_id),
                'status' => $status,
                'messages' => $formatted_messages,
            ),
        ));
    }
    
    /**
     * Handle send ticket message from panel
     */
    public function handle_send_ticket_message_panel() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'arta_ajax_nonce')) {
            wp_send_json_error(array('message' => __('خطای امنیتی. لطفاً صفحه را رفرش کنید.', 'arta-iran-supply')));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('لطفاً وارد شوید.', 'arta-iran-supply')));
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        
        // Handle attachment_ids - can be JSON string or array
        $attachment_ids = array();
        if (isset($_POST['attachment_ids'])) {
            if (is_string($_POST['attachment_ids'])) {
                // Try to decode JSON string
                $decoded = json_decode(stripslashes($_POST['attachment_ids']), true);
                if (is_array($decoded)) {
                    $attachment_ids = array_map('absint', $decoded);
                } elseif (is_numeric($_POST['attachment_ids'])) {
                    $attachment_ids = array(absint($_POST['attachment_ids']));
                }
            } elseif (is_array($_POST['attachment_ids'])) {
                $attachment_ids = array_map('absint', $_POST['attachment_ids']);
            }
        }
        
        $current_user_id = get_current_user_id();
        
        if (!$ticket_id) {
            wp_send_json_error(array('message' => __('شناسه تیکت نامعتبر است.', 'arta-iran-supply')));
        }
        
        // Verify ticket exists and belongs to user
        $ticket = get_post($ticket_id);
        if (!$ticket || $ticket->post_type !== 'ticket') {
            wp_send_json_error(array('message' => __('تیکت یافت نشد.', 'arta-iran-supply')));
        }
        
        $ticket_user_id = get_post_meta($ticket_id, '_ticket_user_id', true);
        if ($ticket_user_id != $current_user_id) {
            wp_send_json_error(array('message' => __('شما دسترسی به این تیکت را ندارید.', 'arta-iran-supply')));
        }
        
        if (empty($content)) {
            wp_send_json_error(array('message' => __('متن پیام نمی‌تواند خالی باشد.', 'arta-iran-supply')));
        }
        
        // Get existing messages
        $messages_json = get_post_meta($ticket_id, '_ticket_messages', true);
        $messages = array();
        if (!empty($messages_json)) {
            $messages = json_decode($messages_json, true);
            if (!is_array($messages)) {
                $messages = array();
            }
        }
        
        // Create new message
        $new_message = array(
            'id' => uniqid('msg_'),
            'sender_id' => $current_user_id,
            'content' => $content,
            'date' => current_time('mysql'),
            'attachments' => $attachment_ids,
            'is_read' => false,
        );
        
        $messages[] = $new_message;
        
        // Save messages
        $messages_json = wp_json_encode($messages, JSON_UNESCAPED_UNICODE);
        update_post_meta($ticket_id, '_ticket_messages', $messages_json);
        
        // Update ticket status to "open" if user sends a message
        update_post_meta($ticket_id, '_ticket_status', 'open');
        
        wp_send_json_success(array(
            'message' => __('پیام با موفقیت ارسال شد.', 'arta-iran-supply'),
        ));
    }
    
    /**
     * Handle upload ticket file from panel
     */
    public function handle_upload_ticket_file_panel() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('لطفاً وارد شوید.', 'arta-iran-supply')));
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        $current_user_id = get_current_user_id();
        
        if (!$ticket_id) {
            wp_send_json_error(array('message' => __('شناسه تیکت نامعتبر است.', 'arta-iran-supply')));
        }
        
        // Verify ticket exists and belongs to user
        $ticket = get_post($ticket_id);
        if (!$ticket || $ticket->post_type !== 'ticket') {
            wp_send_json_error(array('message' => __('تیکت یافت نشد.', 'arta-iran-supply')));
        }
        
        $ticket_user_id = get_post_meta($ticket_id, '_ticket_user_id', true);
        if ($ticket_user_id != $current_user_id) {
            wp_send_json_error(array('message' => __('شما دسترسی به این تیکت را ندارید.', 'arta-iran-supply')));
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
     * Format ticket date
     */
    private function format_ticket_date($date) {
        $timestamp = strtotime($date);
        $date_format = get_option('date_format') . ' ' . get_option('time_format');
        return date_i18n($date_format, $timestamp);
    }
    
    /**
     * Handle get tickets notifications
     */
    public function handle_get_tickets_notifications() {
        check_ajax_referer('arta_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_success(array(
                'has_unread_messages' => false,
            ));
        }
        
        $current_user_id = get_current_user_id();
        $has_unread_messages = false;
        
        // Get user's tickets
        $args = array(
            'post_type' => 'ticket',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_ticket_user_id',
                    'value' => $current_user_id,
                    'compare' => '=',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $ticket_id = get_the_ID();
                
                // Check for unread messages only (ignore new tickets)
                $messages_json = get_post_meta($ticket_id, '_ticket_messages', true);
                if (!empty($messages_json)) {
                    $messages = json_decode($messages_json, true);
                    if (is_array($messages)) {
                        foreach ($messages as $message) {
                            // Check if message is from someone else (not current user) and not read
                            $sender_id = isset($message['sender_id']) ? intval($message['sender_id']) : 0;
                            
                            // Check is_read - handle different formats (boolean, string, null)
                            $is_read = false;
                            if (isset($message['is_read'])) {
                                if (is_bool($message['is_read'])) {
                                    $is_read = $message['is_read'];
                                } elseif (is_string($message['is_read'])) {
                                    $is_read = in_array(strtolower($message['is_read']), array('true', '1', 'yes'));
                                } elseif (is_numeric($message['is_read'])) {
                                    $is_read = (bool) intval($message['is_read']);
                                }
                            }
                            
                            // If message is from someone else (not current user) and not read
                            if ($sender_id != $current_user_id && !$is_read) {
                                $has_unread_messages = true;
                                break 2; // Break both loops
                            }
                        }
                    }
                }
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'has_unread_messages' => $has_unread_messages,
        ));
    }
}

