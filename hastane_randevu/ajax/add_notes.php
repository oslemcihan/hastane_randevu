<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_doctor':
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                throw new Exception('Yetkisiz erişim');
            }
            
            $ad = $_POST['ad'] ?? '';
            $soyad = $_POST['soyad'] ?? '';
            $email = $_POST['email'] ?? '';
            $sifre = $_POST['sifre'] ?? '';
            $brans_id = $_POST['brans_id'] ?? '';
            $uzmanlik = $_POST['uzmanlik'] ?? '';
            
            if (empty($ad) || empty($soyad) || empty($email) || empty($sifre) || empty($brans_id)) {
                throw new Exception('Tüm alanlar doldurulmalıdır');
            }
            
            // Email kontrolü
            $stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Bu email adresi zaten kayıtlı');
            }
            
            $pdo->beginTransaction();
            
            // Kullanıcı ekleme
            $hashed_password = hash('sha256', $sifre);
            $stmt = $pdo->prepare("INSERT INTO kullanicilar (ad, soyad, email, sifre, rol) VALUES (?, ?, ?, ?, 'doktor')");
            $stmt->execute([$ad, $soyad, $email, $hashed_password]);
            $kullanici_id = $pdo->lastInsertId();
            
            // Doktor ekleme
            $stmt = $pdo->prepare("INSERT INTO doktorlar (kullanici_id, brans_id, uzmanlik) VALUES (?, ?, ?)");
            $stmt->execute([$kullanici_id, $brans_id, $uzmanlik]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Doktor başarıyla eklendi']);
            break;
            
        case 'add_branch':
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                throw new Exception('Yetkisiz erişim');
            }
            
            $ad = $_POST['ad'] ?? '';
            
            if (empty($ad)) {
                throw new Exception('Branş adı boş olamaz');
            }
            
            $stmt = $pdo->prepare("INSERT INTO branslar (ad) VALUES (?)");
            $stmt->execute([$ad]);
            
            echo json_encode(['success' => true, 'message' => 'Branş başarıyla eklendi']);
            break;
            
        case 'add_patient_note':
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doktor') {
                throw new Exception('Yetkisiz erişim');
            }
            
            $randevu_id = $_POST['randevu_id'] ?? '';
            $tani = $_POST['tani'] ?? '';
            $tedavi = $_POST['tedavi'] ?? '';
            $notlar = $_POST['notlar'] ?? '';
            
            if (empty($randevu_id)) {
                throw new Exception('Randevu ID gerekli');
            }
            
            // Randevunun bu doktora ait olduğunu kontrol et
            $stmt = $pdo->prepare("
                SELECT r.id 
                FROM randevular r 
                JOIN doktorlar d ON r.doktor_id = d.id 
                WHERE r.id = ? AND d.kullanici_id = ?
            ");
            $stmt->execute([$randevu_id, $_SESSION['user_id']]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Bu randevuya erişim yetkiniz yok');
            }
            
            // Mevcut kayıt var mı kontrol et
            $stmt = $pdo->prepare("SELECT id FROM muayene_kaydi WHERE randevu_id = ?");
            $stmt->execute([$randevu_id]);
            
            if ($stmt->fetch()) {
                // Güncelle
                $stmt = $pdo->prepare("UPDATE muayene_kaydi SET tani = ?, tedavi = ?, notlar = ? WHERE randevu_id = ?");
                $stmt->execute([$tani, $tedavi, $notlar, $randevu_id]);
            } else {
                // Yeni kayıt ekle
                $stmt = $pdo->prepare("INSERT INTO muayene_kaydi (randevu_id, tani, tedavi, notlar) VALUES (?, ?, ?, ?)");
                $stmt->execute([$randevu_id, $tani, $tedavi, $notlar]);
            }
            
            // Randevu durumunu güncelle
            $stmt = $pdo->prepare("UPDATE randevular SET durum = 'tamamlandi' WHERE id = ?");
            $stmt->execute([$randevu_id]);
            
            echo json_encode(['success' => true, 'message' => 'Not başarıyla kaydedildi']);
            break;
            
        default:
            throw new Exception('Geçersiz işlem');
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>