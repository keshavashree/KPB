<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT username, role, verification_status, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // User not found, redirect to login
        session_destroy();
        header("Location: login.html");
        exit();
    }

    // Get user's statistics
    $stats = [];
    if ($user['role'] === 'donor') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_posts, SUM(quantity) as total_food_shared FROM food_posts WHERE user_id = ? AND status != 'expired'");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($user['role'] === 'receiver') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_claims FROM pickups WHERE receiver_id = ? AND status = 'completed'");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $_SESSION['message'] = "Error loading profile: " . htmlspecialchars($e->getMessage());
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
    <title>User Profile - FoodShare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .profile-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            margin-top: 50px;
        }
        .profile-container h2 {
            margin-bottom: 30px;
            text-align: center;
            color: #2ecc71;
        }
        .profile-info p {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        .profile-info p strong {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="profile-container">
            <h2><i class="fas fa-user-circle me-2"></i>User Profile</h2>

            <!-- Verification Status -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p class="mb-1"><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
                        <p class="mb-1"><strong>Member Since:</strong> <?php echo date("M d, Y", strtotime($user['created_at'])); ?></p>
                    </div>
                    <div class="text-end">
                        <?php
                        $verification_status = $user['verification_status'] ?? 'unverified';
                        $badge_class = 'badge-secondary';
                        $badge_text = 'Unverified';

                        if ($verification_status === 'verified') {
                            $badge_class = 'badge-success';
                            $badge_text = 'Verified';
                        } elseif ($verification_status === 'pending') {
                            $badge_class = 'badge-warning';
                            $badge_text = 'Pending Verification';
                        } elseif ($verification_status === 'ngo') {
                            $badge_class = 'badge-primary';
                            $badge_text = 'Verified NGO';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?> fs-6">
                            <i class="fas fa-shield-alt me-1"></i><?php echo $badge_text; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- User Statistics -->
            <div class="mb-4">
                <h5><i class="fas fa-chart-bar me-2"></i>Your Impact</h5>
                <div class="row g-3">
                    <?php if ($user['role'] === 'donor'): ?>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-utensils fa-2x text-primary mb-2"></i>
                                <h4 class="mb-0"><?php echo $stats['total_posts'] ?? 0; ?></h4>
                                <small class="text-muted">Food Posts</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-users fa-2x text-success mb-2"></i>
                                <h4 class="mb-0"><?php echo $stats['total_food_shared'] ?? 0; ?></h4>
                                <small class="text-muted">Servings Shared</small>
                            </div>
                        </div>
                    <?php elseif ($user['role'] === 'receiver'): ?>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-shopping-bag fa-2x text-info mb-2"></i>
                                <h4 class="mb-0"><?php echo $stats['total_claims'] ?? 0; ?></h4>
                                <small class="text-muted">Food Claims</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-hands-helping fa-2x text-warning mb-2"></i>
                                <h4 class="mb-0"><?php echo ($stats['total_claims'] ?? 0) * 4; ?></h4>
                                <small class="text-muted">People Helped</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Verification Request (for receivers only) -->
            <?php if ($user['role'] === 'receiver' && (!isset($user['verification_status']) || $user['verification_status'] === 'unverified')): ?>
            <div class="mb-4">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>NGO Verification</h6>
                    <p class="mb-3">Get verified as an NGO to unlock additional features and build trust with donors.</p>
                    <button class="btn btn-primary btn-sm" onclick="requestVerification()">
                        <i class="fas fa-shield-alt me-1"></i>Request NGO Verification
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-grid gap-2 mt-4">
                <a href="index.php" class="btn btn-primary"><i class="fas fa-home me-2"></i>Back to Home</a>
                <a href="logout.php" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
            </div>
        </div>
    </div>

    <script>
        function requestVerification() {
            if (confirm('This will send a verification request to our team. You will need to provide organization details and documentation. Continue?')) {
                // In a real implementation, this would make an AJAX call to request verification
                alert('Verification request submitted! Our team will review your application within 2-3 business days.');
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
