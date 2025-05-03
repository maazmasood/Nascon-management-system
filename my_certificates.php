<?php
require_once 'includes/header.php';

// Require login
require_login();

// Get user's certificates
$query = "SELECT c.*, r.id as registration_id, r.status, 
          e.title as event_title, e.start_date, e.end_date,
          ec.name as category_name
          FROM certificates c
          JOIN event_registrations r ON c.registration_id = r.id
          JOIN events e ON r.event_id = e.id
          JOIN event_categories ec ON e.category_id = ec.id
          WHERE r.user_id = ? AND r.status = 'attended'
          ORDER BY c.issued_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">My Certificates</h2>
    </div>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="row">
        <?php while($certificate = $result->fetch_assoc()): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light text-center">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($certificate['category_name']); ?></span>
                        <span class="badge bg-success">Certificate</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($certificate['event_title']); ?></h5>
                        
                        <div class="mt-3">
                            <p>
                                <i class="fas fa-calendar-alt"></i> 
                                <strong>Event Date:</strong> 
                                <?php echo date('F d, Y', strtotime($certificate['start_date'])); ?>
                            </p>
                            <p>
                                <i class="fas fa-certificate"></i> 
                                <strong>Certificate Issued:</strong> 
                                <?php echo date('F d, Y', strtotime($certificate['issued_date'])); ?>
                            </p>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <i class="fas fa-award fa-5x text-warning mb-3"></i>
                            <p class="lead">Congratulations on completing this event!</p>
                        </div>
                    </div>
                    <div class="card-footer d-grid">
                        <a href="generate_certificate.php?id=<?php echo $certificate['id']; ?>" class="btn btn-success">
                            <i class="fas fa-download"></i> Download Certificate
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> You don't have any certificates yet. 
        <p>Certificates are awarded after you attend events and the organizer marks your attendance.</p>
        <p>Continue participating in events to earn certificates!</p>
        <a href="events.php" class="btn btn-primary mt-2">Browse Events</a>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 