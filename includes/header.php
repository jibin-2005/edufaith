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

// Normalize session keys used across legacy modules
if (!isset($_SESSION['user_name']) && isset($_SESSION['username'])) {
    $_SESSION['user_name'] = $_SESSION['username'];
}
if (!isset($_SESSION['user_role']) && isset($_SESSION['role'])) {
    $_SESSION['user_role'] = $_SESSION['role'];
}
if (!isset($_SESSION['profile_image']) && isset($_SESSION['profile_picture'])) {
    $_SESSION['profile_image'] = $_SESSION['profile_picture'];
}

if (!function_exists('default_avatar_data_uri')) {
    function default_avatar_data_uri() {
        return 'data:image/svg+xml;utf8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">' .
            '<rect width="80" height="80" rx="40" fill="#e8eef7"/>' .
            '<circle cx="40" cy="30" r="14" fill="#9fb3cf"/>' .
            '<path d="M16 68c3-13 12-20 24-20s21 7 24 20" fill="#9fb3cf"/>' .
            '</svg>'
        );
    }
}

if (!function_exists('resolve_profile_image_src')) {
    function resolve_profile_image_src($basePath = '..') {
        $raw = $_SESSION['profile_image'] ?? '';
        if (!is_string($raw) || trim($raw) === '') {
            return default_avatar_data_uri();
        }

        $raw = str_replace('\\', '/', trim($raw));
        if (stripos($raw, 'http://') === 0 || stripos($raw, 'https://') === 0 || stripos($raw, 'data:') === 0) {
            return $raw;
        }
        if (strpos($raw, '/') === 0) {
            return $raw;
        }
        return rtrim($basePath, '/') . '/' . ltrim($raw, '/');
    }
}

if (!function_exists('render_user_header_profile')) {
    function render_user_header_profile($basePath = '..') {
        $displayName = $_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'User');
        $avatarSrc = resolve_profile_image_src($basePath);
        $fallback = default_avatar_data_uri();
        ?>
        <div class="user-profile">
            <span><?php echo htmlspecialchars($displayName); ?></span>
            <div class="user-img">
                <img
                    src="<?php echo htmlspecialchars($avatarSrc); ?>"
                    alt="User"
                    onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8'); ?>';"
                >
            </div>
        </div>
        <?php
    }
}
