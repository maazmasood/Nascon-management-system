<?php
require_once 'includes/header.php';

// Get all approved active stalls
$query = "SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
          (SELECT COUNT(*) FROM stall_bookings WHERE stall_id = s.id AND status = 'approved') as booking_count
          FROM food_stalls s 
          JOIN users u ON s.created_by = u.id
          WHERE s.is_approved = 1 AND s.is_active = 1
          ORDER BY s.availability_date ASC";
$result = $conn->query($query);
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Food Stalls</h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Browse and book food stalls for your favorite events!
                    <?php if (!is_logged_in()): ?>
                        <a href="login.php" class="btn btn-primary btn-sm ms-2">Login to Book</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php if ($result->num_rows > 0): ?>
        <?php while($stall = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between">
                        <h5 class="mb-0"><?php echo htmlspecialchars($stall['name']); ?></h5>
                        <span class="badge bg-primary">₹<?php echo htmlspecialchars($stall['price']); ?></span>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo substr(htmlspecialchars($stall['description']), 0, 100) . '...'; ?></p>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($stall['location']); ?>
                            </div>
                            <div>
                                <i class="fas fa-calendar-alt"></i> 
                                <?php echo date('M d, Y', strtotime($stall['availability_date'])); ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-2">
                            <div>
                                <i class="fas fa-users"></i> 
                                Capacity: <?php echo $stall['capacity'] ? $stall['capacity'] : 'Unlimited'; ?>
                            </div>
                            <div>
                                <i class="fas fa-check-circle"></i> 
                                Bookings: <?php echo $stall['booking_count']; ?>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">Created by: <?php echo htmlspecialchars($stall['created_by_name']); ?></small>
                        </div>
                    </div>
                    <div class="card-footer d-grid">
                        <a href="stall_details.php?id=<?php echo $stall['id']; ?>" class="btn btn-primary">View Details</a>
                        <?php if (is_logged_in() && !is_admin() && !is_organizer()): ?>
                            <a href="book_stall.php?id=<?php echo $stall['id']; ?>" class="btn btn-success mt-2">Book Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">
                No stalls available at the moment. Please check back later!
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 