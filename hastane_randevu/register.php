<?php
require_once 'config/database.php';

if (isset($_POST['register'])) {
    $ad = $_POST['ad'];
    $soyad = $_POST['soyad'];
    $email = $_POST['email'];
    $password = sha1($_POST['password']);
    $dogum_tarihi = $_POST['dogum_tarihi'];
    $cinsiyet = $_POST['cinsiyet'];
    $telefon = $_POST['telefon'];
    
    try {
        // Email kontrolü
        $stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Bu email adresi zaten kullanılıyor!");
        }
        
        // Transaction başlat
        $pdo->beginTransaction();
        
        // Kullanıcı kaydı
        $stmt = $pdo->prepare("INSERT INTO kullanicilar (ad, soyad, email, sifre, rol) VALUES (?, ?, ?, ?, 'hasta')");
        $stmt->execute([$ad, $soyad, $email, $password]);
        $user_id = $pdo->lastInsertId();
        
        // Hasta bilgileri kaydı
        $stmt = $pdo->prepare("INSERT INTO hastalar (kullanici_id, dogum_tarihi, cinsiyet, telefon) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $dogum_tarihi, $cinsiyet, $telefon]);
        
        $pdo->commit();
        // $success = "Kayıt başarılı! Giriş yapabilirsiniz.";
        header("Location: login.php?register=success");
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Hastane Sistemi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="register-form">
            <h2>Hasta Kaydı</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="ad">Ad:</label>
                        <input type="text" name="ad" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="soyad">Soyad:</label>
                        <input type="text" name="soyad" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre:</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="dogum_tarihi">Doğum Tarihi:</label>
                        <input type="date" name="dogum_tarihi" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cinsiyet">Cinsiyet:</label>
                        <select name="cinsiyet" required>
                            <option value="">Seçiniz</option>
                            <option value="erkek">Erkek</option>
                            <option value="kadın">Kadın</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="telefon">Telefon:</label>
                    <input type="tel" name="telefon" required>
                </div>
                
                <button type="submit" name="register" class="btn-primary">Kayıt Ol</button>
            </form>
            
            <p><a href="login.php">Zaten hesabınız var mı? Giriş yapın</a></p>
        </div>
    </div>
</body>
</html>