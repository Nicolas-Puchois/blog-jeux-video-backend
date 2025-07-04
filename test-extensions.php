<?php
$extensionDir = ini_get('extension_dir');
echo "Extension directory: " . $extensionDir . "\n";

// Check if the PDO MySQL DLL exists
$pdo_mysql_dll = $extensionDir . DIRECTORY_SEPARATOR . 'php_pdo_mysql.dll';
echo "PDO MySQL DLL path: " . $pdo_mysql_dll . "\n";
echo "File exists: " . (file_exists($pdo_mysql_dll) ? 'Yes' : 'No') . "\n";

// Display loaded PHP ini files
echo "\nLoaded php.ini files:\n";
if (function_exists('php_ini_loaded_file')) {
    echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
}
if (function_exists('php_ini_scanned_files')) {
    echo "Additionally loaded files:\n" . php_ini_scanned_files() . "\n";
}
