<?php
require_once __DIR__ . '/../includes/nav.php';
$pageTitle = 'Doctor Dashboard';
$userRole = 'doctor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f8fafc; color: #0f172a; }
        .page-wrapper { max-width: 1080px; margin: 0 auto; padding: 1.5rem; }
        .dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .card { background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card h3 { margin-top: 0; color: #1e293b; }
    </style>
</head>
<body>
    <?php renderNavigationMenuWithStyles('home'); ?>
    <div class="page-wrapper">
        <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="dashboard">
            <div class="card">
                <h3>Today's Appointments</h3>
                <p>View and manage today's patient appointments.</p>
                <a href="#">View Appointments</a>
            </div>
            <div class="card">
                <h3>Patient Messages</h3>
                <p>Check messages from patients.</p>
                <a href="#">View Messages</a>
            </div>
            <div class="card">
                <h3>Medical Reports</h3>
                <p>Access patient medical reports.</p>
                <a href="#">View Reports</a>
            </div>
        </div>
    </div>
</body>
</html>