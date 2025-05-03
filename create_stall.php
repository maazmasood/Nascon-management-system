<?php
require_once 'includes/header.php';

// Require admin or organizer role
require_admin_or_organizer();

// Initialize variables
$name = $description = $location = "";
$price = $capacity = 0;
$availability_date = "";
$name_err = $description_err = $location_err = $price_err = $availability_date_err = "";

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
        if (strtotime($availability_date) < time()) {
            $availability_date_err = "Availability date cannot be in the past.";
        }
    }
    
    // Get capacity (optional)
    $capacity = !empty($_POST["capacity"]) ? intval($_POST["capacity"]) : NULL;
    
    // Get is_approved status (auto-approved for admins, pending for organizers)
    $is_approved = is_admin() ? 1 : 0;
    
    // Get is_active status
    $is_active = isset($_POST["is_active"]) ? 1 : 0;
    
    // Check input errors before inserting into database
    if (empty($name_err) && empty($location_err) && empty($price_err) && 
        empty($availability_date_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO food_stalls (name, description, location, price, availability_date, 
                capacity, created_by, is_approved, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sssdsiiis", $param_name, $param_description, $param_location, 
                           $param_price, $param_availability_date, $param_capacity, 
                           $param_created_by, $param_is_approved, $param_is_active);
            
            // Set parameters
            $param_name = $name;
            $param_description = $description;
            $param_location = $location;
            $param_price = $price;
            $param_availability_date = $availability_date;
            $param_capacity = $capacity;
            $param_created_by = $_SESSION["user_id"];
            $param_is_approved = $is_approved;
            $param_is_active = $is_active;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                $stall_id = $conn->insert_id;
                display_message("Stall created successfully!", "success");
                header("location: stall_details.php?id=" . $stall_id);
                exit();
            } else {
                display_message("Oops! Something went wrong. Please try again later.", "danger");
            }
            
            // Close statement
            $stmt->close();
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Create New Food Stall</h2>
        <div class="card">
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                        <div class="form-text">Inactive stalls won't be visible to users.</div>
                    </div>
                    
                    <?php if (!is_admin()): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Your stall will be pending approval until reviewed by an admin.
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="manage_stalls.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Stall</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 