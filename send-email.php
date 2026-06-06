<?php
/**
 * Email Handler Script
 * Sends emails via SMTP using cPanel webmail credentials
 */

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Load configuration
$config = include('config.php');

// Validate configuration
if (empty($config['smtp_password']) || $config['smtp_password'] === 'YOUR_WEBMAIL_PASSWORD_HERE') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Email service not configured. Please update config.php with your SMTP credentials.']);
    exit;
}

// Get and sanitize form data
$name = sanitizeInput($_POST['name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$message = sanitizeInput($_POST['message'] ?? '');

// Validate required fields
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields (Name, Email, Message)']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

// Build email content
$timestamp = date('Y-m-d H:i:s');
$subject = "New Solar Inquiry from $name";

$body = buildEmailBody($name, $email, $phone, $message, $timestamp);

// Send email
$success = sendEmailViaSMTP(
    $config['smtp_host'],
    $config['smtp_port'],
    $config['smtp_username'],
    $config['smtp_password'],
    $config['smtp_encryption'],
    $config['from_email'],
    $config['from_name'],
    $config['to_email'],
    $subject,
    $body
);

if ($success) {
    // Send confirmation email to customer
    $confirmSubject = "We received your inquiry - Swift Master Energy";
    $confirmBody = buildConfirmationEmail($name);

    sendEmailViaSMTP(
        $config['smtp_host'],
        $config['smtp_port'],
        $config['smtp_username'],
        $config['smtp_password'],
        $config['smtp_encryption'],
        $config['from_email'],
        $config['from_name'],
        $email,
        $confirmSubject,
        $confirmBody
    );

    logEmail($config, $name, $email, 'SUCCESS');
    echo json_encode(['success' => true, 'message' => 'Your inquiry has been sent successfully! We will respond within 24 hours.']);
} else {
    logEmail($config, $name, $email, 'FAILED');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please check your configuration or try again later.']);
}

// ===== HELPER FUNCTIONS =====

function sanitizeInput($input) {
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

function buildEmailBody($name, $email, $phone, $message, $timestamp) {
    $body = "SWIFT MASTER ENERGY – NEW LEAD INQUIRY\n";
    $body .= "═══════════════════════════════════════\n\n";

    $body .= "DATE: " . $timestamp . "\n";
    $body .= "─────────────────────────────────────────\n\n";

    $body .= "CUSTOMER DETAILS:\n";
    $body .= "• Name: " . $name . "\n";
    $body .= "• Email: " . $email . "\n";
    $body .= "• Phone: " . ($phone ?: "Not provided") . "\n\n";

    $body .= "MESSAGE:\n";
    $body .= $message . "\n\n";

    $body .= "─────────────────────────────────────────\n";
    $body .= "ACTION REQUIRED:\n";
    $body .= "The customer is requesting pricing and technical specifications.\n";
    $body .= "Please respond as soon as possible.\n";
    $body .= "─────────────────────────────────────────\n\n";

    $body .= "Source: swiftmasterenergy.com\n";

    return $body;
}

function buildConfirmationEmail($name) {
    $body = "Hi $name,\n\n";
    $body .= "Thank you for reaching out to Swift Master Energy!\n\n";
    $body .= "We've received your inquiry and will review it shortly. ";
    $body .= "One of our solar energy specialists will get back to you within 24 hours with detailed information and pricing.\n\n";
    $body .= "In the meantime, you can:\n";
    $body .= "• Visit our website for more details: swiftmasterenergy.com\n";
    $body .= "• Call us: +234 800 987 6543\n";
    $body .= "• WhatsApp us: +234 706 994 3790\n\n";
    $body .= "Best regards,\n";
    $body .= "Swift Master Energy Team\n";
    $body .= "Powering Your Renewable Future\n";

    return $body;
}

function sendEmailViaSMTP($host, $port, $username, $password, $encryption, $from, $fromName, $to, $subject, $body) {
    try {
        // Create socket connection
        $socket = @fsockopen(($encryption === 'ssl' ? 'ssl://' : '') . $host, $port, $errno, $errstr, 10);

        if (!$socket) {
            error_log("SMTP Connection Error: $errstr ($errno)");
            return false;
        }

        // Read initial response
        $response = fgets($socket, 512);
        if (strpos($response, '220') === false) {
            fclose($socket);
            return false;
        }

        // Send EHLO
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 512);

        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 512);

        fputs($socket, base64_encode($username) . "\r\n");
        fgets($socket, 512);

        fputs($socket, base64_encode($password) . "\r\n");
        $response = fgets($socket, 512);

        if (strpos($response, '235') === false && strpos($response, '250') === false) {
            error_log("SMTP Auth Failed: $response");
            fclose($socket);
            return false;
        }

        // Send FROM
        fputs($socket, "MAIL FROM:<" . $from . ">\r\n");
        fgets($socket, 512);

        // Send TO
        fputs($socket, "RCPT TO:<" . $to . ">\r\n");
        fgets($socket, 512);

        // Send DATA
        fputs($socket, "DATA\r\n");
        fgets($socket, 512);

        // Build headers with better delivery rates
        $headers = "From: " . $fromName . " <" . $from . ">\r\n";
        $headers .= "Reply-To: " . $from . "\r\n";
        $headers .= "Return-Path: " . $from . "\r\n";
        $headers .= "To: " . $to . "\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "X-Mailer: Swift Master Energy Contact Form\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "\r\n";

        // Send complete email
        fputs($socket, $headers . $body . "\r\n.\r\n");
        $response = fgets($socket, 512);

        // Close connection
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return (strpos($response, '250') !== false);

    } catch (Exception $e) {
        error_log("Email Send Error: " . $e->getMessage());
        return false;
    }
}

function logEmail($config, $name, $email, $status) {
    if (!$config['enable_logging']) {
        return;
    }

    $logEntry = date('Y-m-d H:i:s') . " | " . $status . " | " . $name . " (" . $email . ")\n";
    @file_put_contents($config['log_file'], $logEntry, FILE_APPEND);
}
?>
