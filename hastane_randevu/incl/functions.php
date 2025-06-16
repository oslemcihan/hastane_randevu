<?php
/**
 * Hastane Yönetim Sistemi - Genel Fonksiyonlar
 */

// Veritabanı bağlantısı yoksa dahil et
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

/**
 * Güvenli input temizleme
 *
 * @param string $data
 * @return string
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Email format kontrolü
 *
 * @param string $email
 * @return bool
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Şifre güvenlik kontrolü
 * En az 6 karakter olmalı (geliştirilebilir: sayı, büyük harf vs.)
 *
 * @param string $password
 * @return bool
 */
function validate_password($password) {
    return strlen($password) >= 6;
}

/**
 * Kullanıcının oturum açıp açmadığını kontrol eder
 *
 * @return bool
 */
function check_login() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Kullanıcıyı rolüne göre yönlendir
 *
 * @return void
 */
function redirect_by_role() {
    if (!check_login()) {
        header('Location: ../login.php');
        exit;
    }

    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'doktor':
            header('Location: /doctor/dashboard.php');
            break;
        case 'patient':
            header('Location: /patient/dashboard.php');
            break;
        default:
            header('Location: /login.php');
    }

    exit;
}

/**
 * Flash mesaj gösterme
 *
 * @param string $name
 * @param string|null $message
 * @return void|string
 */
function flash_message($name, $message = null) {
    if ($message !== null) {
        $_SESSION['flash_' . $name] = $message;
    } elseif (isset($_SESSION['flash_' . $name])) {
        $msg = $_SESSION['flash_' . $name];
        unset($_SESSION['flash_' . $name]);
        return $msg;
    }
    return '';
}
