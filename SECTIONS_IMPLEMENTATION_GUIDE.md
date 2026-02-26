# Sunday School Sections - Implementation Guide

## 📚 Overview

The Sunday School Management System now supports **4 sections** for organizing students and events:

1. **Little Flower** - Class 1 to 3 (Primary)
2. **Dominic Savio** - Class 4 to 6 (Middle)
3. **Alphonsa** - Class 7 to 9 (Junior)
4. **St Thomas** - Class 10 to 12 (Senior)

---

## 🚀 Installation Steps

### Step 1: Run Database Setup

Navigate to: `http://your-domain/includes/setup_sections.php`

This will:
- Create `sections` table with 4 sections
- Add `section_id` column to `classes` table
- Add `section_id` and `published` columns to `events` table
- Auto-assign existing classes to sections based on class names
- Create necessary indexes and foreign keys

### Step 2: Verify Installation

Check that these tables/columns were created:
- `sections` table (4 rows)
- `classes.section_id` column
- `events.section_id` column
- `events.published` column

### Step 3: Update File References

Replace old event files with new section-aware versions:

**Admin Files:**
- Replace `admin/manage_events.php` with `admin/manage_events_new.php`

**User Files:**
- Replace `user/events.php` with `user/events_new.php`
- Replace `user/manage_event_results.php` with `user/manage_event_results_new.php`
- Replace `user/event_results.php` with `user/event_results_new.php`

**Includes:**
- Replace `includes/event_registration_process.php` with `includes/event_registration_process_new.php`

---

## 🎯 Features Implemented

### 1. Section Management

**Database Structure:**
```sql
sections table:
- id (PK)
- section_name (UNIQUE)
- class_range
- description
- created_at

classes table:
- section_id (FK to sections)

events table:
- section_id (FK to sections)
- published (BOOLEAN)
```

### 2. Event Creation (Admin Only)

**Features:**
- ✅ Admin must select section when creating event
- ✅ Event title must be unique within section
- ✅ Cannot create event without section
- ✅ Section badge displayed on event cards
- ✅ Filter events by section

**Validation:**
```php
// Section required
if ($section_id <= 0) {
    $errors[] = "Please select a section";
}

// Unique title per section
if (!Validator::isEventNameUniqueInSection($conn, $title, $section_id)) {
    $errors[] = "Event title already exists in this section";
}
```

### 3. Event Registration (Students)

**Features:**
- ✅ Students see only their section events
- ✅ Cross-section registration prevented
- ✅ Section validation on registration
- ✅ Clear error messages for section mismatch

**Validation:**
```php
// Get student section
$student_section = Validator::getStudentSection($conn, $student_id);

// Verify section match
if ($student_section != $event['section_id']) {
    // Reject registration
}
```

### 4. Event Evaluation (Teachers)

**Features:**
- ✅ Teachers see only their section events
- ✅ Can evaluate only students from their section
- ✅ Marks entry (0-100) with remarks
- ✅ Progress tracking (evaluated/total)
- ✅ Section banner showing teacher's section

**Validation:**
```php
// Get teacher section
$teacher_section = Validator::getTeacherSection($conn, $teacher_id);

// Verify event belongs to teacher's section
if ($event_section != $teacher_section) {
    $error = "You can only evaluate students from your section";
}
```

### 5. Results Publishing (Admin)

**Features:**
- ✅ Admin can publish results section-wise
- ✅ Cannot publish if not all students evaluated
- ✅ Published status toggle
- ✅ Students see results only after publishing

**Validation:**
```php
// Check all students have marks
if (!Validator::allStudentsHaveMarks($conn, $event_id)) {
    $error = "Cannot publish - not all students evaluated";
}
```

### 6. Results Viewing (Students)

**Features:**
- ✅ Students see only published results
- ✅ Section-wise rankings
- ✅ Rank badges (Gold/Silver/Bronze for top 3)
- ✅ Current student highlighted
- ✅ Rankings show only section students

**Ranking Query:**
```sql
SELECT u.id, u.username, er.marks,
       RANK() OVER (ORDER BY er.marks DESC) as rank_position
FROM event_results er
JOIN users u ON er.student_id = u.id
JOIN classes c ON u.class_id = c.id
WHERE er.event_id = ? AND c.section_id = ?
ORDER BY er.marks DESC
```

---

## 🔒 Security & Validation

### Role-Based Access Control

**Admin:**
- ✅ Create events (must select section)
- ✅ Publish results (with validation)
- ✅ Delete events (if no registrations)
- ✅ View all sections

**Teacher:**
- ✅ View only their section events
- ✅ Evaluate only their section students
- ✅ Cannot evaluate other sections
- ✅ Section displayed in banner

**Student:**
- ✅ View only their section events
- ✅ Register only for their section events
- ✅ View only published results
- ✅ See only section rankings

### Validation Rules

1. **Event Creation:**
   - Section is required
   - Title unique per section
   - Future date only

2. **Event Registration:**
   - Student section must match event section
   - No duplicate registrations
   - Event date not passed

3. **Marks Entry:**
   - Marks 0-100 range
   - Teacher section must match event section
   - Student must be registered

4. **Results Publishing:**
   - All registered students must have marks
   - Only admin can publish
   - Cannot unpublish (one-way operation)

---

## 📊 Database Schema

### Sections Table
```sql
CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL UNIQUE,
    class_range VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Classes Table (Modified)
```sql
ALTER TABLE classes 
ADD COLUMN section_id INT DEFAULT NULL,
ADD CONSTRAINT fk_classes_section 
    FOREIGN KEY (section_id) REFERENCES sections(id) 
    ON DELETE SET NULL;
```

### Events Table (Modified)
```sql
ALTER TABLE events 
ADD COLUMN section_id INT DEFAULT NULL,
ADD COLUMN published BOOLEAN DEFAULT FALSE,
ADD CONSTRAINT fk_events_section 
    FOREIGN KEY (section_id) REFERENCES sections(id) 
    ON DELETE SET NULL;
```

### Event Registrations Table
```sql
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, student_id)
);
```

### Event Results Table
```sql
CREATE TABLE IF NOT EXISTS event_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    marks INT DEFAULT NULL,
    remarks TEXT,
    evaluated_by INT,
    evaluated_at TIMESTAMP NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_result (event_id, student_id)
);
```

---

## 🎨 UI Features

### Section Badges
Each section has a unique color gradient:
- **Little Flower:** Yellow/Orange gradient
- **Dominic Savio:** Blue gradient
- **Alphonsa:** Purple gradient
- **St Thomas:** Pink/Magenta gradient

### Section Banners
- Displayed on teacher and student pages
- Shows current user's section
- Includes class range information

### Filters
- Section dropdown filter on admin event list
- Auto-filtering for teachers and students

### Rankings Display
- Top 3 get special badges (Trophy/Medal/Award)
- Current student highlighted
- Section-wise only (no cross-section rankings)

---

## 🔧 Helper Functions Added

### validation_helper.php

```php
// Get student's section
Validator::getStudentSection($conn, $studentId)

// Get teacher's section
Validator::getTeacherSection($conn, $teacherId)

// Check event name uniqueness in section
Validator::isEventNameUniqueInSection($conn, $eventName, $sectionId, $excludeEventId)

// Check if all students have marks
Validator::allStudentsHaveMarks($conn, $eventId)

// Check if student registered for event
Validator::isStudentRegisteredForEvent($conn, $studentId, $eventId)
```

---

## 📝 Usage Examples

### Admin: Create Event
1. Go to Manage Events
2. Fill event form
3. **Select section** (required)
4. Submit
5. Event visible only to selected section

### Student: Register for Event
1. Go to Events page
2. See only your section events
3. Click "Register Now"
4. System validates section match
5. Registration confirmed

### Teacher: Evaluate Students
1. Go to Manage Event Results
2. See only your section events
3. Select event
4. Enter marks for each student
5. Save

### Admin: Publish Results
1. Go to Manage Events
2. Ensure all students evaluated
3. Click "Publish Results"
4. Students can now view results

### Student: View Results
1. Go to Event Results
2. See only published results
3. Click "View Rankings"
4. See section-wise rankings

---

## ⚠️ Important Notes

1. **Section Assignment:**
   - All classes must be assigned to a section
   - Students inherit section from their class
   - Teachers inherit section from their assigned class

2. **Cross-Section Prevention:**
   - Students cannot register for other section events
   - Teachers cannot evaluate other section students
   - Rankings are strictly section-wise

3. **Publishing:**
   - Results publishing is one-way (cannot unpublish)
   - All students must be evaluated before publishing
   - Only admin can publish results

4. **Data Migration:**
   - Existing classes auto-assigned to sections
   - Existing events need manual section assignment
   - Run setup script to migrate data

---

## 🐛 Troubleshooting

**Issue:** Students can't see any events
- **Solution:** Check if student's class has section_id assigned

**Issue:** Teacher can't evaluate students
- **Solution:** Verify teacher's class has section_id matching event

**Issue:** Cannot publish results
- **Solution:** Ensure all registered students have marks entered

**Issue:** Rankings not showing
- **Solution:** Check if event is published and has results

**Issue:** Section filter not working
- **Solution:** Verify sections table has 4 rows

---

## 📞 Support

For issues or questions:
1. Check database schema is correct
2. Verify all files are updated
3. Check browser console for JavaScript errors
4. Review validation error messages

---

**Version:** 1.0  
**Last Updated:** 2025  
**Status:** Production Ready ✅
