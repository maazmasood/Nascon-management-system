<?php
require_once 'includes/header.php';

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = intval($_GET['id']);

// Process registration form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    require_login(); // Redirect to login if not logged in
    
    // Check if user is already registered
    $check_query = "SELECT id, status FROM event_registrations WHERE event_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $registration = $check_result->fetch_assoc();
        if ($registration['status'] == 'rejected') {
            display_message("Your registration has been rejected. You cannot register again.", "danger");
        } else {
            display_message("You are already registered for this event.", "info");
        }
    } else {
        // Check if the event is full
        $event_query = "SELECT max_participants FROM events WHERE id = ?";
        $event_stmt = $conn->prepare($event_query);
        $event_stmt->bind_param("i", $event_id);
        $event_stmt->execute();
        $event_result = $event_stmt->get_result();
        $event_data = $event_result->fetch_assoc();
        
        $count_query = "SELECT COUNT(*) as reg_count FROM event_registrations WHERE event_id = ? AND status != 'rejected'";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("i", $event_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        
        if ($event_data['max_participants'] > 0 && $count_data['reg_count'] >= $event_data['max_participants']) {
            display_message("Sorry, this event is full. You cannot register at this time.", "danger");
        } else {
            // Register the user
            $insert_query = "INSERT INTO event_registrations (event_id, user_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
            
            if ($insert_stmt->execute()) {
                display_message("You have successfully registered for this event!", "success");
            } else {
                display_message("Error registering for the event. Please try again.", "danger");
            }
        }
    }
}

// Get event details
$query = "SELECT e.*, c.name as category_name, 
         CONCAT(u.first_name, ' ', u.last_name) as organizer_name,
         (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status != 'rejected') as registration_count
         FROM events e 
         JOIN event_categories c ON e.category_id = c.id
         JOIN users u ON e.organizer_id = u.id
         WHERE e.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: events.php");
    exit();
}

$event = $result->fetch_assoc();

// Check if user is registered
$is_registered = false;
$registration_status = '';
if (is_logged_in()) {
    $reg_query = "SELECT status FROM event_registrations WHERE event_id = ? AND user_id = ?";
    $reg_stmt = $conn->prepare($reg_query);
    $reg_stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
    $reg_stmt->execute();
    $reg_result = $reg_stmt->get_result();
    
    if ($reg_result->num_rows > 0) {
        $is_registered = true;
        $registration = $reg_result->fetch_assoc();
        $registration_status = $registration['status'];
    }
}

// Check if registration deadline has passed
$deadline_passed = strtotime($event['registration_deadline']) < time();
// Check if event is full
$is_full = $event['max_participants'] > 0 && $event['registration_count'] >= $event['max_participants'];
?>

<div class="row">
    <div class="col-md-8">
        <h2><?php echo htmlspecialchars($event['title']); ?></h2>
        <div class="mb-3">
            <span class="badge bg-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
            <?php if ($event['is_published'] == 0): ?>
                <span class="badge bg-warning">Unpublished</span>
            <?php endif; ?>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Description</h5>
                <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Event Details</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-calendar-alt"></i> 
                        <strong>Date:</strong> 
                        <?php echo date('F d, Y', strtotime($event['start_date'])); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-clock"></i> 
                        <strong>Time:</strong> 
                        <?php 
                        echo date('g:i A', strtotime($event['start_date'])) . ' - ' . 
                            date('g:i A', strtotime($event['end_date'])); 
                        ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-map-marker-alt"></i> 
                        <strong>Location:</strong> 
                        <?php echo htmlspecialchars($event['location']); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-user"></i> 
                        <strong>Organizer:</strong> 
                        <?php echo htmlspecialchars($event['organizer_name']); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-users"></i> 
                        <strong>Registration Limit:</strong> 
                        <?php echo $event['max_participants'] ? $event['registration_count'] . '/' . $event['max_participants'] : 'Unlimited'; ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-clock"></i> 
                        <strong>Registration Deadline:</strong> 
                        <?php echo date('F d, Y g:i A', strtotime($event['registration_deadline'])); ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Registration</h5>
            </div>
            <div class="card-body">
                <?php if ($deadline_passed): ?>
                    <div class="alert alert-warning">
                        Registration deadline has passed.
                    </div>
                <?php elseif ($is_full && !$is_registered): ?>
                    <div class="alert alert-warning">
                        This event is full. No more registrations allowed.
                    </div>
                <?php elseif ($is_registered): ?>
                    <div class="alert alert-success">
                        You are registered for this event!
                    </div>
                    <?php if ($registration_status == 'pending'): ?>
                        <div class="alert alert-info">
                            Your registration is pending approval.
                        </div>
                    <?php elseif ($registration_status == 'approved'): ?>
                        <div class="alert alert-success">
                            Your registration has been approved.
                        </div>
                    <?php elseif ($registration_status == 'rejected'): ?>
                        <div class="alert alert-danger">
                            Your registration has been rejected.
                        </div>
                    <?php elseif ($registration_status == 'attended'): ?>
                        <div class="alert alert-success">
                            You have attended this event.
                        </div>
                    <?php endif; ?>
                <?php elseif (!is_logged_in()): ?>
                    <div class="alert alert-info">
                        Please <a href="login.php">login</a> to register for this event.
                    </div>
                <?php else: ?>
                    <form method="post" action="event_details.php?id=<?php echo $event_id; ?>">
                        <p>Click below to register for this event. You will receive a confirmation once your registration is approved.</p>
                        <button type="submit" name="register" class="btn btn-primary btn-block">Register Now</button>
                    </form>
                <?php endif; ?>
                
                <div class="mt-3">
                    <p><i class="fas fa-info-circle"></i> Registration Count: <strong><?php echo $event['registration_count']; ?></strong></p>
                    
                    <?php if ($event['max_participants'] > 0): ?>
                        <div class="progress">
                            <?php 
                            $percentage = ($event['registration_count'] / $event['max_participants']) * 100;
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
                <h5 class="mb-0">Share This Event</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                        class="btn btn-outline-primary" target="_blank">
                        <i class="fab fa-facebook-f"></i> Share on Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Check out this event: ' . $event['title']); ?>&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                        class="btn btn-outline-info" target="_blank">
                        <i class="fab fa-twitter"></i> Share on Twitter
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode('Check out this event: ' . $event['title']); ?>&body=<?php echo urlencode('I thought you might be interested in this event: ' . $event['title'] . '\n\nDetails: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                        class="btn btn-outline-secondary">
                        <i class="fas fa-envelope"></i> Share via Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 