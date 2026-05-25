<?php
/**
 * Autoloader para PHPMailer
 */

spl_autoload_register(function ($class) {
    // Namespace base de PHPMailer
    $prefix = 'PHPMailer\\PHPMailer\\';
    
    // Directorio base de PHPMailer
    $base_dir = __DIR__ . '/phpmailer/PHPMailer-6.9.1/src/';
    
    // Verificar si la clase usa el namespace de PHPMailer
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, $len);
    
    // Reemplazar el namespace con la estructura de directorios
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // Si el archivo existe, cargarlo
    if (file_exists($file)) {
        require $file;
    }
});
