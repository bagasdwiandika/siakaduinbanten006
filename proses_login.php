<?php
session_start();
include "config.php";

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_prepare($db,
        "SELECT username, password, role FROM users WHERE username = ?"
    );

    mysqli_stmt_bind_param($query, "s", $username);
    mysqli_stmt_execute($query);

    $result = mysqli_stmt_get_result($query);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: dashboard_admin.php");
        } else {
            header("Location: dashboard_user.php");
        }

        exit;

    } else {
        echo "Username atau password salah!";
        echo "<br><a href='login.php'>Coba lagi</a>";
    }
}
?>