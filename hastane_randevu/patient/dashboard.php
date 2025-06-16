<?php
require_once '../config/database.php';
require_once '../auth/auth.php';
checkLogin();
checkRole('hasta');

// Hasta ID'sini al
$stmt = $pdo->prepare("SELECT id FROM hastalar WHERE kullanici_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$hasta = $stmt->fetch();
$hasta_id = $hasta['id'];

// En son 5 randevuyu al
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        k.ad as doktor_ad, 
        k.soyad as doktor_soyad, 
        b.ad as brans_ad,
        CASE 
            WHEN r.durum = 'beklemede' THEN 'beklemede'
            WHEN r.durum = 'onaylandi' OR r.durum = 'onaylandı' THEN 'onaylandı'
            WHEN r.durum = 'iptal' OR r.durum = 'iptal_edildi' THEN 'iptal'
            WHEN r.durum = 'tamamlandi' OR r.durum = 'tamamlandı' THEN 'tamamlandı'
            ELSE r.durum
        END as durum_normalized
    FROM randevular r
    JOIN doktorlar d ON r.doktor_id = d.id
    JOIN kullanicilar k ON d.kullanici_id = k.id
    JOIN branslar b ON d.brans_id = b.id
    WHERE r.hasta_id = ?
    ORDER BY r.tarih DESC, r.saat DESC
    LIMIT 5
");
$stmt->execute([$hasta_id]);
$randevular = $stmt->fetchAll();

// Branşları al
$branslar = $pdo->query("SELECT id, ad FROM branslar")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Paneli - <?php echo $_SESSION['name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .table-responsive {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            color: #333;
        }

        th {
            background: rgba(107, 115, 255, 0.2);
            color: #2d3748;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            font-size: 1.1em;
            border-bottom: 2px solid rgba(107, 115, 255, 0.3);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            color: #2d3748;
            font-size: 1em;
        }

        tr:hover {
            background: rgba(107, 115, 255, 0.05);
        }

        .status {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }
        
        .status.beklemede {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .status.onaylandi {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .status.iptal {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .status.tamamlandı {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .feature {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .feature h3 {
            color: #2d3748;
            font-size: 1.4em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(107, 115, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Hasta Paneli</h1>
            <nav>
                <span style="color: #fff; font-size: 1.2em; background: rgba(255, 255, 255, 0.2); padding: 8px 15px; border-radius: 8px;">
                    <?php echo $_SESSION['name']; ?>
                </span>
                <a href="../logout.php" class="btn">Çıkış</a>
            </nav>
        </header>

        <main>
            <div class="dashboard">
                <div class="feature" style="margin-bottom: 30px;">
                    <h3>Randevu Al</h3>
                    <form method="GET" action="randevu_al.php" style="margin: 20px 0;">
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="flex: 1;">
                                <label for="brans" style="display: block; margin-bottom: 8px; color: #333;">Branş Seçimi:</label>
                                <select name="brans_id" id="brans" required style="width: 100%; padding: 10px; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.9);">
                                    <option value="">-- Branş Seçin --</option>
                                    <?php foreach ($branslar as $brans): ?>
                                        <option value="<?php echo $brans['id']; ?>"><?php echo $brans['ad']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary" style="margin-top: 24px;">Doktorları Göster</button>
                        </div>
                    </form>
                </div>

                <div class="feature">
                    <h3>Son Randevularınız</h3>
                    <?php if (count($randevular) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Saat</th>
                                        <th>Doktor</th>
                                        <th>Branş</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($randevular as $r): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($r['tarih'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($r['saat'])); ?></td>
                                            <td>Dr. <?php echo $r['doktor_ad'] . ' ' . $r['doktor_soyad']; ?></td>
                                            <td><?php echo $r['brans_ad']; ?></td>
                                            <td>
                                                <?php
                                                $durum = strtolower(trim($r['durum']));
                                                $durum_text = '';
                                                $durum_class = '';

                                                switch($durum) {
                                                    case 'onaylandi':
                                                    case 'onaylandı':
                                                    case 'approved':
                                                        $durum_text = 'Onaylandı';
                                                        $durum_class = 'onaylandi';
                                                        break;
                                                    case 'beklemede':
                                                    case 'waiting':
                                                        $durum_text = 'Beklemede';
                                                        $durum_class = 'beklemede';
                                                        break;
                                                    case 'iptal':
                                                    case 'cancelled':
                                                        $durum_text = 'İptal Edildi';
                                                        $durum_class = 'iptal';
                                                        break;
                                                    default:
                                                        $durum_text = 'Beklemede';
                                                        $durum_class = 'beklemede';
                                                }
                                                ?>
                                                <span class="status <?php echo $durum_class; ?>">
                                                    <?php echo $durum_text; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color: #666; text-align: center; padding: 20px;">Henüz randevunuz bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>