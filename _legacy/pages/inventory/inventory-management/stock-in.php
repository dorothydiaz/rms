<?php
require_once __DIR__ . '/../../../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In / Receiving - Restaurant Management System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/styles.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Server-side Sidebar Include -->
        <?php include __DIR__ . '/../../include/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
                        <header class="main-header">
                <div class="header-left">
                    <?php include ROOT_PATH . 'pages/include/breadcrumbs.php'; ?>
                </div>
                <div class="header-actions">
                    <button class="icon-btn notification-btn" title="Notifications">
                        <i class="ph ph-bell"></i>
                    </button>
                    <button class="mobile-menu-btn">
                        <i class="ph ph-list"></i>
                    </button>
                </div>
            </header>
            <div class="content-area">
                <p>Stock In / Receiving content goes here.</p>
            </div>
        </main>
    </div>
    <script src="<?= BASE_URL ?>assets/js/script.js"></script>
</body>
</html>