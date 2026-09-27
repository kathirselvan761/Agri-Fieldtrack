<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (ob_get_level() === 0) {
    ob_start();
}

// Production & Debugging Configuration
define('APP_DEBUG', true); // Set to false in production after initial setup verification

if (APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

require_once __DIR__ . '/../includes/lang.php';

/*
|--------------------------------------------------------------------------
| Database Environment Settings (InfinityFree & Localhost Support)
|--------------------------------------------------------------------------
*/

$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isLocalhost = (strpos($httpHost, 'localhost') !== false || strpos($httpHost, '127.0.0.1') !== false);

if ($isLocalhost) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'agri');
} else {
    define('DB_HOST', 'sql301.infinityfree.com');
    define('DB_USER', 'if0_42653260');
    define('DB_PASS', 'Kathir0761');
    define('DB_NAME', 'if0_42653260_agri');
}

/*
|--------------------------------------------------------------------------
| Dynamic SITE_URL Auto-Detection
|--------------------------------------------------------------------------
*/
if (!defined('SITE_URL')) {
    $envSiteUrl = getenv('SITE_URL');
    if ($envSiteUrl !== false && $envSiteUrl !== '') {
        define('SITE_URL', rtrim($envSiteUrl, '/'));
    } else {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $protocol = $isHttps ? 'https://' : 'http://';

        if (!empty($httpHost)) {
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $scriptDir = dirname($scriptName);

            $subdirs = ['/bookings', '/customers', '/diesel', '/drivers', '/expenses', '/payments', '/reports', '/services', '/settings', '/tractors', '/work_entries', '/work_types', '/includes', '/config', '/assets'];
            foreach ($subdirs as $sub) {
                if (substr($scriptDir, -strlen($sub)) === $sub) {
                    $scriptDir = substr($scriptDir, 0, -strlen($sub));
                    break;
                }
            }
            $basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/');
            define('SITE_URL', $protocol . $httpHost . $basePath);
        } else {
            define('SITE_URL', 'https://tractorrental.page.gd/agri');
        }
    }
}
define('APP_NAME', 'AgriTrak ERP');

function getDBConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die(
                "<div style='padding:30px; font-family:sans-serif; max-width:600px; margin:50px auto; background:#fee2e2; border:1px solid #f87171; border-radius:12px; color:#991b1b;'>
                    <h3 style='margin-top:0;'>⚠️ Database Connection Error</h3>
                    <p>Could not connect to database on host <code>" . htmlspecialchars(DB_HOST) . "</code>.</p>
                    <p><strong>Error Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                </div>"
            );
        }
    }

    return $pdo;
}

$conn = getDBConnection();

function sanitize($data)
{
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatCurrency($amount)
{
    return '₹' . number_format((float) $amount, 2);
}

function formatDate($date, $format = 'd M Y')
{
    if (empty($date))
        return '-';
    return date($format, strtotime($date));
}

function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function displayFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        echo "<div class='alert alert-{$flash['type']}'>{$flash['message']}</div>";
    }
}

function getCompanySettings()
{
    global $conn;

    try {
        $stmt = $conn->query("SELECT * FROM company_settings LIMIT 1");
        $settings = $stmt->fetch();

        return $settings ?: [
            'company_name' => 'ஸ்ரீ அங்காளபரமேஸ்வரி டிராக்டர்ஸ்'
        ];
    } catch (Exception $e) {
        return [
            'company_name' => 'ஸ்ரீ அங்காளபரமேஸ்வரி டிராக்டர்ஸ்'
        ];
    }
}

$company = getCompanySettings();
?>