<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();
checkRole('doktor');

echo "Hoş geldiniz, Doktor " . $_SESSION['name'];

// Randevu onay/iptal işlemleri
if (isset($_POST['onayla']) && isset($_POST['randevu_id'])) {
    $stmt = $pdo->prepare("UPDATE randevular SET durum = 'onaylandı' WHERE id = ?");
    $stmt->execute([$_POST['randevu_id']]);
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['iptal']) && isset($_POST['randevu_id'])) {
    $stmt = $pdo->prepare("UPDATE randevular SET durum = 'iptal' WHERE id = ?");
    $stmt->execute([$_POST['randevu_id']]);
    header("Location: dashboard.php");
    exit();
}

// Doktor bilgilerini al
$stmt = $pdo->prepare("
    SELECT d.id, d.uzmanlik, b.ad as brans_ad 
    FROM doktorlar d 
    JOIN branslar b ON d.brans_id = b.id 
    WHERE d.kullanici_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$doktor = $stmt->fetch();
$doktor_id = $doktor['id'];

// Bugünkü randevuları al
$stmt = $pdo->prepare("
    SELECT r.*, h.dogum_tarihi, h.cinsiyet, h.telefon,
           k.ad as hasta_ad, k.soyad as hasta_soyad, k.email as hasta_email
    FROM randevular r
    JOIN hastalar h ON r.hasta_id = h.id
    JOIN kullanicilar k ON h.kullanici_id = k.id
    WHERE r.doktor_id = ? AND r.tarih = CURDATE()
    ORDER BY r.saat ASC
");
$stmt->execute([$doktor_id]);
$bugun_randevular = $stmt->fetchAll();

// Bu haftaki toplam randevu sayısı
$stmt = $pdo->prepare("
    SELECT COUNT(*) as toplam 
    FROM randevular 
    WHERE doktor_id = ? AND WEEK(tarih) = WEEK(CURDATE())
");
$stmt->execute([$doktor_id]);
$haftalik_toplam = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doktor Paneli - Dr. Ayşe Demir</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Doktor Paneli</h1>
            <nav>
                <span style="color: #fff; font-size: 1.2em; background: rgba(255, 255, 255, 0.2); padding: 8px 15px; border-radius: 8px;">
                    <?php echo $_SESSION['name']; ?>
                </span>
                <a href="tum_randevular.php" class="btn">Tüm Randevular</a>
                <a href="../logout.php" class="btn">Çıkış</a>
            </nav>
        </header>

        <main>
            <div class="dashboard">
                <div class="feature">
                    <h3>Doktor Kontrol Paneli</h3>
                    
                    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
                        <div class="stat-card" style="background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            <h4 style="color: #1a1a1a; margin-bottom: 10px;">Bugünkü Randevular</h4>
                            <p style="font-size: 2em; color: #6B73FF; font-weight: 600;"><?php echo count($bugun_randevular); ?></p>
                        </div>
                        
                        <div class="stat-card" style="background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            <h4 style="color: #1a1a1a; margin-bottom: 10px;">Bu Hafta Toplam</h4>
                            <p style="font-size: 2em; color: #6B73FF; font-weight: 600;"><?php echo $haftalik_toplam; ?></p>
                        </div>
                        
                        <div class="stat-card" style="background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            <h4 style="color: #1a1a1a; margin-bottom: 10px;">Uzmanlık</h4>
                            <p style="font-size: 1.2em; color: #666;"><?php echo $doktor['uzmanlik']; ?></p>
                        </div>
                    </div>

                    <div class="appointments-section" style="margin-top: 40px; background: rgba(255, 255, 255, 0.9); padding: 30px; border-radius: 15px;">
                        <h4 style="color: #1a1a1a; margin-bottom: 20px;">Bugünkü Randevularım (<?php echo date('d.m.Y'); ?>)</h4>
                        <?php if(count($bugun_randevular) > 0): ?>
                            <div class="appointments-list" style="display: grid; gap: 15px;">
                                <?php foreach ($bugun_randevular as $randevu): ?>
                                    <div class="appointment-card" style="background: rgba(107, 115, 255, 0.1); padding: 15px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
                                        <div style="color: #333;">
                                            <strong>Saat:</strong> <?php echo date('H:i', strtotime($randevu['saat'])); ?>
                                            <strong style="margin-left: 15px;">Hasta:</strong> <?php echo $randevu['hasta_ad'] . ' ' . $randevu['hasta_soyad']; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; text-align: center; padding: 20px;">Bugün randevunuz bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>