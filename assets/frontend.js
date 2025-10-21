jQuery(document).ready(function($) {
    'use strict';
    
    var selectedModelId = null;
    var selectedModelName = null;
    var selectedPrice = null;
    var selectedRepairType = null;
    var selectedRepairTypeLabel = null;
    var otherDescription = null;
    var selectedDate = null;
    var selectedTime = null;
    var selectedTimeDisplay = null;
    
    // Model selection handling
    $('input[name="iphone_model"]').on('change', function() {
        if ($(this).is(':checked')) {
            selectedModelId = $(this).val();
            selectedModelName = $(this).data('name');
            selectedPrice = $(this).data('price');
            
            // Load repair categories for selected model
            loadModelCategories(selectedModelId);
            
            // Go to repair type selection after a brief delay
            setTimeout(function() {
                showStep(2);
            }, 500);
        }
    });
    
    
    // Load model categories function
    function loadModelCategories(modelId) {
        if (!modelId) return;
        
        // Hide all prices first
        $('.pri-repair-price').hide();
        
        $.ajax({
            url: pri_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_model_categories',
                model_id: modelId,
                nonce: pri_ajax.nonce
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                if (response.success && response.data) {
                    console.log('Model Data:', response.data.model);
                    populateRepairTypePricing(response.data);
                } else {
                    console.error('Error loading repair options:', response);
                }
            },
            error: function() {
                console.error('Error loading repair options');
            }
        });
    }
    
    // Populate pricing for static repair type options
    function populateRepairTypePricing(data) {
        var model = data.model;
        console.log('populateRepairTypePricing - model:', model);
        
        // Define pricing mapping from model data
        var modelPricing = {
            screen: model.base_price,
            battery: model.battery_price,
            charging: model.charging_price,
            camera: model.camera_price,
            water: model.water_price,
            other: null
        };
        
        console.log('modelPricing:', modelPricing);
        
        // Update pricing for each static repair type option
        var repairTypes = ['screen', 'battery', 'charging', 'camera', 'water', 'other'];
        
        $.each(repairTypes, function(index, slug) {
            var priceElement = $('#price-' + slug);
            var inputElement = $('#repair-' + slug);
            var price = modelPricing[slug];
            
            console.log('Processing ' + slug + ':', {
                price: price,
                priceElement: priceElement.length,
                inputElement: inputElement.length
            });
            
            // Always show the repair option, but conditionally show pricing
            inputElement.closest('.pri-repair-type-option').show();
            
            if (slug === 'screen') {
                // Screen damage always shows price (original model price)
                if (price && price > 0) {
                    priceElement.text('$' + parseFloat(price).toFixed(2));
                    priceElement.show();
                    inputElement.attr('data-price', price);
                } else {
                    priceElement.hide();
                    inputElement.attr('data-price', '0');
                }
            } else if (slug === 'other') {
                // "Other" option never has pricing
                priceElement.hide();
                inputElement.attr('data-price', '0');
            } else if (price && price > 0) {
                // Show pricing for other categories if configured
                priceElement.text('$' + parseFloat(price).toFixed(2));
                priceElement.show();
                inputElement.attr('data-price', price);
            } else {
                // Hide pricing for categories without configured prices
                priceElement.hide();
                inputElement.attr('data-price', '0');
            }
        });
        
        // Rebind repair type selection events
        bindRepairTypeEvents();
    }
    
    // Bind repair type selection events
    function bindRepairTypeEvents() {
        $('input[name="repair_type"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                selectedRepairType = $(this).val();
                selectedRepairTypeLabel = $(this).data('label');
                selectedPrice = $(this).data('price'); // Update selected price based on category
                
                // Show/hide other description and continue button based on selection
                if (selectedRepairType === 'other') {
                    $('#pri-other-description').slideDown(300);
                    $('#pri-other-continue-btn').show();
                    // Focus on the textarea for better UX
                    setTimeout(function() {
                        $('#other-description').focus();
                    }, 350);
                } else {
                    $('#pri-other-description').slideUp(200);
                    $('#pri-other-continue-btn').hide();
                    $('#other-description').val('');
                    
                    // Auto-advance to scheduling after a brief delay
                    setTimeout(function() {
                        showStep(3); // Go to scheduling step
                    }, 600);
                }
            }
        });
    }
    
    // Initial binding for repair type events (for when page loads)
    $(document).ready(function() {
        bindRepairTypeEvents();
    });
    
    // Search functionality
    $('#pri-model-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();
        var $modelOptions = $('.pri-model-option');
        var visibleCount = 0;
        
        if (searchTerm === '') {
            // Show all models when search is empty
            $modelOptions.show();
            $('#pri-no-results').hide();
            return;
        }
        
        $modelOptions.each(function() {
            var modelName = $(this).data('model-name');
            if (modelName.includes(searchTerm)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0) {
            $('#pri-no-results').show();
        } else {
            $('#pri-no-results').hide();
        }
    });
    
    // Clear search when clicking clear button (if we add one)
    $(document).on('click', '.pri-clear-search', function() {
        $('#pri-model-search').val('').trigger('input');
    });
    
    
    // Repair step navigation
    $('#pri-repair-back-btn').on('click', function() {
        showStep(1);
    });
    
    // Load available time slots for selected date
    function loadAvailableTimeSlots(date) {
        $('#pri-loading-slots').show();
        $('#pri-time-slots').empty();
        $('#pri-no-slots').hide();
        $('#pri-time-slots-container').show();
        
        $.ajax({
            url: pri_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_available_slots',
                date: date,
                nonce: pri_ajax.nonce
            },
            success: function(response) {
                $('#pri-loading-slots').hide();
                
                if (response.success && response.data.length > 0) {
                    displayTimeSlots(response.data);
                } else {
                    $('#pri-no-slots').show();
                }
            },
            error: function() {
                $('#pri-loading-slots').hide();
                showMessage('Error loading available times. Please try again.', 'error');
            }
        });
    }
    
    // Display available time slots
    function displayTimeSlots(slots) {
        var slotsHtml = '';
        
        $.each(slots, function(index, slot) {
            slotsHtml += '<div class="pri-time-slot">';
            slotsHtml += '<input type="radio" id="time-' + index + '" name="appointment_time" value="' + slot.start_time + '" data-display="' + slot.display_time + '">';
            slotsHtml += '<label for="time-' + index + '" class="pri-time-slot-label">' + slot.display_time + '</label>';
            slotsHtml += '</div>';
        });
        
        $('#pri-time-slots').html(slotsHtml);
        
        // Handle time slot selection
        $('input[name="appointment_time"]').on('change', function() {
            if ($(this).is(':checked')) {
                selectedTime = $(this).val();
                selectedTimeDisplay = $(this).data('display');
                $('#pri-scheduling-continue-btn').prop('disabled', false);
            }
        });
    }
    
    // Format appointment display
    function formatAppointmentDisplay(date, time) {
        // Parse date as YYYY-MM-DD to avoid timezone issues
        var dateParts = date.split('-');
        var year = parseInt(dateParts[0]);
        var month = parseInt(dateParts[1]) - 1; // Month is 0-indexed
        var day = parseInt(dateParts[2]);
        
        // Create date in local timezone
        var dateObj = new Date(year, month, day);
        var dateStr = dateObj.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long', 
            day: 'numeric'
        });
        return dateStr + ' at ' + time;
    }
    
    // Handle continue button for "Other" option
    $('#pri-other-continue-btn').on('click', function() {
        proceedToCustomerInfo();
    });
    
    // Auto-advance when user stops typing in Other description (after 2 seconds of inactivity)
    var otherTypingTimer;
    $('#other-description').on('input', function() {
        var description = $(this).val().trim();
        
        // Clear previous timer
        clearTimeout(otherTypingTimer);
        
        // Only auto-advance if there's meaningful content (at least 10 characters)
        if (description.length >= 10) {
            otherTypingTimer = setTimeout(function() {
                if (selectedRepairType === 'other' && $('#other-description').val().trim().length >= 10) {
                    proceedToCustomerInfo();
                }
            }, 2000); // Wait 2 seconds after user stops typing
        }
    });
    
    // Scheduling step navigation
    $('#pri-scheduling-back-btn').on('click', function() {
        showStep(2);
    });
    
    $('#pri-scheduling-continue-btn').on('click', function() {
        if (selectedDate && selectedTime) {
            proceedToCustomerInfo();
        }
    });
    
    // Date selection handler
    $('#appointment-date').on('change', function() {
        selectedDate = $(this).val();
        if (selectedDate) {
            loadAvailableTimeSlots(selectedDate);
        }
    });
    
    // Back to scheduling from customer info
    $('#pri-back-btn').on('click', function() {
        showStep(3);
    });
    
    // Function to proceed to customer info (updated)
    function proceedToCustomerInfo() {
        // Update booking summary with appointment info
        $('#pri-booking-model').text(selectedModelName);
        $('#pri-booking-repair-type').text(selectedRepairTypeLabel + (otherDescription ? ': ' + otherDescription : ''));
        $('#pri-booking-price').text('$' + parseFloat(selectedPrice).toFixed(2));
        $('#pri-booking-appointment').text(formatAppointmentDisplay(selectedDate, selectedTimeDisplay));
        
        // Go to customer information step
        showStep(4);
    }
    
    // Handle appointment form submission
    $('#pri-appointment-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'submit_appointment',
            nonce: pri_ajax.nonce,
            model_id: selectedModelId,
            repair_type: selectedRepairType,
            repair_description: otherDescription || '',
            name: $('#customer-name').val().trim(),
            email: $('#customer-email').val().trim(),
            phone: $('#customer-phone').val().trim(),
            customer_notes: $('#customer-notes').val().trim(),
            accepts_sms: $('#accepts-sms').is(':checked') ? 1 : 0,
            appointment_date: selectedDate,
            appointment_time: selectedTime
        };
        
        // Debug: log form data
        console.log('Form submission data:', formData);
        
        // Validate form
        if (!formData.name || !formData.email || !formData.phone) {
            showMessage('Please fill in all required fields.', 'error');
            return;
        }
        
        if (!selectedModelId) {
            showMessage('Please select an iPhone model.', 'error');
            showStep(1);
            return;
        }
        
        if (!selectedRepairType) {
            showMessage('Please select a repair type.', 'error');
            showStep(2);
            return;
        }
        
        if (!selectedDate || !selectedTime) {
            showMessage('Please select an appointment date and time.', 'error');
            showStep(3);
            return;
        }
        
        if (!isValidEmail(formData.email)) {
            showMessage('Please enter a valid email address.', 'error');
            return;
        }
        
        // Show loading
        showLoading(true);
        
        // Submit form
        $.ajax({
            url: pri_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showStep(5);
                    clearMessages();
                } else {
                    console.error('Server error:', response);
                    var errorMsg = response.data || 'An unknown error occurred. Please try again.';
                    showMessage('Error: ' + errorMsg, 'error');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.error('AJAX error:', xhr, status, error);
                showMessage('Connection error: ' + error + '. Please check your internet connection and try again.', 'error');
            }
        });
    });
    
    // Start new appointment
    $('#pri-new-appointment-btn').on('click', function() {
        resetForm();
        showStep(1);
    });
    
    // Helper functions
    function showStep(stepNumber) {
        $('.pri-form-step').removeClass('pri-active');
        $('#pri-step' + stepNumber).addClass('pri-active');
        
        // Scroll to top of form
        $('html, body').animate({
            scrollTop: $('#pri-repair-form-container').offset().top - 50
        }, 300);
    }
    
    function showLoading(show) {
        if (show) {
            $('#pri-loading').show();
        } else {
            $('#pri-loading').hide();
        }
    }
    
    function hideLoading() {
        showLoading(false);
    }
    
    function showMessage(message, type) {
        var messageClass = 'pri-notice-' + (type || 'info');
        var messageHtml = '<div class="pri-notice ' + messageClass + '"><p>' + message + '</p></div>';
        
        $('#pri-messages').html(messageHtml);
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                $('#pri-messages').fadeOut();
            }, 5000);
        }
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $('#pri-messages').offset().top - 50
        }, 300);
    }
    
    function clearMessages() {
        $('#pri-messages').empty();
    }
    
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function resetForm() {
        // Reset model selection
        $('input[name="iphone_model"]').prop('checked', false);
        
        // Reset search
        $('#pri-model-search').val('');
        $('.pri-model-option').show();
        $('#pri-no-results').hide();
        
        // Reset customer form
        $('#pri-appointment-form')[0].reset();
        
        // Reset variables
        selectedModelId = null;
        selectedModelName = null;
        selectedPrice = null;
        selectedRepairType = null;
        selectedRepairTypeLabel = null;
        otherDescription = null;
        selectedDate = null;
        selectedTime = null;
        selectedTimeDisplay = null;
        
        // Reset repair type form
        $('input[name="repair_type"]').prop('checked', false);
        $('#pri-other-description').hide();
        $('#pri-other-continue-btn').hide();
        $('#other-description').val('');
        
        // Clear typing timer if active
        if (typeof otherTypingTimer !== 'undefined') {
            clearTimeout(otherTypingTimer);
        }
        
        // Clear messages
        clearMessages();
    }
    
    // Form validation on input
    $('#customer-email').on('blur', function() {
        var email = $(this).val().trim();
        if (email && !isValidEmail(email)) {
            $(this).css('border-color', '#dc3545');
            showMessage('Please enter a valid email address.', 'error');
        } else {
            $(this).css('border-color', '');
            if (email) {
                clearMessages();
            }
        }
    });
    
    // Phone number formatting (basic US format)
    $('#customer-phone').on('input', function() {
        var phone = $(this).val().replace(/\D/g, '');
        var formattedPhone = phone.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        if (phone.length === 10) {
            $(this).val(formattedPhone);
        }
    });
    
    // Prevent form submission on Enter key for model selection
    $('#pri-model-selection-form').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            // Enter key will trigger model selection change event automatically
        }
    });
    
    // Handle keyboard navigation for model selection
    $('input[name="iphone_model"]').on('keydown', function(e) {
        if (e.which === 13 || e.which === 32) { // Enter or Space
            $(this).prop('checked', true).trigger('change');
        }
    });
    
    // Add loading states to buttons
    $('.pri-btn').on('click', function() {
        var $btn = $(this);
        if (!$btn.hasClass('pri-btn-secondary') && !$btn.prop('disabled')) {
            $btn.addClass('pri-loading');
            setTimeout(function() {
                $btn.removeClass('pri-loading');
            }, 2000);
        }
    });
});