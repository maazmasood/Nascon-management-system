<?php
require_once 'includes/header.php';

// Require admin or organizer role
require_admin_or_organizer();

// Process actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve']) && isset($_POST['stall_id'])) {
        $stall_id = intval($_POST['stall_id']);
        
        // Check authorization
        $auth_query = "SELECT created_by FROM food_stalls WHERE id = ?";
        $auth_stmt = $conn->prepare($auth_query);
        $auth_stmt->bind_param("i", $stall_id);
        $auth_stmt->execute();
        $auth_result = $auth_stmt->get_result();
        $stall_data = $auth_result->fetch_assoc();
        
        if (is_admin() || $stall_data['created_by'] == $_SESSION['user_id']) {
            $update_query = "UPDATE food_stalls SET is_approved = 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $stall_id);
            
            if ($update_stmt->execute()) {
                display_message("Stall approved successfully!", "success");
            } else {
                display_message("Error approving stall.", "danger");
            }
        } else {
            display_message("You don't have permission to perform this action.", "danger");
        }
    } elseif (isset($_POST['disapprove']) && isset($_POST['stall_id'])) {
        $stall_id = intval($_POST['stall_id']);
        
        // Check authorization
        $auth_query = "SELECT created_by FROM food_stalls WHERE id = ?";
        $auth_stmt = $conn->prepare($auth_query);
        $auth_stmt->bind_param("i", $stall_id);
        $auth_stmt->execute();
        $auth_result = $auth_stmt->get_result();
        $stall_data = $auth_result->fetch_assoc();
        
        if (is_admin() || $stall_data['created_by'] == $_SESSION['user_id']) {
            $update_query = "UPDATE food_stalls SET is_approved = 0 WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $stall_id);
            
            if ($update_stmt->execute()) {
                display_message("Stall unapproved successfully!", "success");
            } else {
                display_message("Error unapproving stall.", "danger");
            }
        } else {
            display_message("You don't have permission to perform this action.", "danger");
        }
    } elseif (isset($_POST['delete']) && isset($_POST['stall_id'])) {
        $stall_id = intval($_POST['stall_id']);
        
        // Check authorization
        $auth_query = "SELECT created_by FROM food_stalls WHERE id = ?";
        $auth_stmt = $conn->prepare($auth_query);
        $auth_stmt->bind_param("i", $stall_id);
        $auth_stmt->execute();
        $auth_result = $auth_stmt->get_result();
        $stall_data = $auth_result->fetch_assoc();
        
        if (is_admin() || $stall_data['created_by'] == $_SESSION['user_id']) {
            // First delete bookings
            $delete_book_query = "DELETE FROM stall_bookings WHERE stall_id = ?";
            $delete_book_stmt = $conn->prepare($delete_book_query);
            $delete_book_stmt->bind_param("i", $stall_id);
            $delete_book_stmt->execute();
            
            // Then delete the stall
            $delete_query = "DELETE FROM food_stalls WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $stall_id);
            
            if ($delete_stmt->execute()) {
                display_message("Stall deleted successfully!", "success");
            } else {
                display_message("Error deleting stall.", "danger");
            }
        } else {
            display_message("You don't have permission to perform this action.", "danger");
        }
    }
}

// Get stalls based on user role
if (is_admin()) {
    $query = "SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
             (SELECT COUNT(*) FROM stall_bookings WHERE stall_id = s.id) as booking_count
             FROM food_stalls s 
             JOIN users u ON s.created_by = u.id
             ORDER BY s.created_at DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
             (SELECT COUNT(*) FROM stall_bookings WHERE stall_id = s.id) as booking_count
             FROM food_stalls s 
             JOIN users u ON s.created_by = u.id
             WHERE s.created_by = ?
             ORDER BY s.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Food Stalls</h2>
    <a href="create_stall.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Stall
    </a>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Availability Date</th>
                    <th>Price</th>
                    <th>Bookings</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($stall = $result->fetch_assoc()): ?>
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
                                
                                <?php if ($stall['is_approved']): ?>
                                    <form method="post" action="manage_stalls.php" class="d-inline" onsubmit="return confirm('Are you sure you want to un-approve this stall?');">
                                        <input type="hidden" name="stall_id" value="<?php echo $stall['id']; ?>">
                                        <button type="submit" name="disapprove" class="btn btn-sm btn-warning">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="manage_stalls.php" class="d-inline">
                                        <input type="hidden" name="stall_id" value="<?php echo $stall['id']; ?>">
                                        <button type="submit" name="approve" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="post" action="manage_stalls.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this stall? This action cannot be undone.');">
                                    <input type="hidden" name="stall_id" value="<?php echo $stall['id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        You haven't created any stalls yet. Click the "Create New Stall" button to get started.
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 