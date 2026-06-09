<?php
/**
 * SMTP Configuration File
 * ========================
 * Update these credentials with your cPanel webmail account details
 *
 * HOW TO FIND YOUR CREDENTIALS:
 * 1. Log in to cPanel
 * 2. Go to "Email Accounts"
 * 3. Find your email account and click "Connect Devices"
 * 4. Look for "SMTP Server" - this is your smtp_host
 * 5. SMTP Port is usually 465 (SSL) or 587 (TLS)
 * 6. Username is your full email address
 * 7. Password is your email account password
 */

return [
    // ===== SMTP SERVER SETTINGS =====
    'smtp_host' => 'mail.bestswiftservices.com',    // e.g., mail.yourdomain.com or smtp.yourdomain.com
    'smtp_port' => 465,                              // Use 465 for SSL, 587 for TLS
    'smtp_encryption' => 'ssl',                      // 'ssl' for port 465, 'tls' for port 587

    // ===== EMAIL CREDENTIALS =====
    'smtp_username' => 'info@bestswiftservices.com', // Your full email address
    'smtp_password' => 'YOUR_WEBMAIL_PASSWORD_HERE', // Your email password

    // ===== EMAIL ADDRESSES =====
    'from_email' => 'info@bestswiftservices.com',    // Sender email (usually same as smtp_username)
    'from_name' => 'Swift Master Energy',             // Display name for emails
    'to_email' => 'info@bestswiftservices.com',      // Where to receive customer inquiries
    'cc_email' => '',                                 // Optional: CC another email (leave empty if not needed)

    // ===== SECURITY =====
    'enable_logging' => true,                        // Log email send attempts (for debugging)
    'log_file' => 'email_logs.txt',                  // Log file location
];
?>
