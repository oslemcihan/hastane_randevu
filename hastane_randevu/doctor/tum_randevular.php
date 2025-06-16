<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';  // Önce database.php'yi dahil et
require_once '../auth/auth.php';        // Sonra auth.php'yi dahil et

checkLogin();
checkRole('doktor');

try {
    // Doktor ID'sini al
    $stmt = $pdo->prepare("SELECT id FROM doktorlar WHERE kullanici_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doktor_id = $stmt->fetchColumn();

    if (!$doktor_id) {
        throw new Exception("Doktor bilgileri bulunamadı!");
    }

    // Tüm randevuları al (son 30 günü göster)
    $stmt = $pdo->prepare("
        SELECT r.*, 
               h.dogum_tarihi, h.cinsiyet, h.telefon,
               k.ad as hasta_ad, k.soyad as hasta_soyad, k.email as hasta_email,
               CASE 
                   WHEN r.durum = 'beklemede' THEN 'waiting'
                   WHEN r.durum = 'onaylandı' THEN 'approved'
                   WHEN r.durum = 'iptal' THEN 'cancelled'
                   ELSE r.durum
               END as durum_class
        FROM randevular r
        JOIN hastalar h ON r.hasta_id = h.id
        JOIN kullanicilar k ON h.kullanici_id = k.id
        WHERE r.doktor_id = ? 
        AND r.tarih >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY r.tarih DESC, r.saat ASC
    ");
    $stmt->execute([$doktor_id]);
    $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Randevu onaylama/iptal etme işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['randevu_id']) && isset($_POST['action'])) {
        try {
            $randevu_id = $_POST['randevu_id'];
            $action = $_POST['action'];
            
            $yeni_durum = ($action === 'onayla') ? 'onaylandi' : 'iptal';
            
            // Debug için mevcut durumu kontrol et
            $check_stmt = $pdo->prepare("SELECT durum FROM randevular WHERE id = ?");
            $check_stmt->execute([$randevu_id]);
            $mevcut_durum = $check_stmt->fetchColumn();
            
            // Durumu güncelle
            $stmt = $pdo->prepare("UPDATE randevular SET durum = ? WHERE id = ? AND doktor_id = ?");
            $stmt->execute([$yeni_durum, $randevu_id, $doktor_id]);
            
            if($stmt->rowCount() > 0) {
                $success = "Randevu durumu başarıyla güncellendi. Eski durum: " . $mevcut_durum . ", Yeni durum: " . $yeni_durum;
            } else {
                $error = "Randevu durumu güncellenemedi!";
            }
            
            header("Location: tum_randevular.php");
            exit;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tüm Randevular - Doktor Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }
        .status-badge.waiting {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        .status-badge.approved {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        .status-badge.cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .action-buttons button {
            padding: 5px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        .approve-btn {
            background: #28a745;
            color: white;
        }
        .cancel-btn {
            background: #dc3545;
            color: white;
        }
        .action-buttons button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Tüm Randevular</h1>
            <nav>
                <span style="color: #fff; font-size: 1.2em; background: rgba(255, 255, 255, 0.2); padding: 8px 15px; border-radius: 8px;">
                    <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Doktor'; ?>
                </span>
                <a href="dashboard.php" class="btn">← Panele Dön</a>
                <a href="../logout.php" class="btn">Çıkış</a>
            </nav>
        </header>

        <main>
            <div class="dashboard">
                <div class="feature">
                    <?php if (isset($error_message)): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="appointments-section" style="background: rgba(255, 255, 255, 0.9); padding: 30px; border-radius: 15px;">
                        <h3 style="color: #1a1a1a; margin-bottom: 20px;">Son 30 Günün Randevuları</h3>
                        
                        <?php if (isset($randevular) && count($randevular) > 0): ?>
                            <div class="appointments-list" style="display: grid; gap: 15px;">
                                <?php foreach ($randevular as $randevu): ?>
                                    <div class="appointment-card" style="background: rgba(255, 255, 255, 0.95); padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center;">
                                        <div style="flex-grow: 1;">
                                            <div style="margin-bottom: 10px;">
                                                <strong style="color: #1a1a1a;">Tarih:</strong> 
                                                <span style="color: #666;"><?php echo date('d.m.Y', strtotime($randevu['tarih'])); ?></span>
                                                <strong style="color: #1a1a1a; margin-left: 15px;">Saat:</strong> 
                                                <span style="color: #666;"><?php echo date('H:i', strtotime($randevu['saat'])); ?></span>
                                            </div>
                                            <div>
                                                <strong style="color: #1a1a1a;">Hasta:</strong> 
                                                <span style="color: #666;"><?php echo htmlspecialchars($randevu['hasta_ad'] . ' ' . $randevu['hasta_soyad']); ?></span>
                                                <strong style="color: #1a1a1a; margin-left: 15px;">Tel:</strong> 
                                                <span style="color: #666;"><?php echo htmlspecialchars($randevu['telefon']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div style="display: flex; align-items: center; gap: 20px;">
                                            <span class="status-badge <?php echo $randevu['durum_class']; ?>">
                                                <?php echo ucfirst($randevu['durum']); ?>
                                            </span>
                                            
                                            <?php if ($randevu['durum'] === 'beklemede'): ?>
                                                <div class="action-buttons">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="randevu_id" value="<?php echo $randevu['id']; ?>">
                                                        <input type="hidden" name="action" value="onayla">
                                                        <button type="submit" class="approve-btn">Onayla</button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="randevu_id" value="<?php echo $randevu['id']; ?>">
                                                        <input type="hidden" name="action" value="iptal">
                                                        <button type="submit" class="cancel-btn">İptal Et</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; text-align: center; padding: 20px;">Son 30 günde hiç randevunuz bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 