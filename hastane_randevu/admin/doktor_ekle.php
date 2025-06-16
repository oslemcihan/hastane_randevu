<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';

// Yetki kontrolü
checkRole('admin');

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = $_POST['ad'] ?? '';
    $soyad = $_POST['soyad'] ?? '';
    $brans_id = $_POST['brans_id'] ?? '';
    $telefon = $_POST['telefon'] ?? '';
    $email = $_POST['email'] ?? '';

    // Basit doğrulama
    $errors = [];
    if (empty($ad)) $errors[] = "Ad alanı zorunludur.";
    if (empty($soyad)) $errors[] = "Soyad alanı zorunludur.";
    if (empty($brans_id)) $errors[] = "Branş seçimi zorunludur.";

    // Hata yoksa kaydet
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO doktorlar (ad, soyad, brans_id, telefon, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ad, $soyad, $brans_id, $telefon, $email]);
            $success_message = "Doktor başarıyla eklendi.";
            
            // Başarılı ekleme sonrası doktorlar listesine yönlendir
            header("Location: doktorlar.php?success=1");
            exit;
        } catch(PDOException $e) {
            $error_message = "Doktor eklenirken bir hata oluştu.";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Branşları getir
try {
    $branslar = $pdo->query("SELECT * FROM branslar ORDER BY ad")->fetchAll();
} catch(PDOException $e) {
    $error_message = "Branşlar yüklenirken bir hata oluştu.";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Doktor Ekle - Admin Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .dashboard-container {
            display: flex;
            gap: 20px;
        }

        .sidebar {
            width: 250px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin-bottom: 10px;
        }

        .sidebar a {
            display: block;
            padding: 12px 15px;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar a:hover {
            background-color: #f0f0f0;
            transform: translateX(5px);
        }

        .sidebar a.active {
            background-color: #3498db;
            color: white;
        }

        .main-content {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 15px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%232c3e50' viewBox='0 0 16 16'%3E%3Cpath d='M8 12L2 6h12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .btn-container {
            margin-top: 35px;
            display: flex;
            gap: 15px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Zorunlu alan yıldızı */
        label span.required {
            color: #e74c3c;
            margin-left: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Yeni Doktor Ekle</h1>
            <nav>
                <span class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></span>
                <a href="../logout.php" class="btn">Çıkış</a>
            </nav>
        </header>

        <div class="dashboard-container">
            <aside class="sidebar">
                <ul>
                    <li><a href="dashboard.php">Ana Sayfa</a></li>
                    <li><a href="doktorlar.php" class="active">Doktor Yönetimi</a></li>
                    <li><a href="hastalar.php">Hasta Yönetimi</a></li>
                    <li><a href="branslar.php">Branş Yönetimi</a></li>
                    <li><a href="randevular.php">Randevu Yönetimi</a></li>
                </ul>
            </aside>

            <main class="main-content">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="ad">Ad <span class="required">*</span></label>
                            <input type="text" id="ad" name="ad" class="form-control" required 
                                   value="<?php echo isset($_POST['ad']) ? htmlspecialchars($_POST['ad']) : ''; ?>"
                                   placeholder="Doktorun adını girin">
                        </div>

                        <div class="form-group">
                            <label for="soyad">Soyad <span class="required">*</span></label>
                            <input type="text" id="soyad" name="soyad" class="form-control" required
                                   value="<?php echo isset($_POST['soyad']) ? htmlspecialchars($_POST['soyad']) : ''; ?>"
                                   placeholder="Doktorun soyadını girin">
                        </div>

                        <div class="form-group">
                            <label for="brans_id">Branş <span class="required">*</span></label>
                            <select id="brans_id" name="brans_id" class="form-control" required>
                                <option value="">Branş Seçin</option>
                                <?php foreach ($branslar as $brans): ?>
                                    <option value="<?php echo $brans['id']; ?>" 
                                            <?php echo (isset($_POST['brans_id']) && $_POST['brans_id'] == $brans['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brans['ad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="telefon">Telefon</label>
                            <input type="tel" id="telefon" name="telefon" class="form-control"
                                   value="<?php echo isset($_POST['telefon']) ? htmlspecialchars($_POST['telefon']) : ''; ?>"
                                   placeholder="Örn: 0555 123 4567">
                        </div>

                        <div class="form-group">
                            <label for="email">E-posta</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   placeholder="ornek@email.com">
                        </div>

                        <div class="btn-container">
                            <button type="submit" class="btn-primary">Doktor Ekle</button>
                            <a href="doktorlar.php" class="btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 