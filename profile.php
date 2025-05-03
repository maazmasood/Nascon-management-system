<?php
require_once 'includes/header.php';

// Require login
require_login();

// Initialize variables
$email = $first_name = $last_name = $phone = $student_id = "";
$email_err = $first_name_err = $last_name_err = $phone_err = $student_id_err = $password_err = $current_password_err = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Validate first name
        if (empty(trim($_POST["first_name"]))) {
            $first_name_err = "Please enter your first name.";
        } else {
            $first_name = trim($_POST["first_name"]);
        }
        
        // Validate last name
        if (empty(trim($_POST["last_name"]))) {
            $last_name_err = "Please enter your last name.";
        } else {
            $last_name = trim($_POST["last_name"]);
        }
        
        // Validate phone (optional)
        $phone = !empty($_POST["phone"]) ? trim($_POST["phone"]) : NULL;
        
        // Validate student ID (required for students and organizers)
        if (is_student() || is_organizer()) {
            if (empty(trim($_POST["student_id"]))) {
                $student_id_err = "Please enter your student ID.";
            } else {
                $student_id = trim($_POST["student_id"]);
            }
        } else {
            $student_id = !empty($_POST["student_id"]) ? trim($_POST["student_id"]) : NULL;
        }
        
        // Check input errors before updating the database
        if (empty($first_name_err) && empty($last_name_err) && empty($student_id_err)) {
            // Prepare an update statement
            $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, student_id = ? WHERE id = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("ssssi", $param_first_name, $param_last_name, $param_phone, $param_student_id, $param_id);
                
                // Set parameters
                $param_first_name = $first_name;
                $param_last_name = $last_name;
                $param_phone = $phone;
                $param_student_id = $student_id;
                $param_id = $_SESSION["user_id"];
                
                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    // Update session variables
                    $_SESSION["first_name"] = $first_name;
                    $_SESSION["last_name"] = $last_name;
                    
                    display_message("Profile updated successfully!", "success");
                } else {
                    display_message("Oops! Something went wrong. Please try again later.", "danger");
                }
                
                // Close statement
                $stmt->close();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Validate current password
        if (empty(trim($_POST["current_password"]))) {
            $current_password_err = "Please enter your current password.";
        } else {
            // Verify current password
            $sql = "SELECT password FROM users WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $_SESSION["user_id"]);
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($hashed_password);
                        if ($stmt->fetch()) {
                            if (!password_verify(trim($_POST["current_password"]), $hashed_password)) {
                                $current_password_err = "Current password is incorrect.";
                            }
                        }
                    }
                }
                $stmt->close();
            }
        }
        
        // Validate new password
        if (empty(trim($_POST["new_password"]))) {
            $password_err = "Please enter a new password.";     
        } elseif (strlen(trim($_POST["new_password"])) < 6) {
            $password_err = "Password must have at least 6 characters.";
        } elseif (trim($_POST["new_password"]) == trim($_POST["current_password"])) {
            $password_err = "New password cannot be the same as current password.";
        } else {
            $new_password = trim($_POST["new_password"]);
        }
        
        // Validate confirm password
        if (empty(trim($_POST["confirm_password"]))) {
            $confirm_password_err = "Please confirm password.";     
        } else {
            $confirm_password = trim($_POST["confirm_password"]);
            if (empty($password_err) && ($new_password != $confirm_password)) {
                $confirm_password_err = "Password did not match.";
            }
        }
        
        // Check input errors before updating the database
        if (empty($current_password_err) && empty($password_err) && empty($confirm_password_err)) {
            // Prepare an update statement
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("si", $param_password, $param_id);
                
                // Set parameters
                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                $param_id = $_SESSION["user_id"];
                
                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    display_message("Password changed successfully!", "success");
                } else {
                    display_message("Oops! Something went wrong. Please try again later.", "danger");
                }
                
                // Close statement
                $stmt->close();
            }
        }
    }
} else {
    // Get user data
    $sql = "SELECT email, first_name, last_name, phone, student_id, role_id FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $_SESSION["user_id"]);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($email, $first_name, $last_name, $phone, $student_id, $role_id);
                $stmt->fetch();
            }
        }
        $stmt->close();
    }
}

// Get user stats
$sql = "SELECT 
        (SELECT COUNT(*) FROM event_registrations WHERE user_id = ?) as total_registrations,
        (SELECT COUNT(*) FROM event_registrations WHERE user_id = ? AND status = 'attended') as attended_events,
        (SELECT COUNT(*) FROM certificates c 
         JOIN event_registrations r ON c.registration_id = r.id 
         WHERE r.user_id = ?) as certificates";

$stats = array('total_registrations' => 0, 'attended_events' => 0, 'certificates' => 0);

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("iii", $_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]);
    if ($stmt->execute()) {
        $stmt->bind_result($stats['total_registrations'], $stats['attended_events'], $stats['certificates']);
        $stmt->fetch();
    }
    $stmt->close();
}
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">My Profile</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Account Information</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h5><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($email); ?></p>
                <p>
                    <span class="badge bg-primary">
                        <?php 
                        if (is_admin()) echo "Admin";
                        elseif (is_organizer()) echo "Organizer";
                        elseif (is_student()) echo "Student";
                        else echo "Outsider";
                        ?>
                    </span>
                </p>
                <hr>
                <div class="row text-center">
                    <div class="col-4">
                        <h5><?php echo $stats['total_registrations']; ?></h5>
                        <small>Registrations</small>
                    </div>
                    <div class="col-4">
                        <h5><?php echo $stats['attended_events']; ?></h5>
                        <small>Events Attended</small>
                    </div>
                    <div class="col-4">
                        <h5><?php echo $stats['certificates']; ?></h5>
                        <small>Certificates</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Edit Profile</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo $first_name; ?>">
                            <div class="invalid-feedback"><?php echo $first_name_err; ?></div>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo $last_name; ?>">
                            <div class="invalid-feedback"><?php echo $last_name_err; ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo $email; ?>" disabled>
                        <div class="form-text">Email cannot be changed. Contact administrator if needed.</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_id" class="form-label">Student ID <?php echo (is_student() || is_organizer()) ? '(Required)' : '(Optional)'; ?></label>
                            <input type="text" class="form-control <?php echo (!empty($student_id_err)) ? 'is-invalid' : ''; ?>" id="student_id" name="student_id" value="<?php echo $student_id; ?>">
                            <div class="invalid-feedback"><?php echo $student_id_err; ?></div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone (Optional)</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>">
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" id="current_password" name="current_password">
                        <div class="invalid-feedback"><?php echo $current_password_err; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="new_password" name="new_password">
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                        <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-danger">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 