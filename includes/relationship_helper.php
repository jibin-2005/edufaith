<?php
if (!function_exists('rel_has_column')) {
    function rel_has_column($conn, $table, $column) {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $tableEsc = $conn->real_escape_string($table);
        $colEsc = $conn->real_escape_string($column);
        $res = $conn->query("SHOW COLUMNS FROM `$tableEsc` LIKE '$colEsc'");
        $cache[$key] = ($res && $res->num_rows > 0);
        return $cache[$key];
    }
}

if (!function_exists('rel_has_table')) {
    function rel_has_table($conn, $table) {
        static $tableCache = [];
        if (array_key_exists($table, $tableCache)) {
            return $tableCache[$table];
        }
        $tableEsc = $conn->real_escape_string($table);
        $res = $conn->query("SHOW TABLES LIKE '$tableEsc'");
        $tableCache[$table] = ($res && $res->num_rows > 0);
        return $tableCache[$table];
    }
}

if (!function_exists('rel_default_avatar')) {
    function rel_default_avatar() {
        return 'data:image/svg+xml;utf8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">' .
            '<rect width="96" height="96" rx="48" fill="#e7edf7"/>' .
            '<circle cx="48" cy="37" r="17" fill="#9bb0cb"/>' .
            '<path d="M18 82c4-16 15-25 30-25s26 9 30 25" fill="#9bb0cb"/>' .
            '</svg>'
        );
    }
}

if (!function_exists('rel_avatar_src')) {
    function rel_avatar_src($profilePath, $basePath = '..') {
        if (!is_string($profilePath) || trim($profilePath) === '') {
            return rel_default_avatar();
        }
        $profilePath = str_replace('\\', '/', trim($profilePath));
        if (stripos($profilePath, 'http://') === 0 || stripos($profilePath, 'https://') === 0 || stripos($profilePath, 'data:') === 0) {
            return $profilePath;
        }
        if (strpos($profilePath, '/') === 0) {
            return $profilePath;
        }
        return rtrim($basePath, '/') . '/' . ltrim($profilePath, '/');
    }
}

if (!function_exists('rel_build_student_select_fields')) {
    function rel_build_student_select_fields($conn, $studentAlias = 's') {
        $optional = [];
        if (rel_has_column($conn, 'users', 'gender')) $optional[] = "$studentAlias.gender";
        if (rel_has_column($conn, 'users', 'admission_number')) $optional[] = "$studentAlias.admission_number";
        if (rel_has_column($conn, 'users', 'academic_status')) $optional[] = "$studentAlias.academic_status";
        if (rel_has_column($conn, 'users', 'phone')) $optional[] = "$studentAlias.phone";
        if (rel_has_column($conn, 'users', 'address')) $optional[] = "$studentAlias.address";
        if (rel_has_column($conn, 'users', 'dob')) $optional[] = "$studentAlias.dob";
        if (rel_has_column($conn, 'users', 'profile_picture')) $optional[] = "$studentAlias.profile_picture";
        return $optional;
    }
}
