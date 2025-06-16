</div> <!-- container-fluid kapatma -->
</div> <!-- wrapper kapatma -->

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><i class="fas fa-hospital"></i> Hastane Yönetim Sistemi</h5>
                <p class="mb-0">Modern hastane randevu ve yönetim çözümü</p>
                <small class="text-muted">© <?php echo date('Y'); ?> Tüm hakları saklıdır.</small>
            </div>
            <div class="col-md-3">
                <h6>Hızlı Linkler</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-light"><i class="fas fa-angle-right"></i> Ana Sayfa</a></li>
                    <li><a href="#" class="text-light"><i class="fas fa-angle-right"></i> Hakkımızda</a></li>
                    <li><a href="#" class="text-light"><i class="fas fa-angle-right"></i> İletişim</a></li>
                    <li><a href="#" class="text-light"><i class="fas fa-angle-right"></i> Gizlilik</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>İletişim</h6>
                <ul class="list-unstyled text-muted">
                    <li><i class="fas fa-phone"></i> +90 (555) 123-4567</li>
                    <li><i class="fas fa-envelope"></i> info@hospital.com</li>
                    <li><i class="fas fa-map-marker-alt"></i> İstanbul, Türkiye</li>
                </ul>
            </div>
        </div>
        <hr class="my-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <small class="text-muted">
                    Sistem Durumu: <span class="text-success"><i class="fas fa-circle"></i> Aktif</span>
                </small>
            </div>
            <div class="col-md-6 text-md-right">
                <small class="text-muted">
                    Son Güncelleme: <?php echo date('d.m.Y H:i'); ?>
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

<!-- Global JavaScript -->
<script>
// Genel AJAX ayarları
$.ajaxSetup({
    beforeSend: function() {
        // Loading göstergesi eklenebilir
        $('body').css('cursor', 'wait');
    },
    complete: function() {
        $('body').css('cursor', 'default');
    },
    error: function(xhr, status, error) {
        console.error('AJAX Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyiniz.');
    }
});

// Otomatik oturum kapatma uyarısı (30 dakika)
let sessionTimeout;
let warningTimeout;

function resetSessionTimer() {
    clearTimeout(sessionTimeout);
    clearTimeout(warningTimeout);
    
    // 25 dakika sonra uyarı
    warningTimeout = setTimeout(function() {
        if (confirm('Oturumunuz 5 dakika içinde sona erecek. Devam etmek istiyor musunuz?')) {
            // Kullanıcı aktif olduğunu belirtti, timer'ı sıfırla
            resetSessionTimer();
        }
    }, 25 * 60 * 1000);
    
    // 30 dakika sonra otomatik çıkış
    sessionTimeout = setTimeout(function() {
        alert('Oturumunuz sona erdi. Giriş sayfasına yönlendiriliyorsunuz.');
        window.location.href = 'logout.php';
    }, 30 * 60 * 1000);
}

// Sayfa yüklendiğinde ve kullanıcı aktivitesinde timer'ı başlat
$(document).ready(function() {
    resetSessionTimer();
    
    // Kullanıcı aktivitelerini dinle
    $(document).on('click keypress scroll mousemove', function() {
        resetSessionTimer();
    });
});

// Form validasyonu için genel fonksiyon
function validateForm(formId) {
    let isValid = true;
    const form = document.getElementById(formId);
    
    // Required alanları kontrol et
    $(form).find('[required]').each(function() {
        if (!$(this).val().trim()) {
            $(this).addClass('is-invalid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Email formatını kontrol et
    $(form).find('input[type="email"]').each(function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid');
            isValid = false;
        }
    });
    
    return isValid;
}

// Tarih formatını düzenle
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR');
}

// Zaman formatını düzenle
function formatTime(timeString) {
    return timeString.substring(0, 5);
}

// Toast bildirimi göster
function showToast(message, type = 'info') {
    const toastHtml = `
        <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
            <div class="toast-header bg-${type} text-white">
                <strong class="mr-auto">Bildirim</strong>
                <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast">
                    <span>&times;</span>
                </button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    // Toast container yoksa oluştur
    if (!$('#toast-container').length) {
        $('body').append('<div id="toast-container" class="position-fixed" style="top: 20px; right: 20px; z-index: 9999;"></div>');
    }
    
    const $toast = $(toastHtml);
    $('#toast-container').append($toast);
    $toast.toast('show');
    
    // Toast kapandığında DOM'dan kaldır
    $toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// Onay modalı göster
function showConfirmModal(title, message, callback) {
    const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-primary" id="confirmButton">Onayla</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Mevcut modal varsa kaldır
    $('#confirmModal').remove();
    
    $('body').append(modalHtml);
    
    $('#confirmButton').click(function() {
        $('#confirmModal').modal('hide');
        if (typeof callback === 'function') {
            callback();
        }
    });
    
    $('#confirmModal').modal('show');
    
    // Modal kapandığında DOM'dan kaldır
    $('#confirmModal').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

// Sayfa yüklenme animasyonu
$(window).on('load', function() {
    $('.page-loader').fadeOut();
});

// Responsive tablo için scroll göstergesi
$(document).ready(function() {
    $('.table-responsive').each(function() {
        const $table = $(this);
        const $scrollIndicator = $('<div class="scroll-indicator">Kaydırmak için sürükleyin →</div>');
        
        if ($table.get(0).scrollWidth > $table.innerWidth()) {
            $table.after($scrollIndicator);
            
            $table.scroll(function() {
                if ($table.scrollLeft() > 0) {
                    $scrollIndicator.hide();
                }
            });
        }
    });
});
</script>

<!-- Sayfa özel JavaScript dosyaları buraya eklenebilir -->
<?php if (isset($additional_js)): ?>
    <?php foreach ($additional_js as $js_file): ?>
        <script src="<?php echo $js_file; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>