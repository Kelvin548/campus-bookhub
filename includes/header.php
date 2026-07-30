<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Campus BookHub'); ?></title>

    <!-- Preconnect Google Fonts -->
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="apple-touch-icon" href="/assets/images/icon.jpeg">
    <link rel="icon" href="/assets/images/icon.jpeg">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Core Theme Variables & Utilities -->
    <style>
        :root {
            --wine-main: #6b1d2f;
            --wine-dark: #2e0811;
            --wine-light: #8e2b43;
            --wine-gradient: linear-gradient(135deg, #6b1d2f 0%, #2e0811 100%);
            --wine-gradient-card: linear-gradient(145deg, #7c2237 0%, #420b17 100%);
            --sidebar-bg: #1f050b;
            --font-family: 'Plus Jakarta Sans', sans-serif;
            --cb-navbar-height: 70px;
        }

        body {
            font-family: var(--font-family);
            background-color: #f6f3f4;
            color: #2b2b2b;
        }

        /* Utility Color Classes */
        .text-wine { color: var(--wine-main) !important; }
        .bg-wine { background-color: var(--wine-main) !important; }
        .bg-wine-gradient { background: var(--wine-gradient) !important; }
        .border-wine { border-color: var(--wine-main) !important; }

        /* Global Wine Primary Button Style */
        .btn-wine {
            background: var(--wine-gradient);
            color: #ffffff;
            border: none;
            transition: all 0.25s ease;
        }

        .btn-wine:hover {
            background: linear-gradient(135deg, #7c2237 0%, #3d0a17 100%);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(107, 29, 47, 0.35);
        }

        .btn-wine:focus, .btn-wine:active {
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(107, 29, 47, 0.25);
        }
    </style>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo defined('BASE_URL') ? BASE_URL : '../'; ?>assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">