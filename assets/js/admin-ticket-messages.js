jQuery(document).ready(function($) {
    var ticketId = parseInt(artaTicket.postId) || 0;
    var uploadedFiles = [];
    var isPublished = $('#post_status').val() === 'publish' || $('#original_publish').length === 0;
    
    // Disable form if ticket is new (not saved yet) or not published
    if (!ticketId || ticketId === 0) {
        $('#ticket-message-content').prop('disabled', true).attr('placeholder', 'لطفاً ابتدا تیکت را ذخیره کنید.');
        $('#ticket-upload-file-btn').prop('disabled', true);
        $('#ticket-send-message-btn').prop('disabled', true);
    } else if (!isPublished) {
        // Form is already disabled in PHP, but add extra check
        $('#ticket-upload-file-btn, #ticket-send-message-btn').on('click', function(e) {
            e.preventDefault();
            alert('برای ارسال پیام، ابتدا باید تیکت را منتشر کنید.');
            return false;
        });
    }
    
    // File upload button
    $('#ticket-upload-file-btn').on('click', function() {
        if (!ticketId || ticketId === 0) {
            alert('لطفاً ابتدا تیکت را ذخیره کنید.');
            return;
        }
        
        // Check if ticket is published
        var currentStatus = $('#post_status').val();
        if (currentStatus !== 'publish') {
            alert('برای آپلود فایل، ابتدا باید تیکت را منتشر کنید.');
            return;
        }
        
        $('#ticket-file-input').click();
    });
    
    // File input change
    $('#ticket-file-input').on('change', function(e) {
        var files = e.target.files;
        if (files.length === 0) return;
        
        // Upload each file
        for (var i = 0; i < files.length; i++) {
            uploadFile(files[i]);
        }
    });
    
    // Upload file function
    function uploadFile(file) {
        var formData = new FormData();
        formData.append('action', 'upload_ticket_file');
        formData.append('nonce', artaTicket.nonce);
        formData.append('ticket_id', ticketId);
        formData.append('file', file);
        
        // Show loading
        var filePreview = $('<div class="ticket-file-preview-item">' +
            '<span>' + escapeHtml(file.name) + '</span> ' +
            '<span class="upload-status" style="color: #6c757d; font-size: 11px;">(در حال آپلود...)</span>' +
            '</div>');
        $('#ticket-files-preview').append(filePreview);
        
        $.ajax({
            url: artaTicket.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    uploadedFiles.push(response.data.attachment);
                    filePreview.find('.upload-status').html('<span style="color: #4CAF50;">✓ آپلود شد</span>');
                    filePreview.attr('data-attachment-id', response.data.attachment.id);
                    
                    // Add remove button
                    var removeBtn = $('<button type="button" class="remove-file-btn">×</button>');
                    removeBtn.on('click', function() {
                        var attachmentId = response.data.attachment.id;
                        uploadedFiles = uploadedFiles.filter(function(f) {
                            return f.id !== attachmentId;
                        });
                        filePreview.fadeOut(200, function() {
                            $(this).remove();
                        });
                    });
                    filePreview.prepend(removeBtn);
                } else {
                    filePreview.find('.upload-status').html('<span style="color: #dc3232;">✗ خطا: ' + escapeHtml(response.data.message) + '</span>');
                }
            },
            error: function() {
                filePreview.find('.upload-status').html('<span style="color: #dc3232;">✗ خطا در ارتباط</span>');
            }
        });
    }
    
    // Send message button
    $('#ticket-send-message-btn').on('click', function() {
        if (!ticketId || ticketId === 0) {
            alert('لطفاً ابتدا تیکت را ذخیره کنید.');
            return;
        }
        
        // Check if ticket is published
        var currentStatus = $('#post_status').val();
        if (currentStatus !== 'publish') {
            alert('برای ارسال پیام، ابتدا باید تیکت را منتشر کنید.');
            return;
        }
        
        var content = $('#ticket-message-content').val().trim();
        
        if (!content) {
            alert('لطفاً متن پیام را وارد کنید.');
            return;
        }
        
        var attachmentIds = uploadedFiles.map(function(f) {
            return f.id;
        });
        
        var button = $(this);
        button.prop('disabled', true).text('در حال ارسال...');
        
        $.ajax({
            url: artaTicket.ajaxUrl,
            type: 'POST',
            data: {
                action: 'send_ticket_message',
                nonce: artaTicket.nonce,
                ticket_id: ticketId,
                content: content,
                attachment_ids: attachmentIds
            },
            success: function(response) {
                if (response.success) {
                    // Clear form
                    $('#ticket-message-content').val('');
                    $('#ticket-file-input').val('');
                    $('#ticket-files-preview').empty();
                    uploadedFiles = [];
                    
                    // Add message to list
                    addMessageToDOM(response.data.ticket_message);
                    
                    // Show success message
                    alert('پیام با موفقیت ارسال شد.');
                } else {
                    alert('خطا: ' + response.data.message);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور.');
            },
            complete: function() {
                button.prop('disabled', false).text('ارسال پیام');
            }
        });
    });
    
    // Mark as read button
    $(document).on('click', '.mark-read-btn', function() {
        var button = $(this);
        var messageId = button.data('message-id');
        
        $.ajax({
            url: artaTicket.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mark_message_read',
                nonce: artaTicket.nonce,
                ticket_id: ticketId,
                message_id: messageId
            },
            success: function(response) {
                if (response.success) {
                    var messageItem = button.closest('.ticket-message-item');
                    messageItem.removeClass('unread');
                    messageItem.find('.mark-read-btn').fadeOut(200, function() {
                        $(this).remove();
                    });
                    var statusBadge = messageItem.find('.ticket-message-status-badge');
                    statusBadge.removeClass('unread').addClass('read').text('خوانده شده');
                } else {
                    alert('خطا: ' + response.data.message);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور.');
            }
        });
    });
    
    // Add message to DOM
    function addMessageToDOM(message) {
        var currentUserId = parseInt(artaTicket.currentUserId || 0);
        var isSent = message.sender_id == currentUserId;
        var messageClass = isSent ? 'sent' : 'received';
        var unreadClass = !isSent ? 'unread' : '';
        var senderInitials = message.sender_name ? message.sender_name.charAt(0).toUpperCase() : '?';
        
        var messageHtml = '<div class="ticket-message-item ' + messageClass + ' ' + unreadClass + '" data-message-id="' + message.id + '">' +
            '<div class="ticket-message-header">' +
            '<div class="ticket-message-sender">' +
            '<div class="ticket-message-avatar">' + escapeHtml(senderInitials) + '</div>' +
            '<div class="ticket-message-sender-info">' +
            '<div class="ticket-message-sender-name">' + escapeHtml(message.sender_name) + '</div>' +
            '<div class="ticket-message-date">' + escapeHtml(message.formatted_date) + '</div>' +
            '</div>' +
            '</div>' +
            '<div class="ticket-message-status">';
        
        if (messageClass === 'received') {
            messageHtml += '<span class="ticket-message-status-badge unread">خوانده نشده</span>';
        }
        
        messageHtml += '</div>' +
            '</div>' +
            '<div class="ticket-message-content">' + escapeHtml(message.content).replace(/\n/g, '<br>') + '</div>';
        
        if (message.attachments && message.attachments.length > 0) {
            messageHtml += '<div class="ticket-message-attachments">' +
                '<div class="ticket-message-attachments-title">فایل‌های پیوست:</div>' +
                '<div>';
            
            message.attachments.forEach(function(attachment) {
                messageHtml += '<div class="ticket-attachment-item">';
                if (attachment.is_image) {
                    messageHtml += '<img src="' + escapeHtml(attachment.url) + '" alt="' + escapeHtml(attachment.name) + '" />';
                } else {
                    messageHtml += '<img src="' + escapeHtml(attachment.thumbnail || '') + '" alt="' + escapeHtml(attachment.name) + '" />';
                }
                messageHtml += '<a href="' + escapeHtml(attachment.url) + '" target="_blank">' + escapeHtml(attachment.name) + '</a>' +
                    '</div>';
            });
            
            messageHtml += '</div></div>';
        }
        
        if (messageClass === 'received') {
            messageHtml += '<button type="button" class="ticket-mark-read-btn mark-read-btn" data-message-id="' + message.id + '">' +
                '✓ علامت‌گذاری به عنوان خوانده شده' +
                '</button>';
        }
        
        messageHtml += '</div>';
        
        // Add message at the end (newest messages appear at the bottom, like a chat)
        var messagesContainer = $('#ticket-messages-list');
        var existingMessages = messagesContainer.find('.ticket-message-item');
        var noMessagesDiv = messagesContainer.find('.ticket-no-messages');
        
        if (existingMessages.length === 0) {
            // First message, add after h3 and remove "no messages" text
            noMessagesDiv.remove();
            messagesContainer.find('h3').after(messageHtml);
        } else {
            // Add at the end of messages list
            existingMessages.last().after(messageHtml);
        }
        
        // Fade in animation
        var newMessage = messagesContainer.find('.ticket-message-item').last();
        newMessage.hide().fadeIn(300);
        
        // Scroll to new message smoothly
        setTimeout(function() {
            $('html, body').animate({
                scrollTop: newMessage.offset().top - 150
            }, 500);
        }, 100);
    }
    
    // Escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});

