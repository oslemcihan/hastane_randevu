// js/main.js

document.addEventListener('DOMContentLoaded', function() {
    
    // Branş seçildiğinde doktorları getir
    const bransSelect = document.getElementById('brans');
    const doktorSelect = document.getElementById('doktor_id');
    const tarihInput = document.getElementById('tarih');
    const saatSelect = document.getElementById('saat');
    
    if (bransSelect) {
        bransSelect.addEventListener('change', function() {
            const bransId = this.value;
            
            if (bransId) {
                // Loading göster
                doktorSelect.innerHTML = '<option value="">Yükleniyor...</option>';
                
                // AJAX ile doktorları getir
                fetch('../ajax/get_doctors.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'brans_id=' + bransId
                })
                .then(response => response.json())
                .then(data => {
                    doktorSelect.innerHTML = '<option value="">Doktor seçiniz</option>';
                    
                    if (data.success) {
                        data.doctors.forEach(doctor => {
                            const option = document.createElement('option');
                            option.value = doctor.id;
                            option.textContent = `Dr. ${doctor.ad} ${doctor.soyad} - ${doctor.uzmanlik}`;
                            doktorSelect.appendChild(option);
                        });
                    } else {
                        doktorSelect.innerHTML = '<option value="">Doktor bulunamadı</option>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    doktorSelect.innerHTML = '<option value="">Hata oluştu</option>';
                });
            } else {
                doktorSelect.innerHTML = '<option value="">Önce branş seçiniz</option>';
            }
            
            // Saat seçimini temizle
            saatSelect.innerHTML = '<option value="">Tarih seçiniz</option>';
        });
    }
    
    // Tarih değiştiğinde müsait saatleri getir
    if (tarihInput) {
        tarihInput.addEventListener('change', function() {
            const tarih = this.value;
            const doktorId = doktorSelect.value;
            
            if (tarih && doktorId) {
                // Loading göster
                saatSelect.innerHTML = '<option value="">Yükleniyor...</option>';
                
                // AJAX ile müsait saatleri getir
                fetch('../ajax/get_available_times.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `doktor_id=${doktorId}&tarih=${tarih}`
                })
                .then(response => response.json())
                .then(data => {
                    saatSelect.innerHTML = '<option value="">Saat seçiniz</option>';
                    
                    if (data.success && data.times.length > 0) {
                        data.times.forEach(time => {
                            const option = document.createElement('option');
                            option.value = time;
                            option.textContent = time;
                            saatSelect.appendChild(option);
                        });
                    } else {
                        saatSelect.innerHTML = '<option value="">Bu tarih için müsait saat yok</option>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    saatSelect.innerHTML = '<option value="">Hata oluştu</option>';
                });
            } else {
                saatSelect.innerHTML = '<option value="">Önce doktor seçiniz</option>';
            }
        });
    }
    
    // Doktor değiştiğinde saat seçimini temizle
    if (doktorSelect) {
        doktorSelect.addEventListener('change', function() {
            saatSelect.innerHTML = '<option value="">Tarih seçiniz</option>';
        });
    }
    
    // Hasta notu ekleme (doktor paneli için)
    const noteForm = document.getElementById('noteForm');
    if (noteForm) {
        noteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Kaydediliyor...';
            submitBtn.disabled = true;
            
            fetch('../ajax/add_notes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Not başarıyla kaydedildi!', 'success');
                    // Formu temizle
                    this.reset();
                } else {
                    showAlert(data.message || 'Hata oluştu!', 'error');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showAlert('Bir hata oluştu!', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
    
    // Admin kullanıcı arama
    const userSearch = document.getElementById('userSearch');
    if (userSearch) {
        userSearch.addEventListener('input', function() {
            const searchTerm = this.value;
            
            if (searchTerm.length >= 2) {
                fetch('../ajax/search_users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'search=' + encodeURIComponent(searchTerm)
                })
                .then(response => response.json())
                .then(data => {
                    const resultDiv = document.getElementById('searchResults');
                    if (data.success && data.users.length > 0) {
                        let html = '<div class="search-results"><h4>Arama Sonuçları:</h4>';
                        data.users.forEach(user => {
                            html += `
                                <div class="user-result">
                                    <span>${user.ad} ${user.soyad} (${user.email}) - ${user.rol}</span>
                                    <button onclick="deleteUser(${user.id})" class="btn-danger">Sil</button>
                                </div>
                            `;
                        });
                        html += '</div>';
                        resultDiv.innerHTML = html;
                    } else {
                        resultDiv.innerHTML = '<p>Kullanıcı bulunamadı</p>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                });
            } else {
                document.getElementById('searchResults').innerHTML = '';
            }
        });
    }
});

// Yardımcı fonksiyonlar
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${type}`;
    alertDiv.textContent = message;
    
    // Sayfanın üstüne ekle
    document.body.insertBefore(alertDiv, document.body.firstChild);
    
    // 3 saniye sonra kaldır
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

function deleteUser(userId) {
    if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
        fetch('../ajax/delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + userId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Kullanıcı başarıyla silindi!', 'success');
                // Arama sonuçlarını yenile
                document.getElementById('userSearch').dispatchEvent(new Event('input'));
            } else {
                showAlert(data.message || 'Hata oluştu!', 'error');
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            showAlert('Bir hata oluştu!', 'error');
        });
    }
}

// Randevu iptal etme
function cancelAppointment(appointmentId) {
    if (confirm('Randevuyu iptal etmek istediğinizden emin misiniz?')) {
        fetch('../ajax/cancel_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'appointment_id=' + appointmentId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Randevu başarıyla iptal edildi!', 'success');
                location.reload(); // Sayfayı yenile
            } else {
                showAlert(data.message || 'Hata oluştu!', 'error');
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            showAlert('Bir hata oluştu!', 'error');
        });
    }
}