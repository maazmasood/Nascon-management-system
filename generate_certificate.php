<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Require login
require_login();

// Check if certificate ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: my_certificates.php");
    exit();
}

$certificate_id = intval($_GET['id']);

// Get certificate and related information
$query = "SELECT c.*, r.id as registration_id, r.status, r.user_id,
          e.title as event_title, e.start_date, e.end_date,
          ec.name as category_name,
          CONCAT(u.first_name, ' ', u.last_name) as user_name
          FROM certificates c
          JOIN event_registrations r ON c.registration_id = r.id
          JOIN events e ON r.event_id = e.id
          JOIN event_categories ec ON e.category_id = ec.id
          JOIN users u ON r.user_id = u.id
          WHERE c.id = ? AND r.status = 'attended'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $certificate_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if certificate exists and belongs to the logged-in user
if ($result->num_rows == 0) {
    header("Location: my_certificates.php");
    exit();
}

$certificate = $result->fetch_assoc();

// Verify that the certificate belongs to the logged-in user
if ($certificate['user_id'] != $_SESSION['user_id']) {
    header("Location: my_certificates.php");
    exit();
}

// Generate a simple certificate as plain text (without FPDF)
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="Certificate_' . $certificate['event_title'] . '.txt"');

// Basic certificate content
echo "=================================================================\n";
echo "                 CERTIFICATE OF PARTICIPATION\n";
echo "=================================================================\n\n";
echo "This certificate is presented to:\n\n";
echo "                    " . $certificate['user_name'] . "\n\n";
echo "For attending the event:\n\n";
echo "                  " . $certificate['event_title'] . "\n\n";
echo "Held on: " . date('F d, Y', strtotime($certificate['start_date'])) . "\n";
echo "Category: " . $certificate['category_name'] . "\n\n";
echo "Certificate ID: FAST-CERT-" . sprintf('%05d', $certificate_id) . "\n";
echo "Issue Date: " . date('F d, Y', strtotime($certificate['issued_date'])) . "\n\n";
echo "=================================================================\n";
echo "                    FAST University\n";
echo "=================================================================\n";
?> 