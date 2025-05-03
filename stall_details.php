<?php
require_once 'includes/header.php';

// Check if stall ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: stalls.php");
    exit();
}

$stall_id = intval($_GET['id']);

// Process booking form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book'])) {
    require_login(); // Redirect to login if not logged in
    
    // Check if user is already booked
    $check_query = "SELECT id, status FROM stall_bookings WHERE stall_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $stall_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $booking = $check_result->fetch_assoc();
        if ($booking['status'] == 'rejected') {
            display_message("Your booking has been rejected. You cannot book again.", "danger");
        } else {
            display_message("You have already booked this stall.", "info");
        }
    } else {
        // Check if the stall is full
        $stall_query = "SELECT capacity FROM food_stalls WHERE id = ?";
        $stall_stmt = $conn->prepare($stall_query);
        $stall_stmt->bind_param("i", $stall_id);
        $stall_stmt->execute();
        $stall_result = $stall_stmt->get_result();
        $stall_data = $stall_result->fetch_assoc();
        
        $count_query = "SELECT COUNT(*) as booking_count FROM stall_bookings WHERE stall_id = ? AND status != 'rejected'";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("i", $stall_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        
        if ($stall_data['capacity'] > 0 && $count_data['booking_count'] >= $stall_data['capacity']) {
            display_message("Sorry, this stall is fully booked. You cannot book at this time.", "danger");
        } else {
            // Book the stall
            $booking_date = $_POST['booking_date'] ?? date('Y-m-d');
            $notes = clean_input($_POST['notes'] ?? '');
            
            $insert_query = "INSERT INTO stall_bookings (stall_id, user_id, booking_date, notes) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iiss", $stall_id, $_SESSION['user_id'], $booking_date, $notes);
            
            if ($insert_stmt->execute()) {
                display_message("You have successfully booked this stall!", "success");
            } else {
                display_message("Error booking the stall. Please try again.", "danger");
            }
        }
    }
}

// Get stall details
$query = "SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
         (SELECT COUNT(*) FROM stall_bookings WHERE stall_id = s.id AND status != 'rejected') as booking_count
         FROM food_stalls s 
         JOIN users u ON s.created_by = u.id
         WHERE s.id = ? AND s.is_approved = 1 AND s.is_active = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $stall_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: stalls.php");
    exit();
}

$stall = $result->fetch_assoc();

// Check if user is booked
$is_booked = false;
$booking_status = '';
if (is_logged_in()) {
    $booking_query = "SELECT status FROM stall_bookings WHERE stall_id = ? AND user_id = ?";
    $booking_stmt = $conn->prepare($booking_query);
    $booking_stmt->bind_param("ii", $stall_id, $_SESSION['user_id']);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    
    if ($booking_result->num_rows > 0) {
        $is_booked = true;
        $booking = $booking_result->fetch_assoc();
        $booking_status = $booking['status'];
    }
}

// Check if stall is full
$is_full = $stall['capacity'] > 0 && $stall['booking_count'] >= $stall['capacity'];
?>

<div class="row">
    <div class="col-md-8">
        <h2><?php echo htmlspecialchars($stall['name']); ?></h2>
        <div class="mb-3">
            <span class="badge bg-primary">₹<?php echo htmlspecialchars($stall['price']); ?></span>
            <?php if (!$stall['is_approved']): ?>
                <span class="badge bg-warning">Pending Approval</span>
            <?php endif; ?>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Description</h5>
                <p class="card-text"><?php echo nl2br(htmlspecialchars($stall['description'])); ?></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Stall Details</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-map-marker-alt"></i> 
                        <strong>Location:</strong> 
                        <?php echo htmlspecialchars($stall['location']); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-calendar-alt"></i> 
                        <strong>Availability Date:</strong> 
                        <?php echo date('F d, Y', strtotime($stall['availability_date'])); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-user"></i> 
                        <strong>Created By:</strong> 
                        <?php echo htmlspecialchars($stall['created_by_name']); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-users"></i> 
                        <strong>Capacity:</strong> 
                        <?php echo $stall['capacity'] ? $stall['capacity'] : 'Unlimited'; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Booking Information</h5>
            </div>
            <div class="card-body">
                <?php if ($is_full && !$is_booked): ?>
                    <div class="alert alert-warning">
                        This stall is fully booked.
                    </div>
                <?php elseif ($is_booked): ?>
                    <div class="alert alert-success">
                        You have booked this stall!
                    </div>
                    <?php if ($booking_status == 'pending'): ?>
                        <div class="alert alert-info">
                            Your booking is pending approval.
                        </div>
                    <?php elseif ($booking_status == 'approved'): ?>
                        <div class="alert alert-success">
                            Your booking has been approved.
                        </div>
                    <?php elseif ($booking_status == 'rejected'): ?>
                        <div class="alert alert-danger">
                            Your booking has been rejected.
                        </div>
                    <?php endif; ?>
                <?php elseif (!is_logged_in()): ?>
                    <div class="alert alert-info">
                        Please <a href="login.php">login</a> to book this stall.
                    </div>
                <?php else: ?>
                    <form method="post" action="stall_details.php?id=<?php echo $stall_id; ?>">
                        <p>Click below to book this stall. You will receive a confirmation once your booking is approved.</p>
                        
                        <div class="mb-3">
                            <label for="booking_date" class="form-label">Booking Date</label>
                            <input type="date" class="form-control" id="booking_date" name="booking_date" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Special Requirements (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" name="book" class="btn btn-primary btn-block">Book Now</button>
                    </form>
                <?php endif; ?>
                
                <div class="mt-3">
                    <p><i class="fas fa-info-circle"></i> Booking Count: <strong><?php echo $stall['booking_count']; ?></strong></p>
                    
                    <?php if ($stall['capacity'] > 0): ?>
                        <div class="progress">
                            <?php 
                            $percentage = ($stall['booking_count'] / $stall['capacity']) * 100;
                            $bar_class = $percentage >= 80 ? "bg-danger" : ($percentage >= 50 ? "bg-warning" : "bg-success");
                            ?>
                            <div class="progress-bar <?php echo $bar_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($percentage); ?>%
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Share This Stall</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                        class="btn btn-outline-primary" target="_blank">
                        <i class="fab fa-facebook-f"></i> Share on Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Check out this food stall: ' . $stall['name']); ?>&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                        class="btn btn-outline-info" target="_blank">
                        <i class="fab fa-twitter"></i> Share on Twitter
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode('Check out this food stall: ' . $stall['name']); ?>&body=<?php echo urlencode('I thought you might be interested in this food stall: ' . $stall['name'] . '\n\nDetails: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                        class="btn btn-outline-secondary">
                        <i class="fas fa-envelope"></i> Share via Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 