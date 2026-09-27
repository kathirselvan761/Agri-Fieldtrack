<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * AgriTrak ERP - Multilingual Language Helper
 * Default Language: Tamil (ta)
 * Languages supported: Tamil (ta), English (en), Hindi (hi)
 */

$allowed_languages = ['ta', 'en', 'hi'];

// Handle direct language switch via GET parameter
if (isset($_GET['lang'])) {
    $requested_lang = strtolower(trim($_GET['lang']));
    if (in_array($requested_lang, $allowed_languages)) {
        $_SESSION['lang'] = $requested_lang;
        setcookie('agri_lang', $requested_lang, time() + (86400 * 365), '/');
        
        // Remove 'lang' parameter from current URL and redirect to avoid dirty URL
        $current_url = strtok($_SERVER["REQUEST_URI"], '?');
        $query_params = $_GET;
        unset($query_params['lang']);
        $new_query = http_build_query($query_params);
        $redirect_url = $current_url . ($new_query ? '?' . $new_query : '');
        
        header("Location: " . $redirect_url);
        exit();
    }
}

/**
 * Detect current language using Priority:
 * 1. User selected language (Session / Cookie / LocalStorage sync)
 * 2. Default: Tamil ('ta')
 */
function getCurrentLang()
{
    global $allowed_languages;
    
    // 1. Session preference
    if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_languages)) {
        return $_SESSION['lang'];
    }
    
    // 2. Cookie preference
    if (!empty($_COOKIE['agri_lang']) && in_array($_COOKIE['agri_lang'], $allowed_languages)) {
        $_SESSION['lang'] = $_COOKIE['agri_lang'];
        return $_COOKIE['agri_lang'];
    }
    
    // 3. Default to Tamil ('ta')
    $_SESSION['lang'] = 'ta';
    return 'ta';
}

$currentLang = getCurrentLang();

// Load target language translations
$lang_file = __DIR__ . "/../languages/{$currentLang}.php";
$LANG_DATA = [];
if (file_exists($lang_file)) {
    $LANG_DATA = require $lang_file;
}

// Load Tamil translations as default fallback dictionary
$LANG_TA_DATA = [];
if ($currentLang !== 'ta') {
    $ta_file = __DIR__ . "/../languages/ta.php";
    if (file_exists($ta_file)) {
        $LANG_TA_DATA = require $ta_file;
    }
} else {
    $LANG_TA_DATA = &$LANG_DATA;
}

// Load English translations as secondary fallback dictionary
$LANG_EN_DATA = [];
if ($currentLang !== 'en') {
    $en_file = __DIR__ . "/../languages/en.php";
    if (file_exists($en_file)) {
        $LANG_EN_DATA = require $en_file;
    }
} else {
    $LANG_EN_DATA = &$LANG_DATA;
}

/**
 * Global Translation Helper Function
 * Usage: __('key') or __('key', 'Default Text')
 */
function __($key, $default = null)
{
    global $LANG_DATA, $LANG_TA_DATA, $LANG_EN_DATA;
    
    // 1. Current language dictionary
    if (isset($LANG_DATA[$key]) && $LANG_DATA[$key] !== '') {
        return $LANG_DATA[$key];
    }
    
    // 2. Tamil dictionary fallback
    if (isset($LANG_TA_DATA[$key]) && $LANG_TA_DATA[$key] !== '') {
        return $LANG_TA_DATA[$key];
    }
    
    // 3. English dictionary fallback
    if (isset($LANG_EN_DATA[$key]) && $LANG_EN_DATA[$key] !== '') {
        return $LANG_EN_DATA[$key];
    }
    
    return $default !== null ? $default : $key;
}

/**
 * Get human-readable language metadata
 */
function getAvailableLanguages()
{
    return [
        'ta' => ['code' => 'ta', 'name' => 'Tamil', 'native' => 'தமிழ்', 'flag' => '🇮🇳'],
        'en' => ['code' => 'en', 'name' => 'English', 'native' => 'English', 'flag' => '🇬🇧'],
        'hi' => ['code' => 'hi', 'name' => 'Hindi', 'native' => 'हिन्दी', 'flag' => '🇮🇳']
    ];
}

