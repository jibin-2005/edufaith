<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    if (!headers_sent()) {
        header("Location: ../login.html");
    } else {
        echo "<script>window.location.href='../login.html';</script>";
    }
    exit;
}

if (!function_exists('sidebar_is_active')) {
    function sidebar_is_active($currentPage, array $pages) {
        return in_array($currentPage, $pages, true);
    }
}

if (!function_exists('render_sidebar')) {
    function render_sidebar($role, $currentPage, $basePath = '..') {
        $role = strtolower((string)$role);
        $currentPage = strtolower((string)$currentPage);
        $prefix = rtrim($basePath, '/');
        $phpSelf = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
        $currentArea = (strpos($phpSelf, '/admin/') !== false) ? 'admin' : ((strpos($phpSelf, '/user/') !== false) ? 'user' : '');

        $buildHref = function ($href, $targetArea) use ($currentArea, $prefix) {
            if (!is_string($href) || $href === '') {
                return '#';
            }
            if (strpos($href, '://') !== false || strpos($href, '#') === 0 || strpos($href, '/') === 0 || strpos($href, '../') === 0) {
                return $href;
            }
            if ($targetArea === '' || $targetArea === $currentArea) {
                return $href;
            }
            return $prefix . '/' . $targetArea . '/' . $href;
        };

        $menus = [
            'admin' => [
                ['label' => 'Dashboard', 'icon' => 'fa-table-columns', 'href' => 'dashboard_admin.php', 'area' => 'admin', 'active' => ['dashboard_admin.php']],
                ['label' => 'Teachers', 'icon' => 'fa-chalkboard-user', 'href' => 'manage_teachers.php', 'area' => 'admin', 'active' => ['manage_teachers.php', 'edit_user.php', 'assign_class.php']],
                ['label' => 'Students', 'icon' => 'fa-user-graduate', 'href' => 'manage_students.php', 'area' => 'admin', 'active' => ['manage_students.php', 'edit_student.php', 'link_student.php', 'add_user.php']],
                ['label' => 'Parents', 'icon' => 'fa-users', 'href' => 'manage_parents.php', 'area' => 'admin', 'active' => ['manage_parents.php', 'edit_parent.php', 'add_parent.php']],
                ['label' => 'Classes', 'icon' => 'fa-school', 'href' => 'manage_classes.php', 'area' => 'admin', 'active' => ['manage_classes.php']],
                ['label' => 'Events', 'icon' => 'fa-calendar-days', 'href' => 'manage_events.php', 'area' => 'admin', 'active' => ['manage_events.php', 'events_setup.php']],
                ['label' => 'Bulletins', 'icon' => 'fa-bullhorn', 'href' => 'manage_bulletins.php', 'area' => 'admin', 'active' => ['manage_bulletins.php']],
                ['label' => 'Attendance', 'icon' => 'fa-calendar-check', 'href' => 'attendance_admin.php', 'area' => 'admin', 'active' => ['attendance_admin.php']],
                ['label' => 'Messages', 'icon' => 'fa-envelope', 'href' => 'messages_admin.php', 'area' => 'admin', 'active' => ['messages_admin.php']],
                ['label' => 'Profile', 'icon' => 'fa-user-gear', 'href' => 'profile.php', 'area' => 'user', 'active' => ['profile.php']],
            ],
            'teacher' => [
                ['label' => 'Dashboard', 'icon' => 'fa-table-columns', 'href' => 'dashboard_teacher.php', 'area' => 'user', 'active' => ['dashboard_teacher.php']],
                ['label' => 'My Class', 'icon' => 'fa-user-group', 'href' => 'my_class.php', 'area' => 'user', 'active' => ['my_class.php', 'add_student.php', 'edit_student.php']],
                ['label' => 'Attendance', 'icon' => 'fa-calendar-check', 'href' => 'attendance_teacher.php', 'area' => 'user', 'active' => ['attendance_teacher.php', 'attendance_history.php', 'mark_attendance.php', 'view_attendance.php']],
                ['label' => 'Leave Requests', 'icon' => 'fa-envelope-open-text', 'href' => 'manage_leaves.php', 'area' => 'user', 'active' => ['manage_leaves.php']],
                ['label' => 'Lesson Plans', 'icon' => 'fa-book', 'href' => 'manage_assignments.php', 'area' => 'user', 'active' => ['manage_assignments.php']],
                ['label' => 'Results', 'icon' => 'fa-chart-line', 'href' => 'manage_results.php', 'area' => 'user', 'active' => ['manage_results.php', 'results_exam1.php', 'results_exam2.php', 'event_results.php', 'manage_event_results.php']],
                ['label' => 'Event Results', 'icon' => 'fa-award', 'href' => 'manage_event_results.php', 'area' => 'user', 'active' => ['manage_event_results.php', 'event_results.php']],
                ['label' => 'Bulletins', 'icon' => 'fa-bullhorn', 'href' => 'bulletins.php', 'area' => 'user', 'active' => ['bulletins.php']],
                ['label' => 'Events', 'icon' => 'fa-calendar-days', 'href' => 'events.php', 'area' => 'user', 'active' => ['events.php']],
                ['label' => 'Messages', 'icon' => 'fa-envelope', 'href' => 'messages_teacher.php', 'area' => 'user', 'active' => ['messages_teacher.php']],
                ['label' => 'Profile', 'icon' => 'fa-user-gear', 'href' => 'profile.php', 'area' => 'user', 'active' => ['profile.php']],
            ],
            'student' => [
                ['label' => 'Dashboard', 'icon' => 'fa-table-columns', 'href' => 'dashboard_student.php', 'area' => 'user', 'active' => ['dashboard_student.php', 'achievements.php', 'calendar.php', 'payments.php']],
                ['label' => 'Achievements', 'icon' => 'fa-trophy', 'href' => 'achievements.php', 'area' => 'user', 'active' => ['achievements.php']],
                ['label' => 'Calendar', 'icon' => 'fa-calendar', 'href' => 'calendar.php', 'area' => 'user', 'active' => ['calendar.php']],
                ['label' => 'Payments', 'icon' => 'fa-credit-card', 'href' => 'payments.php', 'area' => 'user', 'active' => ['payments.php']],
                ['label' => 'Attendance', 'icon' => 'fa-calendar-check', 'href' => 'attendance_student.php', 'area' => 'user', 'active' => ['attendance_student.php', 'view_attendance.php']],
                ['label' => 'Leave Requests', 'icon' => 'fa-envelope-open-text', 'href' => 'leave_student.php', 'area' => 'user', 'active' => ['leave_student.php']],
                ['label' => 'My Lessons', 'icon' => 'fa-book-bible', 'href' => 'my_lessons.php', 'area' => 'user', 'active' => ['my_lessons.php']],
                ['label' => 'Results', 'icon' => 'fa-chart-line', 'href' => 'view_results.php', 'area' => 'user', 'active' => ['view_results.php']],
                ['label' => 'Bulletins', 'icon' => 'fa-bullhorn', 'href' => 'bulletins.php', 'area' => 'user', 'active' => ['bulletins.php']],
                ['label' => 'Events', 'icon' => 'fa-calendar-days', 'href' => 'events.php', 'area' => 'user', 'active' => ['events.php']],
                ['label' => 'Profile', 'icon' => 'fa-user-gear', 'href' => 'profile.php', 'area' => 'user', 'active' => ['profile.php']],
            ],
            'parent' => [
                ['label' => 'Dashboard', 'icon' => 'fa-table-columns', 'href' => 'dashboard_parent.php', 'area' => 'user', 'active' => ['dashboard_parent.php']],
                ['label' => 'Child Attendance', 'icon' => 'fa-calendar-check', 'href' => 'attendance_parent.php', 'area' => 'user', 'active' => ['attendance_parent.php']],
                ['label' => 'My Children', 'icon' => 'fa-users', 'href' => 'my_children.php', 'area' => 'user', 'active' => ['my_children.php']],
                ['label' => 'Results', 'icon' => 'fa-chart-line', 'href' => 'results_parent.php', 'area' => 'user', 'active' => ['results_parent.php']],
                ['label' => 'Messages', 'icon' => 'fa-envelope', 'href' => 'messages.php', 'area' => 'user', 'active' => ['messages.php']],
                ['label' => 'Bulletins', 'icon' => 'fa-bullhorn', 'href' => 'bulletins.php', 'area' => 'user', 'active' => ['bulletins.php']],
                ['label' => 'Events', 'icon' => 'fa-calendar-days', 'href' => 'events.php', 'area' => 'user', 'active' => ['events.php']],
                ['label' => 'Profile', 'icon' => 'fa-user-gear', 'href' => 'profile.php', 'area' => 'user', 'active' => ['profile.php']],
            ],
        ];

        $menu = $menus[$role] ?? [];
        $logoText = 'St. Thomas Church Kanamala';
        ?>
        <div class="sidebar">
            <div class="logo">
                <i class="fa-solid fa-church"></i>
                <span><?php echo htmlspecialchars($logoText); ?></span>
            </div>
            <div class="sidebar-scroll">
                <ul class="menu">
                    <?php foreach ($menu as $item): ?>
                        <?php $activeClass = sidebar_is_active($currentPage, $item['active']) ? 'active' : ''; ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($buildHref($item['href'], $item['area'] ?? '')); ?>" class="<?php echo $activeClass; ?>">
                                <i class="fa-solid <?php echo htmlspecialchars($item['icon']); ?>"></i>
                                <?php echo htmlspecialchars($item['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="logout">
                    <a href="<?php echo $prefix; ?>/includes/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
                </div>
            </div>
        </div>
        <?php
    }
}
