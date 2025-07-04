<?php

declare(strict_types=1);
// mise en place de l'autoload

use Dotenv\Dotenv;

require_once __DIR__ . "/vendor/autoload.php";

// initialisation de la librairie vlucas/phpdotenv
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    die("Erreur lors du chargement du fichier .env : " . $e->getMessage());
}
