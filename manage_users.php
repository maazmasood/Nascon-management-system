<?php
require_once 'includes/header.php';

// Require admin role
require_admin();

// Process user management actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete']) && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        
        // Prevent self-deletion
        if ($user_id == $_SESSION['user_id']) {
            display_message("You cannot delete your own account.", "danger");
        } else {
            // First delete certificates of the user
            $delete_cert_query = "DELETE c FROM certificates c 
                                JOIN event_registrations r ON c.registration_id = r.id 
                                WHERE r.user_id = ?";
            $delete_cert_stmt = $conn->prepare($delete_cert_query);
            $delete_cert_stmt->bind_param("i", $user_id);
            $delete_cert_stmt->execute();
            
            // Then delete registrations
            $delete_reg_query = "DELETE FROM event_registrations WHERE user_id = ?";
            $delete_reg_stmt = $conn->prepare($delete_reg_query);
            $delete_reg_stmt->bind_param("i", $user_id);
            $delete_reg_stmt->execute();
            
            // Then delete events created by the user
            $delete_events_query = "DELETE FROM events WHERE organizer_id = ?";
            $delete_events_stmt = $conn->prepare($delete_events_query);
            $delete_events_stmt->bind_param("i", $user_id);
            $delete_events_stmt->execute();
            
            // Finally delete the user
            $delete_user_query = "DELETE FROM users WHERE id = ?";
            $delete_user_stmt = $conn->prepare($delete_user_query);
            $delete_user_stmt->bind_param("i", $user_id);
            
            if ($delete_user_stmt->execute()) {
                display_message("User deleted successfully!", "success");
            } else {
                display_message("Error deleting user.", "danger");
            }
        }
    } elseif (isset($_POST['update_role']) && isset($_POST['user_id']) && isset($_POST['role_id'])) {
        $user_id = intval($_POST['user_id']);
        $role_id = intval($_POST['role_id']);
        
        // Prevent self role change
        if ($user_id == $_SESSION['user_id']) {
            display_message("You cannot change your own role.", "danger");
        } else {
            $update_query = "UPDATE users SET role_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $role_id, $user_id);
            
            if ($update_stmt->execute()) {
                display_message("User role updated successfully!", "success");
            } else {
                display_message("Error updating user role.", "danger");
            }
        }
    }
}

// Get all users
$query = "SELECT u.id, u.email, u.first_name, u.last_name, u.student_id, 
         u.phone, u.created_at, r.name as role_name, r.id as role_id,
         (SELECT COUNT(*) FROM events WHERE organizer_id = u.id) as events_created,
         (SELECT COUNT(*) FROM event_registrations WHERE user_id = u.id) as events_registered
         FROM users u
         JOIN roles r ON u.role_id = r.id
         ORDER BY u.id ASC";
$result = $conn->query($query);

// Get all roles for the dropdown
$roles_query = "SELECT id, name FROM roles ORDER BY id";
$roles_result = $conn->query($roles_query);
$roles = [];
while($role = $roles_result->fetch_assoc()) {
    $roles[$role['id']] = $role['name'];
}
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Manage Users</h2>
    </div>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Student ID</th>
                    <th>Role</th>
                    <th>Events Created</th>
                    <th>Events Registered</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['student_id'] ? htmlspecialchars($user['student_id']) : 'N/A'; ?></td>
                        <td>
                            <span class="badge 
                                <?php 
                                    if ($user['role_id'] == 1) echo 'bg-danger';
                                    elseif ($user['role_id'] == 2) echo 'bg-success';
                                    elseif ($user['role_id'] == 3) echo 'bg-primary';
                                    else echo 'bg-secondary';
                                ?>">
                                <?php echo htmlspecialchars($user['role_name']); ?>
                            </span>
                        </td>
                        <td><?php echo $user['events_created']; ?></td>
                        <td><?php echo $user['events_registered']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="btn-group dropend">
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <h6 class="dropdown-header">Change Role</h6>
                                    </li>
                                    <?php foreach($roles as $role_id => $role_name): ?>
                                        <?php if ($role_id != $user['role_id']): ?>
                                            <li>
                                                <form method="post" action="manage_users.php" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="role_id" value="<?php echo $role_id; ?>">
                                                    <button type="submit" name="update_role" class="dropdown-item">
                                                        Make <?php echo $role_name; ?>
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="post" action="manage_users.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their events and registrations. This action cannot be undone.');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete" class="dropdown-item text-danger">
                                                    Delete User
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="dropdown-item text-muted">Cannot delete yourself</span>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        No users found.
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 