
<?php
session_start();
require_once '../config/database.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Genel istatistikleri çek
$general_stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM hastalar) as total_patients,
        (SELECT COUNT(*) FROM doktorlar) as total_doctors,
        (SELECT COUNT(*) FROM randevular) as total_appointments,
        (SELECT COUNT(*) FROM randevular WHERE durum = 'tamamlandi') as completed_appointments,
        (SELECT COUNT(*) FROM randevular WHERE durum = 'beklemede') as pending_appointments,
        (SELECT COUNT(*) FROM randevular WHERE durum = 'iptal') as cancelled_appointments
";

$general_result = mysqli_query($conn, $general_stats_query);
$general_stats = mysqli_fetch_assoc($general_result);

// Branş bazında randevu istatistikleri
$branch_stats_query = "
    SELECT b.ad as brans_adi, 
           COUNT(r.id) as randevu_sayisi,
           COUNT(CASE WHEN r.durum = 'tamamlandi' THEN 1 END) as tamamlanan
    FROM branslar b
    LEFT JOIN doktorlar d ON b.id = d.brans_id
    LEFT JOIN randevular r ON d.id = r.doktor_id
    GROUP BY b.id, b.ad
    ORDER BY randevu_sayisi DESC
";

$branch_result = mysqli_query($conn, $branch_stats_query);

// Aylık randevu trendi
$monthly_trend_query = "
    SELECT 
        DATE_FORMAT(tarih, '%Y-%m') as ay,
        COUNT(*) as randevu_sayisi
    FROM randevular 
    WHERE tarih >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tarih, '%Y-%m')
    ORDER BY ay
";

$monthly_result = mysqli_query($conn, $monthly_trend_query);

// En aktif doktorlar
$active_doctors_query = "
    SELECT 
        CONCAT(k.ad, ' ', k.soyad) as doktor_adi,
        b.ad as brans_adi,
        COUNT(r.id) as randevu_sayisi,
        AVG(CASE WHEN r.durum = 'tamamlandi' THEN 1 ELSE 0 END) * 100 as basari_orani
    FROM doktorlar d
    JOIN kullanicilar k ON d.kullanici_id = k.id
    JOIN branslar b ON d.brans_id = b.id
    LEFT JOIN randevular r ON d.id = r.doktor_id
    GROUP BY d.id, doktor_adi, brans_adi
    HAVING randevu_sayisi > 0
    ORDER BY randevu_sayisi DESC
    LIMIT 10
";

$doctors_result = mysqli_query($conn, $active_doctors_query);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İstatistikler - Hastane Sistemi</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <h3>Yönetici Paneli</h3>
            <ul>
                <li><a href="dashboard.php">Ana Sayfa</a></li>
                <li><a href="manage_doctors.php">Doktor Yönetimi</a></li>
                <li><a href="manage_departments.php">Branş Yönetimi</a></li>
                <li><a href="statistics.php" class="active">İstatistikler</a></li>
                <li><a href="../logout.php">Çıkış</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="header">
                <h1>Sistem İstatistikleri</h1>
            </div>

            <!-- Genel İstatistikler -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3><?php echo $general_stats['total_patients']; ?></h3>
                    <p>Toplam Hasta</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $general_stats['total_doctors']; ?></h3>
                    <p>Toplam Doktor</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $general_stats['total_appointments']; ?></h3>
                    <p>Toplam Randevu</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $general_stats['completed_appointments']; ?></h3>
                    <p>Tamamlanan Randevu</p>
                </div>
            </div>

            <!-- Randevu Durum Grafiği -->
            <div class="chart-section">
                <h2>Randevu Durumu Dağılımı</h2>
                <div class="chart-container">
                    <canvas id="appointmentStatusChart"></canvas>
                </div>
            </div>

            <!-- Branş Bazında İstatistikler -->
            <div class="branch-stats">
                <h2>Branş Bazında Randevu İstatistikleri</h2>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Branş</th>
                            <th>Toplam Randevu</th>
                            <th>Tamamlanan</th>
                            <th>Başarı Oranı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($branch = mysqli_fetch_assoc($branch_result)): ?>
                        <tr>
                            <td><?php echo $branch['brans_adi']; ?></td>
                            <td><?php echo $branch['randevu_sayisi']; ?></td>
                            <td><?php echo $branch['tamamlanan']; ?></td>
                            <td>
                                <?php 
                                $oran = $branch['randevu_sayisi'] > 0 ? 
                                    round(($branch['tamamlanan'] / $branch['randevu_sayisi']) * 100, 1) : 0;
                                echo $oran . '%';
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Aylık Trend Grafiği -->
            <div class="chart-section">
                <h2>Aylık Randevu Trendi</h2>
                <div class="chart-container">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>

            <!-- En Aktif Doktorlar -->
            <div class="active-doctors">
                <h2>En Aktif Doktorlar</h2>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Doktor</th>
                            <th>Branş</th>
                            <th>Randevu Sayısı</th>
                            <th>Başarı Oranı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                        <tr>
                            <td><?php echo $doctor['doktor_adi']; ?></td>
                            <td><?php echo $doctor['brans_adi']; ?></td>
                            <td><?php echo $doctor['randevu_sayisi']; ?></td>
                            <td><?php echo round($doctor['basari_orani'], 1); ?>%</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Randevu Durum Grafiği
        const statusCtx = document.getElementById('appointmentStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Tamamlanan', 'Beklemede', 'İptal'],
                datasets: [{
                    data: [
                        <?php echo $general_stats['completed_appointments']; ?>,
                        <?php echo $general_stats['pending_appointments']; ?>,
                        <?php echo $general_stats['cancelled_appointments']; ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Aylık Trend Grafiği
        const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    mysqli_data_seek($monthly_result, 0);
                    while ($month = mysqli_fetch_assoc($monthly_result)) {
                        echo '"' . $month['ay'] . '",';
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Randevu Sayısı',
                    data: [
                        <?php 
                        mysqli_data_seek($monthly_result, 0);
                        while ($month = mysqli_fetch_assoc($monthly_result)) {
                            echo $month['randevu_sayisi'] . ',';
                        }
                        ?>
                    ],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
