<?php
session_start();
require_once '../config/database.php';

// Doktor kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doktor') {
    header('Location: ../login.php');
    exit;
}

// Doktor ID'sini al
$stmt_doktor = $pdo->prepare("SELECT id FROM doktorlar WHERE kullanici_id = ?");
$stmt_doktor->execute([$_SESSION['user_id']]);
$doktor = $stmt_doktor->fetch();

if (!$doktor) {
    header('Location: ../login.php');
    exit;
}

$doktor_id = $doktor['id'];

// Bugünün tarihi
$bugun = date('Y-m-d');

// Gelecek randevular için onay/iptal işlemleri
if (isset($_POST['onayla_gelecek']) && isset($_POST['randevu_id'])) {
    $stmt = $pdo->prepare("UPDATE randevular SET durum = 'onaylandı' WHERE id = ?");
    $stmt->execute([$_POST['randevu_id']]);
    header("Location: appointments.php");
    exit();
}
if (isset($_POST['iptal_gelecek']) && isset($_POST['randevu_id'])) {
    $stmt = $pdo->prepare("UPDATE randevular SET durum = 'iptal' WHERE id = ?");
    $stmt->execute([$_POST['randevu_id']]);
    header("Location: appointments.php");
    exit();
}

// Bugünkü randevuları al
$stmt_bugun = $pdo->prepare("
    SELECT r.*, k.ad, k.soyad, h.dogum_tarihi, h.cinsiyet, h.telefon,
           TIMESTAMPDIFF(YEAR, h.dogum_tarihi, CURDATE()) as yas
    FROM randevular r
    JOIN hastalar h ON r.hasta_id = h.id
    JOIN kullanicilar k ON h.kullanici_id = k.id
    WHERE r.doktor_id = ? AND r.tarih = ?
    ORDER BY r.saat ASC
");
$stmt_bugun->execute([$doktor_id, $bugun]);
$bugun_randevular = $stmt_bugun->fetchAll();

// Gelecek randevuları al
$stmt_gelecek = $pdo->prepare("
    SELECT r.*, k.ad, k.soyad, h.dogum_tarihi, h.cinsiyet, h.telefon,
           TIMESTAMPDIFF(YEAR, h.dogum_tarihi, CURDATE()) as yas
    FROM randevular r
    JOIN hastalar h ON r.hasta_id = h.id
    JOIN kullanicilar k ON h.kullanici_id = k.id
    WHERE r.doktor_id = ? AND r.tarih > ? AND r.durum = 'beklemede'
    ORDER BY r.tarih ASC, r.saat ASC
    LIMIT 10
");
$stmt_gelecek->execute([$doktor_id, $bugun]);
$gelecek_randevular = $stmt_gelecek->fetchAll();

// Geçmiş randevuları al
$stmt_gecmis = $pdo->prepare("
    SELECT r.*, k.ad, k.soyad, h.dogum_tarihi, h.cinsiyet, h.telefon,
           TIMESTAMPDIFF(YEAR, h.dogum_tarihi, CURDATE()) as yas,
           m.tani, m.tedavi, m.notlar
    FROM randevular r
    JOIN hastalar h ON r.hasta_id = h.id
    JOIN kullanicilar k ON h.kullanici_id = k.id
    LEFT JOIN muayene_kaydi m ON r.id = m.randevu_id
    WHERE r.doktor_id = ? AND r.tarih < ?
    ORDER BY r.tarih DESC, r.saat DESC
    LIMIT 20
");
$stmt_gecmis->execute([$doktor_id, $bugun]);
$gecmis_randevular = $stmt_gecmis->fetchAll();

// include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevularım - Doktor Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>Randevularım</h1>
            <nav>
                <a href="dashboard.php" class="btn">← Panele Dön</a>
                <a href="../logout.php" class="btn">Çıkış</a>
            </nav>
        </header>

        <main>
            <div class="dashboard">
                <!-- Bugünkü Randevular -->
                <div class="feature">
                    <h3>Bugünkü Randevular (<?php echo date('d.m.Y'); ?>)</h3>
                    <?php if (empty($bugun_randevular)): ?>
                        <p>Bugün için randevunuz bulunmamaktadır.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Saat</th>
                                        <th>Hasta</th>
                                        <th>Yaş</th>
                                        <th>Cinsiyet</th>
                                        <th>Telefon</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bugun_randevular as $randevu): ?>
                                        <tr>
                                            <td><strong><?php echo date('H:i', strtotime($randevu['saat'])); ?></strong></td>
                                            <td><?php echo htmlspecialchars($randevu['ad'] . ' ' . $randevu['soyad']); ?></td>
                                            <td><?php echo $randevu['yas']; ?></td>
                                            <td><?php echo ucfirst($randevu['cinsiyet']); ?></td>
                                            <td><?php echo $randevu['telefon']; ?></td>
                                            <td>
                                                <span class="status <?php echo $randevu['durum']; ?>">
                                                    <?php echo ucfirst($randevu['durum']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($randevu['durum'] === 'beklemede'): ?>
                                                    <button class="btn-primary" onclick="showPatientNoteModal(<?php echo $randevu['id']; ?>, '<?php echo htmlspecialchars($randevu['ad'] . ' ' . $randevu['soyad']); ?>')">
                                                        Muayene
                                                    </button>
                                                <?php endif; ?>
                                                <a href="patient_notes.php?patient_id=<?php echo $randevu['hasta_id']; ?>" class="btn-primary">
                                                    Geçmiş
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gelecek Randevular -->
                <div class="feature">
                    <h3>Gelecek Randevular</h3>
                    <?php if (empty($gelecek_randevular)): ?>
                        <p>Gelecek için randevunuz bulunmamaktadır.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Saat</th>
                                        <th>Hasta</th>
                                        <th>Yaş</th>
                                        <th>Telefon</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gelecek_randevular as $randevu): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($randevu['tarih'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($randevu['saat'])); ?></td>
                                            <td><?php echo htmlspecialchars($randevu['ad'] . ' ' . $randevu['soyad']); ?></td>
                                            <td><?php echo $randevu['yas']; ?></td>
                                            <td><?php echo $randevu['telefon']; ?></td>
                                            <td>
                                                <?php if ($randevu['durum'] === 'beklemede'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="randevu_id" value="<?php echo $randevu['id']; ?>">
                                                        <button type="submit" name="onayla_gelecek" class="btn-primary">Kabul Et</button>
                                                        <button type="submit" name="iptal_gelecek" class="btn-primary" style="background: #ff4d4d;">Reddet</button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="patient_notes.php?patient_id=<?php echo $randevu['hasta_id']; ?>" class="btn-primary">
                                                    Geçmiş
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Geçmiş Randevular -->
                <div class="feature">
                    <h3>Geçmiş Randevular</h3>
                    <?php if (empty($gecmis_randevular)): ?>
                        <p>Geçmiş randevunuz bulunmamaktadır.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Saat</th>
                                        <th>Hasta</th>
                                        <th>Durum</th>
                                        <th>Tanı</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gecmis_randevular as $randevu): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($randevu['tarih'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($randevu['saat'])); ?></td>
                                            <td><?php echo htmlspecialchars($randevu['ad'] . ' ' . $randevu['soyad']); ?></td>
                                            <td>
                                                <span class="status <?php echo $randevu['durum']; ?>">
                                                    <?php echo ucfirst($randevu['durum']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($randevu['tani']): ?>
                                                    <small><?php echo htmlspecialchars(substr($randevu['tani'], 0, 50)) . '...'; ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="patient_notes.php?patient_id=<?php echo $randevu['hasta_id']; ?>" class="btn-primary">
                                                    Detay
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <style>
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .status.beklemede {
            background: #ffe082;
            color: #7c5700;
        }
        
        .status.tamamlandi {
            background: #81c784;
            color: #1b5e20;
        }
        
        .status.iptal {
            background: #ff8a80;
            color: #b71c1c;
        }
        
        .text-muted {
            color: #888;
        }
        
        .table-responsive {
            overflow-x: auto;
            margin: 20px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            overflow: hidden;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        th {
            background: rgba(107, 115, 255, 0.1);
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background: rgba(107, 115, 255, 0.05);
        }
        
        .btn-primary {
            margin: 0 5px;
            font-size: 0.9em;
            padding: 8px 15px;
        }
    </style>

    <script>
    function showPatientNoteModal(randevuId, hastaAdi) {
        document.getElementById('randevu_id').value = randevuId;
        document.getElementById('hasta_adi').textContent = hastaAdi;
        
        // Formu temizle
        document.getElementById('tani').value = '';
        document.getElementById('tedavi').value = '';
        document.getElementById('notlar').value = '';
        
        $('#patientNoteModal').modal('show');
    }

    // Form gönderme
    document.getElementById('patientNoteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'add_patient_note');
        
        fetch('../ajax/add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu');
        });
    });
    </script>
</body>
</html>
