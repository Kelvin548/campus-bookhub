<?php
// includes/db.php

$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
$user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$pass = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '');
$db   = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'campus_bookhub');
$port = (int)(getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: 3306));

$conn = null;
$max_retries = 5;
$retry_delay = 2; // seconds

for ($i = 0; $i < $max_retries; $i++) {
    // The '@' sign suppresses the raw warning so we can catch it cleanly
    $conn = @new mysqli($host, $user, $pass, $db, $port);
    
    if ($conn && !$conn->connect_error) {
        break; // Success! Exit the retry loop
    }
    
    if ($i < $max_retries - 1) {
        sleep($retry_delay);
    }
}

// If connection still fails after retries, show the professional loading screen with auto-refresh
if (!$conn || $conn->connect_error) {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Campus BookHub - Warming Up Server</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
                background-color: #f6f3f4;
                color: #2b2b2b;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .loader-box {
                background: #ffffff;
                padding: 2.5rem;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.08);
                text-align: center;
                max-width: 400px;
                width: 90%;
            }
            .spinner {
                width: 48px;
                height: 48px;
                border: 4px solid #f3e4e6;
                border-top: 4px solid #6b1d2f;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 1.5rem auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            h2 { font-size: 1.25rem; color: #2e0811; margin-bottom: 0.5rem; }
            p { font-size: 0.88rem; color: #666; line-height: 1.5; margin-bottom: 1.5rem; }
            .btn-retry {
                background: #6b1d2f;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
        </style>
        <!-- Auto-refresh page every 4 seconds until the database wakes up -->
        <script>
            setTimeout(function() {
                location.reload();
            }, 4000);
        </script>
    </head>
    <body>
        <div class="loader-box">
            <div class="spinner"></div>
            <h2>Warming Up Server...</h2>
            <p>Our secure cloud database is waking up from sleep mode. This will only take a few seconds. The page will reload automatically.</p>
            <a href="" class="btn-retry">Refresh Now</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$conn->set_charset("utf8mb4");
?>