<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

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

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Randevularım</h2>
            
            <!-- Bugünkü Randevular -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-calendar-day"></i> Bugünkü Randevular (<?php echo date('d.m.Y'); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bugun_randevular)): ?>
                        <p class="text-muted">Bugün için randevunuz bulunmamaktadır.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                                <?php
                                                $durum_class = '';
                                                $durum_text = '';
                                                switch($randevu['durum']) {
                                                    case 'beklemede':
                                                        $durum_class = 'badge-warning';
                                                        $durum_text = 'Beklemede';
                                                        break;
                                                    case 'tamamlandi':
                                                        $durum_class = 'badge-success';
                                                        $durum_text = 'Tamamlandı';
                                                        break;
                                                    case 'iptal':
                                                        $durum_class = 'badge-danger';
                                                        $durum_text = 'İptal';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $durum_class; ?>"><?php echo $durum_text; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($randevu['durum'] === 'beklemede'): ?>
                                                    <button class="btn btn-sm btn-success" 
                                                            onclick="showPatientNoteModal(<?php echo $randevu['id']; ?>, '<?php echo htmlspecialchars($randevu['ad'] . ' ' . $randevu['soyad']); ?>')">
                                                        <i class="fas fa-notes-medical"></i> Muayene
                                                    </button>
                                                <?php endif; ?>
                                                <a href="patient_notes.php?patient_id=<?php echo $randevu['hasta_id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-history"></i> Geçmiş
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

            <!-- Gelecek Randevular -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-calendar-alt"></i> Gelecek Randevular</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($gelecek_randevular)): ?>
                        <p class="text-muted">Gelecek için randevunuz bulunmamaktadır.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                                <a href="patient_notes.php?patient_id=<?php echo $randevu['hasta_id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-history"></i> Geçmiş
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

            <!-- Geçmiş Randevular -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-history"></i> Geçmiş Randevular</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($gecmis_randevular)): ?>
                        <p class="text-muted">Geçmiş randevunuz bulunmamaktadır.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                                <?php
                                                switch($randevu['durum']) {
                                                    case 'tamamlandi':
                                                        echo '<span class="badge badge-success">Tamamlandı</span>';
                                                        break;
                                                    case 'iptal':
                                                        echo '<span class="badge badge-danger">İptal</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge badge-warning">Beklemede</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($randevu['tani']): ?>
                                                    <small><?php echo htmlspecialchars(substr($randevu['tani'], 0, 50)) . '...'; ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="patient_notes.php?patient_id=<?php echo $randevu['hasta_id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Detay
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
        </div>
    </div>
</div>

<!-- Hasta Muayene Modalı -->
<div class="modal fade" id="patientNoteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hasta Muayenesi</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="patientNoteForm">
                <div class="modal-body">
                    <input type="hidden" id="randevu_id" name="randevu_id">
                    <div class="form-group">
                        <label for="hasta_adi"><strong>Hasta:</strong></label>
                        <p id="hasta_adi" class="form-control-plaintext"></p>
                    </div>
                    <div class="form-group">
                        <label for="tani">Tanı:</label>
                        <textarea class="form-control" id="tani" name="tani" rows="3" placeholder="Tanı bilgilerini giriniz..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="tedavi">Tedavi:</label>
                        <textarea class="form-control" id="tedavi" name="tedavi" rows="3" placeholder="Tedavi bilgilerini giriniz..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="notlar">Notlar:</label>
                        <textarea class="form-control" id="notlar" name="notlar" rows="4" placeholder="Ek notlarınızı giriniz..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

<?php include '../includes/footer.php'; ?>
Improve
Explain
