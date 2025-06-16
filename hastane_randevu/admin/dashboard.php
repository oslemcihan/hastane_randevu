<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';

// Yetki kontrolü
checkRole('admin');

try {
    // İstatistikler için sorgular
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM hastalar) as total_patients,
        (SELECT COUNT(*) FROM doktorlar) as total_doctors";
    
    $stats_result = $pdo->query($stats_query);
    $stats = $stats_result->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Veritabanı hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .menu-links {
            margin: 20px 0;
            padding: 0;
            list-style: none;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .menu-links ul {
            list-style: none;
            padding: 15px;
            margin: 0;
            display: flex;
            gap: 10px;
        }

        .menu-links li {
            margin: 0;
        }

        .menu-links a {
            display: block;
            padding: 12px 20px;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .menu-links a:hover {
            background-color: #f0f0f0;
        }

        .menu-links a.active {
            background-color: #3498db;
            color: white;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 500;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 600;
            color: #3498db;
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Paneli</h1>
            <nav>
                <span class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></span>
                <a href="../logout.php" class="btn">Çıkış</a>
            </nav>
        </header>

        <div class="menu-links">
            <ul>
                <li><a href="dashboard.php" class="active">Ana Sayfa</a></li>
                <li><a href="doktorlar.php">Doktor Yönetimi</a></li>
                <li><a href="hastalar.php">Hasta Yönetimi</a></li>
            </ul>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Toplam Hasta</h3>
                <div class="number"><?php echo $stats['total_patients']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Toplam Doktor</h3>
                <div class="number"><?php echo $stats['total_doctors']; ?></div>
            </div>
        </div>
    </div>
</body>
</html>
