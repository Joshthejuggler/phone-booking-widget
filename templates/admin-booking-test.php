<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Test Booking System', 'phone-repair-intake'); ?></h1>
    
    <div class="card">
        <h2>Live Booking Test</h2>
        <p>Test the complete booking flow with real availability and Google Calendar integration.</p>
        
        <div id="booking-test-form">
            <table class="form-table">
                <tr>
                    <th scope="row">Select Date</th>
                    <td>
                        <input type="date" id="test-date" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
                        <button type="button" id="load-slots" class="button">Load Available Slots</button>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Available Times</th>
                    <td>
                        <div id="available-slots" style="margin-top: 10px;">
                            <em>Select a date to see available appointment times</em>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Test Booking</th>
                    <td>
                        <div id="selected-slot" style="margin-bottom: 10px;">
                            <em>Select a time slot above</em>
                        </div>
                        <input type="text" id="test-name" placeholder="Test Customer Name" style="width: 200px; margin-right: 10px;">
                        <input type="email" id="test-email" placeholder="test@example.com" style="width: 200px; margin-right: 10px;">
                        <button type="button" id="test-book" class="button-primary" disabled>Test Booking</button>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="booking-result" style="margin-top: 20px;"></div>
    </div>
    
    <div class="card">
        <h2>Testing Instructions</h2>
        <ol>
            <li><strong>Set your availability:</strong> Go to Phone Repair → Availability and configure your schedule</li>
            <li><strong>Test basic availability:</strong> Select a date above and load slots</li>
            <li><strong>Test conflict detection:</strong> 
                <ul>
                    <li>Note an available time slot</li>
                    <li>Create an event in Google Calendar at that time</li>
                    <li>Reload slots - the conflicted slot should disappear</li>
                    <li>Delete the Google event - the slot should reappear</li>
                </ul>
            </li>
            <li><strong>Test booking:</strong> Select a slot, enter test details, and book to see if Google Calendar event is created</li>
        </ol>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let selectedSlot = null;
    
    // Load available slots
    $('#load-slots').on('click', function() {
        const date = $('#test-date').val();
        if (!date) {
            alert('Please select a date');
            return;
        }
        
        $('#available-slots').html('<em>Loading...</em>');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'get_available_slots',
                date: date,
                nonce: '<?php echo wp_create_nonce('pri_frontend_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displaySlots(response.data);
                } else {
                    $('#available-slots').html('<div style="color: red;">Error: ' + response.data + '</div>');
                }
            },
            error: function() {
                $('#available-slots').html('<div style="color: red;">Ajax request failed</div>');
            }
        });
    });
    
    function displaySlots(slots) {
        if (slots.length === 0) {
            $('#available-slots').html('<em>No available slots for this date</em>');
            return;
        }
        
        let html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">';
        slots.forEach(function(slot) {
            html += `<button type="button" class="button slot-button" data-slot='${JSON.stringify(slot)}'>
                        ${slot.display_time}
                     </button>`;
        });
        html += '</div>';
        
        $('#available-slots').html(html);
        
        // Handle slot selection
        $('.slot-button').on('click', function() {
            $('.slot-button').removeClass('button-primary').addClass('button');
            $(this).removeClass('button').addClass('button-primary');
            
            selectedSlot = JSON.parse($(this).attr('data-slot'));
            $('#selected-slot').html(`<strong>Selected:</strong> ${selectedSlot.display_time} on ${$('#test-date').val()}`);
            $('#test-book').prop('disabled', false);
        });
    }
    
    // Test booking
    $('#test-book').on('click', function() {
        const name = $('#test-name').val();
        const email = $('#test-email').val();
        
        if (!name || !email || !selectedSlot) {
            alert('Please fill in name, email, and select a time slot');
            return;
        }
        
        $('#booking-result').html('<div class="notice notice-info"><p>Creating test booking and Google Calendar event...</p></div>');
        
        // Actually create the appointment
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'submit_appointment',
                nonce: '<?php echo wp_create_nonce('pri_frontend_nonce'); ?>',
                model_id: 1, // Default to first iPhone model for testing
                repair_type: 'screen_replacement',
                repair_description: 'Test booking from admin',
                name: name,
                email: email,
                phone: '555-TEST',
                customer_notes: 'This is a test booking from the admin panel',
                accepts_sms: 0,
                appointment_date: $('#test-date').val(),
                appointment_time: selectedSlot.start_time
            },
            success: function(response) {
                if (response.success) {
                    $('#booking-result').html(`
                        <div class="notice notice-success">
                            <p><strong>✅ Test Booking Successful!</strong></p>
                            <p>${response.data}</p>
                            <p>Check your Google Calendar - a new event should appear at ${selectedSlot.display_time} on ${$('#test-date').val()}</p>
                        </div>
                    `);
                    
                    // Reload slots to show the booked slot is now unavailable
                    setTimeout(() => {
                        $('#load-slots').click();
                    }, 1000);
                } else {
                    $('#booking-result').html(`
                        <div class="notice notice-error">
                            <p><strong>❌ Booking Failed:</strong></p>
                            <p>${response.data}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#booking-result').html(`
                    <div class="notice notice-error">
                        <p><strong>❌ Ajax Error:</strong> Failed to create booking</p>
                    </div>
                `);
            }
        });
    });
});
</script>

<style>
.card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.card h2 {
    margin-top: 0;
}

.slot-button {
    text-align: center;
    min-height: 35px;
}
</style>