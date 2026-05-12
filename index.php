<?php
require_once __DIR__ . '/includes/nav.php';
$pageTitle = 'Maraki Consultancy Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .page-wrapper {
            max-width: 1080px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .hero {
            padding: 2rem 0;
        }
        .hero h1 {
            margin-top: 0;
            font-size: 2.5rem;
            line-height: 1.1;
        }
        .hero p {
            font-size: 1.05rem;
            color: #475569;
            max-width: 720px;
        }
    </style>
</head>
<body>
    <?php renderNavigationMenuWithStyles('home'); ?>
    <div class="page-wrapper">
        <section class="hero">
            <h1>Welcome to Maraki Consultancy Management</h1>
            <p>Use the modular navigation menu component to keep your site layout consistent across all pages. Add this menu to every page using <code>require_once 'includes/nav.php'</code>.</p>
        </section>
    </div>
</body>
</html>
