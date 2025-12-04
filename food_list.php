<?php
session_start();
require 'db.php'; // Include your database connection file

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'receiver') { // Added check for user_id
    header("Location: login.html"); // Corrected redirect to login.html
    exit();
}

try {
    $stmt = $conn->prepare("SELECT food_posts.*, users.username FROM food_posts JOIN users ON food_posts.user_id = users.id WHERE food_posts.status = 'available' ORDER BY food_posts.created_at DESC");
    $stmt->execute();
    $foods = $stmt->fetchAll();

    if (count($foods) > 0) {
        foreach ($foods as $food) {
            echo '<div class="col-md-6 col-lg-4">';
            echo '    <div class="card food-card card-hover shadow-sm">';
            echo '        <div class="overflow-hidden">';
            echo '            <img src="https://images.unsplash.com/photo-1630918212161-c563b0a58d4b" class="food-card-img w-100" alt="Food Image">'; // Placeholder image
            echo '        </div>';
            echo '        <div class="card-body">';
            echo '            <div class="d-flex justify-content-between mb-2">';
            echo '                <span class="badge bg-success">Available</span>';
            if (!empty($food['expiration_datetime'])) {
                echo '                <small class="text-muted"><i class="fas fa-clock me-1"></i>Expires ' . date("M d, H:i", strtotime($food['expiration_datetime'])) . '</small>';
            } else {
                echo '                <small class="text-muted"><i class="fas fa-clock me-1"></i>Posted ' . date("M d, H:i", strtotime($food['created_at'])) . '</small>';
            }
            echo '            </div>';
            echo '            <h5 class="card-title fw-bold">' . htmlspecialchars($food['food_name']) . '</h5>';
            echo '            <p class="card-text food-card-text">Quantity: ' . htmlspecialchars($food['quantity']) . ' servings.</p>';
            echo '            <div class="d-flex justify-content-between align-items-center mt-auto">';
            echo '                <div>';
            echo '                    <i class="fas fa-store me-1 text-primary"></i>';
            echo '                    <span>' . htmlspecialchars($food['username']) . '</span>';
            echo '                </div>';
            echo '                <form method="POST" action="claim_food.php">';
            echo '                    <input type="hidden" name="food_id" value="' . htmlspecialchars($food['id']) . '">';
            echo '                    <button type="submit" class="btn btn-sm btn-primary">Claim Food</button>';
            echo '                </form>';
            echo '            </div>';
            echo '        </div>';
            echo '        <div class="card-footer bg-transparent border-top-0">';
            echo '            <div class="d-flex justify-content-between">';
            echo '                <small><i class="fas fa-users me-1"></i> Serves ' . htmlspecialchars($food['quantity']) . '</small>';
            if (!empty($food['pickup_location'])) {
                echo '                <small><i class="fas fa-map-marker-alt me-1"></i> ' . htmlspecialchars($food['pickup_location']) . '</small>';
            } else {
                echo '                <small><i class="fas fa-map-marker-alt me-1"></i> Location not specified</small>';
            }
            echo '            </div>';
            echo '        </div>';
            echo '    </div>';
            echo '</div>';
        }
    } else {
        echo '<div class="col-12 text-center"><p>No food currently available.</p></div>';
    }
} catch (PDOException $e) {
    echo '<div class="col-12 text-center"><p class="text-danger">Error loading food listings: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
}
?>
