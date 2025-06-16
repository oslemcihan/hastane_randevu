<?php
session_start();
require_once '../config/database.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

// Hasta ID'sini al
$patient_query = "SELECT id FROM hastalar WHERE kullanici_id = " . $_SESSION['user_id'];
$patient_result = mysqli_query($conn, $patient_query);
$patient_data = mysqli_fetch_assoc($patient_result);

if (!$patient_data) {
    die("Hasta bilgisi bulunamadı!");
}

$hasta_id = $patient_data['id'];


if (isset($_GET['cancel_appointment'])) {
    $randevu_id = (int)$_GET['cancel_appointment'];
    
    // Randevunun bu hastaya ait olduğunu kontrol et
    $check_query = "SELECT id FROM randevular WHERE id = $randevu_id AND hasta_id = $hasta_id AND durum = 'beklemede'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $cancel_query = "UPDATE randevular SET durum = 'iptal' WHERE id = $randevu_id";
        if (mysqli_query($conn, $cancel_query)) {
            $message = "Randevu başarıyla iptal edildi.";
        } else {
            $message = "Randevu iptal edilirken hata oluştu.";
        }
    }
}


$appointments_query = "
    SELECT r.*,
           CONCAT(d_user.ad, ' ', d_user.soyad) as doktor_adi,
           b.ad as brans_adi,
           mk.tani, mk.tedavi, mk.notlar
    FROM randevular r
    JOIN doktorlar d ON r.doktor_id = d.id
    JOIN kullanicilar d_user ON d.kullanici_id = d_user.id
    JOIN branslar b ON d.brans_id = b.id
    LEFT JOIN muayene_kaydi mk ON r.id = mk.randevu_id
    WHERE r.hasta_id = $hasta_id
    ORDER BY r.tarih DESC, r.saat DESC
";

$appointments_result = mysqli_query($conn, $appointments_query);

$stats_query = "
    SELECT 
        COUNT(*) as toplam,
        COUNT(CASE WHEN durum = 'beklemede' THEN 1 END) as beklemede,
        COUNT(CASE WHEN durum = 'tamamlandi' THEN 1 END) as tamamlandi,
        COUNT(CASE WHEN durum = 'iptal' THEN 1 END) as iptal
    FROM randevular 
    WHERE hasta_id = $hasta_id
";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Geçmişim - Hastane Sistemi</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <h3>Hasta Paneli</h3>
            <ul>
                <li><a href="dashboard.php">Ana Sayfa</a></li>
                <li><a href="book_appointment.php">Randevu Al</a></li>
                <li><a href="my_appointments.php" class="active">Randevularım</a></li>
                <li><a href="../logout.php">Çıkış</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="header">
                <h1>Randevu Geçmişim</h1>
                <p>Hoş geldiniz, <?php echo $_SESSION['ad'] . ' ' . $_SESSION['soyad']; ?></p>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

           
            <div class="stats-container">
                <div class="stat-card">
                    <h3><?php echo $stats['toplam']; ?></h3>
                    <p>Toplam Randevu</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['beklemede']; ?></h3>
                    <p>Bekleyen Randevu</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['tamamlandi']; ?></h3>
                    <p>Tamamlanan Randevu</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['iptal']; ?></h3>
                    <p>İptal Edilen</p>
                </div>
            </div>

            
            <div class="appointments-section">
                <h2>Randevu Listesi</h2>
                
                <?php if (mysqli_num_rows($appointments_result) > 0): ?>
                    <div class="appointments-grid">
                        <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): ?>
                            <div class="appointment-card <?php echo $appointment['durum']; ?>">
                                <div class="appointment-header">
                                    <div class="appointment-date">
                                        <strong><?php echo date('d.m.Y', strtotime($appointment['tarih'])); ?></strong>
                                        <span><?php echo $appointment['saat']; ?></span>
                                    </div>
                                    <div class="appointment-status">
                                        <span class="status-<?php echo $appointment['durum']; ?>">
                                            <?php echo ucfirst($appointment['durum']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="appointment-details">
                                    <h4><?php echo $appointment['doktor_adi']; ?></h4>
                                    <p class="branch"><?php echo $appointment['brans_adi']; ?></p>
                                    
                                    <?php if ($appointment['durum'] === 'tamamlandi' && $appointment['tani']): ?>
                                        <div class="medical-notes">
                                            <h5>Muayene Notları:</h5>
                                            <?php if ($appointment['tani']): ?>
                                                <p><strong>Tanı:</strong> <?php echo $appointment['tani']; ?></p>
                                            <?php endif; ?>
                                            <?php if ($appointment['tedavi']): ?>
                                                <p><strong>Tedavi:</strong> <?php echo $appointment['tedavi']; ?></p>
                                            <?php endif; ?>
                                            <?php if ($appointment['notlar']): ?>
                                                <p><strong>Notlar:</strong> <?php echo $appointment['notlar']; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>Henüz randevu geçmişiniz bulunmamaktadır.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
