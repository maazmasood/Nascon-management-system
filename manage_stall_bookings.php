<?php
require_once 'includes/header.php';

// Require admin or organizer role
require_admin_or_organizer();

// Check if stall ID is provided
if (!isset($_GET['stall_id']) || empty($_GET['stall_id'])) {
    header("Location: manage_stalls.php");
    exit();
}

$stall_id = intval($_GET['stall_id']);

// Check authorization and get stall data
$auth_query = "SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name 
               FROM food_stalls s 
               JOIN users u ON s.created_by = u.id 
               WHERE s.id = ?";
$auth_stmt = $conn->prepare($auth_query);
$auth_stmt->bind_param("i", $stall_id);
$auth_stmt->execute();
$auth_result = $auth_stmt->get_result();

if ($auth_result->num_rows == 0) {
    display_message("Stall not found.", "danger");
    header("Location: manage_stalls.php");
    exit();
}

$stall = $auth_result->fetch_assoc();

// Check if user is authorized to manage this stall's bookings
if (!is_admin() && $stall['created_by'] != $_SESSION['user_id']) {
    display_message("You don't have permission to manage bookings for this stall.", "danger");
    header("Location: manage_stalls.php");
    exit();
}

// Process actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve']) && isset($_POST['booking_id'])) {
        $booking_id = intval($_POST['booking_id']);
        
        $update_query = "UPDATE stall_bookings SET status = 'approved' WHERE id = ? AND stall_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $booking_id, $stall_id);
        
        if ($update_stmt->execute()) {
            display_message("Booking approved successfully!", "success");
        } else {
            display_message("Error approving booking.", "danger");
        }
    } elseif (isset($_POST['reject']) && isset($_POST['booking_id'])) {
        $booking_id = intval($_POST['booking_id']);
        
        $update_query = "UPDATE stall_bookings SET status = 'rejected' WHERE id = ? AND stall_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $booking_id, $stall_id);
        
        if ($update_stmt->execute()) {
            display_message("Booking rejected successfully!", "success");
        } else {
            display_message("Error rejecting booking.", "danger");
        }
    } elseif (isset($_POST['mark_paid']) && isset($_POST['booking_id'])) {
        $booking_id = intval($_POST['booking_id']);
        
        $update_query = "UPDATE stall_bookings SET payment_status = 'paid' WHERE id = ? AND stall_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $booking_id, $stall_id);
        
        if ($update_stmt->execute()) {
            display_message("Payment marked as paid successfully!", "success");
        } else {
            display_message("Error updating payment status.", "danger");
        }
    } elseif (isset($_POST['delete']) && isset($_POST['booking_id'])) {
        $booking_id = intval($_POST['booking_id']);
        
        $delete_query = "DELETE FROM stall_bookings WHERE id = ? AND stall_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $booking_id, $stall_id);
        
        if ($delete_stmt->execute()) {
            display_message("Booking deleted successfully!", "success");
        } else {
            display_message("Error deleting booking.", "danger");
        }
    }
}

// Get bookings for this stall
$query = "SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.email, r.name as role_name
          FROM stall_bookings b
          JOIN users u ON b.user_id = u.id
          JOIN roles r ON u.role_id = r.id
          WHERE b.stall_id = ?
          ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $stall_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="manage_stalls.php">Manage Stalls</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($stall['name']); ?> - Bookings</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>Manage Bookings for <?php echo htmlspecialchars($stall['name']); ?></h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="stall_details.php?id=<?php echo $stall_id; ?>" class="btn btn-info">
            <i class="fas fa-eye"></i> View Stall Details
        </a>
        <a href="edit_stall.php?id=<?php echo $stall_id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Stall
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Stall Summary</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p><strong>Location:</strong> <?php echo htmlspecialchars($stall['location']); ?></p>
                <p><strong>Price:</strong> ₹<?php echo htmlspecialchars($stall['price']); ?></p>
            </div>
            <div class="col-md-4">
                <p><strong>Availability Date:</strong> <?php echo date('F d, Y', strtotime($stall['availability_date'])); ?></p>
                <p><strong>Capacity:</strong> <?php echo $stall['capacity'] ? $stall['capacity'] : 'Unlimited'; ?></p>
            </div>
            <div class="col-md-4">
                <p><strong>Status:</strong> 
                    <?php if ($stall['is_approved']): ?>
                        <span class="badge bg-success">Approved</span>
                    <?php else: ?>
                        <span class="badge bg-warning">Pending Approval</span>
                    <?php endif; ?>
                    
                    <?php if ($stall['is_active']): ?>
                        <span class="badge bg-primary">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </p>
                <p><strong>Created By:</strong> <?php echo htmlspecialchars($stall['created_by_name']); ?></p>
            </div>
        </div>
    </div>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Bookings</h5>
                <span class="badge bg-primary"><?php echo $result->num_rows; ?> Bookings</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Booking Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Booked On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($booking = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['role_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                <td>
                                    <?php if ($booking['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($booking['status'] == 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($booking['status'] == 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php elseif ($booking['status'] == 'cancelled'): ?>
                                        <span class="badge bg-secondary">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booking['payment_status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($booking['payment_status'] == 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($booking['payment_status'] == 'refunded'): ?>
                                        <span class="badge bg-info">Refunded</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <form method="post" action="manage_stall_bookings.php?stall_id=<?php echo $stall_id; ?>" class="d-inline">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" name="approve" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="manage_stall_bookings.php?stall_id=<?php echo $stall_id; ?>" class="d-inline">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" name="reject" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['payment_status'] == 'pending' && $booking['status'] != 'rejected'): ?>
                                            <form method="post" action="manage_stall_bookings.php?stall_id=<?php echo $stall_id; ?>" class="d-inline">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" name="mark_paid" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-dollar-sign"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="post" action="manage_stall_bookings.php?stall_id=<?php echo $stall_id; ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php if (!empty($booking['notes'])): ?>
                                <tr class="table-light">
                                    <td colspan="7" class="ps-4">
                                        <small>
                                            <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($booking['notes'])); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        No bookings yet for this stall.
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 