<?php
session_start();
require 'db.php';

// Check if user is logged in and is a donor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    $_SESSION['message'] = "Access denied. Only donors can post food items.";
    $_SESSION['message_type'] = "danger";
    header("Location: login.html");
    exit();
}

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $user_id = $_SESSION['user_id'];

    // Validate and sanitize inputs
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $expiration_datetime = $_POST['expiration_datetime'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $nutritional_info = trim($_POST['nutritional_info'] ?? '');

    // Validation
    if (empty($title)) {
        $errors[] = "Food title is required.";
    }

    if (empty($description)) {
        $errors[] = "Food description is required.";
    }

    if ($quantity <= 0) {
        $errors[] = "Quantity must be greater than 0.";
    }

    if (empty($unit)) {
        $errors[] = "Unit is required (e.g., servings, kg, pieces).";
    }

    if (empty($expiration_datetime)) {
        $errors[] = "Expiration date and time is required.";
    } elseif (strtotime($expiration_datetime) <= time()) {
        $errors[] = "Expiration date and time must be in the future.";
    }

    if (empty($location)) {
        $errors[] = "Pickup location is required.";
    }

    // Handle image upload
    $image_path = '';
    if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['food_image']['type'], $allowed_types)) {
            $errors[] = "Only JPEG, PNG, and GIF images are allowed.";
        }

        if ($_FILES['food_image']['size'] > $max_size) {
            $errors[] = "Image size must be less than 5MB.";
        }

        if (empty($errors)) {
            $file_extension = pathinfo($_FILES['food_image']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('food_', true) . '.' . $file_extension;
            $image_path = $upload_dir . $unique_filename;

            if (!move_uploaded_file($_FILES['food_image']['tmp_name'], $image_path)) {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    } elseif (!isset($_FILES['food_image']) || $_FILES['food_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Error uploading image. Please try again.";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Insert food post
            $stmt = $conn->prepare("
                INSERT INTO food_posts (
                    user_id, food_name, description, quantity, unit,
                    expiration_datetime, pickup_location, nutritional_info,
                    image_path, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())
            ");

            $result = $stmt->execute([
                $user_id, $title, $description, $quantity, $unit,
                $expiration_datetime, $location, $nutritional_info, $image_path
            ]);

            if ($result) {
                $food_post_id = $conn->lastInsertId();

                // Notify all receivers
                $stmt_receivers = $conn->prepare("SELECT id FROM users WHERE role = 'receiver'");
                $stmt_receivers->execute();
                $receivers = $stmt_receivers->fetchAll(PDO::FETCH_ASSOC);

                $notification_message = "New food posted: " . htmlspecialchars($title) . " (" . htmlspecialchars($quantity) . " " . htmlspecialchars($unit) . ") by " . htmlspecialchars($_SESSION['username']);

                foreach ($receivers as $receiver) {
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (food_post_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt_notify->execute([$food_post_id, $receiver['id'], $notification_message]);
                }

                $conn->commit();

                $_SESSION['message'] = "Food posted successfully! Receivers have been notified.";
                $_SESSION['message_type'] = "success";

                header("Location: donor_dashboard.php");
                exit();
            } else {
                throw new Exception("Failed to create food post.");
            }

        } catch (PDOException $e) {
            // Rollback transaction
            $conn->rollBack();

            // Delete uploaded image if transaction failed
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }

            $errors[] = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $conn->rollBack();
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }
            $errors[] = $e->getMessage();
        }
    }

    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: donor_post_food.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Food - FoodShare</title>
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

        .form-container {
            max-width: 600px;
            margin: 2rem auto;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-top: 10px;
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
                        <a class="nav-link active" href="donor_post_food.php">Post Food</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="donor_dashboard.php">My Posts</a>
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

    <!-- Error Messages -->
    <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php
        unset($_SESSION['errors']);
        endif;
    ?>

    <!-- Main Content -->
    <div class="container">
        <div class="form-container">
            <div class="card p-4">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Post Food Item</h3>
                    <p class="mb-0 mt-2">Share surplus food with the community</p>
                </div>
                <div class="card-body">
                    <form action="donor_post_food.php" method="POST" enctype="multipart/form-data" id="foodPostForm">
                        <div class="mb-3">
                            <label for="title" class="form-label">Food Available *</label>
                            <input type="text" class="form-control" id="title" name="title"
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['title'] ?? ''); ?>"
                                   placeholder="e.g., Fresh Pizza Slices" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      placeholder="Describe the food item, its condition, and any special notes..." required><?php echo htmlspecialchars($_SESSION['form_data']['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input type="number" class="form-control" id="quantity" name="quantity"
                                       value="<?php echo htmlspecialchars($_SESSION['form_data']['quantity'] ?? ''); ?>"
                                       min="1" placeholder="e.g., 5" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">Unit *</label>
                                <select class="form-select" id="unit" name="unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="servings" <?php echo (($_SESSION['form_data']['unit'] ?? '') === 'servings') ? 'selected' : ''; ?>>Servings</option>
                                    <option value="pieces" <?php echo (($_SESSION['form_data']['unit'] ?? '') === 'pieces') ? 'selected' : ''; ?>>Pieces</option>
                                    <option value="kg" <?php echo (($_SESSION['form_data']['unit'] ?? '') === 'kg') ? 'selected' : ''; ?>>Kilograms</option>
                                    <option value="lbs" <?php echo (($_SESSION['form_data']['unit'] ?? '') === 'lbs') ? 'selected' : ''; ?>>Pounds</option>
                                    <option value="liters" <?php echo (($_SESSION['form_data']['unit'] ?? '') === 'liters') ? 'selected' : ''; ?>>Liters</option>
                                    <option value="boxes" <?php echo (($_SESSION['form_data']['unit'] ?? '') === 'boxes') ? 'selected' : ''; ?>>Boxes</option>
                                    <option value="other" <?php echo (($_SESSION['form_data']['unit'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="expiration_datetime" class="form-label">Expiration Date & Time *</label>
                            <input type="datetime-local" class="form-control" id="expiration_datetime" name="expiration_datetime"
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['expiration_datetime'] ?? ''); ?>"
                                   min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            <small class="form-text text-muted">When should this food be picked up by?</small>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Pickup Location *</label>
                            <textarea class="form-control" id="location" name="location" rows="2"
                                      placeholder="e.g., Restaurant back entrance, Building A, Room 101, or specific instructions..." required><?php echo htmlspecialchars($_SESSION['form_data']['location'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="nutritional_info" class="form-label">Nutritional Information (Optional)</label>
                            <textarea class="form-control" id="nutritional_info" name="nutritional_info" rows="2"
                                      placeholder="e.g., Contains nuts, Vegetarian, High protein, etc."><?php echo htmlspecialchars($_SESSION['form_data']['nutritional_info'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="food_image" class="form-label">Food Image (Optional)</label>
                            <input type="file" class="form-control" id="food_image" name="food_image"
                                   accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewImage(this)">
                            <small class="form-text text-muted">Upload a photo of the food item (Max 5MB, JPEG/PNG/GIF)</small>
                            <img id="imagePreview" class="image-preview" style="display: none;" alt="Image Preview">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-upload me-2"></i>Post Food Item
                            </button>
                            <a href="donor_dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Image preview functionality
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        // Form validation
        document.getElementById('foodPostForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const quantity = document.getElementById('quantity').value;
            const unit = document.getElementById('unit').value;
            const expiration = document.getElementById('expiration_datetime').value;
            const location = document.getElementById('location').value.trim();

            if (!title || !description || !quantity || !unit || !expiration || !location) {
                e.preventDefault();
                alert('Please fill in all required fields marked with *');
                return false;
            }

            if (quantity <= 0) {
                e.preventDefault();
                alert('Quantity must be greater than 0');
                return false;
            }

            if (new Date(expiration) <= new Date()) {
                e.preventDefault();
                alert('Expiration date and time must be in the future');
                return false;
            }
        });
    </script>
</body>
</html>
<?php
// Clean up form data from session
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>
