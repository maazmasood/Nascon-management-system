<?php
require_once 'includes/header.php';

// Require admin or organizer role
require_admin_or_organizer();

// Check if event ID is provided
if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    header("Location: manage_events.php");
    exit();
}

$event_id = intval($_GET['event_id']);

// Check authorization and get event data
$auth_query = "SELECT e.*, c.name as category_name FROM events e 
              JOIN event_categories c ON e.category_id = c.id 
              WHERE e.id = ?";
$auth_stmt = $conn->prepare($auth_query);
$auth_stmt->bind_param("i", $event_id);
$auth_stmt->execute();
$auth_result = $auth_stmt->get_result();

if ($auth_result->num_rows == 0) {
    display_message("Event not found.", "danger");
    header("Location: manage_events.php");
    exit();
}

$event = $auth_result->fetch_assoc();

// Check if user is authorized to manage registrations for this event
if (!is_admin() && $event['organizer_id'] != $_SESSION['user_id']) {
    display_message("You don't have permission to manage registrations for this event.", "danger");
    header("Location: manage_events.php");
    exit();
}

// Process registration status updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve']) && isset($_POST['registration_id'])) {
        $registration_id = intval($_POST['registration_id']);
        $update_query = "UPDATE event_registrations SET status = 'approved' WHERE id = ? AND event_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $registration_id, $event_id);
        
        if ($update_stmt->execute()) {
            display_message("Registration approved successfully!", "success");
        } else {
            display_message("Error approving registration.", "danger");
        }
    } elseif (isset($_POST['reject']) && isset($_POST['registration_id'])) {
        $registration_id = intval($_POST['registration_id']);
        $update_query = "UPDATE event_registrations SET status = 'rejected' WHERE id = ? AND event_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $registration_id, $event_id);
        
        if ($update_stmt->execute()) {
            display_message("Registration rejected successfully!", "success");
        } else {
            display_message("Error rejecting registration.", "danger");
        }
    } elseif (isset($_POST['mark_attended']) && isset($_POST['registration_id'])) {
        $registration_id = intval($_POST['registration_id']);
        $update_query = "UPDATE event_registrations SET status = 'attended' WHERE id = ? AND event_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $registration_id, $event_id);
        
        if ($update_stmt->execute()) {
            // Check if certificate already exists
            $check_cert_query = "SELECT id FROM certificates WHERE registration_id = ?";
            $check_cert_stmt = $conn->prepare($check_cert_query);
            $check_cert_stmt->bind_param("i", $registration_id);
            $check_cert_stmt->execute();
            $check_cert_result = $check_cert_stmt->get_result();
            
            if ($check_cert_result->num_rows == 0) {
                // Generate certificate
                $cert_url = "certificates/" . uniqid() . ".pdf";
                $insert_cert_query = "INSERT INTO certificates (registration_id, certificate_url) VALUES (?, ?)";
                $insert_cert_stmt = $conn->prepare($insert_cert_query);
                $insert_cert_stmt->bind_param("is", $registration_id, $cert_url);
                $insert_cert_stmt->execute();
            }
            
            display_message("Attendance marked and certificate generated successfully!", "success");
        } else {
            display_message("Error marking attendance.", "danger");
        }
    } elseif (isset($_POST['delete']) && isset($_POST['registration_id'])) {
        $registration_id = intval($_POST['registration_id']);
        
        // First delete any certificates
        $delete_cert_query = "DELETE FROM certificates WHERE registration_id = ?";
        $delete_cert_stmt = $conn->prepare($delete_cert_query);
        $delete_cert_stmt->bind_param("i", $registration_id);
        $delete_cert_stmt->execute();
        
        // Then delete the registration
        $delete_query = "DELETE FROM event_registrations WHERE id = ? AND event_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $registration_id, $event_id);
        
        if ($delete_stmt->execute()) {
            display_message("Registration deleted successfully!", "success");
        } else {
            display_message("Error deleting registration.", "danger");
        }
    }
}

// Get registrations for this event
$registrations_query = "SELECT r.*, u.first_name, u.last_name, u.email, u.student_id, u.phone
                      FROM event_registrations r
                      JOIN users u ON r.user_id = u.id
                      WHERE r.event_id = ?
                      ORDER BY r.registration_date DESC";
$reg_stmt = $conn->prepare($registrations_query);
$reg_stmt->bind_param("i", $event_id);
$reg_stmt->execute();
$registrations_result = $reg_stmt->get_result();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="manage_events.php">Manage Events</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Registrations</li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center">
            <h2>Manage Registrations: <?php echo htmlspecialchars($event['title']); ?></h2>
            <a href="manage_events.php" class="btn btn-secondary">Back to Events</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Event Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($event['category_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($event['start_date'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($event['start_date'])) . ' - ' . date('g:i A', strtotime($event['end_date'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                        <p><strong>Max Participants:</strong> <?php echo $event['max_participants'] ? $event['max_participants'] : 'Unlimited'; ?></p>
                        <p><strong>Registration Deadline:</strong> <?php echo date('F d, Y g:i A', strtotime($event['registration_deadline'])); ?></p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>Registration Statistics:</strong></p>
                        <?php
                        // Get registration stats
                        $stats_query = "SELECT status, COUNT(*) as count FROM event_registrations WHERE event_id = ? GROUP BY status";
                        $stats_stmt = $conn->prepare($stats_query);
                        $stats_stmt->bind_param("i", $event_id);
                        $stats_stmt->execute();
                        $stats_result = $stats_stmt->get_result();
                        
                        $stats = array(
                            'pending' => 0,
                            'approved' => 0,
                            'rejected' => 0,
                            'attended' => 0
                        );
                        
                        while($stat = $stats_result->fetch_assoc()) {
                            $stats[$stat['status']] = $stat['count'];
                        }
                        
                        $total = array_sum($stats);
                        ?>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5><?php echo $stats['pending']; ?></h5>
                                        <p class="mb-0">Pending</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5><?php echo $stats['approved']; ?></h5>
                                        <p class="mb-0">Approved</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h5><?php echo $stats['rejected']; ?></h5>
                                        <p class="mb-0">Rejected</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5><?php echo $stats['attended']; ?></h5>
                                        <p class="mb-0">Attended</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($registrations_result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Student ID</th>
                    <th>Phone</th>
                    <th>Registration Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($registration = $registrations_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($registration['email']); ?></td>
                        <td><?php echo $registration['student_id'] ? htmlspecialchars($registration['student_id']) : 'N/A'; ?></td>
                        <td><?php echo $registration['phone'] ? htmlspecialchars($registration['phone']) : 'N/A'; ?></td>
                        <td><?php echo date('M d, Y g:i A', strtotime($registration['registration_date'])); ?></td>
                        <td>
                            <?php if ($registration['status'] == 'pending'): ?>
                                <span class="badge bg-info">Pending</span>
                            <?php elseif ($registration['status'] == 'approved'): ?>
                                <span class="badge bg-success">Approved</span>
                            <?php elseif ($registration['status'] == 'rejected'): ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php elseif ($registration['status'] == 'attended'): ?>
                                <span class="badge bg-primary">Attended</span>
                                
                                <?php
                                // Check if certificate exists
                                $cert_query = "SELECT id, certificate_url FROM certificates WHERE registration_id = ?";
                                $cert_stmt = $conn->prepare($cert_query);
                                $cert_stmt->bind_param("i", $registration['id']);
                                $cert_stmt->execute();
                                $cert_result = $cert_stmt->get_result();
                                if ($cert_result->num_rows > 0):
                                    $cert = $cert_result->fetch_assoc();
                                ?>
                                    <a href="<?php echo $cert['certificate_url']; ?>" class="badge bg-warning" target="_blank">Certificate</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <?php if ($registration['status'] == 'pending'): ?>
                                    <form method="post" action="manage_registrations.php?event_id=<?php echo $event_id; ?>" class="d-inline">
                                        <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                        <button type="submit" name="approve" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="post" action="manage_registrations.php?event_id=<?php echo $event_id; ?>" class="d-inline">
                                        <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                        <button type="submit" name="reject" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php elseif ($registration['status'] == 'approved'): ?>
                                    <form method="post" action="manage_registrations.php?event_id=<?php echo $event_id; ?>" class="d-inline">
                                        <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                        <button type="submit" name="mark_attended" class="btn btn-sm btn-primary">
                                            <i class="fas fa-user-check"></i> Mark Attended
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="post" action="manage_registrations.php?event_id=<?php echo $event_id; ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this registration?');">
                                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
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
        No registrations found for this event.
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 