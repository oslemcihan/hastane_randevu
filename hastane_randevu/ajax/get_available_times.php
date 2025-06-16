<?php
// ajax/get_available_times.php
require_once '../config/database.php';

header('Content-Type: application/json');

if (isset($_POST['doktor_id']) && isset($_POST['tarih'])) {
    $doktor_id = $_POST['doktor_id'];
    $tarih = $_POST['tarih'];
    
    try {
        // Mevcut randevuları al
        $stmt = $pdo->prepare("
            SELECT saat 
            FROM randevular 
            WHERE doktor_id = ? AND tarih = ? AND durum != 'iptal'
        ");
        $stmt->execute([$doktor_id, $tarih]);
        $busy_times = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Çalışma saatleri (09:00 - 17:00 arası, 30 dakika aralıklar)
        $work_hours = [
            '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', 
            '16:00', '16:30'
        ];
        
        // Müsait saatleri filtrele
        $available_times = array_diff($work_hours, $busy_times);
        
        // Geçmiş saatleri kontrol et (bugün ise)
        if ($tarih == date('Y-m-d')) {
            $current_time = date('H:i');
            $available_times = array_filter($available_times, function($time) use ($current_time) {
                return $time > $current_time;
            });
        }
        
        echo json_encode([
            'success' => true,
            'times' => array_values($available_times)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Saatler alınırken hata oluştu: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Doktor ID ve tarih gerekli'
    ]);
}
?>
