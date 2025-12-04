<?php
session_start();
require 'db.php';

// Check if user is logged in and is a receiver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receiver') {
    $_SESSION['message'] = "Access denied. Only receivers can access this page.";
    $_SESSION['message_type'] = "danger";
    header("Location: login.html");
    exit();
}

$receiver_id = $_SESSION['user_id'];

// Handle status updates (mark as completed, cancel claim, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['pickup_id'])) {
    $pickup_id = intval($_POST['pickup_id']);
    $action = $_POST['action'];

    try {
        $conn->beginTransaction();

        // Get pickup details
        $stmt = $conn->prepare("
            SELECT p.*, fp.food_name, fp.user_id as donor_id, u.username as donor_name
            FROM pickups p
            JOIN food_posts fp ON p.post_id = fp.id
            JOIN users u ON fp.user_id = u.id
            WHERE p.id = ? AND p.receiver_id = ?
        ");
        $stmt->execute([$pickup_id, $receiver_id]);
        $pickup = $stmt->fetch();

        if (!$pickup) {
            throw new Exception("Pickup not found or access denied.");
        }

        $new_status = '';
        $notification_message = '';

        switch ($action) {
            case 'mark_completed':
                $new_status = 'completed';
                $notification_message = "Your food post '" . htmlspecialchars($pickup['food_name']) . "' has been marked as completed by the receiver.";
                break;
            case 'cancel_claim':
                $new_status = 'cancelled';
                $notification_message = "The claim for your food post '" . htmlspecialchars($pickup['food_name']) . "' has been cancelled by the receiver.";
                break;
            default:
                throw new Exception("Invalid action.");
        }

        // Update pickup status
        $stmt = $conn->prepare("UPDATE pickups SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $pickup_id]);

        // If cancelling claim, make food available again
        if ($action === 'cancel_claim') {
            $stmt = $conn->prepare("UPDATE food_posts SET status = 'available' WHERE id = ?");
            $stmt->execute([$pickup['post_id']]);
        }

        // Create notification for donor
        $stmt_notify = $conn->prepare("
            INSERT INTO notifications (food_post_id, receiver_id, message, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt_notify->execute([$pickup['post_id'], $pickup['donor_id'], $notification_message]);

        $conn->commit();

        $_SESSION['message'] = "Claim status updated successfully!";
        $_SESSION['message_type'] = "success";

        header("Location: receiver_dashboard.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header("Location: receiver_dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Food Claims - FoodShare</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2ecc71;
            --secondary-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
        }

        .navbar-brand span {
            color: var(--secondary-color);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .food-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            height: 100%;
        }

        .food-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .no-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            border-radius: 10px 10px 0 0;
        }

        .badge-scheduled {
            background-color: var(--primary-color);
        }

        .badge-completed {
            background-color: #28a745;
        }

        .badge-cancelled {
            background-color: var(--secondary-color);
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), #27ae60);
            color: white;
            border: none;
            border-radius: 15px;
        }

        .stats-card .card-body {
            text-align: center;
        }

        .stats-card .fas {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .pickup-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">Food<span>Share</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="receiver_claim_food.php">Available Food</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="receiver_dashboard.php">My Claims</a>
                    </li>

                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link dropdown-toggle btn btn-primary text-white px-3" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li>
                                <a class="dropdown-item position-relative" href="notifications.php">
                                    <i class="fas fa-bell me-2"></i>Notifications
                                    <?php
                                    $notification_count = 0;
                                    if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'receiver') {
                                        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ? AND is_read = FALSE");
                                        $count_stmt->execute([$receiver_id]);
                                        $notification_count = $count_stmt->fetchColumn();
                                    }
                                    if ($notification_count > 0) {
                                        echo '<span class="notification-badge">' . $notification_count . '</span>';
                                    }
                                    ?>
                                </a>
                            </li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        endif;
    ?>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold text-center">My Food Claims</h2>
                <p class="text-center text-muted">Track your claimed food items and pickup status</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <?php
            try {
                // Get statistics
                $stats = [
                    'total_claims' => 0,
                    'scheduled_claims' => 0,
                    'completed_claims' => 0,
                    'cancelled_claims' => 0
                ];

                $stmt = $conn->prepare("
                    SELECT status, COUNT(*) as count
                    FROM pickups
                    WHERE receiver_id = ?
                    GROUP BY status
                ");
                $stmt->execute([$receiver_id]);
                $status_counts = $stmt->fetchAll();

                foreach ($status_counts as $status_count) {
                    $stats[$status_count['status'] . '_claims'] = $status_count['count'];
                    $stats['total_claims'] += $status_count['count'];
                }
            } catch (PDOException $e) {
                $stats = ['total_claims' => 0, 'scheduled_claims' => 0, 'completed_claims' => 0, 'cancelled_claims' => 0];
            }
            ?>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart"></i>
                        <h3><?php echo $stats['total_claims']; ?></h3>
                        <p class="mb-0">Total Claims</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <i class="fas fa-clock"></i>
                        <h3><?php echo $stats['scheduled_claims']; ?></h3>
                        <p class="mb-0">Scheduled</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <i class="fas fa-check-circle"></i>
                        <h3><?php echo $stats['completed_claims']; ?></h3>
                        <p class="mb-0">Completed</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <i class="fas fa-times-circle"></i>
                        <h3><?php echo $stats['cancelled_claims']; ?></h3>
                        <p class="mb-0">Cancelled</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Claims -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="mb-3"><i class="fas fa-clock me-2 text-primary"></i>Active Claims</h3>
                <div class="row g-4">
                    <?php
                    try {
                        // Get active claims (scheduled status)
                        $stmt = $conn->prepare("
                            SELECT p.*, fp.food_name, fp.quantity, fp.unit, fp.food_type,
                                   fp.pickup_location, fp.expiration_datetime, fp.image_path,
                                   fp.nutritional_info, u.username as donor_name
                            FROM pickups p
                            JOIN food_posts fp ON p.post_id = fp.id
                            JOIN users u ON fp.user_id = u.id
                            WHERE p.receiver_id = ? AND p.status = 'scheduled'
                            ORDER BY p.scheduled_at DESC
                        ");
                        $stmt->execute([$receiver_id]);
                        $active_claims = $stmt->fetchAll();

                        if (count($active_claims) > 0) {
                            foreach ($active_claims as $claim) {
                                echo '<div class="col-md-6 col-lg-4">';
                                echo '    <div class="card food-card card-hover shadow-sm">';

                                // Image section
                                echo '        <div class="position-relative">';
                                if (!empty($claim['image_path']) && file_exists($claim['image_path'])) {
                                    echo '            <img src="' . htmlspecialchars($claim['image_path']) . '" class="food-image" alt="Food Image">';
                                } else {
                                    echo '            <div class="no-image">';
                                    echo '                <i class="fas fa-utensils fa-3x"></i>';
                                    echo '            </div>';
                                }
                                echo '        </div>';

                                echo '        <div class="card-body d-flex flex-column">';
                                echo '            <div class="d-flex justify-content-between mb-2">';
                                echo '                <span class="badge badge-scheduled"><i class="fas fa-clock me-1"></i>Scheduled</span>';
                                echo '                <small class="text-muted"><i class="fas fa-calendar me-1"></i>' . date("M d, H:i", strtotime($claim['scheduled_at'])) . '</small>';
                                echo '            </div>';

                                echo '            <h5 class="card-title fw-bold">' . htmlspecialchars($claim['food_name']) . '</h5>';

                                echo '            <div class="row mb-2">';
                                echo '                <div class="col-6">';
                                echo '                    <small><i class="fas fa-balance-scale me-1"></i>' . htmlspecialchars($claim['quantity']) . ' ' . htmlspecialchars($claim['unit']) . '</small>';
                                echo '                </div>';
                                echo '                <div class="col-6">';
                                echo '                    <small><i class="fas fa-store me-1"></i>' . htmlspecialchars($claim['donor_name']) . '</small>';
                                echo '                </div>';
                                echo '            </div>';

                                if (!empty($claim['pickup_location'])) {
                                    echo '            <div class="pickup-info">';
                                    echo '                <strong><i class="fas fa-map-marker-alt me-1"></i>Pickup Location:</strong><br>';
                                    echo '                ' . htmlspecialchars($claim['pickup_location']);
                                    echo '            </div>';
                                }

                                if (!empty($claim['expiration_datetime'])) {
                                    echo '            <p class="card-text"><i class="fas fa-clock me-1"></i><span class="text-warning">Pickup by: ' . date("M d, H:i", strtotime($claim['expiration_datetime'])) . '</span></p>';
                                }

                                if (!empty($claim['nutritional_info'])) {
                                    echo '            <p class="card-text"><i class="fas fa-info-circle me-1"></i>' . htmlspecialchars($claim['nutritional_info']) . '</p>';
                                }

                                echo '            <div class="mt-auto">';
                                echo '                <div class="btn-group w-100" role="group">';
                                echo '                    <form method="POST" action="receiver_dashboard.php" class="d-inline" onsubmit="return confirmAction(\'mark_completed\', this)">';
                                echo '                        <input type="hidden" name="action" value="mark_completed">';
                                echo '                        <input type="hidden" name="pickup_id" value="' . htmlspecialchars($claim['id']) . '">';
                                echo '                        <button type="submit" class="btn btn-success btn-sm">';
                                echo '                            <i class="fas fa-check me-1"></i>Complete';
                                echo '                        </button>';
                                echo '                    </form>';
                                echo '                    <form method="POST" action="receiver_dashboard.php" class="d-inline" onsubmit="return confirmAction(\'cancel_claim\', this)">';
                                echo '                        <input type="hidden" name="action" value="cancel_claim">';
                                echo '                        <input type="hidden" name="pickup_id" value="' . htmlspecialchars($claim['id']) . '">';
                                echo '                        <button type="submit" class="btn btn-danger btn-sm">';
                                echo '                            <i class="fas fa-times me-1"></i>Cancel';
                                echo '                        </button>';
                                echo '                    </form>';
                                echo '                </div>';
                                echo '            </div>';

                                echo '        </div>';
                                echo '    </div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="col-12">';
                            echo '    <div class="card">';
                            echo '        <div class="card-body text-center py-5">';
                            echo '            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>';
                            echo '            <h4 class="text-muted">No Active Claims</h4>';
                            echo '            <p class="text-muted">Browse available food items to make your first claim.</p>';
                            echo '            <a href="receiver_claim_food.php" class="btn btn-primary">';
                            echo '                <i class="fas fa-search me-2"></i>Browse Available Food';
                            echo '            </a>';
                            echo '        </div>';
                            echo '    </div>';
                            echo '</div>';
                        }
                    } catch (PDOException $e) {
                        echo '<div class="col-12">';
                        echo '    <div class="alert alert-danger" role="alert">';
                        echo '        <i class="fas fa-exclamation-triangle me-2"></i>';
                        echo '        Error loading active claims: ' . htmlspecialchars($e->getMessage());
                        echo '    </div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Past Claims -->
        <div class="row">
            <div class="col-12">
                <h3 class="mb-3"><i class="fas fa-history me-2 text-secondary"></i>Past Claims</h3>
                <div class="row g-4">
                    <?php
                    try {
                        // Get past claims (completed or cancelled status)
                        $stmt = $conn->prepare("
                            SELECT p.*, fp.food_name, fp.quantity, fp.unit, fp.food_type,
                                   fp.pickup_location, fp.expiration_datetime, fp.image_path,
                                   fp.nutritional_info, u.username as donor_name
                            FROM pickups p
                            JOIN food_posts fp ON p.post_id = fp.id
                            JOIN users u ON fp.user_id = u.id
                            WHERE p.receiver_id = ? AND p.status IN ('completed', 'cancelled')
                            ORDER BY p.scheduled_at DESC
                        ");
                        $stmt->execute([$receiver_id]);
                        $past_claims = $stmt->fetchAll();

                        if (count($past_claims) > 0) {
                            foreach ($past_claims as $claim) {
                                $status_class = $claim['status'] === 'completed' ? 'badge-completed' : 'badge-cancelled';
                                $status_text = ucfirst($claim['status']);
                                $status_icon = $claim['status'] === 'completed' ? 'fa-check-circle' : 'fa-times-circle';

                                echo '<div class="col-md-6 col-lg-4">';
                                echo '    <div class="card food-card shadow-sm">';

                                // Image section
                                echo '        <div class="position-relative">';
                                if (!empty($claim['image_path']) && file_exists($claim['image_path'])) {
                                    echo '            <img src="' . htmlspecialchars($claim['image_path']) . '" class="food-image" alt="Food Image">';
                                } else {
                                    echo '            <div class="no-image">';
                                    echo '                <i class="fas fa-utensils fa-3x"></i>';
                                    echo '            </div>';
                                }
                                echo '        </div>';

                                echo '        <div class="card-body">';
                                echo '            <div class="d-flex justify-content-between mb-2">';
                                echo '                <span class="badge ' . $status_class . '"><i class="fas ' . $status_icon . ' me-1"></i>' . $status_text . '</span>';
                                echo '                <small class="text-muted"><i class="fas fa-calendar me-1"></i>' . date("M d, H:i", strtotime($claim['scheduled_at'])) . '</small>';
                                echo '            </div>';

                                echo '            <h5 class="card-title fw-bold">' . htmlspecialchars($claim['food_name']) . '</h5>';

                                echo '            <div class="row mb-2">';
                                echo '                <div class="col-6">';
                                echo '                    <small><i class="fas fa-balance-scale me-1"></i>' . htmlspecialchars($claim['quantity']) . ' ' . htmlspecialchars($claim['unit']) . '</small>';
                                echo '                </div>';
                                echo '                <div class="col-6">';
                                echo '                    <small><i class="fas fa-store me-1"></i>' . htmlspecialchars($claim['donor_name']) . '</small>';
                                echo '                </div>';
                                echo '            </div>';

                                if (!empty($claim['pickup_location'])) {
                                    echo '            <p class="card-text"><i class="fas fa-map-marker-alt me-1"></i>' . htmlspecialchars($claim['pickup_location']) . '</p>';
                                }

                                if (!empty($claim['nutritional_info'])) {
                                    echo '            <p class="card-text"><i class="fas fa-info-circle me-1"></i>' . htmlspecialchars($claim['nutritional_info']) . '</p>';
                                }

                                echo '        </div>';
                                echo '    </div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="col-12 text-center"><p class="text-muted">No past claims.</p></div>';
                        }
                    } catch (PDOException $e) {
                        echo '<div class="col-12 text-center"><p class="text-danger">Error loading past claims: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Confirmation dialog for actions
        function confirmAction(action, form) {
            const foodName = form.closest('.card').querySelector('.card-title').textContent;

            if (action === 'mark_completed') {
                return confirm('Mark "' + foodName + '" as completed?\n\nThis will notify the donor that you have successfully picked up the food.');
            } else if (action === 'cancel_claim') {
                return confirm('Cancel your claim for "' + foodName + '"?\n\nThis will make the food available for other receivers to claim.');
            }

            return false;
        }

        // Auto-refresh page every 5 minutes to show status updates
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>
