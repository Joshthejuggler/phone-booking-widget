<?php
/**
 * Google Calendar Integration for Phone Repair Intake Form
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GoogleCalendarIntegration {
    private $service;
    private $calendar_id;
    
    public function __construct() {
        // Load Composer autoloader
        if (file_exists(PRI_PLUGIN_PATH . 'vendor/autoload.php')) {
            require_once PRI_PLUGIN_PATH . 'vendor/autoload.php';
        }
        
        $this->calendar_id = get_option('pri_google_calendar_id', 'primary');
        $this->initialize_service();
    }
    
    private function initialize_service() {
        try {
            $credentials_path = PRI_PLUGIN_PATH . 'credentials/service-account.json';
            
            if (!file_exists($credentials_path)) {
                error_log('Google Calendar: Service account credentials file not found');
                return false;
            }
            
            $client = new Google_Client();
            $client->setAuthConfig($credentials_path);
            $client->addScope(Google_Service_Calendar::CALENDAR);
            
            $this->service = new Google_Service_Calendar($client);
            
            return true;
        } catch (Exception $e) {
            error_log('Google Calendar initialization error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Book an existing appointment slot by adding customer as attendee
     */
    public function book_appointment_slot($event_id, $appointment_data) {
        if (!$this->service) {
            return false;
        }
        
        try {
            // Get the existing event
            $event = $this->service->events->get($this->calendar_id, $event_id);
            
            // Update the event title and description
            $event->setSummary('Phone Repair - ' . $appointment_data['customer_name']);
            $event->setDescription($this->build_event_description($appointment_data));
            
            // Add customer as attendee
            $attendees = $event->getAttendees() ?: [];
            $attendees[] = new Google_Service_Calendar_EventAttendee([
                'email' => $appointment_data['customer_email'],
                'displayName' => $appointment_data['customer_name']
            ]);
            $event->setAttendees($attendees);
            
            // Update the event
            $updated_event = $this->service->events->update($this->calendar_id, $event_id, $event);
            
            return [
                'success' => true,
                'event_id' => $updated_event->getId(),
                'event_link' => $updated_event->getHtmlLink()
            ];
            
        } catch (Exception $e) {
            error_log('Google Calendar booking error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create calendar event for appointment (legacy method - kept for compatibility)
     */
    public function create_appointment_event($appointment_data) {
        if (!$this->service) {
            return false;
        }
        
        try {
            $event = new Google_Service_Calendar_Event([
                'summary' => 'Phone Repair - ' . $appointment_data['customer_name'],
                'description' => $this->build_event_description($appointment_data),
                'start' => [
                    'dateTime' => $appointment_data['start_time'],
                    'timeZone' => 'America/Regina'
                ],
                'end' => [
                    'dateTime' => $appointment_data['end_time'],
                    'timeZone' => 'America/Regina'
                ]
                // Note: Removed attendees - service accounts can't invite without Domain-Wide Delegation
            ]);
            
            $created_event = $this->service->events->insert($this->calendar_id, $event);
            
            return [
                'success' => true,
                'event_id' => $created_event->getId(),
                'event_link' => $created_event->getHtmlLink()
            ];
            
        } catch (Exception $e) {
            error_log('Google Calendar event creation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check availability for a given time slot
     */
    public function check_availability($start_time, $end_time) {
        if (!$this->service) {
            return false;
        }
        
        try {
            $time_min = date('c', strtotime($start_time));
            $time_max = date('c', strtotime($end_time));
            
            $events = $this->service->events->listEvents($this->calendar_id, [
                'timeMin' => $time_min,
                'timeMax' => $time_max,
                'singleEvents' => true,
                'orderBy' => 'startTime'
            ]);
            
            return count($events->getItems()) === 0;
            
        } catch (Exception $e) {
            error_log('Google Calendar availability check error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available appointment slots for a given date
     * Uses WordPress availability schedule + Google Calendar conflict detection
     */
    public function get_available_slots($date, $brand_id = 'default') {
        if (!$this->service) {
            error_log('Google Calendar: Service not initialized');
            return [];
        }
        
        error_log('Google Calendar: Getting WordPress availability for ' . $date . ' (brand: ' . $brand_id . ')');
        
        // Get available slots from WordPress schedule
        $wordpress_slots = $this->get_wordpress_availability($date, $brand_id);
        
        if (empty($wordpress_slots)) {
            error_log('Google Calendar: No WordPress availability found for ' . $date);
            return [];
        }
        
        // Check each slot for Google Calendar conflicts
        $available_slots = $this->filter_conflicting_slots($wordpress_slots, $date);
        
        error_log('Google Calendar: Returning ' . count($available_slots) . ' available slots after conflict check');
        return $available_slots;
    }
    
    /**
     * Get available slots from WordPress availability schedule
     */
    private function get_wordpress_availability($date, $brand_id = 'default') {
        global $wpdb;
        
        $day_of_week = date('N', strtotime($date)); // 1 = Monday, 7 = Sunday
        $availability_table = $wpdb->prefix . 'pri_availability';
        
        $slots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $availability_table 
             WHERE day_of_week = %d AND brand_id = %s AND is_active = 1 
             ORDER BY start_time",
            $day_of_week,
            $brand_id
        ));
        
        $available_slots = [];
        foreach ($slots as $slot) {
            // Create slots in Saskatchewan timezone (no DST)
            $sask_tz = new DateTimeZone('America/Regina'); // Saskatchewan = Regina timezone
            
            $start_dt = new DateTime($date . ' ' . $slot->start_time, $sask_tz);
            $end_dt = new DateTime($date . ' ' . $slot->end_time, $sask_tz);
            
            $available_slots[] = [
                'slot_id' => $slot->id,
                'start' => substr($slot->start_time, 0, 5),
                'end' => substr($slot->end_time, 0, 5),
                'datetime' => $start_dt->format('Y-m-d H:i:s'),
                'start_datetime' => $start_dt->format('c'),
                'end_datetime' => $end_dt->format('c'),
                'title' => 'Available Appointment Slot'
            ];
            
            error_log('Google Calendar: Created WordPress slot ' . $slot->start_time . ' in Regina timezone: ' . $start_dt->format('Y-m-d H:i:s T'));
        }
        
        error_log('Google Calendar: Found ' . count($available_slots) . ' WordPress availability slots');
        return $available_slots;
    }
    
    /**
     * Filter out slots that conflict with existing Google Calendar events
     */
    private function filter_conflicting_slots($wordpress_slots, $date) {
        try {
            $start_of_day = $date . ' 00:00:00';
            $end_of_day = $date . ' 23:59:59';
            
            $time_min = date('c', strtotime($start_of_day));
            $time_max = date('c', strtotime($end_of_day));
            
            // Get all events from Google Calendar for this day
            $events = $this->service->events->listEvents($this->calendar_id, [
                'timeMin' => $time_min,
                'timeMax' => $time_max,
                'singleEvents' => true,
                'orderBy' => 'startTime'
            ]);
            
            error_log('Google Calendar: Found ' . count($events->getItems()) . ' existing events to check for conflicts');
            
            // Log all events found
            foreach ($events->getItems() as $event) {
                $event_start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
                $event_end = $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate();
                error_log('Google Calendar: Found event "' . $event->getSummary() . '" from ' . $event_start . ' to ' . $event_end);
            }
            
            $available_slots = [];
            
            foreach ($wordpress_slots as $slot) {
                $slot_start = strtotime($slot['start_datetime']);
                $slot_end = strtotime($slot['end_datetime']);
                $has_conflict = false;
                
                error_log('Google Calendar: Checking slot ' . $slot['start'] . '-' . $slot['end'] . ' (' . $slot['start_datetime'] . ' to ' . $slot['end_datetime'] . ')');
                
                // Check each existing event for conflicts
                foreach ($events->getItems() as $event) {
                    $event_start = $event->getStart()->getDateTime();
                    $event_end = $event->getEnd()->getDateTime();
                    
                    if ($event_start && $event_end) {
                        // Convert Google Calendar times to Saskatchewan timezone for comparison
                        $event_start_local = new DateTime($event_start);
                        $event_start_local->setTimezone(new DateTimeZone('America/Regina')); // Saskatchewan timezone
                        $event_end_local = new DateTime($event_end);
                        $event_end_local->setTimezone(new DateTimeZone('America/Regina'));
                        
                        $event_start_timestamp = $event_start_local->getTimestamp();
                        $event_end_timestamp = $event_end_local->getTimestamp();
                        
                        error_log('Google Calendar: Event timezone converted - original: ' . $event_start . ' -> local: ' . $event_start_local->format('Y-m-d H:i:s T'));
                        
                        error_log('Google Calendar: Comparing with event "' . $event->getSummary() . '" (' . $event_start . ' to ' . $event_end . ')');
                        
                        // Debug the timestamps
                        error_log('Google Calendar: Slot timestamps - start: ' . $slot_start . ' (' . date('Y-m-d H:i:s', $slot_start) . '), end: ' . $slot_end . ' (' . date('Y-m-d H:i:s', $slot_end) . ')');
                        error_log('Google Calendar: Event timestamps - start: ' . $event_start_timestamp . ' (' . date('Y-m-d H:i:s', $event_start_timestamp) . '), end: ' . $event_end_timestamp . ' (' . date('Y-m-d H:i:s', $event_end_timestamp) . ')');
                        
                        // Check for any overlap using more inclusive logic
                        $overlap_start = max($slot_start, $event_start_timestamp);
                        $overlap_end = min($slot_end, $event_end_timestamp);
                        
                        if ($overlap_start < $overlap_end) {
                            $has_conflict = true;
                            error_log('Google Calendar: CONFLICT DETECTED! Slot ' . $slot['start'] . '-' . $slot['end'] . ' overlaps with: ' . $event->getSummary());
                            error_log('Google Calendar: Overlap period: ' . date('H:i', $overlap_start) . ' to ' . date('H:i', $overlap_end));
                            break;
                        } else {
                            error_log('Google Calendar: No conflict - no time overlap detected');
                        }
                    } else {
                        error_log('Google Calendar: Skipping all-day event or event with no time: ' . $event->getSummary());
                    }
                }
                
                if (!$has_conflict) {
                    $available_slots[] = $slot;
                    error_log('Google Calendar: Slot ' . $slot['start'] . '-' . $slot['end'] . ' is available');
                }
            }
            
            return $available_slots;
            
        } catch (Exception $e) {
            error_log('Google Calendar conflict check error: ' . $e->getMessage());
            return $wordpress_slots; // Return original slots if conflict check fails
        }
    }
    
    /**
     * Generate available appointment slots based on business hours and existing bookings
     * This generates potential slots throughout the day, then checks against bookings
     */
    private function generate_available_slots($date, $booked_events) {
        $available_slots = [];
        
        // Define business hours and slot duration
        $business_start = '09:00';
        $business_end = '17:00';
        $slot_duration = 30; // 30 minute slots
        $slot_interval = 30; // 30 minutes between slots
        
        // Generate all possible 30-minute slots from 9am to 5pm
        $current_time = strtotime($date . ' ' . $business_start . ':00');
        $end_timestamp = strtotime($date . ' ' . $business_end . ':00');
        
        while ($current_time < $end_timestamp) {
            $slot_time = date('H:i', $current_time);
            $slot_datetime = $date . ' ' . $slot_time . ':00';
            
            // Check if this slot conflicts with any booked events
            $is_available = true;
            
            foreach ($booked_events as $event) {
                $event_start = $event->getStart()->getDateTime();
                if ($event_start) {
                    $event_start_timestamp = strtotime($event_start);
                    $event_end_timestamp = strtotime($event->getEnd()->getDateTime());
                    
                    // Check if this slot overlaps with the booked event
                    $slot_end_timestamp = $current_time + ($slot_duration * 60);
                    
                    if (($current_time < $event_end_timestamp) && ($slot_end_timestamp > $event_start_timestamp)) {
                        $is_available = false;
                        error_log('Google Calendar: Slot ' . $slot_time . ' conflicts with: ' . $event->getSummary());
                        break;
                    }
                }
            }
            
            if ($is_available) {
                $end_time = date('H:i', $current_time + ($slot_duration * 60));
                
                $available_slots[] = [
                    'event_id' => null,
                    'start' => $slot_time,
                    'end' => $end_time,
                    'datetime' => $slot_datetime,
                    'start_datetime' => date('c', $current_time),
                    'end_datetime' => date('c', $current_time + ($slot_duration * 60)),
                    'title' => 'Available Appointment Slot'
                ];
                
                error_log('Google Calendar: Added available slot: ' . $slot_time);
            }
            
            // Move to next slot
            $current_time += ($slot_interval * 60);
        }
        
        return $available_slots;
    }
    
    /**
     * Filter appointment slots to show only available ones
     * Checks if appointment events have customer attendees (booked) or not (available)
     */
    private function filter_available_appointment_slots($events) {
        $available_slots = [];
        
        foreach ($events as $event) {
            error_log('Google Calendar: Processing event - ' . $event->getSummary());
            
            // Check if this is an available slot
            $attendees = $event->getAttendees();
            $is_available = true;
            
            error_log('Google Calendar: Event has ' . (is_array($attendees) ? count($attendees) : 0) . ' attendees');
            
            if ($attendees) {
                // If there are attendees other than Josh/service account, it's booked
                foreach ($attendees as $attendee) {
                    $email = $attendee->getEmail();
                    error_log('Google Calendar: Attendee email - ' . $email);
                    
                    // Skip if it's Josh's own email or the service account
                    if (strpos($email, 'jchalmers') === false && 
                        strpos($email, 'gserviceaccount') === false &&
                        strpos($email, '.iam.gserviceaccount.com') === false) {
                        $is_available = false;
                        error_log('Google Calendar: Event is booked by ' . $email);
                        break;
                    }
                }
            }
            
            if ($is_available) {
                $start_datetime = $event->getStart()->getDateTime();
                $end_datetime = $event->getEnd()->getDateTime();
                
                // Handle all-day events
                if (!$start_datetime) {
                    $start_datetime = $event->getStart()->getDate() . 'T09:00:00';
                    $end_datetime = $event->getEnd()->getDate() . 'T10:00:00';
                }
                
                error_log('Google Calendar: Adding available slot - ' . $start_datetime);
                
                $available_slots[] = [
                    'event_id' => $event->getId(),
                    'start' => date('H:i', strtotime($start_datetime)),
                    'end' => date('H:i', strtotime($end_datetime)),
                    'datetime' => date('Y-m-d H:i:s', strtotime($start_datetime)),
                    'start_datetime' => $start_datetime,
                    'end_datetime' => $end_datetime,
                    'title' => $event->getSummary()
                ];
            }
        }
        
        error_log('Google Calendar: Returning ' . count($available_slots) . ' available slots');
        return $available_slots;
    }
    
    /**
     * Detect appointment slots by analyzing calendar events
     * Looks for patterns that indicate available vs booked appointment times
     */
    private function detect_appointment_slots($date, $all_events) {
        $available_slots = [];
        
        error_log('Google Calendar: Analyzing ' . count($all_events) . ' events for appointment patterns');
        
        foreach ($all_events as $event) {
            $summary = $event->getSummary();
            $start_datetime = $event->getStart()->getDateTime();
            
            if (!$start_datetime) {
                continue; // Skip all-day events
            }
            
            $start_timestamp = strtotime($start_datetime);
            $end_datetime = $event->getEnd()->getDateTime();
            $attendees = $event->getAttendees();
            $attendee_count = is_array($attendees) ? count($attendees) : 0;
            
            // Check if this looks like an appointment slot
            $is_appointment_slot = (
                stripos($summary, 'appointment') !== false ||
                stripos($summary, 'josh') !== false ||
                $event->getVisibility() === 'public'
            );
            
            if ($is_appointment_slot) {
                error_log("Google Calendar: Found appointment slot candidate: '{$summary}' with {$attendee_count} attendees");
                
                // Check if this slot appears to be available
                $is_available = false;
                
                if ($attendee_count <= 1) {
                    // No attendees or just Josh = available
                    $is_available = true;
                } elseif ($attendees) {
                    // Check if attendees are just Josh/service account
                    $customer_attendees = 0;
                    foreach ($attendees as $attendee) {
                        $email = $attendee->getEmail();
                        if (strpos($email, 'jchalmers') === false && 
                            strpos($email, 'gserviceaccount') === false &&
                            strpos($email, '.iam.gserviceaccount.com') === false) {
                            $customer_attendees++;
                        }
                    }
                    $is_available = ($customer_attendees == 0);
                }
                
                if ($is_available) {
                    $slot_time = date('H:i', $start_timestamp);
                    $end_time = date('H:i', strtotime($end_datetime));
                    
                    $available_slots[] = [
                        'event_id' => $event->getId(),
                        'start' => $slot_time,
                        'end' => $end_time,
                        'datetime' => date('Y-m-d H:i:s', $start_timestamp),
                        'start_datetime' => $start_datetime,
                        'end_datetime' => $end_datetime,
                        'title' => $summary
                    ];
                    
                    error_log("Google Calendar: Added available slot: {$slot_time} ('{$summary}')");
                } else {
                    error_log("Google Calendar: Slot {$summary} is booked");
                }
            }
        }
        
        error_log('Google Calendar: Detected ' . count($available_slots) . ' available appointment slots');
        return $available_slots;
    }
    
    /**
     * Build event description with appointment details
     */
    private function build_event_description($data) {
        $description = "Phone Repair Appointment\n\n";
        $description .= "Customer: {$data['customer_name']}\n";
        $description .= "Email: {$data['customer_email']}\n";
        $description .= "Phone: {$data['customer_phone']}\n";
        $description .= "Device: {$data['model_name']}\n";
        $description .= "Repair: {$data['repair_type']}\n";
        
        if (!empty($data['repair_description'])) {
            $description .= "Description: {$data['repair_description']}\n";
        }
        
        if (!empty($data['customer_notes'])) {
            $description .= "Customer Notes: {$data['customer_notes']}\n";
        }
        
        $description .= "Price: $" . number_format($data['price'], 2);
        
        return $description;
    }
    
    /**
     * Test connection to Google Calendar
     */
    public function test_connection() {
        if (!$this->service) {
            return [
                'success' => false,
                'message' => 'Service not initialized'
            ];
        }
        
        try {
            $calendar = $this->service->calendars->get($this->calendar_id);
            return [
                'success' => true,
                'message' => 'Connected to calendar: ' . $calendar->getSummary(),
                'calendar_name' => $calendar->getSummary()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
}