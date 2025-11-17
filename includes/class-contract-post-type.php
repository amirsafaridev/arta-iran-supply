<?php
/**
 * Contract Post Type
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_Contract_Post_Type {
    
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
        add_filter('manage_contract_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_contract_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
        add_action('pre_get_posts', array($this, 'filter_contracts_by_author'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'contract' && ($hook === 'post.php' || $hook === 'post-new.php')) {
            // Enqueue WordPress media uploader
            wp_enqueue_media();
            
            // Enqueue custom script for media uploader
            wp_enqueue_script(
                'arta-contract-stages',
                ARTA_IRAN_SUPPLY_PLUGIN_URL . 'assets/js/admin-contract-stages.js',
                array('jquery'),
                ARTA_IRAN_SUPPLY_VERSION,
                true
            );
        }
    }
    
    /**
     * Register contract post type
     */
    public static function register_post_type() {
        $labels = array(
            'name' => __('قراردادها', 'arta-iran-supply'),
            'singular_name' => __('قرارداد', 'arta-iran-supply'),
            'menu_name' => __('قراردادها', 'arta-iran-supply'),
            'add_new' => __('افزودن قرارداد جدید', 'arta-iran-supply'),
            'add_new_item' => __('افزودن قرارداد جدید', 'arta-iran-supply'),
            'edit_item' => __('ویرایش قرارداد', 'arta-iran-supply'),
            'new_item' => __('قرارداد جدید', 'arta-iran-supply'),
            'view_item' => __('مشاهده قرارداد', 'arta-iran-supply'),
            'search_items' => __('جستجوی قراردادها', 'arta-iran-supply'),
            'not_found' => __('قراردادی یافت نشد', 'arta-iran-supply'),
            'not_found_in_trash' => __('قراردادی در سطل زباله یافت نشد', 'arta-iran-supply'),
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
            'menu_position' => 20,
            'menu_icon' => 'dashicons-clipboard',
            'supports' => array('title', 'editor', 'thumbnail', 'author'),
            'show_in_rest' => false,
        );
        
        register_post_type('contract', $args);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'contract_info',
            __('اطلاعات قرارداد', 'arta-iran-supply'),
            array($this, 'render_contract_info_meta_box'),
            'contract',
            'normal',
            'high'
        );
        
        add_meta_box(
            'contract_status',
            __('وضعیت قرارداد', 'arta-iran-supply'),
            array($this, 'render_contract_status_meta_box'),
            'contract',
            'side',
            'default'
        );
        
        add_meta_box(
            'contract_stages',
            __('مراحل قرارداد', 'arta-iran-supply'),
            array($this, 'render_contract_stages_meta_box'),
            'contract',
            'normal',
            'default'
        );
    }
    
    /**
     * Render contract info meta box
     */
    public function render_contract_info_meta_box($post) {
        wp_nonce_field('arta_contract_info_meta_box', 'arta_contract_info_meta_box_nonce');
        
        $contract_id = get_post_meta($post->ID, '_contract_id', true);
        $client_user_id = get_post_meta($post->ID, '_contract_client_user_id', true);
        $start_date = get_post_meta($post->ID, '_contract_start_date', true);
        $end_date = get_post_meta($post->ID, '_contract_end_date', true);
        $value = get_post_meta($post->ID, '_contract_value', true);
        $progress = get_post_meta($post->ID, '_contract_progress', true);
        
        // Get all users for dropdown
        $users = get_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC',
        ));
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="contract_id"><?php _e('شماره قرارداد', 'arta-iran-supply'); ?></label></th>
                <td><input type="text" id="contract_id" name="contract_id" value="<?php echo esc_attr($contract_id); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="contract_client_user_id"><?php _e('مشتری', 'arta-iran-supply'); ?></label></th>
                <td>
                    <select id="contract_client_user_id" name="contract_client_user_id" class="regular-text" >
                        <option value=""><?php _e('-- انتخاب مشتری --', 'arta-iran-supply'); ?></option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($client_user_id, $user->ID); ?>>
                                <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="contract_start_date"><?php _e('تاریخ شروع', 'arta-iran-supply'); ?></label></th>
                <td><input type="date" id="contract_start_date" name="contract_start_date" value="<?php echo esc_attr($start_date); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="contract_end_date"><?php _e('تاریخ پایان', 'arta-iran-supply'); ?></label></th>
                <td><input type="date" id="contract_end_date" name="contract_end_date" value="<?php echo esc_attr($end_date); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="contract_value"><?php _e('ارزش قرارداد', 'arta-iran-supply'); ?></label></th>
                <td><input type="text" id="contract_value" name="contract_value" value="<?php echo esc_attr($value); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="contract_progress"><?php _e('پیشرفت پروژه (%)', 'arta-iran-supply'); ?></label></th>
                <td><input type="number" id="contract_progress" name="contract_progress" value="<?php echo esc_attr($progress); ?>" min="0" max="100" class="small-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render contract status meta box
     */
    public function render_contract_status_meta_box($post) {
        wp_nonce_field('arta_contract_status_meta_box', 'arta_contract_status_meta_box_nonce');
        
        $status = get_post_meta($post->ID, '_contract_status', true);
        if (empty($status)) {
            $status = 'in_progress';
        }
        
        $statuses = array(
            'in_progress' => __('در حال انجام', 'arta-iran-supply'),
            'completed' => __('انجام شده', 'arta-iran-supply'),
            'cancelled' => __('لغو شده', 'arta-iran-supply'),
        );
        
        ?>
        <select name="contract_status" id="contract_status" style="width: 100%;">
            <?php foreach ($statuses as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($status, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render contract stages meta box
     */
    public function render_contract_stages_meta_box($post) {
        wp_nonce_field('arta_contract_stages_meta_box', 'arta_contract_stages_meta_box_nonce');
        
        $stages = Arta_Iran_Supply_Contract_Stages::get_stages($post->ID);
        
        ?>
        <div id="contract-stages-container">
            <div id="stages-list">
                <?php if (!empty($stages)) : ?>
                    <?php foreach ($stages as $index => $stage) : ?>
                        <div class="stage-item" data-stage-index="<?php echo esc_attr($index); ?>" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #f9f9f9;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <h4 style="margin: 0;">مرحله <?php echo esc_html($index + 1); ?></h4>
                                <button type="button" class="button remove-stage" data-stage-index="<?php echo esc_attr($index); ?>" style="background: #dc3232; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">حذف</button>
                            </div>
                            <table class="form-table" style="margin-top: 10px;">
                                <tr>
                                    <th><label>عنوان مرحله</label></th>
                                    <td>
                                        <input type="text" name="stages[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($stage['title'] ?? ''); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>تاریخ</label></th>
                                    <td>
                                        <input type="date" name="stages[<?php echo esc_attr($index); ?>][date]" value="<?php echo esc_attr($stage['date'] ?? ''); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>وضعیت</label></th>
                                    <td>
                                        <select name="stages[<?php echo esc_attr($index); ?>][status]" class="regular-text">
                                            <option value="pending" <?php selected($stage['status'] ?? 'pending', 'pending'); ?>>در انتظار</option>
                                            <option value="in_progress" <?php selected($stage['status'] ?? 'pending', 'in_progress'); ?>>در حال انجام</option>
                                            <option value="completed" <?php selected($stage['status'] ?? 'pending', 'completed'); ?>>تکمیل شده</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>توضیحات</label></th>
                                    <td>
                                        <textarea name="stages[<?php echo esc_attr($index); ?>][description]" rows="3" class="large-text"><?php echo esc_textarea($stage['description'] ?? ''); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>فایل‌ها</label></th>
                                    <td>
                                        <div class="stage-files-list" data-stage-index="<?php echo esc_attr($index); ?>" style="margin-bottom: 10px;">
                                            <?php if (!empty($stage['files'])) : ?>
                                                <?php foreach ($stage['files'] as $file_index => $attachment_id) : ?>
                                                    <?php
                                                    $attachment = get_post($attachment_id);
                                                    if ($attachment) {
                                                        $file_url = wp_get_attachment_url($attachment_id);
                                                        $file_name = get_the_title($attachment_id);
                                                        $file_type = get_post_mime_type($attachment_id);
                                                        $is_image = wp_attachment_is_image($attachment_id);
                                                        $thumbnail = $is_image ? wp_get_attachment_image_url($attachment_id, 'thumbnail') : wp_mime_type_icon($file_type);
                                                    ?>
                                                    <div class="file-item" data-file-id="<?php echo esc_attr($attachment_id); ?>" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px;">
                                                        <div style="flex-shrink: 0; width: 48px; height: 48px; overflow: hidden; border-radius: 6px; background: #fff; display: flex; align-items: center; justify-content: center;">
                                                            <?php if ($is_image) : ?>
                                                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($file_name); ?>" style="width: 100%; height: 100%; object-fit: cover;" />
                                                            <?php else : ?>
                                                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($file_name); ?>" style="width: 32px; height: 32px;" />
                                                            <?php endif; ?>
                                                        </div>
                                                        <div style="flex: 1; min-width: 0;">
                                                            <a href="<?php echo esc_url($file_url); ?>" target="_blank" style="display: block; color: #0066ff; text-decoration: none; font-weight: 500; word-break: break-word;">
                                                                <?php echo esc_html($file_name); ?>
                                                            </a>
                                                            <span style="font-size: 0.85rem; color: #666;"><?php echo esc_html($file_type); ?></span>
                                                        </div>
                                                        <button type="button" class="button remove-file-btn" data-file-id="<?php echo esc_attr($attachment_id); ?>" style="background: #dc3232; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">حذف</button>
                                                        <input type="hidden" name="stages[<?php echo esc_attr($index); ?>][files][]" value="<?php echo esc_attr($attachment_id); ?>" />
                                                    </div>
                                                    <?php } ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="button button-secondary add-file-btn" data-stage-index="<?php echo esc_attr($index); ?>" style="margin-top: 5px;">
                                            📎 افزودن فایل
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>هنوز مرحله‌ای اضافه نشده است.</p>
                <?php endif; ?>
            </div>
            <button type="button" id="add-stage-btn" class="button button-primary" style="margin-top: 15px;">➕ افزودن مرحله جدید</button>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var stageIndex = <?php echo count($stages); ?>;
            
            $('#add-stage-btn').on('click', function() {
                var stageHtml = '<div class="stage-item" data-stage-index="' + stageIndex + '" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #f9f9f9;">' +
                    '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">' +
                    '<h4 style="margin: 0;">مرحله ' + (stageIndex + 1) + '</h4>' +
                    '<button type="button" class="button remove-stage" data-stage-index="' + stageIndex + '" style="background: #dc3232; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">حذف</button>' +
                    '</div>' +
                    '<table class="form-table" style="margin-top: 10px;">' +
                    '<tr><th><label>عنوان مرحله</label></th><td><input type="text" name="stages[' + stageIndex + '][title]" class="regular-text" /></td></tr>' +
                    '<tr><th><label>تاریخ</label></th><td><input type="date" name="stages[' + stageIndex + '][date]" class="regular-text" /></td></tr>' +
                    '<tr><th><label>وضعیت</label></th><td><select name="stages[' + stageIndex + '][status]" class="regular-text"><option value="pending">در انتظار</option><option value="in_progress">در حال انجام</option><option value="completed">تکمیل شده</option></select></td></tr>' +
                    '<tr><th><label>توضیحات</label></th><td><textarea name="stages[' + stageIndex + '][description]" rows="3" class="large-text"></textarea></td></tr>' +
                    '<tr><th><label>فایل‌ها</label></th><td><div class="stage-files-list" data-stage-index="' + stageIndex + '" style="margin-bottom: 10px;"></div><button type="button" class="button button-secondary add-file-btn" data-stage-index="' + stageIndex + '" style="margin-top: 5px;">📎 افزودن فایل</button></td></tr>' +
                    '</table></div>';
                $('#stages-list').append(stageHtml);
                stageIndex++;
            });
            
            $(document).on('click', '.remove-stage', function() {
                if (confirm('آیا مطمئن هستید که می‌خواهید این مرحله را حذف کنید؟')) {
                    $(this).closest('.stage-item').remove();
                }
            });
        });
        </script>
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
        if (get_post_type($post_id) !== 'contract') {
            return;
        }
        
        // Save contract info
        if (isset($_POST['arta_contract_info_meta_box_nonce']) && 
            wp_verify_nonce($_POST['arta_contract_info_meta_box_nonce'], 'arta_contract_info_meta_box')) {
            
            if (isset($_POST['contract_id'])) {
                update_post_meta($post_id, '_contract_id', sanitize_text_field($_POST['contract_id']));
            }
            
            if (isset($_POST['contract_client_user_id'])) {
                $client_user_id = absint($_POST['contract_client_user_id']);
                update_post_meta($post_id, '_contract_client_user_id', $client_user_id);
                
                // Also save client name for display
                if ($client_user_id > 0) {
                    $client_user = get_user_by('ID', $client_user_id);
                    if ($client_user) {
                        update_post_meta($post_id, '_contract_client', $client_user->display_name);
                    }
                } else {
                    // Clear client name if no user selected
                    delete_post_meta($post_id, '_contract_client');
                }
            }
            
            if (isset($_POST['contract_start_date'])) {
                update_post_meta($post_id, '_contract_start_date', sanitize_text_field($_POST['contract_start_date']));
            }
            
            if (isset($_POST['contract_end_date'])) {
                update_post_meta($post_id, '_contract_end_date', sanitize_text_field($_POST['contract_end_date']));
            }
            
            if (isset($_POST['contract_value'])) {
                update_post_meta($post_id, '_contract_value', sanitize_text_field($_POST['contract_value']));
            }
            
            if (isset($_POST['contract_progress'])) {
                $progress = absint($_POST['contract_progress']);
                $progress = min(100, max(0, $progress));
                update_post_meta($post_id, '_contract_progress', $progress);
            }
        }
        
        // Save contract status
        if (isset($_POST['arta_contract_status_meta_box_nonce']) && 
            wp_verify_nonce($_POST['arta_contract_status_meta_box_nonce'], 'arta_contract_status_meta_box')) {
            
            if (isset($_POST['contract_status'])) {
                $status = sanitize_text_field($_POST['contract_status']);
                $allowed_statuses = array('in_progress', 'completed', 'cancelled');
                if (in_array($status, $allowed_statuses)) {
                    update_post_meta($post_id, '_contract_status', $status);
                }
            }
        }
        
        // Save contract stages (only for admins)
        if (isset($_POST['arta_contract_stages_meta_box_nonce']) && 
            wp_verify_nonce($_POST['arta_contract_stages_meta_box_nonce'], 'arta_contract_stages_meta_box') &&
            current_user_can('edit_contracts')) {
            
            if (isset($_POST['stages']) && is_array($_POST['stages'])) {
                $stages = array();
                foreach ($_POST['stages'] as $stage_data) {
                    $stage = array(
                        'title' => isset($stage_data['title']) ? sanitize_text_field($stage_data['title']) : '',
                        'date' => isset($stage_data['date']) ? sanitize_text_field($stage_data['date']) : '',
                        'status' => isset($stage_data['status']) ? sanitize_text_field($stage_data['status']) : 'pending',
                        'description' => isset($stage_data['description']) ? sanitize_textarea_field($stage_data['description']) : '',
                        'files' => array(), // Files will be handled separately via AJAX
                    );
                    
                    // Validate status
                    $allowed_statuses = array('pending', 'in_progress', 'completed');
                    if (!in_array($stage['status'], $allowed_statuses)) {
                        $stage['status'] = 'pending';
                    }
                    
                    $stages[] = $stage;
                }
                
                // Preserve file attachments from submitted data
                foreach ($stages as $index => &$stage) {
                    if (isset($_POST['stages'][$index]['files']) && is_array($_POST['stages'][$index]['files'])) {
                        $stage['files'] = array_map('absint', $_POST['stages'][$index]['files']);
                    }
                }
                
                // Save stages as JSON to match the format used by save_stages method
                $stages_json = wp_json_encode($stages, JSON_UNESCAPED_UNICODE);
                update_post_meta($post_id, '_contract_stages', $stages_json);
            } else {
                // If no stages submitted, clear existing stages
                delete_post_meta($post_id, '_contract_stages');
            }
        }
    }
    
    /**
     * Add custom columns
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['contract_id'] = __('شماره قرارداد', 'arta-iran-supply');
        $new_columns['contract_client'] = __('مشتری', 'arta-iran-supply');
        $new_columns['contract_dates'] = __('تاریخ‌ها', 'arta-iran-supply');
        $new_columns['contract_status'] = __('وضعیت', 'arta-iran-supply');
        $new_columns['contract_progress'] = __('پیشرفت', 'arta-iran-supply');
        $new_columns['author'] = $columns['author'];
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Render custom columns
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'contract_id':
                echo esc_html(get_post_meta($post_id, '_contract_id', true));
                break;
                
            case 'contract_client':
                $client_user_id = get_post_meta($post_id, '_contract_client_user_id', true);
                if ($client_user_id) {
                    $client_user = get_user_by('ID', $client_user_id);
                    if ($client_user) {
                        echo esc_html($client_user->display_name);
                    } else {
                        echo esc_html(get_post_meta($post_id, '_contract_client', true));
                    }
                } else {
                    echo esc_html(get_post_meta($post_id, '_contract_client', true));
                }
                break;
                
            case 'contract_dates':
                $start = get_post_meta($post_id, '_contract_start_date', true);
                $end = get_post_meta($post_id, '_contract_end_date', true);
                if ($start) {
                    echo esc_html($start);
                }
                if ($start && $end) {
                    echo ' - ';
                }
                if ($end) {
                    echo esc_html($end);
                }
                break;
                
            case 'contract_status':
                $status = get_post_meta($post_id, '_contract_status', true);
                $statuses = array(
                    'in_progress' => __('در حال انجام', 'arta-iran-supply'),
                    'completed' => __('انجام شده', 'arta-iran-supply'),
                    'cancelled' => __('لغو شده', 'arta-iran-supply'),
                );
                echo isset($statuses[$status]) ? esc_html($statuses[$status]) : '-';
                break;
                
            case 'contract_progress':
                $progress = get_post_meta($post_id, '_contract_progress', true);
                echo esc_html($progress ? $progress . '%' : '0%');
                break;
        }
    }
    
    /**
     * Filter contracts by author for organization role
     */
    public function filter_contracts_by_author($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'contract') {
            return;
        }
        
        $current_user = wp_get_current_user();
        if (in_array('organization', $current_user->roles)) {
            $query->set('author', $current_user->ID);
        }
    }
}

