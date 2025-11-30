<?php
/**
 * Order Received Page Handler
 * 
 * Handles custom content for order-received page when payment method is "ثبت درخواست"
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_Order_Received {
    
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
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Check if request order feature is enabled
        if (!$this->is_request_order_enabled()) {
            return;
        }
        
        // Hide default order details using CSS
        add_action('wp_head', array($this, 'hide_default_order_content_css'));
        
        // Hook into order received page to show custom content
        add_action('woocommerce_thankyou', array($this, 'custom_order_received_content'), 5, 1);
    }
    
    /**
     * Check if request order feature is enabled
     */
    private function is_request_order_enabled() {
        $settings = Arta_Iran_Supply_Settings::get_settings();
        return isset($settings['request_order_enabled']) && (int)$settings['request_order_enabled'] === 1;
    }
    
    /**
     * Hide default order content CSS when payment method is "ثبت درخواست"
     */
    public function hide_default_order_content_css() {
        // Only on order received page
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        // Get order ID from query var or order key
        $order_id = absint(get_query_var('order-received'));
        
        // If order ID is not available, try to get from order key
        if (!$order_id && isset($_GET['key'])) {
            $order_id = wc_get_order_id_by_order_key($_GET['key']);
        }
        
        if (!$order_id) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Check if payment method is "arta_request_order"
        if ($order->get_payment_method() === 'arta_request_order') {
            ?>
            <style>
                .woocommerce-order-details,
                .woocommerce-order-details__title,
                .woocommerce-order-overview,
                .woocommerce-order-overview__title,
                .woocommerce-customer-details,
                .woocommerce-customer-details__title,
                .woocommerce-order-details__order-table,
                .woocommerce-table--order-details,
                .woocommerce-order-details__section {
                    display: none !important;
                }
                
                /* Hide order received message if it exists */
                .woocommerce-thankyou-order-received {
                    display: none !important;
                }
            </style>
            <?php
        }
    }
    
    /**
     * Customize order received page content
     */
    public function custom_order_received_content($order_id) {
        if (!$order_id) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Check if payment method is "arta_request_order"
        if ($order->get_payment_method() !== 'arta_request_order') {
            return;
        }
        
        // Get custom message from settings
        $settings = Arta_Iran_Supply_Settings::get_settings();
        $custom_message = isset($settings['request_order_received_message']) && !empty($settings['request_order_received_message']) 
            ? $settings['request_order_received_message'] 
            : __('درخواست شما ثبت شد و پس از بررسی توسط تیم ما در اسرع وقت با شما تماس گرفته خواهد شد', 'arta-iran-supply');
        
        // Display custom message
        ?>
        <div class="arta-order-received-message" style="text-align: center; padding: 40px 20px; background: #f8f9fa; border-radius: 8px; margin: 30px 0;">
            <div style="font-size: 64px; color: #28a745; margin-bottom: 20px; line-height: 1;">
                ✓
            </div>
            <h2 style="color: #333; margin-bottom: 15px; font-size: 24px;">
                <?php _e('درخواست شما با موفقیت ثبت شد', 'arta-iran-supply'); ?>
            </h2>
            <p style="color: #666; font-size: 16px; line-height: 1.6; max-width: 600px; margin: 0 auto;">
                <?php echo wp_kses_post(nl2br($custom_message)); ?>
            </p>
            <div style="margin-top: 30px;">
                <p style="color: #999; font-size: 14px;">
                    <?php printf(
                        __('شماره سفارش: %s', 'arta-iran-supply'),
                        '<strong style="color: #333;">' . esc_html($order->get_order_number()) . '</strong>'
                    ); ?>
                </p>
            </div>
        </div>
        <?php
    }
    
}

