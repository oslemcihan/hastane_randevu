<?php
require_once '../config/database.php';
checkLogin();
checkRole('hasta');


$stmt = $pdo->prepare("SELECT id FROM hastalar WHERE kullanici_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$hasta = $stmt->fetch();
$hasta_id = $hasta['id'];


$stmt = $pdo->query("SELECT * FROM branslar ORDER BY ad");
$branslar = $stmt->fetchAll();


if (isset($_POST['book_appointment'])) {
    $doktor_id = $_POST['doktor_id'];
    $tarih = $_POST['tarih'];
    $saat = $_POST['saat'];
    
    try {
       
        $stmt = $pdo->prepare("SELECT id FROM randevular WHERE doktor_id = ? AND tarih = ? AND saat = ? AND durum != 'iptal'");
        $stmt->execute([$doktor_id, $tarih, $saat]);
        if ($stmt->fetch()) {
            throw new Exception("Bu tarih ve saat için randevu dolu!");
        }
        
        
        $stmt = $pdo->prepare("INSERT INTO randevular (hasta_id, doktor_id, tarih, saat) VALUES (?, ?, ?, ?)");
        $stmt->execute([$hasta_id, $doktor_id, $tarih, $saat]);
        
        $success = "Randevunuz başarıyla alındı!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Al - Hastane Sistemi</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Randevu Al</h1>
            <nav>
                <a href="dashboard.php" class="btn">Panele Dön</a>
                <a href="my_appointments.php" class="btn">Randevularım</a>
                <a href="../logout.php" class="btn">Çıkış</a>
            </nav>
        </header>

        <main>
            <div class="dashboard">
                <h2>Yeni Randevu Al</h2>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" id="appointmentForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="brans">Branş Seçin:</label>
                            <select name="brans" id="brans" required>
                                <option value="">Branş seçiniz</option>
                                <?php foreach($branslar as $brans): ?>
                                    <option value="<?php echo $brans['id']; ?>"><?php echo $brans['ad']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="doktor_id">Doktor Seçin:</label>
                            <select name="doktor_id" id="doktor_id" required>
                                <option value="">Önce branş seçiniz</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tarih">Tarih:</label>
                            <input type="date" name="tarih" id="tarih" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="saat">Saat:</label>
                            <select name="saat" id="saat" required>
                                <option value="">Tarih seçiniz</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="book_appointment" class="btn-primary">Randevu Al</button>
                </form>
            </div>
        </main>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>