<?php
session_start();

// Güvenlik kontrolü - CSRF token doğrulama (GET isteği için basit kontrol)
if (isset($_GET['token']) && isset($_SESSION['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        // CSRF saldırısı olabilir, güvenli çıkış yap
        session_destroy();
        header("Location: login.php?error=security");
        exit();
    }
}

// Kullanıcı bilgilerini logla (isteğe bağlı)
if (isset($_SESSION['kullanici_id'])) {
    // Çıkış işlemini veritabanına kaydet (isteğe bağlı)
    try {
        require_once 'config/database.php';
        
        $query = "INSERT INTO kullanici_loglari (kullanici_id, islem, ip_adresi, tarih) 
                  VALUES (?, 'cikis', ?, NOW())";
        $stmt = $conn->prepare($query);
        $ip_adresi = $_SERVER['REMOTE_ADDR'] ?? 'bilinmeyen';
        $stmt->bind_param("is", $_SESSION['kullanici_id'], $ip_adresi);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // Log hatası önemli değil, çıkış işlemine devam et
        error_log("Logout log hatası: " . $e->getMessage());
    }
}

// Oturum verilerini temizle
$_SESSION = array();

// Oturum çerezini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu yok et
session_destroy();

// Tarayıcı önbelleğini temizle
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Giriş sayfasına yönlendir
header("Location: login.php?logout=success");
exit();
?>
