# Events Module - Quick Start Guide

## What's New?

Your Sunday School Management System now has a complete **Events Management** module with:
- ✅ Event creation and management
- ✅ Teacher assignment to events  
- ✅ Student registration system
- ✅ Result entry and publishing
- ✅ Results visible to students

## Initial Setup (One-Time)

### Step 1: Run Database Setup
1. Log in as Admin
2. Go to: `/admin/events_setup.php`
3. Click "Initialize Database" button
4. Wait for confirmation that tables are created

That's it! The database is now ready.

## How to Use

### Admin: Create and Manage Events

1. **Create Event**
   - Go to: `/admin/manage_events.php`
   - Fill in title, date, and description
   - Click "Create Event"

2. **Assign Teachers**
   - Click "Details" on an event
   - Go to "Teachers" tab
   - Select a teacher and their role (Coordinator/Co-Coordinator/Participant)
   - Click "Assign Teacher"

3. **Enter Student Results**
   - Click "Details" on event
   - Go to "Results" tab
   - Click "Enter/Edit" for each student
   - Enter marks (0-100) and remarks
   - Save result
   - Click "Publish Results" when done

### Teachers: Create Events & Enter Results

1. **Create Event**
   - Go to: `/user/events.php`
   - Fill in "Create New Event" form
   - Event is immediately available to students

2. **Enter Results** (if assigned as coordinator)
   - Go to: `/user/event_results.php`
   - See events where you're assigned
   - Click to edit student marks/remarks

### Students: Register & View Results

1. **Register for Event**
   - Go to: `/user/events.php`
   - Click "Register" on desired event
   - You're now registered!

2. **View Your Events & Results**
   - Go to: `/user/events.php`
   - Click "My Registered Events" tab
   - See all events you're registered for
   - View marks once admin publishes results

## Key Features

### Event Status
Events have 4 status types:
- **Upcoming** → Event scheduled for future
- **Ongoing** → Event currently happening
- **Completed** → Event finished
- **Cancelled** → Event cancelled

### Results Workflow
1. Admin/Teacher enters marks for students (0-100)
2. Admin publishes results
3. Students see published results immediately

### Teacher Roles
- **Coordinator**: Primary teacher for event
- **Co-Coordinator**: Secondary coordinator
- **Participant**: Assisting teacher

## File Locations

| Feature | File Path |
|---------|-----------|
| Admin Management | `/admin/manage_events.php` |
| Setup Page | `/admin/events_setup.php` |
| Student/Teacher Events | `/user/events.php` |
| Teacher Results Entry | `/user/event_results.php` |
| Database Setup | `/includes/setup_events_schema.php` |

## Database Tables Created

```
event_registrations    → Tracks student registrations
event_teachers         → Maps teachers to events
event_results          → Stores marks and remarks
```

## Common Tasks

### Task: Delete an Event
→ Go to `/admin/manage_events.php` → Click "Delete" button → Confirm

### Task: Change Event Status
→ Click event "Details" → Change status dropdown → Click "Update"

### Task: Remove Teacher Assignment
→ Go to event "Teachers" tab → Click "Remove" next to teacher

### Task: Edit Results After Publishing
→ Go to "Results" tab → Click "Enter/Edit" → Change marks → Save

## Troubleshooting

| Problem | Solution |
|---------|----------|
| "Event Registration System Not Ready" | Run setup at `/admin/events_setup.php` |
| Can't assign teacher | Verify teacher exists in system and database is initialized |
| Results not showing to students | Check that admin clicked "Publish Results" |
| Can't enter marks > 100 | Marks must be between 0-100 only |

## Important Notes

⚠️ **Before Using**: Run the setup page at `/admin/events_setup.php`

✅ **All inputs validated**: Marks range 0-100, dates in future, etc.

✅ **Secure**: Session authentication, role-based access, prepared SQL statements

✅ **Real-time**: AJAX saves results without page reload

## Next Steps

1. ✅ Run database setup (`/admin/events_setup.php`)
2. Create first event as Admin
3. Assign teachers if needed
4. Students can start registering
5. Enter and publish results

---

**Need Help?** Check the detailed guide: `EVENTS_IMPLEMENTATION_GUIDE.md`
