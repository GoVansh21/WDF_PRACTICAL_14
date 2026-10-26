<?php
session_start();
require_once "db.php";

// Only logged-in admins may access this dashboard.
if (
    empty($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    http_response_code(403);
    exit("Access denied. Administrator login is required.");
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$userCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM users"
)->fetchColumn();

$studentCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM students"
)->fetchColumn();

$eventCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM events"
)->fetchColumn();

$registrationCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM registrations"
)->fetchColumn();

$latest = $pdo->query(
    "SELECT u.name, u.email, u.role, u.created_at
     FROM users u
     ORDER BY u.id DESC
     LIMIT 5"
)->fetchAll();

$logs = $pdo->query(
    "SELECT a.action, a.details, a.created_at, u.name
     FROM audit_logs a
     LEFT JOIN users u ON a.user_id = u.id
     ORDER BY a.id DESC
     LIMIT 10"
)->fetchAll();

$activeUsers = (int)$pdo->query(
    "SELECT COUNT(*) FROM users WHERE status = 'active'"
)->fetchColumn();

$inactiveUsers = (int)$pdo->query(
    "SELECT COUNT(*) FROM users WHERE status = 'inactive'"
)->fetchColumn();

// Role-based menu options
$menu = [];

if (($_SESSION["role"] ?? "") === "admin") {
    $menu = [
        ["Dashboard", "dashboard.php"],
        ["Latest Registrations", "#registrations"],
        ["Audit Logs", "#audit"],
        ["Logout", "logout.php"]
    ];
} else {
    $menu = [
        ["Home", "login.php"],
        ["Logout", "logout.php"]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body { background: #f3f6fa; }
        .sidebar {
            min-height: 100vh;
            background: #17365d;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 5px;
        }
        .sidebar a:hover { background: #285080; }
        .stat-card { border: 0; }
    </style>
</head>
<body>

<div class="container-fluid">
<div class="row">

    <aside class="col-md-2 sidebar p-3">
        <h4 class="text-white mb-4">Admin Panel</h4>

        <?php foreach ($menu as $item): ?>
            <a href="<?= h($item[1]) ?>">
                <?= h($item[0]) ?>
            </a>
        <?php endforeach; ?>
    </aside>

    <main class="col-md-10 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Dashboard</h2>
                <p class="text-muted mb-0">
                    Welcome, <?= h($_SESSION["name"] ?? "Admin") ?>
                </p>
            </div>
            <span class="badge text-bg-primary">Administrator</span>
        </div>

        <div class="row g-3 mb-4">
            <?php
            $cards = [
                ["Total Users", $userCount, "primary"],
                ["Total Students", $studentCount, "success"],
                ["Total Events", $eventCount, "warning"],
                ["Registrations", $registrationCount, "info"]
            ];

            foreach ($cards as $card):
            ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">
                                <?= h($card[0]) ?>
                            </h6>
                            <h2 class="text-<?= h($card[2]) ?>">
                                <?= h($card[1]) ?>
                            </h2>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>User Status</h5>
                        <p class="mb-1">Active: <?= $activeUsers ?></p>
                        <p class="mb-0">Inactive: <?= $inactiveUsers ?></p>
                        <a href="action.php"
                           class="btn btn-sm btn-outline-primary mt-3">
                            Manage User Status
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4" id="registrations">
            <div class="card-body">
                <h5>Latest User Registrations</h5>

                <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($latest as $row): ?>
                        <tr>
                            <td><?= h($row["name"]) ?></td>
                            <td><?= h($row["email"]) ?></td>
                            <td><?= h($row["role"]) ?></td>
                            <td><?= h($row["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$latest): ?>
                        <tr><td colspan="4">No registrations found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm" id="audit">
            <div class="card-body">
                <h5>Recent Audit Logs</h5>

                <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Admin/User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= h($log["name"] ?? "Deleted user") ?></td>
                            <td><?= h($log["action"]) ?></td>
                            <td><?= h($log["details"] ?? "") ?></td>
                            <td><?= h($log["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$logs): ?>
                        <tr><td colspan="4">No audit logs found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </main>
</div>
</div>

</body>
</html>