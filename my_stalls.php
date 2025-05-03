<?php
require_once 'includes/header.php';

// Require login
require_login();

// Get user's bookings
$query = "SELECT b.*, s.name, s.description, s.location, s.price,
          CONCAT(u.first_name, ' ', u.last_name) as owner_name
          FROM stall_bookings b
          JOIN food_stalls s ON b.stall_id = s.id
          JOIN users u ON s.created_by = u.id
          WHERE b.user_id = ?
          ORDER BY b.booking_date ASC";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$bookings_result = $stmt->get_result();

// Process cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // Verify that the booking belongs to the user
    $check_query = "SELECT id FROM stall_bookings WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update booking status to cancelled
        $update_query = "UPDATE stall_bookings SET status = 'cancelled' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $booking_id);
        
        if ($update_stmt->execute()) {
            display_message("Booking cancelled successfully!", "success");
            // Refresh page to show updated data
            header("Location: my_stalls.php");
            exit();
        } else {
            display_message("Error cancelling booking. Please try again.", "danger");
        }
    } else {
        display_message("Invalid booking ID.", "danger");
    }
}

// Get stalls created by the user (for organizers and admins)
$created_stalls = array();
if (is_admin() || is_organizer()) {
    $created_query = "SELECT s.*, 
                     (SELECT COUNT(*) FROM stall_bookings WHERE stall_id = s.id) as booking_count
                     FROM food_stalls s
                     WHERE s.created_by = ?
                     ORDER BY s.created_at DESC";
    $created_stmt = $conn->prepare($created_query);
    $created_stmt->bind_param("i", $_SESSION['user_id']);
    $created_stmt->execute();
    $created_result = $created_stmt->get_result();
    
    while ($row = $created_result->fetch_assoc()) {
        $created_stalls[] = $row;
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">My Stalls</h2>
        
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab" aria-controls="bookings" aria-selected="true">My Bookings</button>
            </li>
            <?php if (is_admin() || is_organizer()): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="created-tab" data-bs-toggle="tab" data-bs-target="#created" type="button" role="tab" aria-controls="created" aria-selected="false">Created Stalls</button>
            </li>
            <?php endif; ?>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- Bookings Tab -->
            <div class="tab-pane fade show active" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
                <?php if ($bookings_result->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($booking['name']); ?></h5>
                                        <div>
                                            <?php if ($booking['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php elseif ($booking['status'] == 'approved'): ?>
                                                <span class="badge bg-success">Approved</span>
                                            <?php elseif ($booking['status'] == 'rejected'): ?>
                                                <span class="badge bg-danger">Rejected</span>
                                            <?php elseif ($booking['status'] == 'cancelled'): ?>
                                                <span class="badge bg-secondary">Cancelled</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['payment_status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Payment Pending</span>
                                            <?php elseif ($booking['payment_status'] == 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php elseif ($booking['payment_status'] == 'refunded'): ?>
                                                <span class="badge bg-info">Refunded</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
                                        <p><i class="fas fa-calendar-alt"></i> <strong>Booking Date:</strong> <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?></p>
                                        <p><i class="fas fa-money-bill-wave"></i> <strong>Price:</strong> ₹<?php echo htmlspecialchars($booking['price']); ?></p>
                                        <p><i class="fas fa-user"></i> <strong>Stall Owner:</strong> <?php echo htmlspecialchars($booking['owner_name']); ?></p>
                                        
                                        <?php if (!empty($booking['notes'])): ?>
                                            <div class="alert alert-light mt-3">
                                                <strong>Your Notes:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($booking['notes'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <small class="text-muted">Booked on: <?php echo date('M d, Y', strtotime($booking['created_at'])); ?></small>
                                            </div>
                                            <div>
                                                <a href="stall_details.php?id=<?php echo $booking['stall_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View Stall
                                                </a>
                                                
                                                <?php if ($booking['status'] != 'cancelled' && $booking['status'] != 'rejected'): ?>
                                                    <form method="post" action="my_stalls.php" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <button type="submit" name="cancel" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>You haven't booked any stalls yet.</p>
                        <a href="stalls.php" class="btn btn-primary">Browse Food Stalls</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Created Stalls Tab (for admins and organizers) -->
            <?php if (is_admin() || is_organizer()): ?>
            <div class="tab-pane fade" id="created" role="tabpanel" aria-labelledby="created-tab">
                <?php if (count($created_stalls) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th>Price</th>
                                    <th>Bookings</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($created_stalls as $stall): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stall['name']); ?></td>
                                        <td><?php echo htmlspecialchars($stall['location']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($stall['availability_date'])); ?></td>
                                        <td>₹<?php echo htmlspecialchars($stall['price']); ?></td>
                                        <td>
                                            <?php 
                                                echo $stall['booking_count'];
                                                if ($stall['capacity'] > 0) {
                                                    echo ' / ' . $stall['capacity'];
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($stall['is_approved']): ?>
                                                <span class="badge bg-success">Approved</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($stall['is_active']): ?>
                                                <span class="badge bg-primary">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="stall_details.php?id=<?php echo $stall['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_stall.php?id=<?php echo $stall['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="manage_stall_bookings.php?stall_id=<?php echo $stall['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>You haven't created any stalls yet.</p>
                        <a href="create_stall.php" class="btn btn-primary">Create New Stall</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 