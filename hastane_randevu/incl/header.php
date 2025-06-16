<?php
// Oturum kontrolü
if (!isset($_SESSION)) {
    session_start();
}

// Güvenlik kontrolü - Kullanıcı giriş yapmış mı?
function checkLogin() {
    if (!isset($_SESSION['kullanici_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

// Kullanıcı rolü kontrolü
function checkRole($requiredRole) {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $requiredRole) {
        header("Location: ../index.php");
        exit();
    }
}

// Güvenli çıktı için fonksiyon
function safe_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// CSRF token oluşturma
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token doğrulama
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Tarih formatı düzenleme
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

// Saat formatı düzenleme
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Branş adı getirme
function getBransAdi($brans_id, $conn) {
    $query = "SELECT ad FROM branslar WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $brans_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['ad'];
    }
    return "Bilinmeyen Branş";
}

// Doktor adı getirme
function getDoktorAdi($doktor_id, $conn) {
    $query = "SELECT k.ad, k.soyad FROM doktorlar d 
              INNER JOIN kullanicilar k ON d.kullanici_id = k.id 
              WHERE d.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $doktor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return "Dr. " . $row['ad'] . " " . $row['soyad'];
    }
    return "Bilinmeyen Doktor";
}

// Hasta adı getirme
function getHastaAdi($hasta_id, $conn) {
    $query = "SELECT k.ad, k.soyad FROM hastalar h 
              INNER JOIN kullanicilar k ON h.kullanici_id = k.id 
              WHERE h.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $hasta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['ad'] . " " . $row['soyad'];
    }
    return "Bilinmeyen Hasta";
}

// Randevu durumu renk kodu
function getRandevuDurumRenk($durum) {
    switch($durum) {
        case 'beklemede':
            return 'warning';
        case 'tamamlandi':
            return 'success';
        case 'iptal':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Randevu durumu Türkçe
function getRandevuDurumTurkce($durum) {
    switch($durum) {
        case 'beklemede':
            return 'Beklemede';
        case 'tamamlandi':
            return 'Tamamlandı';
        case 'iptal':
            return 'İptal Edildi';
        default:
            return 'Bilinmeyen';
    }
}

// Başarı mesajı gösterme
function showSuccessMessage($message) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>' . safe_output($message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Hata mesajı gösterme
function showErrorMessage($message) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>' . safe_output($message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Bilgi mesajı gösterme
function showInfoMessage($message) {
    echo '<div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>' . safe_output($message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Sayfa başlığı ayarlama
function setPageTitle($title) {
    return "Hastane Yönetim Sistemi - " . $title;
}

// Navigation menü oluşturma
function createNavigation($currentPage = '') {
    $navigation = '';
    
    if (isset($_SESSION['rol'])) {
        $navigation .= '<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-hospital-alt me-2"></i>Hastane Sistemi
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">';
        
        // Rol bazlı menü öğeleri
        switch($_SESSION['rol']) {
            case 'hasta':
                $navigation .= '
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'dashboard' ? 'active' : '') . '" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'randevu-al' ? 'active' : '') . '" href="randevu_al.php">
                            <i class="fas fa-calendar-plus me-1"></i>Randevu Al
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'randevularim' ? 'active' : '') . '" href="randevularim.php">
                            <i class="fas fa-calendar-check me-1"></i>Randevularım
                        </a>
                    </li>';
                break;
                
            case 'doktor':
                $navigation .= '
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'dashboard' ? 'active' : '') . '" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'randevularim' ? 'active' : '') . '" href="randevularim.php">
                            <i class="fas fa-calendar-check me-1"></i>Randevularım
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'hastalar' ? 'active' : '') . '" href="hastalar.php">
                            <i class="fas fa-users me-1"></i>Hastalarım
                        </a>
                    </li>';
                break;
                
            case 'admin':
                $navigation .= '
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'dashboard' ? 'active' : '') . '" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'doktorlar' ? 'active' : '') . '" href="doktor_yonetimi.php">
                            <i class="fas fa-user-md me-1"></i>Doktorlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'branslar' ? 'active' : '') . '" href="brans_yonetimi.php">
                            <i class="fas fa-list-alt me-1"></i>Branşlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ' . ($currentPage == 'istatistikler' ? 'active' : '') . '" href="istatistikler.php">
                            <i class="fas fa-chart-bar me-1"></i>İstatistikler
                        </a>
                    </li>';
                break;
        }
        
        $navigation .= '
                    </ul>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>' . safe_output($_SESSION['ad'] . ' ' . $_SESSION['soyad']) . '
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profil.php">
                                    <i class="fas fa-user-edit me-2"></i>Profil
                                </a></li>
                                <li><a class="dropdown-item" href="sifre_degistir.php">
                                    <i class="fas fa-key me-2"></i>Şifre Değiştir
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>';
    }
    
    return $navigation;
}

// Breadcrumb oluşturma
function createBreadcrumb($items) {
    $breadcrumb = '<nav aria-label="breadcrumb">
        <ol class="breadcrumb">';
    
    $lastItem = end($items);
    foreach ($items as $text => $url) {
        if ($url === $lastItem && $url === null) {
            $breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . safe_output($text) . '</li>';
        } else {
            $breadcrumb .= '<li class="breadcrumb-item"><a href="' . safe_output($url) . '">' . safe_output($text) . '</a></li>';
        }
    }
    
    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}

// Loading spinner
function showLoadingSpinner() {
    return '<div class="d-flex justify-content-center my-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Yükleniyor...</span>
                </div>
            </div>';
}

// Sayfalama fonksiyonu
function createPagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $pagination = '<nav aria-label="Sayfa navigasyonu">
        <ul class="pagination justify-content-center">';
    
    // Önceki sayfa
    if ($currentPage > 1) {
        $pagination .= '<li class="page-item">
            <a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Önceki</a>
        </li>';
    }
    
    // Sayfa numaraları
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($start > 2) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '">
            <a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a>
        </li>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Sonraki sayfa
    if ($currentPage < $totalPages) {
        $pagination .= '<li class="page-item">
            <a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Sonraki</a>
        </li>';
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}
?>
