<?php
session_start();
require_once "db.php";

if (
    empty($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    http_response_code(403);
    exit("Access denied.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Invalid security token.");
    }

    $id = filter_var(
        $_POST["user_id"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if (!$id) {
        $message = "Invalid user ID.";
    } elseif ($id == $_SESSION["user_id"]) {
        $message = "You cannot change your own account status here.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT id, name, role, status
                 FROM users WHERE id = ? FOR UPDATE"
            );
            $stmt->execute([$id]);
            $target = $stmt->fetch();

            if (!$target) {
                $pdo->rollBack();
                $message = "User not found.";
            } elseif ($target["role"] === "admin") {
                $pdo->rollBack();
                $message = "Administrator accounts cannot be changed here.";
            } else {
                $newStatus = $target["status"] === "active"
                    ? "inactive"
                    : "active";

                $update = $pdo->prepare(
                    "UPDATE users SET status = ? WHERE id = ?"
                );
                $update->execute([$newStatus, $id]);

                $details = "Changed status of user ID " . $id .
                    " to " . $newStatus;

                $log = $pdo->prepare(
                    "INSERT INTO audit_logs (user_id, action, details)
                     VALUES (?, 'USER_STATUS_CHANGE', ?)"
                );
                $log->execute([$_SESSION["user_id"], $details]);

                $pdo->commit();
                $message = "User status updated successfully.";
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $message = "Could not update user status.";
        }
    }
}

$users = $pdo->query(
    "SELECT id, name, email, role, status
     FROM users ORDER BY id DESC"
)->fetchAll();

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2>Manage User Status</h2>
    <a href="dashboard.php" class="btn btn-secondary mb-3">
        Back to Dashboard
    </a>

    <?php if ($message !== ""): ?>
        <div class="alert alert-info">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= h($user["id"]) ?></td>
                <td><?= h($user["name"]) ?></td>
                <td><?= h($user["email"]) ?></td>
                <td><?= h($user["role"]) ?></td>
                <td><?= h($user["status"]) ?></td>
                <td>
                    <?php if (
                        $user["role"] !== "admin" &&
                        (int)$user["id"] !== (int)$_SESSION["user_id"]
                    ): ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token"
                                   value="<?= h($_SESSION["csrf_token"]) ?>">
                            <input type="hidden" name="user_id"
                                   value="<?= h($user["id"]) ?>">

                            <button class="btn btn-sm
                                <?= $user["status"] === "active"
                                    ? "btn-danger" : "btn-success" ?>">
                                <?= $user["status"] === "active"
                                    ? "Deactivate" : "Activate" ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted">Protected</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>