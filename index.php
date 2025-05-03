<?php
require_once 'includes/header.php';

// Get upcoming events
$query = "SELECT e.*, c.name as category_name, 
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
          FROM events e 
          JOIN event_categories c ON e.category_id = c.id
          WHERE e.is_published = 1 AND e.start_date > NOW()
          ORDER BY e.start_date ASC LIMIT 6";
$result = $conn->query($query);
?>

<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1>FAST University Event Management</h1>
                <p class="lead">Discover, register, and participate in exciting events happening at FAST University.</p>
                <div class="mt-4">
                    <a href="events.php" class="btn btn-primary btn-lg">Browse Events</a>
                    <?php if (!is_logged_in()): ?>
                    <a href="register.php" class="btn btn-outline-dark btn-lg ms-2">Register Now</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <img src="images/fast.jpg" alt="FAST Events" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</div>

<div class="container">
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Upcoming Events</h2>
            <a href="events.php" class="btn btn-outline-primary">View All</a>
        </div>
        
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while($event = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light text-center">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <p class="card-text"><?php echo substr(htmlspecialchars($event['description']), 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo date('M d, Y', strtotime($event['start_date'])); ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?php echo htmlspecialchars($event['location']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-users"></i> <?php echo $event['registration_count']; ?> registered
                                </small>
                                <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No upcoming events at the moment. Check back later!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="mb-4">Event Categories</h2>
        <div class="row">
            <?php
            $categories_query = "SELECT id, name, description FROM event_categories";
            $categories_result = $conn->query($categories_query);
            while($category = $categories_result->fetch_assoc()):
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="card-text">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </p>
                    </div>
                    <div class="card-footer">
                        <a href="events.php?category=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary">View Events</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>

    <section>
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title"><i class="fas fa-user-plus"></i> Registration is Easy</h4>
                        <p class="card-text">Create an account and start registering for events. Get certificates for attended events.</p>
                        <?php if (!is_logged_in()): ?>
                        <a href="register.php" class="btn btn-primary">Register Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title"><i class="fas fa-calendar-check"></i> Organize Events</h4>
                        <p class="card-text">Organizers can create and manage events through our platform.</p>
                        <?php if (!is_logged_in()): ?>
                        <a href="login.php" class="btn btn-primary">Login to Organize</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?> 