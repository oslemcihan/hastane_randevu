<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';

// Yetki kontrolü
checkRole('admin');

// Hasta silme işlemi
if (isset($_POST['delete_patient']) && isset($_POST['patient_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM hastalar WHERE id = ?");
        $stmt->execute([$_POST['patient_id']]);
        $success_message = "Hasta başarıyla silindi.";
    } catch(PDOException $e) {
        $error_message = "Hasta silinirken bir hata oluştu.";
    }
}

// Hastaları getir
$hastalar = [];

try {
    // Hastaları getir
    $hastalar_query = "SELECT h.*, 
                             COUNT(r.id) as randevu_sayisi,
                             MAX(r.tarih) as son_randevu_tarihi
                      FROM hastalar h
                      LEFT JOIN randevular r ON h.id = r.hasta_id
                      GROUP BY h.id
                      ORDER BY h.ad, h.soyad";
    $hastalar = $pdo->query($hastalar_query)->fetchAll();
} catch(PDOException $e) {
    error_log("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Yönetimi - Admin Paneli</title>
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

        .content-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 20px 0;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 500;
            color: #2c3e50;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #c0392b;
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
            <h1>Hasta Yönetimi</h1>
            <nav>
                <span class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></span>
                <a href="../logout.php" class="btn">Çıkış</a>
            </nav>
        </header>

        <div class="menu-links">
            <ul>
                <li><a href="dashboard.php">Ana Sayfa</a></li>
                <li><a href="doktorlar.php">Doktor Yönetimi</a></li>
                <li><a href="hastalar.php" class="active">Hasta Yönetimi</a></li>
            </ul>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="content-container">
            <a href="hasta_ekle.php" class="btn-primary">Yeni Hasta Ekle</a>

            <table>
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>TC Kimlik No</th>
                        <th>Telefon</th>
                        <th>Email</th>
                        <th>Toplam Randevu</th>
                        <th>Son Randevu</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($hastalar)): ?>
                        <?php foreach ($hastalar as $hasta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hasta['ad'] . ' ' . $hasta['soyad']); ?></td>
                                <td><?php echo htmlspecialchars($hasta['tc_no'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($hasta['telefon'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($hasta['email'] ?? '-'); ?></td>
                                <td><?php echo $hasta['randevu_sayisi']; ?></td>
                                <td>
                                    <?php 
                                    echo $hasta['son_randevu_tarihi'] 
                                        ? date('d.m.Y', strtotime($hasta['son_randevu_tarihi']))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <a href="hasta_duzenle.php?id=<?php echo $hasta['id']; ?>" class="btn-primary">Düzenle</a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="patient_id" value="<?php echo $hasta['id']; ?>">
                                        <button type="submit" name="delete_patient" class="btn-danger" 
                                                onclick="return confirm('Bu hastayı silmek istediğinizden emin misiniz?')">
                                            Sil
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Henüz hasta kaydı bulunmamaktadır.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 