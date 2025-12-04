<?php
session_start();
require 'db.php';

// Check if user is logged in and is a receiver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receiver') {
    $_SESSION['message'] = "Access denied. Only receivers can claim food items.";
    $_SESSION['message_type'] = "danger";
    header("Location: login.html");
    exit();
}

$receiver_id = $_SESSION['user_id'];

// Handle food claiming
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_food_id'])) {
    $food_id = intval($_POST['claim_food_id']);

    try {
        $conn->beginTransaction();

        // Check if food is available and not expired
        $stmt = $conn->prepare("
            SELECT fp.id, fp.food_name, fp.quantity, fp.unit, fp.expiration_datetime,
                   fp.user_id, u.username as donor_name
            FROM food_posts fp
            JOIN users u ON fp.user_id = u.id
            WHERE fp.id = ? AND fp.status = 'available'
        ");
        $stmt->execute([$food_id]);
        $food = $stmt->fetch();

        if (!$food) {
            throw new Exception("Food item not found or no longer available.");
        }

        // Check if food has expired
        if (!empty($food['expiration_datetime']) && strtotime($food['expiration_datetime']) < time()) {
            throw new Exception("This food item has expired and is no longer available.");
        }

        // Check if receiver has already claimed this post
        $stmt = $conn->prepare("SELECT id FROM pickups WHERE post_id = ? AND receiver_id = ?");
        $stmt->execute([$food_id, $receiver_id]);
        $existing_claim = $stmt->fetch();

        if ($existing_claim) {
            throw new Exception("You have already claimed this food item.");
        }

        // Insert record into pickups table
        $stmt = $conn->prepare("
            INSERT INTO pickups (post_id, receiver_id, scheduled_at, status, created_at)
            VALUES (?, ?, NOW(), 'scheduled', NOW())
        ");
        $stmt->execute([$food_id, $receiver_id]);

        // Update food_posts status to 'claimed'
        $stmt = $conn->prepare("UPDATE food_posts SET status = 'claimed' WHERE id = ?");
        $stmt->execute([$food_id]);

        // Create notification for donor
        $notification_message = "Your food post '" . htmlspecialchars($food['food_name']) . "' has been claimed by " . htmlspecialchars($_SESSION['username']);
        $stmt_notify = $conn->prepare("
            INSERT INTO notifications (food_post_id, receiver_id, message, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt_notify->execute([$food_id, $food['user_id'], $notification_message]);

        $conn->commit();

        $_SESSION['message'] = "Successfully claimed: " . htmlspecialchars($food['food_name']) . " (" . htmlspecialchars($food['quantity']) . " " . htmlspecialchars($food['unit']) . ") from " . htmlspecialchars($food['donor_name']) . "!";
        $_SESSION['message_type'] = "success";

        header("Location: receiver_dashboard.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = "warning";
        header("Location: receiver_claim_food.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Food - FoodShare</title>
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

        .food-card img {
            height: 200px;
            object-fit: cover;
        }

        .badge-available {
            background-color: var(--primary-color);
        }

        .badge-expired {
            background-color: var(--secondary-color);
        }

        .claim-btn {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .claim-btn:hover {
            background-color: #27ae60;
            border-color: #27ae60;
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

        .expired-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px 10px 0 0;
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
                        <a class="nav-link active" href="receiver_claim_food.php">Available Food</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="receiver_dashboard.php">My Claims</a>
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
                <h2 class="fw-bold text-center">Available Food</h2>
                <p class="text-center text-muted">Fresh food items ready for pickup from our generous donors</p>
            </div>
        </div>

        <div class="row g-4">
            <?php
            try {
                // Get available food posts that haven't expired
                $stmt = $conn->prepare("
                    SELECT fp.*, u.username as donor_name, u.id as donor_id
                    FROM food_posts fp
                    JOIN users u ON fp.user_id = u.id
                    WHERE fp.status = 'available'
                    AND (fp.expiration_datetime IS NULL OR fp.expiration_datetime > NOW())
                    ORDER BY fp.created_at DESC
                ");
                $stmt->execute();
                $food_posts = $stmt->fetchAll();

                if (count($food_posts) > 0) {
                    foreach ($food_posts as $food) {
                        $is_expired = !empty($food['expiration_datetime']) && strtotime($food['expiration_datetime']) < time();
                        $card_class = $is_expired ? 'opacity-50' : '';

                        echo '<div class="col-md-6 col-lg-4">';
                        echo '    <div class="card food-card card-hover shadow-sm ' . $card_class . '">';

                        // Image section
                        echo '        <div class="position-relative">';
                        if (!empty($food['image_path']) && file_exists($food['image_path'])) {
                            echo '            <img src="' . htmlspecialchars($food['image_path']) . '" class="food-image" alt="Food Image">';
                        } else {
                            echo '            <div class="no-image">';
                            echo '                <i class="fas fa-utensils fa-3x"></i>';
                            echo '            </div>';
                        }

                        if ($is_expired) {
                            echo '            <div class="expired-overlay">';
                            echo '                <span class="badge badge-expired">EXPIRED</span>';
                            echo '            </div>';
                        }
                        echo '        </div>';

                        echo '        <div class="card-body d-flex flex-column">';
                        echo '            <div class="d-flex justify-content-between mb-2">';
                        echo '                <span class="badge badge-available">Available</span>';
                        echo '                <small class="text-muted"><i class="fas fa-clock me-1"></i>Posted ' . date("M d, H:i", strtotime($food['created_at'])) . '</small>';
                        echo '            </div>';

                        echo '            <h5 class="card-title fw-bold">' . htmlspecialchars($food['food_name']) . '</h5>';

                        if (!empty($food['description'])) {
                            echo '            <p class="card-text text-muted">' . htmlspecialchars($food['description']) . '</p>';
                        }

                        echo '            <div class="row mb-2">';
                        echo '                <div class="col-6">';
                        echo '                    <small><i class="fas fa-balance-scale me-1"></i>Quantity: ' . htmlspecialchars($food['quantity']) . ' ' . htmlspecialchars($food['unit']) . '</small>';
                        echo '                </div>';
                        echo '                <div class="col-6">';
                        echo '                    <small><i class="fas fa-store me-1"></i>' . htmlspecialchars($food['donor_name']) . '</small>';
                        echo '                </div>';
                        echo '            </div>';

                        if (!empty($food['pickup_location'])) {
                            echo '            <p class="card-text"><i class="fas fa-map-marker-alt me-1"></i>' . htmlspecialchars($food['pickup_location']) . '</p>';
                        }

                        if (!empty($food['expiration_datetime'])) {
                            $expiration_class = $is_expired ? 'text-danger' : 'text-warning';
                            echo '            <p class="card-text"><i class="fas fa-clock me-1"></i><span class="' . $expiration_class . '">Expires: ' . date("M d, H:i", strtotime($food['expiration_datetime'])) . '</span></p>';
                        }

                        if (!empty($food['nutritional_info'])) {
                            echo '            <p class="card-text"><i class="fas fa-info-circle me-1"></i>' . htmlspecialchars($food['nutritional_info']) . '</p>';
                        }

                        echo '            <div class="mt-auto">';
                        if ($is_expired) {
                            echo '                <button class="btn btn-secondary w-100" disabled>';
                            echo '                    <i class="fas fa-times me-1"></i>Expired';
                            echo '                </button>';
                        } else {
                            echo '                <form method="POST" action="receiver_claim_food.php" class="d-inline w-100" onsubmit="return confirmClaim(this)">';
                            echo '                    <input type="hidden" name="claim_food_id" value="' . htmlspecialchars($food['id']) . '">';
                            echo '                    <button type="submit" class="btn claim-btn w-100">';
                            echo '                        <i class="fas fa-shopping-cart me-1"></i>Claim Food';
                            echo '                    </button>';
                            echo '                </form>';
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
                    echo '            <h4 class="text-muted">No Food Available</h4>';
                    echo '            <p class="text-muted">Check back later for new food donations from our generous donors.</p>';
                    echo '        </div>';
                    echo '    </div>';
                    echo '</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="col-12">';
                echo '    <div class="alert alert-danger" role="alert">';
                echo '        <i class="fas fa-exclamation-triangle me-2"></i>';
                echo '        Error loading food items: ' . htmlspecialchars($e->getMessage());
                echo '    </div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Confirmation dialog for claiming food
        function confirmClaim(form) {
            const foodName = form.closest('.card').querySelector('.card-title').textContent;
            return confirm('Are you sure you want to claim: ' + foodName + '?\n\nYou will be responsible for picking up this food item.');
        }

        // Auto-refresh page every 5 minutes to show new food posts
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>
