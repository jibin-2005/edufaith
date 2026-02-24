<?php
/**
 * SETUP GUIDE - Read the instructions in this file
 * 
 * This file contains setup instructions for the Enhanced Events Module
 * You do NOT need to run this file - it's just informational
 * 
 * IMPORTANT: Before using the events module, you MUST run the database migration
 */

echo "
╔══════════════════════════════════════════════════════════════════════════════╗
║                    EVENTS MODULE - SETUP INSTRUCTIONS                        ║
╚══════════════════════════════════════════════════════════════════════════════╝

STEP 1: Run the Database Migration
─────────────────────────────────────
Open your browser and navigate to:
    http://localhost/xampp/htdocs/Sunday%20School%20Management%20System/includes/setup_events_schema.php

OR run via terminal:
    php -r \"require 'includes/db.php'; require 'includes/setup_events_schema.php';\"

You should see output confirming the tables were created:
    ✓ Table 'event_registrations' created/verified successfully.
    ✓ Table 'event_teachers' created/verified successfully.
    ✓ Table 'event_results' created/verified successfully.
    ✓ Added 'status' column to events table.
    ✓ Added 'is_results_published' column to events table.
    ✓ Database schema setup completed successfully!

STEP 2: Verify Access Points
──────────────────────────────
Admin:
    URL: /admin/manage_events.php
    - Create and manage events
    - Assign teachers to events
    - Track student registrations
    - View and publish results

Students:
    URL: /user/events.php
    - View upcoming events
    - Register for events
    - View event details
    - View published results

Teachers:
    URL: /user/event_results.php
    - View coordinated events
    - Enter marks and remarks for students
    - Track result entry progress

STEP 3: Create Your First Event
────────────────────────────────
1. Log in as Admin
2. Navigate to Events menu
3. Click 'Create Event'
4. Fill in event details (title, date, description)
5. Click 'Create Event'

STEP 4: Assign Teachers
────────────────────────
1. From Events list, click 'Details' on your event
2. Go to 'Teachers' tab
3. Click 'Assign New Teacher'
4. Select teacher(s) and their role
5. Click 'Assign'

STEP 5: Students Register
──────────────────────────
1. Students log in with their account
2. Navigate to Events menu
3. Click 'Register' on desired event
4. Confirm registration

STEP 6: Track Attendance
─────────────────────────
1. Admin navigates to event Details
2. Go to 'Registrations' tab
3. Update attendance status for each student
4. Save changes

STEP 7: Enter Results
──────────────────────
1. Teacher navigates to 'Event Results'
2. Selects coordinated event
3. Clicks 'Edit' for each student
4. Enters marks (0-100) and remarks
5. Clicks 'Save Result'

STEP 8: Publish Results
────────────────────────
1. Admin navigates to event Details
2. Goes to 'Results' tab
3. Reviews results
4. Clicks 'Publish Results'
5. Students can now see their scores

SUCCESS! Your Events Module is ready to use.

FEATURES SUMMARY:
─────────────────
✓ Admin can create and manage events
✓ Admin can change event status (upcoming → ongoing → completed)
✓ Admin can assign multiple teachers to events
✓ Students can register for events
✓ Teachers can enter results for registered students
✓ Admin can track attendance for registrations
✓ Results can be published to students
✓ Students can view published results

TROUBLESHOOTING:
────────────────
Q: Database tables not created
A: Run setup_events_schema.php via browser or terminal

Q: Teachers cannot see Event Results page
A: Verify teacher is assigned to the event in admin panel

Q: Students cannot register
A: Check event hasn't passed and status is not 'cancelled'

Q: Results button is disabled
A: Make sure all results are filled in (marks entered for all students)

For more details, see: EVENTS_MODULE_README.md
";
?>
