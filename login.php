
<?php
session_start();
require_once "db.php";

if (!empty($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare(
        "SELECT id, name, email, password, role, status
         FROM users WHERE email = ?"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (
        $user &&
        $user["status"] === "active" &&
        password_verify($password, $user["password"])
    ) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "admin") {
            $stmt = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, details)
                 VALUES (?, 'LOGIN', 'Administrator logged in')"
            );
            $stmt->execute([$user["id"]]);

            header("Location: dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    }

    $error = "Invalid credentials or inactive account.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:450px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="mb-4">College Portal Login</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <label class="form-label">Email</label>
                <input type="email" name="email"
                       class="form-control mb-3" required>

                <label class="form-label">Password</label>
                <input type="password" name="password"
                       class="form-control mb-3" required>

                <button class="btn btn-primary w-100">
                    Login
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>