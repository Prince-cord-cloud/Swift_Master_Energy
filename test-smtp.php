<?php
/**
 * SMTP Connection Test
 * Use this to verify your credentials work
 */

header('Content-Type: text/html; charset=UTF-8');

$config = include('config.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>SMTP Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .result { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h2>🔍 SMTP Connection Test</h2>";

// Check configuration
echo "<div class='result'>";
if ($config['smtp_password'] === 'YOUR_WEBMAIL_PASSWORD_HERE') {
    echo "<div class='error'>❌ <strong>Error:</strong> Password not configured in config.php</div>";
    echo "<p>Go to config.php and update the <code>smtp_password</code> with your actual email password.</p>";
} else {
    echo "<div class='success'>✅ Password is configured</div>";
}
echo "</div>";

// Display current configuration (without showing password)
echo "<div class='result warning'>";
echo "<h3>Current Configuration:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Host:</strong> <code>" . $config['smtp_host'] . "</code></li>";
echo "<li><strong>SMTP Port:</strong> <code>" . $config['smtp_port'] . "</code></li>";
echo "<li><strong>SMTP Encryption:</strong> <code>" . $config['smtp_encryption'] . "</code></li>";
echo "<li><strong>Username:</strong> <code>" . $config['smtp_username'] . "</code></li>";
echo "<li><strong>From Email:</strong> <code>" . $config['from_email'] . "</code></li>";
echo "<li><strong>To Email:</strong> <code>" . $config['to_email'] . "</code></li>";
echo "</ul>";
echo "<p><strong>Note:</strong> Password is hidden for security.</p>";
echo "</div>";

// Test connection
echo "<div class='result'>";
echo "<h3>Connection Test:</h3>";

try {
    $encryption = ($config['smtp_port'] == 465) ? 'ssl://' : '';
    $socket = @fsockopen($encryption . $config['smtp_host'], $config['smtp_port'], $errno, $errstr, 10);

    if ($socket) {
        echo "<div class='success'>✅ Connected to SMTP server successfully</div>";

        // Read response
        $response = fgets($socket, 512);
        echo "<p><strong>Server Response:</strong> <code>" . htmlspecialchars($response) . "</code></p>";

        // Try EHLO
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 512);

        // Try AUTH
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 512);

        fputs($socket, base64_encode($config['smtp_username']) . "\r\n");
        fgets($socket, 512);

        fputs($socket, base64_encode($config['smtp_password']) . "\r\n");
        $authResponse = fgets($socket, 512);

        if (strpos($authResponse, '235') !== false || strpos($authResponse, '250') !== false) {
            echo "<div class='success'>✅ Authentication successful</div>";
        } else {
            echo "<div class='error'>❌ Authentication failed</div>";
            echo "<p><strong>Response:</strong> <code>" . htmlspecialchars($authResponse) . "</code></p>";
            echo "<p>Check that your email and password are correct in config.php</p>";
        }

        fputs($socket, "QUIT\r\n");
        fclose($socket);
    } else {
        echo "<div class='error'>❌ Could not connect to SMTP server</div>";
        echo "<p><strong>Error:</strong> $errstr ($errno)</p>";
        echo "<p><strong>Check:</strong></p>";
        echo "<ul>";
        echo "<li>Is the SMTP host correct? (from cPanel → Email Accounts → Connect Devices)</li>";
        echo "<li>Is the SMTP port correct? (usually 465 for SSL or 587 for TLS)</li>";
        echo "<li>Can your server reach external SMTP servers?</li>";
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// View logs
echo "<div class='result'>";
echo "<h3>Recent Email Logs:</h3>";
if (file_exists($config['log_file'])) {
    $logs = file_get_contents($config['log_file']);
    echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($logs) . "</pre>";
} else {
    echo "<p>No logs yet. Submit a form to generate logs.</p>";
}
echo "</div>";

echo "</body></html>";
?>
