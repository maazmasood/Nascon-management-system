<?php
require_once 'includes/header.php';

// Initialize variables
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_term = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build the SQL query
$query = "SELECT e.*, c.name as category_name, 
          CONCAT(u.first_name, ' ', u.last_name) as organizer_name,
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
          FROM events e 
          JOIN event_categories c ON e.category_id = c.id
          JOIN users u ON e.organizer_id = u.id
          WHERE e.is_published = 1 AND e.start_date >= NOW()";

// Add category filter if selected
if ($category_filter > 0) {
    $query .= " AND e.category_id = " . $category_filter;
}

// Add search filter if provided
if (!empty($search_term)) {
    $query .= " AND (e.title LIKE '%" . $search_term . "%' OR e.description LIKE '%" . $search_term . "%')";
}

// Add ordering
$query .= " ORDER BY e.start_date ASC";

$result = $conn->query($query);

// Get all categories for the filter
$categories_query = "SELECT id, name FROM event_categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = array();
while($category = $categories_result->fetch_assoc()) {
    $categories[$category['id']] = $category['name'];
}
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Upcoming Events</h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="get" action="events.php" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search events..." value="<?php echo $search_term; ?>">
                    </div>
                    <div class="col-md-5">
                        <select class="form-select" id="category" name="category">
                            <option value="0">All Categories</option>
                            <?php foreach($categories as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($category_filter == $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php if ($result->num_rows > 0): ?>
        <?php while($event = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($event['start_date'])); ?></small>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                        <p class="card-text"><?php echo substr(htmlspecialchars($event['description']), 0, 100) . '...'; ?></p>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($event['location']); ?>
                            </div>
                            <div>
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($event['organizer_name']); ?>
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
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">
                No events found matching your criteria. Please try different filters or check back later!
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 