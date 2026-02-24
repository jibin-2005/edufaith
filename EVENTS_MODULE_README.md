# Enhanced Events Module Documentation

## Overview
The Enhanced Events Module provides a complete event management system for the Sunday School Management System, including:
- Event creation and management by admins and teachers
- Student event registration
- Teacher assignment to events
- Event result publication
- Attendance tracking

## Database Setup

### Running the Migration
First, run the database schema setup to create the necessary tables:

```bash
# Via browser: http://localhost/xampp/htdocs/Sunday School Management System/includes/setup_events_schema.php
# Or via PHP CLI
```

This creates the following tables:
- `event_registrations` - Student registrations for events
- `event_teachers` - Teacher assignments to coordinate events
- `event_results` - Event results/scores for students

### Database Tables

#### event_registrations
```sql
- id (INT PRIMARY KEY)
- event_id (INT FK - events.id)
- student_id (INT FK - users.id)
- registered_at (TIMESTAMP)
- attendance_status (ENUM: 'registered', 'attended', 'absent', 'cancelled')
```

#### event_teachers
```sql
- id (INT PRIMARY KEY)
- event_id (INT FK - events.id)
- teacher_id (INT FK - users.id)
- role (VARCHAR: 'coordinator', 'co-coordinator', 'participant')
- assigned_at (TIMESTAMP)
```

#### event_results
```sql
- id (INT PRIMARY KEY)
- event_id (INT FK - events.id)
- student_id (INT FK - users.id)
- marks (INT 0-100)
- remarks (TEXT)
- result_status (ENUM: 'pending', 'published')
- published_at (TIMESTAMP)
- published_by (INT FK - users.id)
```

#### events (Modified)
New columns added:
- `status` (ENUM: 'upcoming', 'ongoing', 'completed', 'cancelled')
- `is_results_published` (BOOLEAN)

## Features

### 1. Admin: Manage Events (/admin/manage_events.php)

#### Create Events
- Add event title, date/time, and description
- Events default to "upcoming" status
- Validation ensures proper data entry

#### Manage Event Details
Click "Details" on any event to access:

**Event Information Tab:**
- View event title, date, description
- Change event status (upcoming → ongoing → completed → cancelled)

**Student Registrations Tab:**
- View all student registrations
- Track attendance status (registered, attended, absent, cancelled)
- Update attendance status for each student
- Remove registrations if needed

**Teachers Tab:**
- Assign teachers to the event
- Set teacher roles:
  - Coordinator (primary organizer)
  - Co-Coordinator (assistant)
  - Participant (contributing teacher)
- Remove teacher assignments

**Results Tab:**
- View all event results
- Publish results to students
- View publication status

### 2. Student: Register for Events (/user/events.php)

#### View Events
- Browse all upcoming events
- See event details: date, time, description
- View registered events with badge indicator

#### Register for Event
- Click "Register" button on event card
- Or click "View Details" then register
- Get confirmation of successful registration
- Cannot register twice for same event

#### View Event Details
- See full event description
- View assigned event coordinators
- View registration status
- View published results (if available)

#### Cancel Registration
- From event details page
- Click "Cancel Registration"
- Registration is removed from event

### 3. Teacher: Manage Event Results (/user/event_results.php)

#### View Coordinated Events
- See all events where teacher is assigned
- View registration and result entry statistics

#### Enter Results
- Click on event to view registered students
- Click "Edit" button for each student
- Enter marks (0-100)
- Add remarks/feedback
- Save results

#### Result Status
- Pending: Results not yet published to students
- Published: Results visible to students

## Workflow Examples

### Scenario 1: Running a Class Event

1. **Admin Creates Event**
   - Go to Admin > Events
   - Click "Create Event"
   - Fill in event details
   - Event status is "upcoming"

2. **Admin Assigns Teachers**
   - Click event "Details"
   - Go to "Teachers" tab
   - Add teacher(s) as coordinator(s)

3. **Students Register**
   - Students go to Events page
   - Click "Register" on event
   - Confirmation received

4. **Track Attendance**
   - Admin views event details
   - Update attendance status per student
   - Can mark as attended, absent, or cancelled

5. **Teachers Enter Results**
   - Teachers go to Event Results
   - Select event
   - Enter marks and remarks for each student
   - Save results

6. **Admin Publishes Results**
   - Admin views event details
   - Goes to Results tab
   - Clicks "Publish Results"
   - All pending results become published

7. **Students View Results**
   - Students view event details
   - See their scores if published

### Scenario 2: Simple Announcement Event

1. **Teacher Creates Event**
   - Teacher goes to Events
   - Creates announcement event
   - No need to grade

2. **Students Register**
   - Students browse and register

3. **Track Attendance**
   - Admin marks attendance
   - Event serves as registration record

## API Endpoints

### Event Registration Process
**File:** `/includes/event_registration_process.php`

**Actions:**
- `register` - Register student for event
- `unregister` - Cancel registration

### Event Attendance Process
**File:** `/includes/event_attendance_process.php`

Handles attendance status updates for registrations.

## Validation & Security

- Only authenticated users can access their respective pages
- Admin-only operations verified on backend
- Teacher can only enter results for events they're assigned to
- Student data is isolated by user ID
- All inputs validated and sanitized
- AJAX requests (future) will verify origin

## File Structure

```
admin/
  └── manage_events.php          # Admin event management (NEW)

user/
  ├── events.php                 # Student/Teacher event listing (UPDATED)
  └── event_results.php          # Teacher result management (NEW)

includes/
  ├── setup_events_schema.php    # Database migration (NEW)
  ├── event_registration_process.php # Registration handler (NEW)
  └── event_attendance_process.php   # Attendance handler (NEW)
```

## Future Enhancements

1. Event categories (academic, sports, social)
2. Event capacity limits
3. Waitlist functionality
4. Event reminders/notifications
5. Bulk result import (CSV)
6. Grade scales and curved grading
7. Calendar integration
8. Event feedback/surveys
9. Parent event notifications
10. Event photo gallery

## Troubleshooting

### Database Tables Not Created
- Ensure `setup_events_schema.php` ran successfully
- Check database permissions
- Verify database connection

### Teachers Cannot Access Event Results
- Verify teacher is assigned to the event
- Check `event_teachers` table for assignment

### Results Not Publishing
- Ensure admin role
- Check that results exist (not null)
- Verify event_results records were created

### Students Cannot Register
- Check event hasn't passed
- Ensure event status is not 'cancelled'
- Verify student account is active

## Support

For issues or questions, contact the system administrator.
