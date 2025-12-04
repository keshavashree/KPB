<?php
session_start();
require 'db.php';

// Check if user is logged in and is a donor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    $_SESSION['message'] = "Access denied. Only donors can access this page.";
    $_SESSION['message_type'] = "danger";
    header("Location: login.html");
    exit();
}

$donor_id = $_SESSION['user_id'];

// Handle status updates (mark as completed, cancel, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['pickup_id'])) {
    $pickup_id = intval($_POST['pickup_id']);
    $action = $_POST['action'];

    try {
        $conn->beginTransaction();

        // Get pickup details
        $stmt = $conn->prepare("
            SELECT p.*, fp.food_name, u.username as receiver_name
            FROM pickups p
            JOIN food_posts fp ON p.post_id = fp.id
            JOIN users u ON p.receiver_id = u.id
            WHERE p.id = ? AND fp.user_id = ?
        ");
        $stmt->execute([$pickup_id, $donor_id]);
        $pickup = $stmt->fetch();

        if (!$pickup) {
            throw new Exception("Pickup not found or access denied.");
        }

        $new_status = '';
        $notification_message = '';

        switch ($action) {
            case 'mark_completed':
                $new_status = 'completed';
                $notification_message = "Your pickup for '" . htmlspecialchars($pickup['food_name']) . "' has been marked as completed by the donor.";
                break;
            case 'cancel_pickup':
                $new_status = 'cancelled';
                $notification_message = "Your pickup for '" . htmlspecialchars($pickup['food_name']) . "' has been cancelled by the donor.";
                break;
            default:
                throw new Exception("Invalid action.");
        }

        // Update pickup status
        $stmt = $conn->prepare("UPDATE pickups SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $pickup_id]);

        // Create notification for receiver
        $stmt_notify = $conn->prepare("
            INSERT INTO notifications (food_post_id, receiver_id, message, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt_notify->execute([$pickup['post_id'], $pickup['receiver_id'], $notification_message]);

        $conn->commit();

        $_SESSION['message'] = "Pickup status updated successfully!";
        $_SESSION['message_type'] = "success";

        header("Location: donor_dashboard.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header("Location: donor_dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Food Posts - FoodShare</title>
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

        .badge-available {
            background-color: var(--primary-color);
        }

        .badge-claimed {
            background-color: #17a2b8;
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
                        <a class="nav-link" href="donor_post_food.php">Post Food</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="donor_dashboard.php">My Posts</a>
                    </li>

                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link dropdown-toggle btn btn-primary text-white px-3" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
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
                <h2 class="fw-bold text-center">My Food Posts</h2>
                <p class="text-center text-muted">Manage your food donations and track their status</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <?php
            try {
                // Get statistics
                $stats = [
                    'total_posts' => 0,
                    'available_posts' => 0,
                    'claimed_posts' => 0,
                    'completed_posts' => 0
                ];

                $stmt = $conn->prepare("
                    SELECT status, COUNT(*) as count
                    FROM food_posts
                    WHERE user_id = ?
                    GROUP BY status
                ");
                $stmt->execute([$donor_id]);
                $status_counts = $stmt->fetchAll();

                foreach ($status_counts as $status_count) {
                    $stats[$status_count['status'] . '_posts'] = $status_count['count'];
                    $stats['total_posts'] += $status_count['count'];
                }

                // Active pickups count
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count
                    FROM pickups p
                    JOIN food_posts fp ON p.post_id = fp.id
                    WHERE fp.user_id = ? AND p.status = 'scheduled'
                ");
                $stmt->execute([$donor_id]);
                $active_pickups = $stmt->fetch()['count'];
            } catch (PDOException $e) {
                $stats = ['total_posts' => 0, 'available_posts' => 0, 'claimed_posts' => 0, 'completed_posts' => 0];
                $active_pickups = 0;
            }
            ?>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <i class="fas fa-plus-circle"></i>
                        <h3><?php echo $stats['total_posts']; ?></h3>
                        <p class="mb-0">Total Posts</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <i class="fas fa-clock"></i>
                        <h3><?php echo $stats['available_posts']; ?></h3>
                        <p class="mb-0">Available</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart"></i>
                        <h3><?php echo $active_pickups; ?></h3>
                        <p class="mb-0">Active Pickups</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <i class="fas fa-check-circle"></i>
                        <h3><?php echo $stats['completed_posts']; ?></h3>
                        <p class="mb-0">Completed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Food Posts -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><i class="fas fa-list me-2"></i>My Food Posts</h3>
                    <a href="donor_post_food.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Post New Food
                    </a>
                </div>

                <div class="row g-4">
                    <?php
                    try {
                        // Get food posts with pickup information
                        $stmt = $conn->prepare("
                            SELECT fp.*, u.username as claimed_by,
                                   p.id as pickup_id, p.status as pickup_status, p.scheduled_at
                            FROM food_posts fp
                            LEFT JOIN pickups p ON fp.id = p.post_id
                            LEFT JOIN users u ON p.receiver_id = u.id
                            WHERE fp.user_id = ?
                            ORDER BY fp.created_at DESC
                        ");
                        $stmt->execute([$donor_id]);
                        $food_posts = $stmt->fetchAll();

                        if (count($food_posts) > 0) {
                            foreach ($food_posts as $post) {
                                $status_class = 'badge-available';
                                $status_text = 'Available';
                                $status_icon = 'fa-clock';

                                if ($post['pickup_status'] === 'scheduled') {
                                    $status_class = 'badge-claimed';
                                    $status_text = 'Claimed';
                                    $status_icon = 'fa-shopping-cart';
                                } elseif ($post['pickup_status'] === 'completed') {
                                    $status_class = 'badge-completed';
                                    $status_text = 'Completed';
                                    $status_icon = 'fa-check-circle';
                                } elseif ($post['pickup_status'] === 'cancelled') {
                                    $status_class = 'badge-cancelled';
                                    $status_text = 'Cancelled';
                                    $status_icon = 'fa-times-circle';
                                }

                                echo '<div class="col-md-6 col-lg-4">';
                                echo '    <div class="card food-card card-hover shadow-sm">';

                                // Image section
                                echo '        <div class="position-relative">';
                                if (!empty($post['image_path']) && file_exists($post['image_path'])) {
                                    echo '            <img src="' . htmlspecialchars($post['image_path']) . '" class="food-image" alt="Food Image">';
                                } else {
                                    echo '            <div class="no-image">';
                                    echo '                <i class="fas fa-utensils fa-3x"></i>';
                                    echo '            </div>';
                                }
                                echo '        </div>';

                                echo '        <div class="card-body d-flex flex-column">';
                                echo '            <div class="d-flex justify-content-between mb-2">';
                                echo '                <span class="badge ' . $status_class . '"><i class="fas ' . $status_icon . ' me-1"></i>' . $status_text . '</span>';
                                echo '                <small class="text-muted"><i class="fas fa-calendar me-1"></i>' . date("M d, H:i", strtotime($post['created_at'])) . '</small>';
                                echo '            </div>';

                                echo '            <h5 class="card-title fw-bold">' . htmlspecialchars($post['food_name']) . '</h5>';

                                if (!empty($post['description'])) {
                                    echo '            <p class="card-text text-muted">' . htmlspecialchars($post['description']) . '</p>';
                                }

                                echo '            <div class="row mb-2">';
                                echo '                <div class="col-6">';
                                echo '                    <small><i class="fas fa-balance-scale me-1"></i>' . htmlspecialchars($post['quantity']) . ' ' . htmlspecialchars($post['unit']) . '</small>';
                                echo '                </div>';
                                echo '                <div class="col-6">';
                                if (!empty($post['claimed_by'])) {
                                    echo '                    <small><i class="fas fa-user me-1"></i>' . htmlspecialchars($post['claimed_by']) . '</small>';
                                } else {
                                    echo '                    <small class="text-muted">Not claimed</small>';
                                }
                                echo '                </div>';
                                echo '            </div>';

                                if (!empty($post['pickup_location'])) {
                                    echo '            <p class="card-text"><i class="fas fa-map-marker-alt me-1"></i>' . htmlspecialchars($post['pickup_location']) . '</p>';
                                }

                                if (!empty($post['expiration_datetime'])) {
                                    $expiration_class = strtotime($post['expiration_datetime']) < time() ? 'text-danger' : 'text-warning';
                                    echo '            <p class="card-text"><i class="fas fa-clock me-1"></i><span class="' . $expiration_class . '">Expires: ' . date("M d, H:i", strtotime($post['expiration_datetime'])) . '</span></p>';
                                }

                                if (!empty($post['nutritional_info'])) {
                                    echo '            <p class="card-text"><i class="fas fa-info-circle me-1"></i>' . htmlspecialchars($post['nutritional_info']) . '</p>';
                                }

                                echo '            <div class="mt-auto">';
                                if ($post['pickup_status'] === 'scheduled' && !empty($post['pickup_id'])) {
                                    echo '                <div class="btn-group w-100" role="group">';
                                    echo '                    <form method="POST" action="donor_dashboard.php" class="d-inline" onsubmit="return confirmAction(\'mark_completed\', this)">';
                                    echo '                        <input type="hidden" name="action" value="mark_completed">';
                                    echo '                        <input type="hidden" name="pickup_id" value="' . htmlspecialchars($post['pickup_id']) . '">';
                                    echo '                        <button type="submit" class="btn btn-success btn-sm">';
                                    echo '                            <i class="fas fa-check me-1"></i>Complete';
                                    echo '                        </button>';
                                    echo '                    </form>';
                                    echo '                    <form method="POST" action="donor_dashboard.php" class="d-inline" onsubmit="return confirmAction(\'cancel_pickup\', this)">';
                                    echo '                        <input type="hidden" name="action" value="cancel_pickup">';
                                    echo '                        <input type="hidden" name="pickup_id" value="' . htmlspecialchars($post['pickup_id']) . '">';
                                    echo '                        <button type="submit" class="btn btn-danger btn-sm">';
                                    echo '                            <i class="fas fa-times me-1"></i>Cancel';
                                    echo '                        </button>';
                                    echo '                    </form>';
                                    echo '                </div>';
                                } elseif ($post['status'] === 'available') {
                                    echo '                <button class="btn btn-outline-primary w-100 btn-sm" disabled>';
                                    echo '                    <i class="fas fa-clock me-1"></i>Waiting for claims';
                                    echo '                </button>';
                                } else {
                                    echo '                <button class="btn btn-outline-secondary w-100 btn-sm" disabled>';
                                    echo '                    <i class="fas fa-check me-1"></i>' . ucfirst($post['pickup_status'] ?? $post['status']);
                                    echo '                </button>';
                                }
                                echo '            </div>';

                                echo '        </div>';
                                echo '    </div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="col-12">';
                            echo '    <div class="card">';
                            echo '        <div class="card-body text-center py-5">';
                            echo '            <i class="fas fa-utensils fa-4x text-muted mb-3"></i>';
                            echo '            <h4 class="text-muted">No Food Posts Yet</h4>';
                            echo '            <p class="text-muted">Start sharing food with the community by posting your first food item.</p>';
                            echo '            <a href="donor_post_food.php" class="btn btn-primary">';
                            echo '                <i class="fas fa-plus me-2"></i>Post Your First Food Item';
                            echo '            </a>';
                            echo '        </div>';
                            echo '    </div>';
                            echo '</div>';
                        }
                    } catch (PDOException $e) {
                        echo '<div class="col-12">';
                        echo '    <div class="alert alert-danger" role="alert">';
                        echo '        <i class="fas fa-exclamation-triangle me-2"></i>';
                        echo '        Error loading food posts: ' . htmlspecialchars($e->getMessage());
                        echo '    </div>';
                        echo '</div>';
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
                return confirm('Mark "' + foodName + '" as completed?\n\nThis will notify the receiver that the pickup is complete.');
            } else if (action === 'cancel_pickup') {
                return confirm('Cancel the pickup for "' + foodName + '"?\n\nThis will notify the receiver and make the food available again.');
            }

            return false;
        }

        // Auto-refresh page every 5 minutes to show new claims
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>
