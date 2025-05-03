<?php
require_once 'includes/header.php';

// Require admin or organizer role
require_admin_or_organizer();

// Process actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['publish']) && isset($_POST['event_id'])) {
        $event_id = intval($_POST['event_id']);
        
        // Check authorization
        $auth_query = "SELECT organizer_id FROM events WHERE id = ?";
        $auth_stmt = $conn->prepare($auth_query);
        $auth_stmt->bind_param("i", $event_id);
        $auth_stmt->execute();
        $auth_result = $auth_stmt->get_result();
        $event_data = $auth_result->fetch_assoc();
        
        if (is_admin() || $event_data['organizer_id'] == $_SESSION['user_id']) {
            $update_query = "UPDATE events SET is_published = 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $event_id);
            
            if ($update_stmt->execute()) {
                display_message("Event published successfully!", "success");
            } else {
                display_message("Error publishing event.", "danger");
            }
        } else {
            display_message("You don't have permission to perform this action.", "danger");
        }
    } elseif (isset($_POST['unpublish']) && isset($_POST['event_id'])) {
        $event_id = intval($_POST['event_id']);
        
        // Check authorization
        $auth_query = "SELECT organizer_id FROM events WHERE id = ?";
        $auth_stmt = $conn->prepare($auth_query);
        $auth_stmt->bind_param("i", $event_id);
        $auth_stmt->execute();
        $auth_result = $auth_stmt->get_result();
        $event_data = $auth_result->fetch_assoc();
        
        if (is_admin() || $event_data['organizer_id'] == $_SESSION['user_id']) {
            $update_query = "UPDATE events SET is_published = 0 WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $event_id);
            
            if ($update_stmt->execute()) {
                display_message("Event unpublished successfully!", "success");
            } else {
                display_message("Error unpublishing event.", "danger");
            }
        } else {
            display_message("You don't have permission to perform this action.", "danger");
        }
    } elseif (isset($_POST['delete']) && isset($_POST['event_id'])) {
        $event_id = intval($_POST['event_id']);
        
        // Check authorization
        $auth_query = "SELECT organizer_id FROM events WHERE id = ?";
        $auth_stmt = $conn->prepare($auth_query);
        $auth_stmt->bind_param("i", $event_id);
        $auth_stmt->execute();
        $auth_result = $auth_stmt->get_result();
        $event_data = $auth_result->fetch_assoc();
        
        if (is_admin() || $event_data['organizer_id'] == $_SESSION['user_id']) {
            // First delete registrations
            $delete_reg_query = "DELETE FROM event_registrations WHERE event_id = ?";
            $delete_reg_stmt = $conn->prepare($delete_reg_query);
            $delete_reg_stmt->bind_param("i", $event_id);
            $delete_reg_stmt->execute();
            
            // Then delete the event
            $delete_query = "DELETE FROM events WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $event_id);
            
            if ($delete_stmt->execute()) {
                display_message("Event deleted successfully!", "success");
            } else {
                display_message("Error deleting event.", "danger");
            }
        } else {
            display_message("You don't have permission to perform this action.", "danger");
        }
    }
}

// Get events based on user role
if (is_admin()) {
    $query = "SELECT e.*, c.name as category_name, 
             CONCAT(u.first_name, ' ', u.last_name) as organizer_name,
             (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
             FROM events e 
             JOIN event_categories c ON e.category_id = c.id
             JOIN users u ON e.organizer_id = u.id
             ORDER BY e.created_at DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT e.*, c.name as category_name, 
             CONCAT(u.first_name, ' ', u.last_name) as organizer_name,
             (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
             FROM events e 
             JOIN event_categories c ON e.category_id = c.id
             JOIN users u ON e.organizer_id = u.id
             WHERE e.organizer_id = ?
             ORDER BY e.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Events</h2>
    <a href="create_event.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Event
    </a>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Registrations</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($event = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['category_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($event['start_date'])); ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td>
                            <?php 
                                echo $event['registration_count'];
                                if ($event['max_participants'] > 0) {
                                    echo ' / ' . $event['max_participants'];
                                }
                            ?>
                        </td>
                        <td>
                            <?php if ($event['is_published']): ?>
                                <span class="badge bg-success">Published</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Unpublished</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="manage_registrations.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-users"></i>
                                </a>
                                
                                <?php if ($event['is_published']): ?>
                                    <form method="post" action="manage_events.php" class="d-inline" onsubmit="return confirm('Are you sure you want to unpublish this event?');">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" name="unpublish" class="btn btn-sm btn-warning">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="manage_events.php" class="d-inline">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" name="publish" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="post" action="manage_events.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this event? This action cannot be undone.');">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
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
        You haven't created any events yet. Click the "Create New Event" button to get started.
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 