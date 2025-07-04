<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Loaded PHP Extensions:\n";
print_r(get_loaded_extensions());
echo "\nPDO Drivers Available:\n";
if (class_exists('PDO')) {
    print_r(PDO::getAvailableDrivers());
} else {
    echo "PDO class not found!\n";
}
