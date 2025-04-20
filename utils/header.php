<header class="bgImage">
    <nav class="navbar">
        <div class="container">
            <div class="navbar-header">
              
                <?php 
                require_once 'utils/functions.php';
                echo '<a href="index.php" class="navbar-brand">Nason Management System</a>';
                ?>
            </div>

            <ul class="nav navbar-nav navbar-right">
                <!-- Navigation -->
                <?php 
                if (is_logged_in()) {
                    echo '<li><a href="index.php">Home</a></li>';
                    echo '<li><a href="viewEvents.php">Events</a></li>';
                    echo '<li><a href="viewLocations.php">Locations</a></li>';
                    echo '<li><a href="contact.php">Contact Us</a></li>';
                    echo '<li class="btnlogout">
                            <a class="btn btn-default navbar-btn" href="logout.php">
                                Logout <span class="glyphicon glyphicon-log-out"></span>
                            </a>
                          </li>';
                } else {
                    echo '<li><a href="index.php">Home</a></li>';
                    echo '<li><a href="events2.php">Events</a></li>';
                    echo '<li><a href="locations2.php">Locations</a></li>';
                    echo '<li><a href="contact.php">Contact Us</a></li>';
                    echo '<button type="button" class="btn btn-default navbar-btn" data-toggle="modal" data-target="#login">
                            Login <span class="glyphicon glyphicon-log-in"></span>
                          </button>';
                }
                ?>

                <!-- Modal for login -->
                <div class="modal fade" id="login" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <!-- Modal header -->
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="myModalLabel">Login</h4>
                            </div>
                            <div class="row">
                                <div class="modal-body">
                                    <!-- Modal content -->
                                    <div class="col-md-6 col-md-offset-3">
                                        <form action="login.php" method="POST">
                                            <!-- Username -->
                                            <div class="form-group">
                                                <label for="username">Username:</label>
                                                <input type="text" name="username" class="form-control"
                                                    value="<?php if (isset($formdata['username'])) echo $formdata['username']; ?>" />
                                                <span class="error">
                                                    <?php if (isset($errors['username'])) echo $errors['username']; ?>
                                                </span>
                                            </div>

                                            <!-- Password -->
                                            <div class="form-group">
                                                <label for="password">Password:</label>
                                                <input type="password" name="password" class="form-control" value="" />
                                                <span class="error">
                                                    <?php if (isset($errors['password'])) echo $errors['password']; ?>
                                                </span>
                                            </div>

                                            <!-- Login + Register buttons -->
                                            <button type="submit" class="btn btn-default loginbtn">Login</button>
                                            <a class="btn btn-default navbar-btn rgsterbtn" href="register_form.php">Register</a>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <!-- Modal footer -->
                                <button type="button" class="btn btn-default closebtn" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </ul>
        </div>
    </nav>

   <!-- Jumbotron -->
<div class="col-md-12">
    <div class="container">
        <div class="jumbotron">
            <h1>Nascon Management System</h1>
            <p>
                Whether you're looking to streamline project management, optimize team collaboration, or ensure efficient workflow across various departments, the Nascon Management System offers a comprehensive solution tailored to meet all your business needs.
            </p>
            <p id="dateAndTime"></p>
        </div>
    </div>
</div>

</header>
