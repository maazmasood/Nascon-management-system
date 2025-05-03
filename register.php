<?php
require_once 'includes/header.php';

// Initialize variables
$email = $password = $confirm_password = $first_name = $last_name = $student_id = $phone = "";
$email_err = $password_err = $confirm_password_err = $first_name_err = $last_name_err = "";
$role_id = 3; // Default to student role

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Check if email is valid
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Prepare a select statement
            $sql = "SELECT id FROM users WHERE email = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("s", $param_email);
                
                // Set parameters
                $param_email = trim($_POST["email"]);
                
                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    // Store result
                    $stmt->store_result();
                    
                    if ($stmt->num_rows == 1) {
                        $email_err = "This email is already taken.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                
                // Close statement
                $stmt->close();
            }
        }
    }
    
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
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // Get additional fields
    $student_id = isset($_POST["student_id"]) ? trim($_POST["student_id"]) : null;
    $phone = isset($_POST["phone"]) ? trim($_POST["phone"]) : null;
    $role_id = isset($_POST["role_id"]) ? intval($_POST["role_id"]) : 3; // Default to student
    
    // Check if email domain is fast.edu.pk for students and organizers
    if (($role_id == 2 || $role_id == 3) && !preg_match('/@fast\.edu\.pk$/', $email)) {
        $email_err = "FAST email domain required for students and organizers.";
    }

    // Check input errors before inserting into database
    if (empty($email_err) && empty($password_err) && empty($confirm_password_err) && 
        empty($first_name_err) && empty($last_name_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (email, password, first_name, last_name, role_id, student_id, phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sssssss", $param_email, $param_password, $param_first_name, 
                            $param_last_name, $param_role_id, $param_student_id, $param_phone);
            
            // Set parameters
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_first_name = $first_name;
            $param_last_name = $last_name;
            $param_role_id = $role_id;
            $param_student_id = $student_id;
            $param_phone = $phone;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to login page
                display_message("Registration successful! You can now login.", "success");
                header("location: login.php");
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            $stmt->close();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-lg border-0 rounded-lg mt-5">
            <div class="card-header">
                <h3 class="text-center font-weight-light my-4">Create Account</h3>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" id="first_name" type="text" name="first_name" placeholder="First Name" value="<?php echo $first_name; ?>" />
                                <label for="first_name">First name</label>
                                <div class="invalid-feedback"><?php echo $first_name_err; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" id="last_name" type="text" name="last_name" placeholder="Last Name" value="<?php echo $last_name; ?>" />
                                <label for="last_name">Last name</label>
                                <div class="invalid-feedback"><?php echo $last_name_err; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-floating mb-3">
                        <input class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" type="email" name="email" placeholder="name@example.com" value="<?php echo $email; ?>" />
                        <label for="email">Email address</label>
                        <div class="invalid-feedback"><?php echo $email_err; ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="password" type="password" name="password" placeholder="Create a password" />
                                <label for="password">Password</label>
                                <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" id="confirm_password" type="password" name="confirm_password" placeholder="Confirm password" />
                                <label for="confirm_password">Confirm Password</label>
                                <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-floating mb-3">
                        <select class="form-select" id="role_id" name="role_id">
                            <option value="3" <?php echo ($role_id == 3) ? 'selected' : ''; ?>>Student</option>
                            <option value="4" <?php echo ($role_id == 4) ? 'selected' : ''; ?>>Outsider</option>
                            <option value="2" <?php echo ($role_id == 2) ? 'selected' : ''; ?>>Organizer</option>
                        </select>
                        <label for="role_id">Account Type</label>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input class="form-control" id="student_id" type="text" name="student_id" placeholder="Student ID" value="<?php echo $student_id; ?>" />
                                <label for="student_id">Student ID (if applicable)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input class="form-control" id="phone" type="text" name="phone" placeholder="Phone Number" value="<?php echo $phone; ?>" />
                                <label for="phone">Phone Number (optional)</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 mb-0">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <div class="small"><a href="login.php">Have an account? Go to login</a></div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 