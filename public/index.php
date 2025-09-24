<?php
// Mise en place autoload

use App\core\CorsMiddleWare;
use App\core\Database;
use App\core\Router;
use Dotenv\Dotenv;

require_once __DIR__ . "/../bootstrap.php";

// Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Forcer le chargement dans l'environnement
foreach ($_ENV as $key => $value) {
    putenv("$key=$value");
}

// Debug des variables d'environnement
error_log("Variables d'environnement chargées:");
error_log("CSRF_SECRET via _ENV: " . ($_ENV['CSRF_SECRET'] ?? 'non défini'));
error_log("CSRF_SECRET via getenv: " . (getenv('CSRF_SECRET') ?? 'non défini'));
error_log("DB_HOST: " . (getenv('DB_HOST') ?? 'non défini'));

$corsMiddleWare = new CorsMiddleWare();
$corsMiddleWare->handle();

try {

    $router = new Router();

    $db = Database::getConnexion();

    $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $method = $_SERVER["REQUEST_METHOD"];

    $router->dispatch($uri, $method);
} catch (\Exception $e) {
    return $json = json_encode([
        "error" => "une erreur est survenue",
        "message" => $e->getMessage()
    ]);
}

// Servir les fichiers statiques depuis le dossier uploads
if (preg_match('/^\/public\/uploads\//', $_SERVER['REQUEST_URI'])) {
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (file_exists($file)) {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $contentTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif'
        ];

        if (isset($contentTypes[$extension])) {
            header('Content-Type: ' . $contentTypes[$extension]);
            readfile($file);
            exit;
        }
    }
}

header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
