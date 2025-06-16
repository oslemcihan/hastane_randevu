<?php
// Oturum başlatılmamışsa başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kullanıcının giriş yapmış olup olmadığını kontrol eder
 */
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit();
    }
}

/**
 * Kullanıcının belirli bir role sahip olup olmadığını kontrol eder
 */
function checkRole($required_role) {
    checkLogin(); // Önce giriş kontrolü yap
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header('Location: /login.php?error=yetki');
        exit();
    }
}

/**
 * Kullanıcının giriş yapmış olup olmadığını döndürür
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Mevcut kullanıcının ID'sini döndürür
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Mevcut kullanıcının rolünü döndürür
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Mevcut kullanıcının tam adını döndürür
 */
function getCurrentUserName() {
    return ($_SESSION['ad'] ?? '') . ' ' . ($_SESSION['soyad'] ?? '');
}

/**
 * Kullanıcı oturumunu sonlandırır
 */
function logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: /login.php');
    exit();
}

// Fonksiyonlar config/database.php'de tanımlı olduğu için burada tekrar tanımlanmıyor.