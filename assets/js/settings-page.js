/**
 * Settings Page JavaScript
 * 
 * @package Arta_Iran_Supply
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize color pickers
        $('.arta-color-picker').wpColorPicker({
            change: function(event, ui) {
                // Color changed
            }
        });
        
        // Tab switching
        $('.arta-tab-btn').on('click', function() {
            var tabId = $(this).data('tab');
            
            // Remove active class from all tabs and contents
            $('.arta-tab-btn').removeClass('active');
            $('.arta-tab-content').removeClass('active');
            
            // Add active class to clicked tab and corresponding content
            $(this).addClass('active');
            $('#tab-' + tabId).addClass('active');
        });
        
        // Logo upload
        var logoUploader;
        
        $('#upload-logo').on('click', function(e) {
            e.preventDefault();
            
            // If the uploader object has already been created, reopen it
            if (logoUploader) {
                logoUploader.open();
                return;
            }
            
            // Create the media uploader
            logoUploader = wp.media({
                title: 'انتخاب لوگو',
                button: {
                    text: 'استفاده از این تصویر'
                },
                multiple: false
            });
            
            // When an image is selected, run a callback
            logoUploader.on('select', function() {
                var attachment = logoUploader.state().get('selection').first().toJSON();
                var logoUrl = attachment.url;
                var logoId = attachment.id;
                
                // Update hidden input
                $('#panel_logo').val(logoId);
                
                // Update preview
                var previewHtml = '<img src="' + logoUrl + '" alt="Logo" />';
                previewHtml += '<button type="button" class="arta-remove-logo" id="remove-logo">×</button>';
                $('#logo-preview').html(previewHtml);
            });
            
            // Open the uploader
            logoUploader.open();
        });
        
        // Remove logo
        $(document).on('click', '#remove-logo', function(e) {
            e.preventDefault();
            $('#panel_logo').val('');
            var placeholderHtml = '<div class="arta-logo-placeholder">';
            placeholderHtml += '<span class="dashicons dashicons-format-image"></span>';
            placeholderHtml += '<p>هیچ لوگویی انتخاب نشده</p>';
            placeholderHtml += '</div>';
            $('#logo-preview').html(placeholderHtml);
        });
        
        // Background image upload
        var bgImageUploader;
        
        $('#upload-bg-image').on('click', function(e) {
            e.preventDefault();
            
            if (bgImageUploader) {
                bgImageUploader.open();
                return;
            }
            
            bgImageUploader = wp.media({
                title: 'انتخاب تصویر پس‌زمینه',
                button: {
                    text: 'استفاده از این تصویر'
                },
                multiple: false
            });
            
            bgImageUploader.on('select', function() {
                var attachment = bgImageUploader.state().get('selection').first().toJSON();
                var imageUrl = attachment.url;
                var imageId = attachment.id;
                
                $('#login_bg_image').val(imageId);
                
                var previewHtml = '<img src="' + imageUrl + '" alt="Background" />';
                previewHtml += '<button type="button" class="arta-remove-logo" id="remove-bg-image">×</button>';
                $('#bg-image-preview').html(previewHtml);
            });
            
            bgImageUploader.open();
        });
        
        // Remove background image
        $(document).on('click', '#remove-bg-image', function(e) {
            e.preventDefault();
            $('#login_bg_image').val('');
            var placeholderHtml = '<div class="arta-logo-placeholder">';
            placeholderHtml += '<span class="dashicons dashicons-format-image"></span>';
            placeholderHtml += '<p>هیچ تصویری انتخاب نشده</p>';
            placeholderHtml += '</div>';
            $('#bg-image-preview').html(placeholderHtml);
        });
        
        // Show/hide background image field based on background type
        $('#login_bg_type').on('change', function() {
            if ($(this).val() === 'image') {
                $('#bg-image-group').slideDown();
            } else {
                $('#bg-image-group').slideUp();
            }
        });
        
        // Reset settings
        $('#reset-settings').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('آیا مطمئن هستید که می‌خواهید تمام تنظیمات را به حالت پیش‌فرض بازگردانید؟')) {
                return;
            }
            
            // Reset form values
            $('#panel_title').val('پنل مدیریت');
            $('#panel_logo').val('');
            $('#login_title').val('خوش آمدید');
            $('#login_subtitle').val('لطفاً اطلاعات خود را وارد کنید');
            $('#login_bg_color').wpColorPicker('color', '#667eea');
            $('#login_primary_color').wpColorPicker('color', '#667eea');
            $('#login_secondary_color').wpColorPicker('color', '#764ba2');
            $('#login_button_color').wpColorPicker('color', '#667eea');
            $('#panel_primary_color').wpColorPicker('color', '#0066ff');
            $('#panel_secondary_color').wpColorPicker('color', '#00d4ff');
            $('#sidebar_bg_color').wpColorPicker('color', '#ffffff');
            $('#login_bg_type').val('gradient');
            $('#login_bg_image').val('');
            $('#login_bg_animation').val('shapes');
            $('#request_order_enabled').prop('checked', false);
            
            // Reset logo preview
            var placeholderHtml = '<div class="arta-logo-placeholder">';
            placeholderHtml += '<span class="dashicons dashicons-format-image"></span>';
            placeholderHtml += '<p>هیچ لوگویی انتخاب نشده</p>';
            placeholderHtml += '</div>';
            $('#logo-preview').html(placeholderHtml);
            
            // Reset background image preview
            var bgPlaceholderHtml = '<div class="arta-logo-placeholder">';
            bgPlaceholderHtml += '<span class="dashicons dashicons-format-image"></span>';
            bgPlaceholderHtml += '<p>هیچ تصویری انتخاب نشده</p>';
            bgPlaceholderHtml += '</div>';
            $('#bg-image-preview').html(bgPlaceholderHtml);
            $('#bg-image-group').slideUp();
        });
        
    });
    
})(jQuery);

