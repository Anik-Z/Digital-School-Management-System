<?php
session_start();
require_once 'db_connection.php';

if (isset($_COOKIE['remember_me'])) {

    $cookie = base64_decode($_COOKIE['remember_me']);
    $parts = explode('|', $cookie);

    if (count($parts) === 2) {
        $user_id = mysqli_real_escape_string($conn, $parts[0]);
        $token = hash('sha256', $parts[1]);

        mysqli_query(
            $conn,
            "DELETE FROM remember_tokens 
             WHERE user_id='$user_id' AND token='$token'"
        );
    }

    setcookie("remember_me", "", time() - 3600, "/");
}

$_SESSION = [];
session_destroy();

header("Location: login.php");
exit();
?>