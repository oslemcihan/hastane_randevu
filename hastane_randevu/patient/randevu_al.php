<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

checkLogin();
checkRole('hasta');

$brans_id = isset($_GET['brans_id']) ? intval($_GET['brans_id']) : 0;

$doktorlar = [];
if ($brans_id) {
    $stmt = $pdo->prepare("
        SELECT d.id, k.ad, k.soyad 
        FROM doktorlar d 
        JOIN kullanicilar k ON d.kullanici_id = k.id 
        WHERE d.brans_id = ?
    ");
    $stmt->execute([$brans_id]);
    $doktorlar = $stmt->fetchAll();
}


if (isset($_POST['randevu_al'])) {
    $doktor_id = intval($_POST['doktor_id']);
    $tarih     = $_POST['tarih'];
    $saat      = $_POST['saat'];

    
    $stmt = $pdo->prepare("SELECT id FROM hastalar WHERE kullanici_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $hasta_id = $stmt->fetchColumn();

    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM randevular 
        WHERE doktor_id = ? AND tarih = ? AND saat = ?
    ");
    $stmt->execute([$doktor_id, $tarih, $saat]);

    if ($stmt->fetchColumn() > 0) {
        $error = "x Bu doktorun bu saatte başka bir randevusu var!";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO randevular (hasta_id, doktor_id, tarih, saat) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$hasta_id, $doktor_id, $tarih, $saat]);
        $success = "✅ Randevunuz başarıyla alındı!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Al - Hasta Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Randevu Al</h1>
            <nav>
                <span style="color: #fff; font-size: 1.2em; background: rgba(255, 255, 255, 0.2); padding: 8px 15px; border-radius: 8px;">
                    <?php echo $_SESSION['name']; ?>
                </span>
                <a href="dashboard.php" class="btn">← Panele Dön</a>
                <a href="../logout.php" class="btn">Çıkış</a>
            </nav>
        </header>

        <main>
            <div class="dashboard">
                <div class="feature">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($brans_id && $doktorlar): ?>
                        <form method="POST" action="randevu_al.php?brans_id=<?= $brans_id ?>" class="appointment-form">
                            <div class="form-group">
                                <label for="doktor_id">Doktor Seçimi:</label>
                                <select name="doktor_id" id="doktor_id" required>
                                    <option value="">-- Doktor Seçin --</option>
                                    <?php foreach ($doktorlar as $d): ?>
                                        <option value="<?= $d['id'] ?>">
                                            Dr. <?= htmlspecialchars($d['ad'] . ' ' . $d['soyad'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="tarih">Randevu Tarihi:</label>
                                <input type="date" name="tarih" id="tarih" min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="saat">Randevu Saati:</label>
                                <input type="time" name="saat" id="saat" required>
                            </div>

                            <button type="submit" name="randevu_al" class="btn-primary">Randevu Al</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Lütfen önce branş seçip doktorları listeleyin.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <style>
        .appointment-form {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            background: rgba(255,255,255,0.9);
            font-size: 1em;
            color: #333;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: rgba(107, 115, 255, 0.5);
            box-shadow: 0 0 0 2px rgba(107, 115, 255, 0.2);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }

        .alert-error {
            background: rgba(255, 82, 82, 0.1);
            color: #ff5252;
            border: 1px solid rgba(255, 82, 82, 0.2);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .alert-info {
            background: rgba(33, 150, 243, 0.1);
            color: #2196f3;
            border: 1px solid rgba(33, 150, 243, 0.2);
            padding: 30px;
            font-size: 1.1em;
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            font-size: 1.1em;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .appointment-form {
                padding: 10px;
            }
        }
    </style>
</body>
</html>