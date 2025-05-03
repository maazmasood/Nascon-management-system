<?php
require_once 'includes/header.php';

// Require admin role
require_admin();

// Initialize variables
$name = $description = "";
$name_err = $description_err = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_category'])) {
        // Validate name
        if (empty(trim($_POST["name"]))) {
            $name_err = "Please enter a category name.";
        } else {
            // Check if category already exists
            $sql = "SELECT id FROM event_categories WHERE name = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("s", $param_name);
                
                // Set parameters
                $param_name = trim($_POST["name"]);
                
                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    // Store result
                    $stmt->store_result();
                    
                    if ($stmt->num_rows > 0) {
                        $name_err = "This category already exists.";
                    } else {
                        $name = trim($_POST["name"]);
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                
                // Close statement
                $stmt->close();
            }
        }
        
        // Validate description (optional)
        $description = !empty($_POST["description"]) ? trim($_POST["description"]) : NULL;
        
        // Check input errors before inserting into database
        if (empty($name_err)) {
            // Prepare an insert statement
            $sql = "INSERT INTO event_categories (name, description) VALUES (?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("ss", $param_name, $param_description);
                
                // Set parameters
                $param_name = $name;
                $param_description = $description;
                
                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    display_message("Category added successfully!", "success");
                    $name = $description = ""; // Clear the form
                } else {
                    display_message("Error adding category. Please try again.", "danger");
                }
                
                // Close statement
                $stmt->close();
            }
        }
    } elseif (isset($_POST['update_category']) && isset($_POST['category_id'])) {
        $category_id = intval($_POST['category_id']);
        
        // Validate name
        if (empty(trim($_POST["edit_name"]))) {
            display_message("Category name cannot be empty.", "danger");
        } else {
            // Check if category name already exists (except for this category)
            $sql = "SELECT id FROM event_categories WHERE name = ? AND id != ?";
            
            if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("si", $param_name, $param_id);
                
                // Set parameters
                $param_name = trim($_POST["edit_name"]);
                $param_id = $category_id;
                
                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    // Store result
                    $stmt->store_result();
                    
                    if ($stmt->num_rows > 0) {
                        display_message("This category name already exists.", "danger");
                    } else {
                        // Update the category
                        $update_sql = "UPDATE event_categories SET name = ?, description = ? WHERE id = ?";
                        
                        if ($update_stmt = $conn->prepare($update_sql)) {
                            // Bind variables to the prepared statement as parameters
                            $update_stmt->bind_param("ssi", $param_name, $param_description, $param_id);
                            
                            // Set parameters
                            $param_name = trim($_POST["edit_name"]);
                            $param_description = !empty($_POST["edit_description"]) ? trim($_POST["edit_description"]) : NULL;
                            $param_id = $category_id;
                            
                            // Attempt to execute the prepared statement
                            if ($update_stmt->execute()) {
                                display_message("Category updated successfully!", "success");
                            } else {
                                display_message("Error updating category. Please try again.", "danger");
                            }
                            
                            // Close statement
                            $update_stmt->close();
                        }
                    }
                } else {
                    display_message("Oops! Something went wrong. Please try again later.", "danger");
                }
                
                // Close statement
                $stmt->close();
            }
        }
    } elseif (isset($_POST['delete_category']) && isset($_POST['category_id'])) {
        $category_id = intval($_POST['category_id']);
        
        // Check if category has events
        $check_sql = "SELECT COUNT(*) as event_count FROM events WHERE category_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $event_count = $check_result->fetch_assoc()['event_count'];
        
        if ($event_count > 0) {
            display_message("Cannot delete category because it has $event_count events associated with it.", "danger");
        } else {
            // Delete the category
            $delete_sql = "DELETE FROM event_categories WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $category_id);
            
            if ($delete_stmt->execute()) {
                display_message("Category deleted successfully!", "success");
            } else {
                display_message("Error deleting category. Please try again.", "danger");
            }
            
            $delete_stmt->close();
        }
    }
}

// Get all categories
$query = "SELECT c.*, COUNT(e.id) as event_count 
         FROM event_categories c
         LEFT JOIN events e ON c.id = e.category_id
         GROUP BY c.id
         ORDER BY c.name ASC";
$result = $conn->query($query);
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Manage Event Categories</h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Add New Category</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo $name; ?>">
                        <div class="invalid-feedback"><?php echo $name_err; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $description; ?></textarea>
                    </div>
                    
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Events</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($category = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td><?php echo $category['event_count']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $category['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($category['event_count'] == 0): ?>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="Cannot delete category with events">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $category['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $category['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?php echo $category['id']; ?>">Edit Category</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                            <div class="modal-body">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="edit_name<?php echo $category['id']; ?>" class="form-label">Category Name</label>
                                                    <input type="text" class="form-control" id="edit_name<?php echo $category['id']; ?>" name="edit_name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="edit_description<?php echo $category['id']; ?>" class="form-label">Description (Optional)</label>
                                                    <textarea class="form-control" id="edit_description<?php echo $category['id']; ?>" name="edit_description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No categories found.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 