<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'receiver') {
    header("Location: login.html");
    exit();
}

$receiver_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Claimed Foods - FoodShare</title>
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

        .badge-scheduled {
            background-color: var(--primary-color);
        }

        .badge-completed {
            background-color: #17a2b8;
        }

        .badge-cancelled {
            background-color: var(--secondary-color);
        }

        .food-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .food-card img {
            height: 200px;
            object-fit: cover;
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
                        <a class="nav-link active" href="my_claimed_foods.php">My Claimed Foods</a>
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

    <!-- Flash Message Display -->
    <?php
    if (isset($_SESSION['message'])) {
        $message_type = $_SESSION['message_type'] ?? 'info';
        echo '<div class="alert alert-' . htmlspecialchars($message_type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_SESSION['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
    ?>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold text-center">My Claimed Foods</h2>
                <p class="text-center text-muted">Track your food claims and pickup status</p>
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
                            SELECT p.*, fp.food_name, fp.quantity, fp.food_type, fp.pickup_location,
                                   fp.expiration_datetime, u.username as donor_name
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
                                echo '        <div class="card-body">';
                                echo '            <div class="d-flex justify-content-between mb-2">';
                                echo '                <span class="badge badge-scheduled">Scheduled</span>';
                                echo '                <small class="text-muted"><i class="fas fa-calendar me-1"></i>' . date("M d, H:i", strtotime($claim['scheduled_at'])) . '</small>';
                                echo '            </div>';
                                echo '            <h5 class="card-title fw-bold">' . htmlspecialchars($claim['food_name']) . '</h5>';
                                echo '            <p class="card-text">Quantity: ' . htmlspecialchars($claim['quantity']) . ' servings</p>';
                                echo '            <p class="card-text"><i class="fas fa-store me-1"></i>Donor: ' . htmlspecialchars($claim['donor_name']) . '</p>';
                                if (!empty($claim['pickup_location'])) {
                                    echo '            <p class="card-text"><i class="fas fa-map-marker-alt me-1"></i>' . htmlspecialchars($claim['pickup_location']) . '</p>';
                                }
                                if (!empty($claim['expiration_datetime'])) {
                                    echo '            <p class="card-text"><i class="fas fa-clock me-1"></i>Expires: ' . date("M d, H:i", strtotime($claim['expiration_datetime'])) . '</p>';
                                }
                                echo '        </div>';
                                echo '    </div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="col-12 text-center"><p class="text-muted">No active claims.</p></div>';
                        }
                    } catch (PDOException $e) {
                        echo '<div class="col-12 text-center"><p class="text-danger">Error loading active claims: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
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
                            SELECT p.*, fp.food_name, fp.quantity, fp.food_type, fp.pickup_location,
                                   fp.expiration_datetime, u.username as donor_name
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

                                echo '<div class="col-md-6 col-lg-4">';
                                echo '    <div class="card food-card shadow-sm">';
                                echo '        <div class="card-body">';
                                echo '            <div class="d-flex justify-content-between mb-2">';
                                echo '                <span class="badge ' . $status_class . '">' . $status_text . '</span>';
                                echo '                <small class="text-muted"><i class="fas fa-calendar me-1"></i>' . date("M d, H:i", strtotime($claim['scheduled_at'])) . '</small>';
                                echo '            </div>';
                                echo '            <h5 class="card-title fw-bold">' . htmlspecialchars($claim['food_name']) . '</h5>';
                                echo '            <p class="card-text">Quantity: ' . htmlspecialchars($claim['quantity']) . ' servings</p>';
                                echo '            <p class="card-text"><i class="fas fa-store me-1"></i>Donor: ' . htmlspecialchars($claim['donor_name']) . '</p>';
                                if (!empty($claim['pickup_location'])) {
                                    echo '            <p class="card-text"><i class="fas fa-map-marker-alt me-1"></i>' . htmlspecialchars($claim['pickup_location']) . '</p>';
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
</body>
</html>
