<?php
session_start();
require_once "db.php";

if (
    !empty($_SESSION["user_id"]) &&
    ($_SESSION["role"] ?? "") === "admin"
) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs (user_id, action, details)
             VALUES (?, 'LOGOUT', 'Administrator logged out')"
        );
        $stmt->execute([$_SESSION["user_id"]]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: login.php");
exit;
?>