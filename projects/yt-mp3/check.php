<?php
header('Content-Type: text/plain; charset=utf-8');

echo "php.ini que Apache está usando realmente:\n";
echo php_ini_loaded_file() . "\n\n";

echo "Archivos .ini adicionales cargados:\n";
echo (php_ini_scanned_files() ?: '(ninguno)') . "\n\n";

echo "Valor de disable_functions:\n";
$df = ini_get('disable_functions');
echo ($df === '' ? '(vacío — nada deshabilitado)' : $df) . "\n\n";

echo "¿popen existe y es llamable? " . (function_exists('popen') ? 'SÍ' : 'NO') . "\n";
echo "¿pclose existe y es llamable? " . (function_exists('pclose') ? 'SÍ' : 'NO') . "\n";
echo "¿proc_open existe y es llamable? " . (function_exists('proc_open') ? 'SÍ' : 'NO') . "\n";
echo "¿exec existe y es llamable? " . (function_exists('exec') ? 'SÍ' : 'NO') . "\n\n";

echo "Prueba real de popen (lanza 'whoami' y lee la salida):\n";
$ph = @popen('whoami', 'r');
if ($ph === false) {
    echo "  popen() devolvió false — algo lo está bloqueando.\n";
} else {
    $out = stream_get_contents($ph);
    pclose($ph);
    echo "  Usuario bajo el que corre Apache/PHP: " . trim($out) . "\n";
}