<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * CONFIGURACIÓN DE EMAIL - 100% FUNCIONAL
 * ══════════════════════════════════════════════════════════════════════════════
 * 
 * INSTRUCCIONES PARA CONFIGURAR:
 * 
 * 1. GMAIL (Recomendado para desarrollo):
 *    - Ve a: https://myaccount.google.com/apppasswords
 *    - Genera una "Contraseña de aplicación"
 *    - Usa esa contraseña aquí (NO tu contraseña normal de Gmail)
 * 
 * 2. OUTLOOK/HOTMAIL:
 *    - Usa tu correo y contraseña normal
 *    - Host: smtp-mail.outlook.com
 *    - Puerto: 587
 * 
 * 3. OTROS PROVEEDORES:
 *    - Consulta la documentación de tu proveedor de email
 * 
 * ══════════════════════════════════════════════════════════════════════════════
 */

return [
    // ── CONFIGURACIÓN SMTP ────────────────────────────────────────────────────
    
    'smtp' => [
        // Servidor SMTP - Gmail
        'host' => 'smtp.gmail.com',
        
        // Puerto (587 para TLS, 465 para SSL)
        'port' => 587,
        
        // Tipo de encriptación ('tls' o 'ssl')
        'encryption' => 'tls',
        
        // Autenticación
        'auth' => true,
        
        // Usuario (tu correo completo)
        'username' => 'eliasandrok@gmail.com',
        
        // Contraseña (App Password de Gmail - sin espacios)
        'password' => 'naxqofmpkqwgwrbn',
    ],
    
    // ── CONFIGURACIÓN DEL REMITENTE ───────────────────────────────────────────
    
    'from' => [
        'email' => 'eliasandrok@gmail.com',
        'name'  => 'SEDD — Institución Litoral',
    ],
    
    // ── CONFIGURACIÓN DE LA APLICACIÓN ────────────────────────────────────────
    
    'app' => [
        'url'  => 'http://localhost/correccion/public',
        'name' => 'SEDD',
    ],
    
    // ── OPCIONES AVANZADAS ────────────────────────────────────────────────────
    
    'options' => [
        // Habilitar debug (0=off, 1=client, 2=server)
        'debug' => 0,
        
        // Timeout en segundos
        'timeout' => 30,
        
        // Charset
        'charset' => 'UTF-8',
        
        // Guardar log de errores
        'log_errors' => true,
    ],
];
