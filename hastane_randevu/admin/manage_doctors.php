<?php
session_start();
require_once '../config/database.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Doktor ekleme işlemi
if ($_POST['action'] == 'add_doctor' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad = mysqli_real_escape_string($conn, $_POST['ad']);
    $soyad = mysqli_real_escape_string($conn, $_POST['soyad']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $sifre = password_hash($_POST['sifre'], PASSWORD_DEFAULT);
    $brans_id = (int)$_POST['brans_id'];
    
    // Kullanıcı tablosuna ekle
    $user_query = "INSERT INTO kullanicilar (ad, soyad, email, sifre, rol) VALUES ('$ad', '$soyad', '$email', '$sifre', 'doctor')";
    
    if (mysqli_query($conn, $user_query)) {
        $user_id = mysqli_insert_id($conn);
        
        // Doktor tablosuna ekle
        $doctor_query = "INSERT INTO doktorlar (kullanici_id, brans_id) VALUES ($user_id, $brans_id)";
        
        if (mysqli_query($conn, $doctor_query)) {
            $message = "Doktor başarıyla eklendi!";
        } else {
            $message = "Doktor eklenirken hata oluştu!";
        }
    } else {
        $message = "Kullanıcı eklenirken hata oluştu!";
    }
}

// Doktor silme işlemi
if (isset($_GET['delete_doctor'])) {
    $doctor_id = (int)$_GET['delete_doctor'];
    
    // Önce kullanıcı ID'sini al
    $user_query = "SELECT kullanici_id FROM doktorlar WHERE id = $doctor_id";
    $user_result = mysqli_query($conn, $user_query);
    $user_data = mysqli_fetch_assoc($user_result);
    
    if ($user_data) {
        $user_id = $user_data['kullanici_id'];
        
        // Doktor tablosundan sil
        $delete_doctor = "DELETE FROM doktorlar WHERE id = $doctor_id";
        if (mysqli_query($conn, $delete_doctor)) {
            // Kullanıcı tablosundan sil
            $delete_user = "DELETE FROM kullanicilar WHERE id = $user_id";
            mysqli_query($conn, $delete_user);
            $message = "Doktor başarıyla silindi!";
        }
    }
}

// Doktorları listele
$doctors_query = "
    SELECT d.id, d.kullanici_id,
           CONCAT(k.ad, ' ', k.soyad) as doktor_adi,
           k.email,
           b.ad as brans_adi,
           (SELECT COUNT(*) FROM randevular WHERE doktor_id = d.id) as randevu_sayisi
    FROM doktorlar d
    JOIN kullanicilar k ON d.kullanici_id = k.id
    JOIN branslar b ON d.brans_id = b.id
    ORDER BY k.ad, k.soyad
";
$doctors_result = mysqli_query($conn, $doctors_query);

// Branşları çek
$branches_query = "SELECT * FROM branslar ORDER BY ad";
$branches_result = mysqli_query($conn, $branches_query);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doktor Yönetimi - Hastane Sistemi</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <h3>Yönetici Paneli</h3>
            <ul>
                <li><a href="dashboard.php">Ana Sayfa</a></li>
                <li><a href="manage_doctors.php" class="active">Doktor Yönetimi</a></li>
                <li><a href="manage_departments.php">Branş Yönetimi</a></li>
                <li><a href="statistics.php">İstatistikler</a></li>
                <li><a href="../logout.php">Çıkış</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="header">
                <h1>Doktor Yönetimi</h1>
            </div>

            <?php if ($message): ?>
                <div class="alert"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Yeni Doktor Ekleme Formu -->
            <div class="form-section">
                <h2>Yeni Doktor Ekle</h2>
                <form class="doctor-form" method="POST" action="">
                    <input type="hidden" name="action" value="add_doctor">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ad">Ad:</label>
                            <input type="text" id="ad" name="ad" required>
                        </div>
                        <div class="form-group">
                            <label for="soyad">Soyad:</label>
                            <input type="text" id="soyad" name="soyad" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">E-mail:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="sifre">Şifre:</label>
                            <input type="password" id="sifre" name="sifre" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="brans_id">Branş:</label>
                        <select id="brans_id" name="brans_id" required>
                            <option value="">Branş Seçin</option>
                            <?php while ($branch = mysqli_fetch_assoc($branches_result)): ?>
                                <option value="<?php echo $branch['id']; ?>">
                                    <?php echo $branch['ad']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Doktor Ekle</button>
                </form>
            </div>

            <!-- Doktor Listesi -->
            <div class="doctors-list">
                <h2>Mevcut Doktorlar</h2>
                <table class="doctors-table">
                    <thead>
                        <tr>
                            <th>Doktor Adı</th>
                            <th>E-mail</th>
                            <th>Branş</th>
                            <th>Randevu Sayısı</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                        <tr>
                            <td><?php echo $doctor['doktor_adi']; ?></td>
                            <td><?php echo $doctor['email']; ?></td>
                            <td><?php echo $doctor['brans_adi']; ?></td>
                            <td><?php echo $doctor['randevu_sayisi']; ?></td>
                            <td>
                                <a href="?delete_doctor=<?php echo $doctor['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Bu doktoru silmek istediğinizden emin misiniz?')">
                                    Sil
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Form gönderildikten sonra mesajı temizle
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        }, 3000);
    </script>
</body>
</html>
