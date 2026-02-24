# Events Module Implementation Guide

## Overview

The Events Management System has been completely enhanced to provide a full-featured platform for managing church events with teacher assignments, student registration, and result management.

## Features Implemented

### 1. **Admin Event Management** (`/admin/manage_events.php`)
- ✅ Create new events with title, description, and date
- ✅ View all events with status and statistics
- ✅ Update event status (Upcoming → Ongoing → Completed → Cancelled)
- ✅ Detailed event view with tabbed interface:
  - **Registrations Tab**: View all student registrations with attendance status
  - **Teachers Tab**: Assign teachers to events with roles (Coordinator/Co-Coordinator/Participant)
  - **Results Tab**: Enter and publish event results for students

### 2. **Teacher Assignment System**
- ✅ Assign multiple teachers to each event
- ✅ Define teacher roles:
  - **Coordinator**: Primary teacher overseeing the event
  - **Co-Coordinator**: Secondary coordinator
  - **Participant**: Assisting teacher
- ✅ Edit or remove teacher assignments
- ✅ Teachers can view assigned events and enter results

### 3. **Student Registration & Results**
- ✅ Students can register for upcoming events
- ✅ View registered events in dedicated "My Registered Events" tab
- ✅ See published results with marks and remarks
- ✅ Cancel registration if needed
- ✅ Event status badges (Upcoming, Ongoing, Completed, Cancelled)

### 4. **Result Management**
- ✅ Admin enters marks (0-100) for each student
- ✅ Add remarks/comments for individual results
- ✅ Result validation with real-time feedback
- ✅ Publish results to make them visible to students
- ✅ Students see results once published

### 5. **Database Schema**
Three new tables created:

#### `event_registrations`
- Tracks which students registered for events
- Attendance status tracking
- Registration timestamps

#### `event_teachers`
- Maps teachers to events
- Stores teacher roles
- Assignment timestamps

#### `event_results`
- Stores student marks and remarks
- Result publication status
- Published by/date tracking

## Database Setup

### Option 1: Automatic Setup (Recommended)
1. Go to `/admin/events_setup.php`
2. Review the status of required tables
3. Click "Initialize Database" button
4. System will create all necessary tables

### Option 2: Manual Setup
Run `/includes/setup_events_schema.php` directly to create tables.

### Database Changes
The system adds these columns to existing `events` table:
- `status` (upcoming, ongoing, completed, cancelled)
- `is_results_published` (boolean)
- `created_at` (timestamp)

## Usage Workflow

### For Admins

#### Creating an Event
1. Go to `/admin/manage_events.php`
2. Fill in event details (Title, Date, Description)
3. Click "Create Event"

#### Assigning Teachers
1. Click "Details" on an event
2. Go to "Teachers" tab
3. Select a teacher and role
4. Click "Assign Teacher"
5. Teachers appear in the list once assigned

#### Entering Results
1. Click "Details" on a completed event
2. Go to "Results" tab
3. Click "Enter/Edit" button for each student
4. Enter marks (0-100) and remarks
5. Click "Save Result"
6. When done, click "Publish Results" to make them visible to students

#### Publishing Results
- Results must be entered before publishing
- Published results are visible to students immediately
- Admin can still edit results after publishing

### For Teachers

#### Creating Events
1. Go to `/user/events.php`
2. Fill in "Create New Event" form
3. Event is created and visible to students

#### Viewing Assigned Events
1. Teachers see events they're assigned to
2. Can view student registrations
3. Can enter/edit results for assigned events

#### Entering Results
1. Go to `/user/event_results.php`
2. See events where teacher is assigned as Coordinator
3. View list of registered students
4. Click to edit and enter marks/remarks
5. Results saved for later publishing by admin

### For Students

#### Registering for Events
1. Go to `/user/events.php`
2. View "All Upcoming Events" tab
3. Click "Register" button on desired event
4. Confirmation message shows registration successful

#### Viewing Registered Events
1. Go to `/user/events.php`
2. Click "My Registered Events" tab
3. See all events student is registered for
4. View marks if results are published
5. Can cancel registration if needed

#### Viewing Results
- Results appear in "My Registered Events" tab after publishing
- Shows score and remarks if available
- Click "View Details" for full event information

## File Structure

```
├── admin/
│   ├── manage_events.php          ✅ Main admin events management
│   └── events_setup.php           ✅ Setup and verification page
├── user/
│   ├── events.php                 ✅ Student/Teacher event viewing
│   └── event_results.php          ✅ Teacher result entry interface
├── includes/
│   ├── setup_events_schema.php    ✅ Database migration script
│   ├── save_event_result.php      ✅ AJAX result saving handler
│   ├── event_registration_process.php    ✅ Student registration handler
│   └── event_attendance_process.php      ✅ Attendance tracking
└── EVENTS_IMPLEMENTATION_GUIDE.md (this file)
```

## API Endpoints

### Save Event Result
**Endpoint**: `/includes/save_event_result.php`
**Method**: POST
**Parameters**:
- `event_id` (integer) - Event ID
- `student_id` (integer) - Student ID
- `marks` (integer, 0-100) - Student marks
- `remarks` (string) - Optional remarks

**Response**:
```json
{
  "success": true,
  "message": "Result saved successfully"
}
```

## Error Handling

### Database Not Initialized
- System checks for required tables on page load
- Shows warning banner if tables missing
- Direct users to setup page

### Invalid Input
- Marks must be between 0-100
- All fields are validated server-side
- Error messages displayed to user

### Duplicate Registration
- System prevents duplicate registrations
- Shows error if student already registered
- One-click registration enables quick signup

## Key Components

### Validation Classes
- `Validator::validateTitle()` - Validates event titles
- `Validator::validateDescription()` - Validates descriptions
- `Validator::validateDate()` - Validates event dates

### Database Queries
All queries use prepared statements for security.
Connection parameters in `/includes/db.php`.

### Session Management
- Admin-only pages check for admin role
- Teachers see teacher-specific content
- Students see student-specific content
- Parents can view child's registered events

## Response Codes

### Success
- Result saved successfully
- Event created successfully
- Teacher assigned successfully
- Results published successfully

### Errors
- "Event not found" - Event ID invalid
- "Student not registered" - Student not in registrations
- "Marks must be between 0-100" - Invalid marks range
- "Unauthorized" - User not logged in

## Security Features

1. **Session Verification**: All pages check user login
2. **Role-Based Access**: Content shows based on user role
3. **Prepared Statements**: All database queries use parameterized statements
4. **Input Validation**: All user inputs validated server-side
5. **CSRF Protection**: Forms require POST with session validation

## Accessibility

- ARIA labels on form fields
- Icon + Text buttons for clarity
- Color-coded status badges
- Responsive design for mobile devices
- Keyboard navigation support

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Troubleshooting

### Issue: "Event Registration System Not Ready"
**Solution**: Run database setup at `/admin/events_setup.php`

### Issue: Can't assign teachers to event
**Solution**: Check that event exists and database is initialized

### Issue: Results not showing for students
**Solution**: Verify admin published results (check "Results Published" badge)

### Issue: AJAX result saving fails
**Solution**: Check browser console for errors, ensure marks are 0-100

## Performance Considerations

- Event listings paginated for large datasets
- Result queries optimized with indexes
- AJAX method for result entry reduces page reloads
- Table structure normalizes data to prevent redundancy

## Future Enhancements

Potential features for future versions:
- Event capacity limits
- Automatic email notifications
- Attendance tracking via QR codes
- Event categories/types
- Teacher availability scheduling
- Student feedback/reviews for events

## Support

For issues or questions:
1. Check this guide
2. Visit `/admin/events_setup.php` to verify database setup
3. Check browser console for JavaScript errors
4. Review server error logs

## Version History

- **v1.0** (Current)
  - Complete events management system
  - Teacher assignment workflow
  - Student registration system
  - Result management and publishing
  - Tab-based interface
  - AJAX result entry
