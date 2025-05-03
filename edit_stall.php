<?php
require_once 'includes/header.php';

// Require admin or organizer role
require_admin_or_organizer();

// Check if stall ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_stalls.php");
    exit();
}

$stall_id = intval($_GET['id']);

// Initialize variables
$name = $description = $location = "";
$price = $capacity = 0;
$availability_date = "";
$is_approved = $is_active = 0;
$name_err = $description_err = $location_err = $price_err = $availability_date_err = "";

// Check authorization and get stall data
$auth_query = "SELECT * FROM food_stalls WHERE id = ?";
$auth_stmt = $conn->prepare($auth_query);
$auth_stmt->bind_param("i", $stall_id);
$auth_stmt->execute();
$auth_result = $auth_stmt->get_result();

if ($auth_result->num_rows == 0) {
    display_message("Stall not found.", "danger");
    header("Location: manage_stalls.php");
    exit();
}

$stall_data = $auth_result->fetch_assoc();

// Check if user is authorized to edit this stall
if (!is_admin() && $stall_data['created_by'] != $_SESSION['user_id']) {
    display_message("You don't have permission to edit this stall.", "danger");
    header("Location: manage_stalls.php");
    exit();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter a stall name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate description (optional)
    $description = !empty($_POST["description"]) ? trim($_POST["description"]) : NULL;
    
    // Validate location
    if (empty(trim($_POST["location"]))) {
        $location_err = "Please enter a location.";
    } else {
        $location = trim($_POST["location"]);
    }
    
    // Validate price
    if (empty($_POST["price"]) || !is_numeric($_POST["price"]) || $_POST["price"] < 0) {
        $price_err = "Please enter a valid price.";
    } else {
        $price = floatval($_POST["price"]);
    }
    
    // Validate availability date
    if (empty($_POST["availability_date"])) {
        $availability_date_err = "Please enter an availability date.";
    } else {
        $availability_date = $_POST["availability_date"];
    }
    
    // Get capacity (optional)
    $capacity = !empty($_POST["capacity"]) ? intval($_POST["capacity"]) : NULL;
    
    // Get approval status (only admins can change approval)
    if (is_admin() && isset($_POST["is_approved"])) {
        $is_approved = 1;
    } elseif (is_admin()) {
        $is_approved = 0;
    } else {
        $is_approved = $stall_data['is_approved']; // Keep existing value for non-admins
    }
    
    // Get active status
    $is_active = isset($_POST["is_active"]) ? 1 : 0;
    
    // Check input errors before updating database
    if (empty($name_err) && empty($location_err) && empty($price_err) && 
        empty($availability_date_err)) {
        
        // Prepare an update statement
        $sql = "UPDATE food_stalls SET name = ?, description = ?, location = ?, price = ?, 
               availability_date = ?, capacity = ?, is_approved = ?, is_active = ? 
               WHERE id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sssdsiiis", $param_name, $param_description, $param_location, 
                           $param_price, $param_availability_date, $param_capacity, 
                           $param_is_approved, $param_is_active, $param_stall_id);
            
            // Set parameters
            $param_name = $name;
            $param_description = $description;
            $param_location = $location;
            $param_price = $price;
            $param_availability_date = $availability_date;
            $param_capacity = $capacity;
            $param_is_approved = $is_approved;
            $param_is_active = $is_active;
            $param_stall_id = $stall_id;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                display_message("Stall updated successfully!", "success");
                header("location: stall_details.php?id=" . $stall_id);
                exit();
            } else {
                display_message("Oops! Something went wrong. Please try again later.", "danger");
            }
            
            // Close statement
            $stmt->close();
        }
    }
} else {
    // Populate form with current stall data
    $name = $stall_data['name'];
    $description = $stall_data['description'];
    $location = $stall_data['location'];
    $price = $stall_data['price'];
    $availability_date = date('Y-m-d', strtotime($stall_data['availability_date']));
    $capacity = $stall_data['capacity'];
    $is_approved = $stall_data['is_approved'];
    $is_active = $stall_data['is_active'];
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Edit Food Stall</h2>
        <div class="card">
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $stall_id); ?>" method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">Stall Name</label>
                        <input type="text" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo $name; ?>">
                        <div class="invalid-feedback"><?php echo $name_err; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="5"><?php echo $description; ?></textarea>
                        <div class="invalid-feedback"><?php echo $description_err; ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control <?php echo (!empty($location_err)) ? 'is-invalid' : ''; ?>" id="location" name="location" value="<?php echo $location; ?>">
                            <div class="invalid-feedback"><?php echo $location_err; ?></div>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price (₹)</label>
                            <input type="number" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" id="price" name="price" step="0.01" min="0" value="<?php echo $price; ?>">
                            <div class="invalid-feedback"><?php echo $price_err; ?></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="availability_date" class="form-label">Availability Date</label>
                            <input type="date" class="form-control <?php echo (!empty($availability_date_err)) ? 'is-invalid' : ''; ?>" id="availability_date" name="availability_date" value="<?php echo $availability_date; ?>">
                            <div class="invalid-feedback"><?php echo $availability_date_err; ?></div>
                        </div>
                        <div class="col-md-6">
                            <label for="capacity" class="form-label">Capacity (leave empty for unlimited)</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="0" value="<?php echo $capacity; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $is_active ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                        <div class="form-text">Inactive stalls won't be visible to users.</div>
                    </div>
                    
                    <?php if (is_admin()): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_approved" name="is_approved" value="1" <?php echo $is_approved ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_approved">Approved</label>
                        <div class="form-text">Unapproved stalls won't be visible to users.</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="manage_stalls.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Stall</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 