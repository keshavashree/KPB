<?php
session_start(); // Starts or resumes a session
require 'db.php'; // Includes your database connection file (db.php)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodShare - Leftover Food Management Platform</title>
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

        .hero-section {
            background: linear-gradient(135deg, #2ecc71, #3498db);
            color: white;
            padding: 5rem 0;
            margin-bottom: 3rem;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .stats-card {
            border-radius: 15px;
        }

        .stats-card i {
            font-size: 2.5rem;
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

        .badge-available {
            background-color: var(--primary-color);
        }

        .badge-claimed {
            background-color: var(--secondary-color);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--secondary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        footer {
            background-color: var(--dark-color);
            color: white;
        }

        .timeline {
            position: relative;
            padding-left: 50px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: var(--light-color);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -45px;
            top: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 3px solid var(--light-color);
        }
        .food-card-img {
            width: 100%;
            height: 200px; /* Fixed height for consistency */
            object-fit: cover; /* Ensures image covers the area without distortion */
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
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#food-listings">Available Food</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#donors">Donors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#receivers">Receivers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>

                    <?php if (isset($_SESSION['user_id'])): // Check if a user is logged in ?>
                        <li class="nav-item dropdown ms-lg-3">
                            <a class="nav-link dropdown-toggle btn btn-primary text-white px-3" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <!-- Display the username if available in session, otherwise default to 'User' -->
                                <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li>
                                    <a class="dropdown-item position-relative" href="notifications.php">
                                        <i class="fas fa-bell me-2"></i>Notifications
                                        <?php
                                        // Dynamic notification count (example, requires DB query)
                                        $notification_count = 0;
                                        if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'receiver') {
                                            // Assuming 'ngo_id' column in 'notifications' table is renamed to 'receiver_id'
                                            $count_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ? AND is_read = FALSE");
                                            $count_stmt->execute([$_SESSION['user_id']]);
                                            $notification_count = $count_stmt->fetchColumn();
                                        }
                                        if ($notification_count > 0) {
                                            echo '<span class="notification-badge">' . $notification_count . '</span>';
                                        }
                                        ?>
                                    </a>
                                </li>
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <?php if ($_SESSION['role'] == 'donor'): // Show "Offer Food" only if the user is a donor ?>
                                    <li><a class="dropdown-item" href="donor_post_food.php"><i class="fas fa-plus-circle me-2"></i>Offer Food</a></li>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] == 'receiver'): // Show "My Claimed Foods" only if the user is a receiver ?>
                                    <li><a class="dropdown-item" href="my_claimed_foods.php"><i class="fas fa-list me-2"></i>My Claimed Foods</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: // If no user is logged in, show login/register buttons ?>
                        <li class="nav-item ms-lg-3">
                            <a class="btn btn-light btn-lg px-4 gap-3" href="login.html">Donor Login</a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-accent btn-lg px-4" href="login.html">Receiver Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Message Display -->
    <?php
    if (isset($_SESSION['message'])) {
        $message_type = $_SESSION['message_type'] ?? 'info'; // Default to info if type not set
        echo '<div class="alert alert-' . htmlspecialchars($message_type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_SESSION['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['message']); // Clear the message after displaying
        unset($_SESSION['message_type']); // Clear the message type
    }
    ?>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Reduce Food Waste, Feed the Community</h1>
            <p class="lead mb-5">Connecting donors with surplus food to receivers serving the hungry</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a type="button" class="btn btn-light btn-lg px-4 gap-3" href="login.html">Donor Login</a>
                    <a type="button" class="btn btn-outline-light btn-lg px-4" href="login.html">Receiver Login</a>
                <?php else: ?>
                    <a type="button" class="btn btn-light btn-lg px-4 gap-3" href="#food-listings">View Food Listings</a>
                    <?php if ($_SESSION['role'] == 'donor'): ?>
                        <a type="button" class="btn btn-outline-light btn-lg px-4" href="donor_post_food.php">Offer Food</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container mb-5" id="stats">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white text-center p-4">
                    <i class="fas fa-utensils mb-3"></i>
                    <h3>1,245+</h3>
                    <p class="mb-0">Meals Shared</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white text-center p-4">
                    <i class="fas fa-store mb-3"></i>
                    <h3>10</h3>
                    <p class="mb-0">Partner Donors</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white text-center p-4">
                    <i class="fas fa-hands-helping mb-3"></i>
                    <h3>10</h3>
                    <p class="mb-0">Partner Receivers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-white text-center p-4">
                    <i class="fas fa-users mb-3"></i>
                    <h3>750+</h3>
                    <p class="mb-0">People Fed</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="container mb-5" id="about">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">How FoodShare Works</h2>
                <p class="lead">A simple three-step process to reduce food waste</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 card-hover p-4 text-center">
                    <div class="mb-3">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                    </div>
                    <h4>1. Donor Post</h4>
                    <p>Donors post surplus food details including quantity, type, and pickup time.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 card-hover p-4 text-center">
                    <div class="mb-3">
                        <i class="fas fa-bell fa-3x text-warning"></i>
                    </div>
                    <h4>2. Instant Alert</h4>
                    <p>Receivers receive instant notifications about available food in their area.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 card-hover p-4 text-center">
                    <div class="mb-3">
                        <i class="fas fa-truck fa-3x text-success"></i>
                    </div>
                    <h4>3. Quick Pickup</h4>
                    <p>Receivers coordinate pickup and distribute food to those in need.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Food Listing Section (Visible only to logged-in Receivers) -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'receiver'): ?>
    <section class="container mb-5 py-5" id="food-listings">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold section-title text-center">Available Food Near You</h2>
                <p class="text-center">Fresh meals ready for immediate pickup from our partner donors</p>
            </div>
        </div>
        <div class="row g-4">
            <?php
            // Prepare and execute the query to get available food posts
            $stmt = $conn->prepare("SELECT fp.*, u.username FROM food_posts fp JOIN users u ON fp.user_id = u.id WHERE fp.status = 'available' ORDER BY fp.created_at DESC");
            $stmt->execute();
            $result = $stmt->fetchAll();

            if (count($result) > 0) {
                // Loop through each food post and display it
                foreach ($result as $food) {
                    echo '<div class="col-md-6 col-lg-4">';
                    echo '    <div class="card food-card card-hover shadow-sm">';
                    echo '        <div class="overflow-hidden">';
                    echo '            <img src="https://images.unsplash.com/photo-1630918212161-c563b0a58d4b" class="food-card-img w-100" alt="Food Image">'; // Placeholder image
                    echo '        </div>';
                    echo '        <div class="card-body">';
                    echo '            <div class="d-flex justify-content-between mb-2">';
                    echo '                <span class="badge bg-success">Available</span>';
                    echo '                <small class="text-muted"><i class="fas fa-clock me-1"></i>Posted ' . date("M d, H:i", strtotime($food['created_at'])) . '</small>';
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
                    echo '                <small><i class="fas fa-map-marker-alt me-1"></i> 2.5km away</small>'; // Placeholder, needs actual location data
                    echo '            </div>';
                    echo '        </div>';
                    echo '    </div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="col-12 text-center"><p>No food currently available.</p></div>';
            }
            ?>
        </div>
    </section>
    <?php elseif (!isset($_SESSION['user_id'])): // If no user is logged in, prompt them to join ?>
    <section class="container mb-5 py-5 text-center">
        <h2 class="fw-bold">Join Us to See Available Food!</h2>
        <p class="lead">Register as a Receiver to view and claim food donations from donors.</p>
        <a href="login.html" class="btn btn-primary btn-lg">Login / Register</a>
    </section>
    <?php endif; ?>

    <!-- Offer Food Section (Visible only to logged-in Donors) -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'donor'): ?>
    <section class="container mb-5 py-5" id="post-food-section">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center mb-5">
                <h2 class="fw-bold section-title">Offer Surplus Food</h2>
                <p class="lead">Share your excess food with Receivers to feed the community.</p>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4 shadow-sm">
                    <form action="donor_post_food.php" method="POST">
                        <div class="mb-3">
                            <label for="food_name" class="form-label">Food Item Name</label>
                            <input type="text" class="form-control" id="food_name" name="food_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity (Servings)</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="food_type" class="form-label">Food Type</label>
                            <select class="form-select" id="food_type" name="food_type" required>
                                <option value="">Select Food Type</option>
                                <option value="Cooked Meal">Cooked Meal</option>
                                <option value="Raw Ingredients">Raw Ingredients</option>
                                <option value="Baked Goods">Baked Goods</option>
                                <option value="Fruits/Vegetables">Fruits/Vegetables</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="pickup_location" class="form-label">Pickup Location Details</label>
                            <textarea class="form-control" id="pickup_location" name="pickup_location" rows="3" placeholder="e.g., Back entrance, ring bell, or specific room number"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location on Map</label>
                            <div id="map" style="height: 300px; width: 100%; border-radius: 8px; margin-bottom: 10px;"></div>
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                            <input type="hidden" id="formatted_address" name="formatted_address">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="getCurrentLocation()">
                                <i class="fas fa-crosshairs me-1"></i>Use Current Location
                            </button>
                            <small class="text-muted d-block mt-1">Click on the map to set pickup location, or use your current location</small>
                        </div>
                        <div class="mb-3">
                            <label for="expiration_datetime" class="form-label">Best Before/Pickup By</label>
                            <input type="datetime-local" class="form-control" id="expiration_datetime" name="expiration_datetime">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dietary Information (Optional)</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="vegetarian" name="dietary_info[]" value="Vegetarian">
                                    <label class="form-check-label" for="vegetarian">Vegetarian</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="vegan" name="dietary_info[]" value="Vegan">
                                    <label class="form-check-label" for="vegan">Vegan</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="gluten_free" name="dietary_info[]" value="Gluten-Free">
                                    <label class="form-check-label" for="gluten_free">Gluten-Free</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="halal" name="dietary_info[]" value="Halal">
                                    <label class="form-check-label" for="halal">Halal</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number (Optional)</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="e.g., +1234567890">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Offer Food</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Partner Donors Section -->
    <section class="container mb-5" id="donors">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold">Our Donor Partners</h2>
                <p class="lead">Helping reduce food waste through responsible surplus management</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card text-center p-4">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945" class="card-img-top rounded-circle w-50 mx-auto mb-3" alt="Donor">
                    <h5>The Grand Plaza</h5>
                    <p class="text-muted">12 donations</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card text-center p-4">
                    <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4" class="card-img-top rounded-circle w-50 mx-auto mb-3" alt="Donor">
                    <h5>Oriental Bay Hotel</h5>
                    <p class="text-muted">8 donations</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card text-center p-4">
                    <img src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa" class="card-img-top rounded-circle w-50 mx-auto mb-3" alt="Donor">
                    <h5>Sunrise Resort</h5>
                    <p class="text-muted">15 donations</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card text-center p-4">
                    <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d" class="card-img-top rounded-circle w-50 mx-auto mb-3" alt="Donor">
                    <h5>Mountain View Hotel</h5>
                    <p class="text-muted">6 donations</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Receiver Partners Section -->
    <section class="container mb-5" id="receivers">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold">Our Receiver Partners</h2>
                <p class="lead">Distributing food to those in need across our community</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card text-center p-4">
                    <img src="https://images.unsplash.com/photo-1603366445787-09714680cbf1" class="card-img-top rounded-circle w-50 mx-auto mb-3" alt="Receiver">
                    <h5>Hope Foundation</h5>
                    <p class="text-muted">25 pickups</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card text-center p-4">
                    <img src="https://images.unsplash.com/photo-1527511624584-388d0e1a3e7e" class="card-img-top rounded-circle w-50 mx-auto mb-3" alt="Receiver">
                    <h5>Food for All</h5>
                    <p class="text-muted">18 pickups</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card text-center p-4">
                    <img src="https://images.unsplash.com/photo-1532629345422-7515f3d16bb6" class="card-img-top rounded-circle w-50 mx-auto mb-3" alt="Receiver">
                    <h5>Compassion Aid</h5>
                    <p class="text-muted">22 pickups</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card text-center p-4">
                    <img src="https://images.unsplash.com/photo-1507676184212-d03ab07a01d8" class="card-img-top rounded-circle w-50 mx-auto mb-3" alt="Receiver">
                    <h5>Unity Kitchen</h5>
                    <p class="text-muted">10 pickups</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="text-center py-4">
        <p>&copy; 2023 FoodShare. All rights reserved.</p>
    </footer>

    <!-- Google Maps API -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places"></script>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let map;
        let marker;

        // Initialize map when page loads
        function initMap() {
            // Default location (you can change this to a more appropriate default)
            const defaultLocation = { lat: -34.397, lng: 150.644 };

            map = new google.maps.Map(document.getElementById("map"), {
                center: defaultLocation,
                zoom: 13,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true
            });

            // Add click listener to map
            map.addListener('click', function(event) {
                placeMarker(event.latLng);
            });

            // Try to get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        map.setCenter(userLocation);
                        placeMarker(userLocation);
                    },
                    function(error) {
                        console.log('Geolocation error:', error);
                    }
                );
            }
        }

        // Place marker on map
        function placeMarker(location) {
            // Remove existing marker
            if (marker) {
                marker.setMap(null);
            }

            // Create new marker
            marker = new google.maps.Marker({
                position: location,
                map: map,
                draggable: true,
                title: 'Pickup Location'
            });

            // Update form fields
            document.getElementById('latitude').value = location.lat();
            document.getElementById('longitude').value = location.lng();

            // Get formatted address
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: location }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    document.getElementById('formatted_address').value = results[0].formatted_address;
                }
            });

            // Add drag listener
            marker.addListener('dragend', function(event) {
                document.getElementById('latitude').value = event.latLng.lat();
                document.getElementById('longitude').value = event.latLng.lng();

                // Update address when marker is dragged
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: event.latLng }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        document.getElementById('formatted_address').value = results[0].formatted_address;
                    }
                });
            });
        }

        // Get current location function
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        map.setCenter(userLocation);
                        map.setZoom(15);
                        placeMarker(userLocation);
                    },
                    function(error) {
                        alert('Error getting your location: ' + error.message);
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }

        // Initialize map when window loads
        window.onload = function() {
            // Check if Google Maps API is loaded
            if (typeof google !== 'undefined') {
                initMap();
            } else {
                // Fallback if API fails to load
                document.getElementById('map').innerHTML = '<div class="alert alert-warning">Unable to load map. Please check your internet connection and try again.</div>';
            }
        };
    </script>
</body>
</html>
<?php
$conn = null; // Close the database connection at the end of the script
?>
