<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

try {
    require_once 'config/database.php';
} catch(Exception $e) {
    // Veritabanı hatası olsa bile sayfanın görüntülenmesine izin ver
    error_log("Database connection error: " . $e->getMessage());
}

// Eğer kullanıcı giriş yapmışsa uygun panele yönlendir
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch($_SESSION['role']) {
        case 'hasta':
            header("Location: patient/dashboard.php");
            break;
        case 'doktor':
            header("Location: doctor/dashboard.php");
            break;
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hastane Randevu Sistemi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <h1>Hastane Randevu Sistemi</h1>
            <nav>
                <a href="login.php" class="btn">Giriş Yap</a>
                <a href="register.php" class="btn">Kayıt Ol</a>
            </nav>
        </header>

        <main>
            <div class="hero">
                <h2>Online Randevu Al</h2>
                <p>Hızlı, güvenli ve kolay randevu sistemi ile sağlığınız için en iyi hizmeti alın</p>
                
                <div class="features">
                    <div class="feature">
                        <h3>Hasta Girişi</h3>
                        <p>Kolayca randevu alın ve geçmiş randevularınızı takip edin. Sağlık geçmişinize anında erişin.</p>
                        <a href="register.php?role=hasta" class="btn-primary">Hasta Kaydı</a>
                    </div>
                    
                    <div class="feature">
                        <h3>Doktor Girişi</h3>
                        <p>Randevularınızı yönetin, hasta kayıtlarını inceleyin ve tedavi notları ekleyin.</p>
                        <a href="login.php" class="btn-primary">Doktor Girişi</a>
                    </div>
                    
                    <div class="feature">
                        <h3>Yönetici Paneli</h3>
                        <p>Sistem yönetimi, istatistikler ve raporlara tek noktadan erişim sağlayın.</p>
                        <a href="login.php" class="btn-primary">Admin Girişi</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>