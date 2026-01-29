<?php
require 'includes/db.php';
echo "--- Classes ---\n";
$res_c = $conn->query("SELECT * FROM classes");
while($row = $res_c->fetch_assoc()) {
    print_r($row);
}
echo "\n--- Teachers ---\n";
$res_t = $conn->query("SELECT id, username FROM users WHERE role = 'teacher'");
while($row = $res_t->fetch_assoc()) {
    print_r($row);
}
echo "\n--- Students ---\n";
$res_s = $conn->query("SELECT id, username, class_id FROM users WHERE role = 'student'");
while($row = $res_s->fetch_assoc()) {
    print_r($row);
}
?>
