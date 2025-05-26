<?php
$password = 'YourPassword123'; // Replace with your desired password
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Password Hash: " . $hash;
?>