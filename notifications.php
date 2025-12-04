<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receiver') {
    header("Location: login.html");
    exit();
}

$receiver_id = $_SESSION['user_id'];

try {
    // Fetch notifications
    $stmt = $conn->prepare("SELECT n.message, n.created_at, n.is_read FROM notifications n WHERE n.receiver_id = ? ORDER BY n.created_at DESC");
    $stmt->execute([$receiver_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark all as read - only mark unread notifications as read to avoid unnecessary updates
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE receiver_id = ? AND is_read = FALSE");
    $stmt->execute([$receiver_id]);

} catch (PDOException $e) {
    $_SESSION['message'] = "Error loading notifications: " . htmlspecialchars($e->getMessage());
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Notifications - FoodShare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
  <h2>Notifications</h2>
  <?php if (count($notifications) === 0): ?>
    <p class="alert alert-info">No notifications.</p>
  <?php else: ?>
    <ul class="list-group">
      <?php foreach ($notifications as $note): ?>
        <li class="list-group-item <?php echo $note['is_read'] ? '' : 'list-group-item-info fw-bold'; ?>">
          <?php echo htmlspecialchars($note['message']); ?>
          <br /><small class="text-muted"><?php echo $note['created_at']; ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <a href="index.php" class="btn btn-primary mt-3">Back to Home</a>
</div>
</body>
</html>
