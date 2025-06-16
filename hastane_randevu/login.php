<?php
session_start();
require_once 'config/database.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = sha1($_POST['password']);
    
    $stmt = $pdo->prepare("SELECT id, ad, soyad, rol FROM kullanicilar WHERE email = ? AND sifre = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['ad'] . ' ' . $user['soyad'];
        $_SESSION['role'] = $user['rol'];
        switch($user['rol']) {
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
    } else {
        $error = "Email veya şifre hatalı!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Hastane Sistemi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2>Giriş Yap</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre:</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn-primary">Giriş Yap</button>
            </form>
            
            <p><a href="register.php">Hesabınız yok mu? Kayıt olun</a></p>
            <p><a href="index.php">Ana Sayfaya Dön</a></p>
        </div>
    </div>
</body>
</html>