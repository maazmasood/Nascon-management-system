<?php
require_once 'includes/header.php';

// Require admin or organizer role
require_admin_or_organizer();

// Initialize variables
$title = $description = $location = "";
$category_id = $max_participants = 0;
$start_date = $end_date = $registration_deadline = "";
$title_err = $description_err = $category_err = $start_date_err = $end_date_err = $location_err = $reg_deadline_err = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate title
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } else {
        $title = trim($_POST["title"]);
    }
    
    // Validate description
    if (empty(trim($_POST["description"]))) {
        $description_err = "Please enter a description.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Validate category
    if (empty($_POST["category_id"]) || $_POST["category_id"] == 0) {
        $category_err = "Please select a category.";
    } else {
        $category_id = intval($_POST["category_id"]);
    }
    
    // Validate start date
    if (empty($_POST["start_date"])) {
        $start_date_err = "Please enter a start date and time.";
    } else {
        $start_date = $_POST["start_date"];
        if (strtotime($start_date) < time()) {
            $start_date_err = "Start date cannot be in the past.";
        }
    }
    
    // Validate end date
    if (empty($_POST["end_date"])) {
        $end_date_err = "Please enter an end date and time.";
    } else {
        $end_date = $_POST["end_date"];
        if (strtotime($end_date) <= strtotime($start_date)) {
            $end_date_err = "End date must be after start date.";
        }
    }
    
    // Validate registration deadline
    if (empty($_POST["registration_deadline"])) {
        $reg_deadline_err = "Please enter a registration deadline.";
    } else {
        $registration_deadline = $_POST["registration_deadline"];
        if (strtotime($registration_deadline) > strtotime($start_date)) {
            $reg_deadline_err = "Registration deadline must be before event start.";
        }
    }
    
    // Validate location
    if (empty(trim($_POST["location"]))) {
        $location_err = "Please enter a location.";
    } else {
        $location = trim($_POST["location"]);
    }
    
    // Get max participants (optional)
    $max_participants = !empty($_POST["max_participants"]) ? intval($_POST["max_participants"]) : NULL;
    
    // Check is_published status
    $is_published = isset($_POST["is_published"]) ? 1 : 0;
    
    // Check input errors before inserting into database
    if (empty($title_err) && empty($description_err) && empty($category_err) && 
        empty($start_date_err) && empty($end_date_err) && empty($location_err) && 
        empty($reg_deadline_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO events (title, description, category_id, start_date, end_date, registration_deadline, 
                location, max_participants, organizer_id, is_published) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ssissssiii", $param_title, $param_description, $param_category_id, 
                           $param_start_date, $param_end_date, $param_reg_deadline, 
                           $param_location, $param_max_participants, $param_organizer_id, $param_is_published);
            
            // Set parameters
            $param_title = $title;
            $param_description = $description;
            $param_category_id = $category_id;
            $param_start_date = $start_date;
            $param_end_date = $end_date;
            $param_reg_deadline = $registration_deadline;
            $param_location = $location;
            $param_max_participants = $max_participants;
            $param_organizer_id = $_SESSION["user_id"];
            $param_is_published = $is_published;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                $event_id = $conn->insert_id;
                display_message("Event created successfully!", "success");
                header("location: event_details.php?id=" . $event_id);
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            $stmt->close();
        }
    }
}

// Get categories for dropdown
$categories_query = "SELECT id, name FROM event_categories ORDER BY name";
$categories_result = $conn->query($categories_query);
?>

<div class="row">
    <div class="col-md-12">
        <h2>Create New Event</h2>
        <div class="card">
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="title" class="form-label">Event Title</label>
                        <input type="text" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" id="title" name="title" value="<?php echo $title; ?>">
                        <div class="invalid-feedback"><?php echo $title_err; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="5"><?php echo $description; ?></textarea>
                        <div class="invalid-feedback"><?php echo $description_err; ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>" id="category_id" name="category_id">
                                <option value="0">Select a category</option>
                                <?php while($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback"><?php echo $category_err; ?></div>
                        </div>
                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control <?php echo (!empty($location_err)) ? 'is-invalid' : ''; ?>" id="location" name="location" value="<?php echo $location; ?>">
                            <div class="invalid-feedback"><?php echo $location_err; ?></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control <?php echo (!empty($start_date_err)) ? 'is-invalid' : ''; ?>" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            <div class="invalid-feedback"><?php echo $start_date_err; ?></div>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control <?php echo (!empty($end_date_err)) ? 'is-invalid' : ''; ?>" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            <div class="invalid-feedback"><?php echo $end_date_err; ?></div>
                        </div>
                        <div class="col-md-4">
                            <label for="registration_deadline" class="form-label">Registration Deadline</label>
                            <input type="datetime-local" class="form-control <?php echo (!empty($reg_deadline_err)) ? 'is-invalid' : ''; ?>" id="registration_deadline" name="registration_deadline" value="<?php echo $registration_deadline; ?>">
                            <div class="invalid-feedback"><?php echo $reg_deadline_err; ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="max_participants" class="form-label">Maximum Participants (leave empty for unlimited)</label>
                        <input type="number" class="form-control" id="max_participants" name="max_participants" min="0" value="<?php echo $max_participants; ?>">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1">
                        <label class="form-check-label" for="is_published">Publish immediately</label>
                        <div class="form-text">If unchecked, the event will be saved as a draft.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="manage_events.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 