<?php
/**
 * Request Order Handler
 * 
 * Handles the "Request Order" feature that changes add to cart behavior
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_Request_Order {
    
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
        // var_dump('constructor is called');
        // var_dump($this->is_enabled());
        // die;
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Register payment gateway after WooCommerce is fully loaded (always register, but only use if enabled)
        add_action('plugins_loaded', array($this, 'register_payment_gateway'), 20);
        
        // Check if feature is enabled - only add hooks if enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Hide default add to cart button
        add_action('wp_head', array($this, 'hide_add_to_cart_button_css'));
        
        // Remove add to cart button from loop (shop/archive pages)
        add_action('init', array($this, 'remove_add_to_cart_actions'), 20);
        
        // Filter to remove add to cart button
        add_filter('woocommerce_loop_add_to_cart_link', array($this, 'remove_add_to_cart_link'), 10, 2);
        
        // Remove add to cart button from single product page (but keep variations)
        add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'remove_single_add_to_cart'), 999);
        
        // Add custom "Request Order" button to product pages
        add_action('woocommerce_single_product_summary', array($this, 'add_request_order_button_single'), 30);
        
        // Add custom "Request Order" button to shop/archive pages
        add_action('woocommerce_after_shop_loop_item', array($this, 'add_request_order_button_loop'), 15);
        
        // Hide payment methods on checkout and use our custom one
        add_filter('woocommerce_available_payment_gateways', array($this, 'filter_payment_gateways'), 10, 1);
        
        // Set order status to on-hold after checkout
        add_action('woocommerce_checkout_order_processed', array($this, 'set_order_status_on_hold'), 10, 3);
        
        // Hide payment section on checkout page but keep place order button
        add_action('wp_head', array($this, 'hide_payment_section_css'));
        
        // Add JavaScript to handle checkout
        add_action('wp_footer', array($this, 'add_checkout_script'));
        
        // Add JavaScript to handle request order buttons
        add_action('wp_footer', array($this, 'add_request_order_script'));
        
        // Handle redirect to checkout after adding to cart via our button
        add_filter('woocommerce_add_to_cart_redirect', array($this, 'redirect_to_checkout_if_request_order'), 10, 1);
        
        // AJAX handler to clear cart
        add_action('wp_ajax_arta_clear_cart', array($this, 'ajax_clear_cart'));
        add_action('wp_ajax_nopriv_arta_clear_cart', array($this, 'ajax_clear_cart'));
        
        // Skip payment processing
        add_action('woocommerce_before_checkout_process', array($this, 'skip_payment_processing'), 1);
    }
    
    /**
     * Register payment gateway
     */
    public function register_payment_gateway() {
        if (class_exists('WC_Payment_Gateway')) {
            add_filter('woocommerce_payment_gateways', array($this, 'add_request_payment_gateway'));
        }
    }
    
    /**
     * Check if request order feature is enabled
     */
    private function is_enabled() {
        $settings = Arta_Iran_Supply_Settings::get_settings();
        // var_dump($settings);
        // die;
        // Debug: uncomment to check settings
        // error_log('Request Order Settings: ' . print_r($settings, true));
        // error_log('request_order_enabled value: ' . (isset($settings['request_order_enabled']) ? $settings['request_order_enabled'] : 'NOT SET'));
        
        // Check if setting exists
        if (!isset($settings['request_order_enabled'])) {
            return false;
        }
        
        // Get the value and convert to integer for strict comparison
        $value = (int)$settings['request_order_enabled'];
        
        // Return true only if explicitly set to 1
        return $value === 1;
    }
    
    /**
     * Remove add to cart actions
     */
    public function remove_add_to_cart_actions() {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Remove add to cart button from loop
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    }
    
    /**
     * Remove add to cart link from loop
     */
    public function remove_add_to_cart_link($link, $product) {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return $link;
        }
        
        // Return empty string to remove the link
        return '';
    }
    
    /**
     * Remove single product add to cart text (hide button but keep form for variations)
     */
    public function remove_single_add_to_cart($text) {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return $text;
        }
        
        // Return empty to hide button text (button will be hidden by CSS)
        return '';
    }
    
    /**
     * Hide default add to cart button CSS
     */
    public function hide_add_to_cart_button_css() {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        if (!is_product() && !is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
            return;
        }
        ?>
        <style>
            /* Hide default add to cart button */
            .woocommerce .single_add_to_cart_button,
            .woocommerce .add_to_cart_button,
            .woocommerce a.add_to_cart_button,
            .woocommerce button.add_to_cart_button,
            .woocommerce input.add_to_cart_button,
            .woocommerce form.cart .single_add_to_cart_button,
            .woocommerce form.cart button[type="submit"],
            .woocommerce form.cart input[type="submit"],
            .woocommerce ul.products li.product .add_to_cart_button,
            .woocommerce ul.products li.product a.add_to_cart_button,
            .woocommerce ul.products li.product button.add_to_cart_button,
            .woocommerce ul.products li.product .button.add_to_cart_button {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 0 !important;
            }
            
            /* Keep cart form visible for variations */
            .woocommerce form.cart {
                display: block !important;
            }
            
            /* Hide quantity input */
            .woocommerce form.cart .quantity,
            .woocommerce form.cart .qty {
                display: none !important;
            }
            
            /* Keep variations table visible for variable products */
            .woocommerce form.cart .variations {
                display: block !important;
            }
            
            /* For simple products without variations, hide the entire cart form */
            .woocommerce div.product form.cart:not(.has-variations):not(:has(.variations)) {
                display: none !important;
            }
            
            /* Fallback: hide cart form for simple products using JavaScript */
            .woocommerce div.product.simple-product form.cart {
                display: none !important;
            }
        </style>
        <?php
    }
    
    /**
     * Add request order button to single product page
     */
    public function add_request_order_button_single() {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        global $product;
        
        if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
            return;
        }
        
        $product_id = $product->get_id();
        $product_type = $product->get_type();
        
        ?>
        <div class="arta-request-order-button-wrapper" style="margin-top: 15px;">
            <button type="button" class="button alt arta-request-order-btn-single" 
                    data-product-id="<?php echo esc_attr($product_id); ?>"
                    data-product-type="<?php echo esc_attr($product_type); ?>"
                    style="width: 100%; padding: 15px; font-size: 16px; font-weight: bold; background: #0066ff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <?php _e('ثبت درخواست', 'arta-iran-supply'); ?>
            </button>
        </div>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Hide cart form for simple products
            if ('<?php echo esc_js($product_type); ?>' === 'simple') {
                $('form.cart').hide();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Add request order button to shop/archive pages
     */
    public function add_request_order_button_loop() {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        global $product;
        
        if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
            return;
        }
        
        $product_id = $product->get_id();
        $product_type = $product->get_type();
        
        if ($product_type !== 'simple') {
            // For variable products, show a link to product page
            ?>
            <a href="<?php echo esc_url($product->get_permalink()); ?>" class="button arta-request-order-btn-loop" >
                <?php _e('ثبت درخواست', 'arta-iran-supply'); ?>
            </a>
            <?php
        } else {
            // For simple products
            ?>
            <button type="button" class="button arta-request-order-btn-loop" 
                    data-product-id="<?php echo esc_attr($product_id); ?>"
                    data-product-type="simple"
                    style="">
                <?php _e('ثبت درخواست', 'arta-iran-supply'); ?>
            </button>
            <?php
        }
    }
    
    /**
     * Add JavaScript to handle request order buttons
     */
    public function add_request_order_script() {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        if (!is_product() && !is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle request order button click
            $(document).on('click', '.arta-request-order-btn-single, .arta-request-order-btn-loop', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var productId = $button.data('product-id');
                var productType = $button.data('product-type');
                
                // Mark that we're using request order button
                sessionStorage.setItem('arta_request_order', '1');
                
                // Disable button to prevent double click
                $button.prop('disabled', true).text('<?php echo esc_js(__('در حال پردازش...', 'arta-iran-supply')); ?>');
                
                // Function to clear cart and add product
                function clearCartAndAddProduct() {
                    // Clear cart first via AJAX
                    $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                        action: 'arta_clear_cart',
                        nonce: '<?php echo wp_create_nonce('arta_clear_cart'); ?>'
                    }, function(response) {
                        if (response.success) {
                            // Cart cleared, now add product
                            addProductToCart();
                        } else {
                            // Even if clearing fails, try to add product
                            // (maybe cart is already empty)
                            addProductToCart();
                        }
                    }).fail(function() {
                        // If AJAX fails, try to add product anyway
                        addProductToCart();
                    });
                }
                
                // Function to add product to cart
                function addProductToCart() {
                
                    if (productType === 'variable') {
                        // For variable products, get variation from the cart form
                        var $cartForm = $('form.cart');
                        if ($cartForm.length) {
                            var variationId = $cartForm.find('input[name="variation_id"]').val();
                            if (!variationId) {
                                alert('<?php echo esc_js(__('لطفاً نوع محصول را انتخاب کنید.', 'arta-iran-supply')); ?>');
                                $button.prop('disabled', false).text('<?php echo esc_js(__('ثبت درخواست', 'arta-iran-supply')); ?>');
                                return;
                            }
                            
                            // Get variation data
                            var variationData = {};
                            $cartForm.find('select, input[type="radio"]:checked').each(function() {
                                var name = $(this).attr('name');
                                if (name && name.indexOf('attribute_') === 0) {
                                    variationData[name] = $(this).val();
                                }
                            });
                            
                            // Add to cart via AJAX
                            var data = {
                                product_id: productId,
                                variation_id: variationId,
                                quantity: $cartForm.find('input[name="quantity"]').val() || 1
                            };
                            
                            $.extend(data, variationData);
                            
                            if (typeof wc_add_to_cart_params !== 'undefined') {
                                $.post(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'), data, function(response) {
                                    if (response.error) {
                                        alert(response.error_message || '<?php echo esc_js(__('خطا در افزودن به سبد خرید', 'arta-iran-supply')); ?>');
                                        $button.prop('disabled', false).text('<?php echo esc_js(__('ثبت درخواست', 'arta-iran-supply')); ?>');
                                    } else {
                                        // Update cart fragments
                                        if (response.fragments) {
                                            $.each(response.fragments, function(key, value) {
                                                $(key).replaceWith(value);
                                            });
                                        }
                                        
                                        // Clear the flag and redirect
                                        sessionStorage.removeItem('arta_request_order');
                                        window.location.href = '<?php echo esc_url(wc_get_checkout_url()); ?>';
                                    }
                                });
                            } else {
                                // Fallback
                                alert('<?php echo esc_js(__('خطا در افزودن به سبد خرید', 'arta-iran-supply')); ?>');
                                $button.prop('disabled', false).text('<?php echo esc_js(__('ثبت درخواست', 'arta-iran-supply')); ?>');
                            }
                        }
                    } else {
                        // For simple products, add to cart via AJAX
                        if (typeof wc_add_to_cart_params !== 'undefined') {
                            $.post(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'), {
                                product_id: productId,
                                quantity: 1
                            }, function(response) {
                                if (response.error) {
                                    alert(response.error_message || '<?php echo esc_js(__('خطا در افزودن به سبد خرید', 'arta-iran-supply')); ?>');
                                    $button.prop('disabled', false).text('<?php echo esc_js(__('ثبت درخواست', 'arta-iran-supply')); ?>');
                                } else {
                                    // Update cart fragments
                                    if (response.fragments) {
                                        $.each(response.fragments, function(key, value) {
                                            $(key).replaceWith(value);
                                        });
                                    }
                                    
                                    // Clear the flag and redirect
                                    sessionStorage.removeItem('arta_request_order');
                                    window.location.href = '<?php echo esc_url(wc_get_checkout_url()); ?>';
                                }
                            });
                        } else {
                            // Fallback: direct form submission
                            var $form = $('<form method="post" action="<?php echo esc_url(wc_get_checkout_url()); ?>"></form>');
                            $form.append('<input type="hidden" name="add-to-cart" value="' + productId + '" />');
                            $form.append('<input type="hidden" name="quantity" value="1" />');
                            $('body').append($form);
                            $form.submit();
                        }
                    }
                }
                
                // Start the process: clear cart then add product
                clearCartAndAddProduct();
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler to clear cart
     */
    public function ajax_clear_cart() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            wp_send_json_error(array('message' => __('WooCommerce فعال نیست', 'arta-iran-supply')));
        }
        
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            wp_send_json_error(array('message' => __('قابلیت فعال نیست', 'arta-iran-supply')));
        }
        
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'arta_clear_cart')) {
            wp_send_json_error(array('message' => __('خطای امنیتی', 'arta-iran-supply')));
        }
        
        // Initialize WooCommerce cart if not already done
        if (!WC()->cart) {
            wc_load_cart();
        }
        
        // Clear the cart
        WC()->cart->empty_cart();
        
        wp_send_json_success(array('message' => __('سبد خرید خالی شد', 'arta-iran-supply')));
    }
    
    /**
     * Redirect to checkout if product was added via request order button
     */
    public function redirect_to_checkout_if_request_order($url) {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return $url;
        }
        
        // Check if this is from our request order button
        if (isset($_POST['arta_redirect_checkout']) || 
            (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'arta-request-order') !== false)) {
            return wc_get_checkout_url();
        }
        return $url;
    }
    
    /**
     * Add custom payment gateway for request orders
     */
    public function add_request_payment_gateway($gateways) {
        if (class_exists('WC_Payment_Gateway') && class_exists('Arta_Iran_Supply_Request_Payment_Gateway')) {
            $gateways[] = 'Arta_Iran_Supply_Request_Payment_Gateway';
        }
        return $gateways;
    }
    
    /**
     * Filter payment gateways to only show our custom one
     */
    public function filter_payment_gateways($available_gateways) {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return $available_gateways;
        }
        
        if (is_checkout() && class_exists('Arta_Iran_Supply_Request_Payment_Gateway')) {
            // Remove all other gateways
            $available_gateways = array();
            
            // Add our custom payment gateway
            if (class_exists('WC_Payment_Gateway')) {
                $custom_gateway = new Arta_Iran_Supply_Request_Payment_Gateway();
                $available_gateways['arta_request_order'] = $custom_gateway;
            }
        }
        return $available_gateways;
    }
    
    /**
     * Skip payment processing
     */
    public function skip_payment_processing() {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Set payment method if not set
        if (empty($_POST['payment_method'])) {
            $_POST['payment_method'] = 'arta_request_order';
        }
    }
    
    /**
     * Set order status to on-hold after checkout
     */
    public function set_order_status_on_hold($order_id, $data, $order) {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        if (!$order_id) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if ($order && $order->get_payment_method() === 'arta_request_order') {
            // Status will be set by the payment gateway, but we ensure it here too
            if ($order->get_status() !== 'on-hold') {
                $order->update_status('on-hold', __('سفارش در حالت در انتظار بررسی ثبت شد.', 'arta-iran-supply'));
            }
        }
    }
    
    /**
     * Hide payment section with CSS but keep place order button visible
     */
    public function hide_payment_section_css() {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        if (!is_checkout()) {
            return;
        }
        ?>
        <style>
            /* Hide payment methods section */
            .woocommerce-checkout #payment_methods,
            .woocommerce-checkout .payment_methods,
            .woocommerce-checkout .wc_payment_methods,
            .woocommerce-checkout .payment_method_arta_request_order {
                display: none !important;
            }
            
            /* Keep payment section structure but hide payment methods */
            .woocommerce-checkout #payment {
                border: none !important;
                padding: 20px 0 !important;
                background: transparent !important;
            }
            
            /* Hide payment method list items */
            .woocommerce-checkout #payment ul.payment_methods,
            .woocommerce-checkout #payment .payment_methods {
                display: none !important;
            }
            
            /* Show original place order button - make it visible even if inside payment section */
            .woocommerce-checkout #place_order,
            .woocommerce-checkout button[name="woocommerce_checkout_place_order"],
            .woocommerce-checkout #payment #place_order,
            .woocommerce-checkout #payment button[name="woocommerce_checkout_place_order"] {
                display: block !important;
                width: 100% !important;
                padding: 15px !important;
                font-size: 18px !important;
                font-weight: bold !important;
                margin-top: 20px !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                z-index: 10 !important;
            }
            
            /* Ensure checkout place order section is visible */
            .woocommerce-checkout .woocommerce-checkout-place-order {
                display: block !important;
                visibility: visible !important;
                padding: 20px 0 !important;
            }
            
            /* Make sure payment section doesn't hide the button */
            .woocommerce-checkout #payment {
                position: relative;
            }
        </style>
        <?php
    }
    
    /**
     * Add JavaScript to handle checkout without payment
     */
    public function add_checkout_script() {
        // Check if feature is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        if (!is_checkout()) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Set payment method automatically
            if ($('input[name="payment_method"]').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'payment_method',
                    value: 'arta_request_order'
                }).appendTo('form.checkout');
            } else {
                $('input[name="payment_method"]').val('arta_request_order');
            }
            
            // Ensure payment method is set on form submit
            $('form.checkout').on('submit', function() {
                if ($('input[name="payment_method"]').length === 0 || !$('input[name="payment_method"]').val()) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'payment_method',
                        value: 'arta_request_order'
                    }).appendTo('form.checkout');
                }
            });
            
            // Ensure place order button is visible
            setTimeout(function() {
                $('#place_order, button[name="woocommerce_checkout_place_order"]').css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }, 100);
        });
        </script>
        <?php
    }
}

/**
 * Custom Payment Gateway for Request Orders
 * This class is defined conditionally after WooCommerce is loaded
 */
add_action('plugins_loaded', function() {
    if (class_exists('WC_Payment_Gateway') && !class_exists('Arta_Iran_Supply_Request_Payment_Gateway')) {
        class Arta_Iran_Supply_Request_Payment_Gateway extends WC_Payment_Gateway {
            
            /**
             * Constructor
             */
            public function __construct() {
                $this->id = 'arta_request_order';
                $this->icon = '';
                $this->has_fields = false;
                $this->method_title = __('ثبت درخواست', 'arta-iran-supply');
                $this->method_description = __('این روش پرداخت برای ثبت درخواست بدون نیاز به پرداخت استفاده می‌شود.', 'arta-iran-supply');
                
                // Load the settings
                $this->init_form_fields();
                $this->init_settings();
                
                // Define user set variables
                $this->title = $this->get_option('title', __('ثبت درخواست', 'arta-iran-supply'));
                $this->description = $this->get_option('description', __('سفارش شما در حالت در انتظار بررسی ثبت خواهد شد.', 'arta-iran-supply'));
                $this->enabled = 'yes';
                
                // Actions
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            }
            
            /**
             * Initialize Gateway Settings Form Fields
             */
            public function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('فعال/غیرفعال', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('فعال‌سازی ثبت درخواست', 'arta-iran-supply'),
                        'default' => 'yes'
                    ),
                    'title' => array(
                        'title' => __('عنوان', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('این عنوان در صفحه تسویه حساب نمایش داده می‌شود.', 'woocommerce'),
                        'default' => __('ثبت درخواست', 'arta-iran-supply'),
                        'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => __('توضیحات', 'woocommerce'),
                        'type' => 'textarea',
                        'description' => __('توضیحاتی که در صفحه تسویه حساب نمایش داده می‌شود.', 'woocommerce'),
                        'default' => __('سفارش شما در حالت در انتظار بررسی ثبت خواهد شد.', 'arta-iran-supply'),
                    ),
                );
            }
            
            /**
             * Process the payment and return the result
             */
            public function process_payment($order_id) {
                $order = wc_get_order($order_id);
                
                // Mark as on-hold (we're awaiting the payment)
                $order->update_status('on-hold', __('سفارش در حالت در انتظار بررسی ثبت شد.', 'arta-iran-supply'));
                
                // Reduce stock levels
                wc_reduce_stock_levels($order_id);
                
                // Remove cart
                WC()->cart->empty_cart();
                
                // Return thankyou redirect
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }
        }
    }
}, 20);

