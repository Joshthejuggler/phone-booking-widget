jQuery(document).ready(function($) {
    'use strict';
    
    var $modal = $('#pri-model-modal');
    var $form = $('#pri-model-form');
    var $loadingOverlay = $('#pri-loading-overlay');
    
    // Open modal for adding new model
    $('#pri-add-model-btn').on('click', function() {
        resetForm();
        $('#pri-modal-title').text('Add iPhone Model');
        $modal.show();
    });
    
    // Open modal for editing existing model (using event delegation)
    $(document).on('click', '.pri-edit-model', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var price = $(this).data('price');
        var batteryPrice = $(this).data('battery-price');
        var chargingPrice = $(this).data('charging-price');
        var cameraPrice = $(this).data('camera-price');
        var waterPrice = $(this).data('water-price');
        var active = $(this).data('active');
        
        // Debug: show what data we're reading from the button
        console.log('=== EDIT MODAL OPENING DEBUG ===');
        console.log('Button data-active value:', active);
        console.log('Button data-active type:', typeof active);
        console.log('All button data:', $(this).data());
        console.log('=================================');
        
        resetForm();
        $('#model-id').val(id);
        $('#model-name').val(name);
        $('#model-screen-price').val(price);
        $('#model-battery-price').val(batteryPrice || '');
        $('#model-charging-price').val(chargingPrice || '');
        $('#model-camera-price').val(cameraPrice || '');
        $('#model-water-price').val(waterPrice || '');
        $('#model-active').prop('checked', active == 1);
        
        $('#pri-modal-title').text('Edit iPhone Model');
        $modal.show();
    });
    
    // Close modal
    $('.pri-modal-close').on('click', function() {
        $modal.hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if (e.target === $modal[0]) {
            $modal.hide();
        }
    });
    
    // Handle form submission
    $form.on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'save_iphone_model',
            nonce: pri_admin_ajax.nonce,
            id: $('#model-id').val(),
            model_name: $('#model-name').val().trim(),
            price: $('#model-screen-price').val(),
            battery_price: $('#model-battery-price').val() || null,
            charging_price: $('#model-charging-price').val() || null,
            camera_price: $('#model-camera-price').val() || null,
            water_price: $('#model-water-price').val() || null
        };
        
        // Only send is_active if checkbox is checked (matches standard HTML form behavior)
        if ($('#model-active').is(':checked')) {
            formData.is_active = 1;
        }
        
        // Validate form
        if (!formData.model_name || !formData.price) {
            showAdminNotice('Please fill in all required fields.', 'error');
            return;
        }
        
        if (isNaN(formData.price) || parseFloat(formData.price) < 0) {
            showAdminNotice('Please enter a valid price.', 'error');
            return;
        }
        
        // Debug: log form data
        console.log('Submitting form data:', formData);
        
        // Show loading
        showLoading(true);
        
        // Submit form
        $.ajax({
            url: pri_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    $modal.hide();
                    showAdminNotice('iPhone model saved successfully!', 'success');
                    
                    // Reload page to show updated data
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAdminNotice(response.data || 'An error occurred while saving.', 'error');
                }
            },
            error: function() {
                hideLoading();
                showAdminNotice('Connection error. Please try again.', 'error');
            }
        });
    });
    
    // Handle model deletion (using event delegation)
    $(document).on('click', '.pri-delete-model', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var $row = $(this).closest('tr');
        
        if (!confirm('Are you sure you want to delete "' + name + '"?\n\nThis action cannot be undone.')) {
            return;
        }
        
        // Show loading
        showLoading(true);
        
        $.ajax({
            url: pri_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_iphone_model',
                nonce: pri_admin_ajax.nonce,
                id: id
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    $row.fadeOut(500, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if ($('.wp-list-table tbody tr').length === 0) {
                            window.location.reload();
                        }
                    });
                    showAdminNotice('iPhone model deleted successfully!', 'success');
                } else {
                    showAdminNotice(response.data || 'An error occurred while deleting.', 'error');
                }
            },
            error: function() {
                hideLoading();
                showAdminNotice('Connection error. Please try again.', 'error');
            }
        });
    });
    
    // Helper functions
    function resetForm() {
        $form[0].reset();
        $('#model-id').val('0');
        $('#model-active').prop('checked', true);
        clearAdminNotices();
    }
    
    function showLoading(show) {
        if (show) {
            $loadingOverlay.show();
        } else {
            $loadingOverlay.hide();
        }
    }
    
    function hideLoading() {
        showLoading(false);
    }
    
    function showAdminNotice(message, type) {
        var noticeClass = 'notice-' + (type || 'info');
        var dismissible = type === 'success' ? 'is-dismissible' : '';
        
        var noticeHtml = '<div class="notice ' + noticeClass + ' ' + dismissible + '">' +
            '<p>' + message + '</p>' +
            (dismissible ? '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' : '') +
            '</div>';
        
        // Remove existing notices
        $('.notice').not('.settings-error').remove();
        
        // Add new notice
        $('.wrap h1').after(noticeHtml);
        
        // Auto-dismiss success messages
        if (type === 'success') {
            setTimeout(function() {
                $('.notice-success').fadeOut();
            }, 3000);
        }
        
        // Scroll to top
        $('html, body').animate({
            scrollTop: $('.wrap').offset().top - 50
        }, 300);
    }
    
    function clearAdminNotices() {
        $('.notice').not('.settings-error').remove();
    }
    
    // Handle notice dismiss buttons
    $(document).on('click', '.notice-dismiss', function() {
        $(this).parent().fadeOut();
    });
    
    // Form validation
    $('#model-name').on('blur', function() {
        var name = $(this).val().trim();
        if (!name) {
            $(this).css('border-color', '#dc3545');
        } else {
            $(this).css('border-color', '');
        }
    });
    
    $('#model-screen-price').on('blur', function() {
        var price = $(this).val();
        if (!price || isNaN(price) || parseFloat(price) < 0) {
            $(this).css('border-color', '#dc3545');
        } else {
            $(this).css('border-color', '');
        }
    });
    
    // Price input formatting
    $('#model-screen-price, #model-battery-price, #model-charging-price, #model-camera-price, #model-water-price').on('input', function() {
        var value = $(this).val();
        // Remove any non-numeric characters except decimal point
        value = value.replace(/[^0-9.]/g, '');
        
        // Ensure only one decimal point
        var parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Limit to 2 decimal places
        if (parts[1] && parts[1].length > 2) {
            value = parts[0] + '.' + parts[1].substring(0, 2);
        }
        
        $(this).val(value);
    });
    
    // Enhanced table interactions
    $('.wp-list-table tbody tr').hover(
        function() {
            $(this).addClass('hover');
        },
        function() {
            $(this).removeClass('hover');
        }
    );
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // ESC to close modal
        if (e.keyCode === 27 && $modal.is(':visible')) {
            $modal.hide();
        }
        
        // Ctrl+N or Cmd+N to add new model (when not in input field)
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 78 && !$(e.target).is('input, textarea')) {
            e.preventDefault();
            $('#pri-add-model-btn').click();
        }
    });
    
    // Auto-save draft functionality (for future enhancement)
    var autoSaveTimer;
    $form.on('input', 'input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Could implement auto-save draft here
        }, 2000);
    });
});
