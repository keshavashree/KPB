<?php
session_start();
require 'db.php';

// Check if user is admin (you might want to add an admin role to your users table)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$admin_id = $_SESSION['user_id'];

try {
    // Get pending verification requests
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.role, u.created_at, u.verification_status
        FROM users u
        WHERE u.verification_status = 'pending'
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all users for management
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.role, u.verification_status, u.created_at,
               COUNT(CASE WHEN fp.status = 'available' THEN 1 END) as active_posts,
               COUNT(CASE WHEN p.status = 'completed' THEN 1 END) as completed_pickups
        FROM users u
        LEFT JOIN food_posts fp ON u.id = fp.user_id
        LEFT JOIN pickups p ON u.id = p.receiver_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle verification actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $user_id = $_POST['user_id'] ?? 0;

        if ($action === 'approve' && $user_id) {
            $stmt = $conn->prepare("UPDATE users SET verification_status = 'ngo' WHERE id = ?");
            $stmt->execute([$user_id]);

            // Log the approval
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_user_id, details) VALUES (?, 'approve_verification', ?, ?)");
            $stmt->execute([$admin_id, $user_id, 'NGO verification approved']);

            $_SESSION['message'] = "NGO verification approved successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: admin_panel.php");
            exit();
        } elseif ($action === 'reject' && $user_id) {
            $stmt = $conn->prepare("UPDATE users SET verification_status = 'unverified' WHERE id = ?");
            $stmt->execute([$user_id]);

            // Log the rejection
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_user_id, details) VALUES (?, 'reject_verification', ?, ?)");
            $stmt->execute([$admin_id, $user_id, 'NGO verification rejected']);

            $_SESSION['message'] = "NGO verification rejected.";
            $_SESSION['message_type'] = "warning";
            header("Location: admin_panel.php");
            exit();
        }
    }

} catch (PDOException $e) {
    $_SESSION['message'] = "Error loading admin panel: " . htmlspecialchars($e->getMessage());
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - FoodShare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .admin-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }
        .badge-verified {
            background-color: #28a745;
        }
        .badge-ngo {
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-shield-alt me-2"></i>FoodShare Admin
                </a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home me-1"></i>Back to Platform
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="admin-container p-4">
                    <h2 class="mb-4"><i class="fas fa-cog me-2"></i>Admin Dashboard</h2>

                    <!-- Quick Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h4><?php echo count($all_users); ?></h4>
                                <p>Total Users</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h4><?php echo count($pending_requests); ?></h4>
                                <p>Pending Verifications</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h4><?php echo count(array_filter($all_users, fn($u) => $u['verification_status'] === 'ngo')); ?></h4>
                                <p>Verified NGOs</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-utensils fa-2x mb-2"></i>
                                <h4><?php echo array_sum(array_column($all_users, 'active_posts')); ?></h4>
                                <p>Active Food Posts</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Verification Requests -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending NGO Verifications</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pending_requests)): ?>
                                <p class="text-muted">No pending verification requests.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Role</th>
                                                <th>Registration Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_requests as $request): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-user me-2"></i>
                                                        <?php echo htmlspecialchars($request['username']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-<?php echo $request['role'] === 'donor' ? 'store' : 'hands-helping'; ?> me-1"></i>
                                                            <?php echo ucfirst($request['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date("M d, Y", strtotime($request['created_at'])); ?></td>
                                                    <td>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?php echo $request['id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-success btn-sm me-2"
                                                                    onclick="return confirm('Approve NGO verification for <?php echo htmlspecialchars($request['username']); ?>?')">
                                                                <i class="fas fa-check me-1"></i>Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?php echo $request['id']; ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Reject NGO verification for <?php echo htmlspecialchars($request['username']); ?>?')">
                                                                <i class="fas fa-times me-1"></i>Reject
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- All Users Management -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>User Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Active Posts</th>
                                            <th>Completed Pickups</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_users as $user): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-user me-2"></i>
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['role'] === 'donor' ? 'primary' : 'info'; ?>">
                                                        <i class="fas fa-<?php echo $user['role'] === 'donor' ? 'store' : 'hands-helping'; ?> me-1"></i>
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status = $user['verification_status'] ?? 'unverified';
                                                    $badge_class = 'badge-secondary';
                                                    $badge_text = 'Unverified';

                                                    if ($status === 'verified') {
                                                        $badge_class = 'badge-success';
                                                        $badge_text = 'Verified';
                                                    } elseif ($status === 'pending') {
                                                        $badge_class = 'badge-warning';
                                                        $badge_text = 'Pending';
                                                    } elseif ($status === 'ngo') {
                                                        $badge_class = 'badge-primary';
                                                        $badge_text = 'NGO';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                                </td>
                                                <td><?php echo $user['active_posts']; ?></td>
                                                <td><?php echo $user['completed_pickups']; ?></td>
                                                <td><?php echo date("M d, Y", strtotime($user['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
