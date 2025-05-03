<?php
require_once 'includes/header.php';

// Require admin or organizer role
require_admin_or_organizer();

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_events.php");
    exit();
}

$event_id = intval($_GET['id']);

// Initialize variables
$title = $description = $location = "";
$category_id = $max_participants = 0;
$start_date = $end_date = $registration_deadline = "";
$is_published = 0;
$title_err = $description_err = $category_err = $start_date_err = $end_date_err = $location_err = $reg_deadline_err = "";

// Check authorization and get event data
$auth_query = "SELECT * FROM events WHERE id = ?";
$auth_stmt = $conn->prepare($auth_query);
$auth_stmt->bind_param("i", $event_id);
$auth_stmt->execute();
$auth_result = $auth_stmt->get_result();

if ($auth_result->num_rows == 0) {
    display_message("Event not found.", "danger");
    header("Location: manage_events.php");
    exit();
}

$event_data = $auth_result->fetch_assoc();

// Check if user is authorized to edit this event
if (!is_admin() && $event_data['organizer_id'] != $_SESSION['user_id']) {
    display_message("You don't have permission to edit this event.", "danger");
    header("Location: manage_events.php");
    exit();
}

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
    
    // Check input errors before updating database
    if (empty($title_err) && empty($description_err) && empty($category_err) && 
        empty($start_date_err) && empty($end_date_err) && empty($location_err) && 
        empty($reg_deadline_err)) {
        
        // Prepare an update statement
        $sql = "UPDATE events SET title = ?, description = ?, category_id = ?, start_date = ?, 
               end_date = ?, registration_deadline = ?, location = ?, max_participants = ?, is_published = ? 
               WHERE id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ssissssiii", $param_title, $param_description, $param_category_id, 
                           $param_start_date, $param_end_date, $param_reg_deadline, 
                           $param_location, $param_max_participants, $param_is_published, $param_event_id);
            
            // Set parameters
            $param_title = $title;
            $param_description = $description;
            $param_category_id = $category_id;
            $param_start_date = $start_date;
            $param_end_date = $end_date;
            $param_reg_deadline = $registration_deadline;
            $param_location = $location;
            $param_max_participants = $max_participants;
            $param_is_published = $is_published;
            $param_event_id = $event_id;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                display_message("Event updated successfully!", "success");
                header("location: event_details.php?id=" . $event_id);
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            $stmt->close();
        }
    }
} else {
    // Populate form with current event data
    $title = $event_data['title'];
    $description = $event_data['description'];
    $category_id = $event_data['category_id'];
    $start_date = date('Y-m-d\TH:i', strtotime($event_data['start_date']));
    $end_date = date('Y-m-d\TH:i', strtotime($event_data['end_date']));
    $registration_deadline = date('Y-m-d\TH:i', strtotime($event_data['registration_deadline']));
    $location = $event_data['location'];
    $max_participants = $event_data['max_participants'];
    $is_published = $event_data['is_published'];
}

// Get categories for dropdown
$categories_query = "SELECT id, name FROM event_categories ORDER BY name";
$categories_result = $conn->query($categories_query);
?>

<div class="row">
    <div class="col-md-12">
        <h2>Edit Event</h2>
        <div class="card">
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $event_id); ?>" method="post">
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
                                <?php 
                                $categories_result->data_seek(0); // Reset result set pointer
                                while($category = $categories_result->fetch_assoc()): 
                                ?>
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
                        <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1" <?php echo $is_published ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_published">Published</label>
                        <div class="form-text">Uncheck to save as draft.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="manage_events.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 