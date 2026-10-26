
<?php
require_once "db.php";

$message = "";

$count = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
)->fetchColumn();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($count > 0) {
        $message = "An admin already exists. Setup is disabled.";
    } else {
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        if (
            $name === "" ||
            strlen($name) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 150 ||
            strlen($password) < 8
        ) {
            $message = "Enter valid details and a password of at least 8 characters.";
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, role)
                     VALUES (?, ?, ?, 'admin')"
                );

                $stmt->execute([
                    $name,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);

                $message = "Admin created. Delete setup_admin.php now.";
                $count = 1;
            } catch (PDOException $e) {
                $message = "Could not create admin. Check whether the email already exists.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Admin</title>
</head>
<body>
    <h2>Create First Administrator</h2>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>

    <?php if ((int)$count === 0): ?>
    <form method="post">
        <input name="name" placeholder="Admin name"
               maxlength="100" required><br><br>

        <input type="email" name="email"
               placeholder="Admin email" required><br><br>

        <input type="password" name="password"
               placeholder="Password (8+ characters)"
               minlength="8" required><br><br>

        <button type="submit">Create Admin</button>
    </form>
    <?php endif; ?>
</body>
</html>