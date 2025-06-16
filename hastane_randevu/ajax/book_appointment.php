<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hasta') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_doctors':
            $brans_id = $_POST['brans_id'] ?? '';
            
            if (empty($brans_id)) {
                throw new Exception('Branş seçimi gerekli');
            }
            
            $stmt = $pdo->prepare("
                SELECT d.id, k.ad, k.soyad, d.uzmanlik 
                FROM doktorlar d 
                JOIN kullanicilar k ON d.kullanici_id = k.id 
                WHERE d.brans_id = ? 
                ORDER BY k.ad, k.soyad
            ");
            $stmt->execute([$brans_id]);
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'doctors' => $doctors]);
            break;
            
        case 'get_available_times':
            $doktor_id = $_POST['doktor_id'] ?? '';
            $tarih = $_POST['tarih'] ?? '';
            
            if (empty($doktor_id) || empty($tarih)) {
                throw new Exception('Doktor ve tarih seçimi gerekli');
            }
            
            // Seçilen tarihte bu doktorun mevcut randevularını al
            $stmt = $pdo->prepare("
                SELECT saat 
                FROM randevular 
                WHERE doktor_id = ? AND tarih = ? AND durum != 'iptal'
            ");
            $stmt->execute([$doktor_id, $tarih]);
            $taken_times = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Çalışma saatleri (09:00 - 17:00, 30 dakika aralıklarla)
            $work_hours = [];
            for ($hour = 9; $hour < 17; $hour++) {
                for ($minute = 0; $minute < 60; $minute += 30) {
                    $time = sprintf('%02d:%02d', $hour, $minute);
                    if (!in_array($time, $taken_times)) {
                        $work_hours[] = $time;
                    }
                }
            }
            
            echo json_encode(['success' => true, 'available_times' => $work_hours]);
            break;
            
        case 'book_appointment':
            $doktor_id = $_POST['doktor_id'] ?? '';
            $tarih = $_POST['tarih'] ?? '';
            $saat = $_POST['saat'] ?? '';
            
            if (empty($doktor_id) || empty($tarih) || empty($saat)) {
                throw new Exception('Tüm alanlar doldurulmalıdır');
            }
            
            // Geçmiş tarih kontrolü
            if (strtotime($tarih) < strtotime(date('Y-m-d'))) {
                throw new Exception('Geçmiş tarih için randevu alınamaz');
            }
            
            // Hasta ID'sini al
            $stmt = $pdo->prepare("SELECT id FROM hastalar WHERE kullanici_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $hasta = $stmt->fetch();
            
            if (!$hasta) {
                throw new Exception('Hasta kaydınız bulunamadı');
            }
            
            $hasta_id = $hasta['id'];
            
            // Bu saatte randevu var mı kontrol et
            $stmt = $pdo->prepare("
                SELECT id 
                FROM randevular 
                WHERE doktor_id = ? AND tarih = ? AND saat = ? AND durum != 'iptal'
            ");
            $stmt->execute([$doktor_id, $tarih, $saat]);
            
            if ($stmt->fetch()) {
                throw new Exception('Bu saat için randevu zaten alınmış');
            }
            
            // Hastanın aynı gün başka randevusu var mı kontrol et
            $stmt = $pdo->prepare("
                SELECT id 
                FROM randevular 
                WHERE hasta_id = ? AND tarih = ? AND durum != 'iptal'
            ");
            $stmt->execute([$hasta_id, $tarih]);
            
            if ($stmt->fetch()) {
                throw new Exception('Aynı gün için zaten randevunuz bulunmaktadır');
            }
            
            // Randevu oluştur
            $stmt = $pdo->prepare("
                INSERT INTO randevular (hasta_id, doktor_id, tarih, saat, durum) 
                VALUES (?, ?, ?, ?, 'beklemede')
            ");
            $stmt->execute([$hasta_id, $doktor_id, $tarih, $saat]);
            
            // Doktor bilgilerini al
            $stmt = $pdo->prepare("
                SELECT k.ad, k.soyad, b.ad as brans_adi
                FROM doktorlar d
                JOIN kullanicilar k ON d.kullanici_id = k.id
                JOIN branslar b ON d.brans_id = b.id
                WHERE d.id = ?
            ");
            $stmt->execute([$doktor_id]);
            $doktor = $stmt->fetch();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Randevunuz başarıyla oluşturuldu',
                'appointment_info' => [
                    'doktor' => $doktor['ad'] . ' ' . $doktor['soyad'],
                    'brans' => $doktor['brans_adi'],
                    'tarih' => $tarih,
                    'saat' => $saat
                ]
            ]);
            break;
            
        case 'cancel_appointment':
            $randevu_id = $_POST['randevu_id'] ?? '';
            
            if (empty($randevu_id)) {
                throw new Exception('Randevu ID gerekli');
            }
            
            // Hasta ID'sini al
            $stmt = $pdo->prepare("SELECT id FROM hastalar WHERE kullanici_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $hasta = $stmt->fetch();
            
            if (!$hasta) {
                throw new Exception('Hasta kaydınız bulunamadı');
            }
            
            // Randevunun bu hastaya ait olduğunu kontrol et
            $stmt = $pdo->prepare("
                SELECT id, tarih, saat 
                FROM randevular 
                WHERE id = ? AND hasta_id = ? AND durum = 'beklemede'
            ");
            $stmt->execute([$randevu_id, $hasta['id']]);
            $randevu = $stmt->fetch();
            
            if (!$randevu) {
                throw new Exception('Randevu bulunamadı veya iptal edilemez');
            }
            
            // Randevu tarihine 24 saat kala iptal edilemez
            $randevu_zamani = strtotime($randevu['tarih'] . ' ' . $randevu['saat']);
            $su_an = time();
            $fark = $randevu_zamani - $su_an;
            
            if ($fark < 86400) { // 24 saat = 86400 saniye
                throw new Exception('Randevu tarihine 24 saatten az kaldığında iptal edilemez');
            }
            
            // Randevuyu iptal et
            $stmt = $pdo->prepare("UPDATE randevular SET durum = 'iptal' WHERE id = ?");
            $stmt->execute([$randevu_id]);
            
            echo json_encode(['success' => true, 'message' => 'Randevunuz başarıyla iptal edildi']);
            break;
            
        default:
            throw new Exception('Geçersiz işlem');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>