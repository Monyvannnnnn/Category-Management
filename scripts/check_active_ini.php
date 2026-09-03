<?php
header("Content-Type: text/plain");
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
echo "mysqli extension loaded: " . (extension_loaded('mysqli') ? 'YES' : 'NO') . "\n";
echo "Available extensions: \n";
print_r(get_loaded_extensions());
?>
