jQuery(document).ready(function($) {
    var fileFrame;
    
    // Handle add file button click
    $(document).on('click', '.add-file-btn', function(e) {
        e.preventDefault();
        
        var stageIndex = $(this).data('stage-index');
        var filesList = $('.stage-files-list[data-stage-index="' + stageIndex + '"]');
        
        // If the media frame already exists, reopen it
        if (fileFrame) {
            fileFrame.open();
            return;
        }
        
        // Create the media frame
        fileFrame = wp.media({
            title: 'انتخاب فایل',
            button: {
                text: 'استفاده از فایل انتخاب شده'
            },
            multiple: true,
            library: {
                type: '' // Allow all file types
            }
        });
        
        // When files are selected, run a callback
        fileFrame.on('select', function() {
            var attachments = fileFrame.state().get('selection').toJSON();
            
            attachments.forEach(function(attachment) {
                var fileId = attachment.id;
                var fileName = attachment.filename || attachment.title;
                var fileUrl = attachment.url;
                var fileType = attachment.mime || attachment.type;
                var isImage = attachment.type && attachment.type.indexOf('image/') === 0;
                var thumbnail = isImage ? (attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : fileUrl) : attachment.icon;
                
                // Check if file already exists
                if (filesList.find('input[value="' + fileId + '"]').length > 0) {
                    return; // Skip if already added
                }
                
                var fileHtml = '<div class="file-item" data-file-id="' + fileId + '" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px;">' +
                    '<div style="flex-shrink: 0; width: 48px; height: 48px; overflow: hidden; border-radius: 6px; background: #fff; display: flex; align-items: center; justify-content: center;">';
                
                if (isImage) {
                    fileHtml += '<img src="' + thumbnail + '" alt="' + fileName + '" style="width: 100%; height: 100%; object-fit: cover;" />';
                } else {
                    fileHtml += '<img src="' + thumbnail + '" alt="' + fileName + '" style="width: 32px; height: 32px;" />';
                }
                
                fileHtml += '</div>' +
                    '<div style="flex: 1; min-width: 0;">' +
                    '<a href="' + fileUrl + '" target="_blank" style="display: block; color: #0066ff; text-decoration: none; font-weight: 500; word-break: break-word;">' + fileName + '</a>' +
                    '<span style="font-size: 0.85rem; color: #666;">' + fileType + '</span>' +
                    '</div>' +
                    '<button type="button" class="button remove-file-btn" data-file-id="' + fileId + '" style="background: #dc3232; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">حذف</button>' +
                    '<input type="hidden" name="stages[' + stageIndex + '][files][]" value="' + fileId + '" />' +
                    '</div>';
                
                filesList.append(fileHtml);
            });
        });
        
        // Open the media frame
        fileFrame.open();
    });
    
    // Handle remove file button click
    $(document).on('click', '.remove-file-btn', function(e) {
        e.preventDefault();
        if (confirm('آیا مطمئن هستید که می‌خواهید این فایل را حذف کنید؟')) {
            $(this).closest('.file-item').remove();
        }
    });
});

