<?php
require_once 'includes/header.php';

// Require login
require_login();

// Get user's registered events
$query = "SELECT e.*, c.name as category_name, r.status as registration_status, r.id as registration_id,
         (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
         FROM event_registrations r 
         JOIN events e ON r.event_id = e.id
         JOIN event_categories c ON e.category_id = c.id
         WHERE r.user_id = ?
         ORDER BY e.start_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Upcoming events are those with start_date in the future
// Past events are those with start_date in the past
$upcoming_events = [];
$past_events = [];

while ($event = $result->fetch_assoc()) {
    if (strtotime($event['start_date']) > time()) {
        $upcoming_events[] = $event;
    } else {
        $past_events[] = $event;
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">My Events</h2>
        
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">Upcoming Events (<?php echo count($upcoming_events); ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">Past Events (<?php echo count($past_events); ?>)</button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- Upcoming Events Tab -->
            <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                <?php if (count($upcoming_events) > 0): ?>
                    <div class="row">
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light d-flex justify-content-between">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                                        <span class="badge 
                                            <?php 
                                                if ($event['registration_status'] == 'pending') echo 'bg-info';
                                                elseif ($event['registration_status'] == 'approved') echo 'bg-success';
                                                elseif ($event['registration_status'] == 'rejected') echo 'bg-danger';
                                                elseif ($event['registration_status'] == 'attended') echo 'bg-primary';
                                            ?>">
                                            <?php echo ucfirst($event['registration_status']); ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                        <p class="card-text"><?php echo substr(htmlspecialchars($event['description']), 0, 100) . '...'; ?></p>
                                        
                                        <div class="d-flex justify-content-between mt-3">
                                            <div>
                                                <i class="fas fa-calendar-alt"></i> 
                                                <?php echo date('M d, Y', strtotime($event['start_date'])); ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo htmlspecialchars($event['location']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-2">
                                            <div>
                                                <i class="fas fa-clock"></i> 
                                                <?php 
                                                    echo date('g:i A', strtotime($event['start_date'])) . ' - ' . 
                                                         date('g:i A', strtotime($event['end_date'])); 
                                                ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-users"></i> 
                                                <?php echo $event['registration_count']; ?>
                                                <?php echo $event['max_participants'] ? '/' . $event['max_participants'] : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer d-grid">
                                        <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        You haven't registered for any upcoming events. <a href="events.php">Browse events</a> to register.
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Past Events Tab -->
            <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                <?php if (count($past_events) > 0): ?>
                    <div class="row">
                        <?php foreach ($past_events as $event): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light d-flex justify-content-between">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                                        <span class="badge 
                                            <?php 
                                                if ($event['registration_status'] == 'pending') echo 'bg-info';
                                                elseif ($event['registration_status'] == 'approved') echo 'bg-success';
                                                elseif ($event['registration_status'] == 'rejected') echo 'bg-danger';
                                                elseif ($event['registration_status'] == 'attended') echo 'bg-primary';
                                            ?>">
                                            <?php echo ucfirst($event['registration_status']); ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                        <p class="card-text"><?php echo substr(htmlspecialchars($event['description']), 0, 100) . '...'; ?></p>
                                        
                                        <div class="d-flex justify-content-between mt-3">
                                            <div>
                                                <i class="fas fa-calendar-alt"></i> 
                                                <?php echo date('M d, Y', strtotime($event['start_date'])); ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo htmlspecialchars($event['location']); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($event['registration_status'] == 'attended'): ?>
                                            <?php
                                            // Check if certificate exists
                                            $cert_query = "SELECT id, certificate_url FROM certificates WHERE registration_id = ?";
                                            $cert_stmt = $conn->prepare($cert_query);
                                            $cert_stmt->bind_param("i", $event['registration_id']);
                                            $cert_stmt->execute();
                                            $cert_result = $cert_stmt->get_result();
                                            if ($cert_result->num_rows > 0):
                                                $cert = $cert_result->fetch_assoc();
                                            ?>
                                                <div class="alert alert-success mt-3">
                                                    <i class="fas fa-certificate"></i> You have a certificate for this event!
                                                    <a href="<?php echo $cert['certificate_url']; ?>" class="btn btn-sm btn-success mt-2" target="_blank">Download Certificate</a>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-info mt-3">
                                                    <i class="fas fa-info-circle"></i> You attended this event but no certificate is available yet.
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer d-grid">
                                        <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        You haven't attended any past events.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 