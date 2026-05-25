# 📧 Configuración de Email 100% Funcional

## 🚀 Instalación Rápida

### Paso 1: Instalar PHPMailer

Abre la terminal en la carpeta raíz del proyecto y ejecuta:

```bash
composer install
```

Si no tienes Composer instalado, descárgalo de: https://getcomposer.org/download/

### Paso 2: Configurar credenciales SMTP

Edita el archivo `src/config_email.php` y configura tus credenciales:

#### Opción A: Gmail (Recomendado para desarrollo)

1. Ve a: https://myaccount.google.com/apppasswords
2. Genera una "Contraseña de aplicación"
3. Configura en `src/config_email.php`:

```php
'smtp' => [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'tucorreo@gmail.com',      // ← Tu Gmail
    'password' => 'xxxx xxxx xxxx xxxx',     // ← App Password (16 caracteres)
],
```

#### Opción B: Outlook/Hotmail

```php
'smtp' => [
    'host' => 'smtp-mail.outlook.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'tucorreo@outlook.com',
    'password' => 'tu_contraseña_normal',
],
```

#### Opción C: Office 365

```php
'smtp' => [
    'host' => 'smtp.office365.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'tucorreo@tuempresa.com',
    'password' => 'tu_contraseña',
],
```

### Paso 3: Probar la configuración

Accede a: http://localhost/correccion/public/test_email.php

Ingresa tu email y haz clic en "Enviar correo de prueba". Si todo está bien configurado, recibirás un correo de confirmación.

---

## 📝 Uso en el código

### Reemplazar el mailer actual

Para usar la versión SMTP, reemplaza la línea en los archivos que usan el mailer:

**Antes:**
```php
require_once __DIR__.'/../src/mailer.php';
```

**Después:**
```php
require_once __DIR__.'/../vendor/autoload.php';  // Cargar PHPMailer
require_once __DIR__.'/../src/mailer_smtp.php';
```

### Archivos que debes actualizar:

1. `public/crear_solicitud.php` (línea ~90)
2. `public/aprobar_solicitud.php` (línea ~50)
3. Cualquier otro archivo que use `Mailer`

---

## 🎯 Funciones disponibles

### 1. Notificar nueva solicitud a admins

```php
$mailer = new Mailer($pdo);
$mailer->notificarNuevaSolicitud($solicitud, $nombre_solicitante, $email_solicitante);
```

### 2. Notificar decisión al solicitante

```php
$mailer = new Mailer($pdo);
$mailer->notificarDecision($solicitud, 'APROBADA', 'Comentario del admin');
```

### 3. Enviar correo personalizado

```php
$mailer = new Mailer($pdo);
$resultado = $mailer->enviarCorreo(
    'destinatario@example.com',
    'Nombre Destinatario',
    'Asunto del correo',
    '<h1>Contenido HTML</h1><p>Tu mensaje aquí</p>'
);

if ($resultado) {
    echo "Correo enviado exitosamente";
} else {
    echo "Error al enviar correo";
}
```

### 4. Probar conexión SMTP

```php
$mailer = new Mailer($pdo);
$resultado = $mailer->probarConexion('tu@email.com');

if ($resultado['success']) {
    echo "✅ Configuración correcta";
} else {
    echo "❌ Error: " . $resultado['message'];
}
```

---

## 🔧 Solución de problemas

### Error: "PHPMailer no está instalado"

**Solución:** Ejecuta `composer install` en la raíz del proyecto.

### Error: "SMTP connect() failed"

**Causas comunes:**
1. Credenciales incorrectas
2. Puerto bloqueado por firewall
3. Gmail: necesitas usar App Password, no tu contraseña normal

**Solución:**
- Verifica usuario y contraseña en `src/config_email.php`
- Para Gmail: genera App Password en https://myaccount.google.com/apppasswords
- Verifica que el puerto 587 no esté bloqueado

### Error: "Could not authenticate"

**Solución:**
- Gmail: usa App Password (16 caracteres)
- Outlook: usa tu contraseña normal
- Verifica que el email sea correcto

### Los correos llegan a spam

**Solución:**
1. Configura SPF y DKIM en tu dominio (para producción)
2. Usa un servicio profesional como SendGrid, Mailgun o Amazon SES
3. Marca el correo como "No es spam" en tu cliente de email

---

## 🌟 Características

✅ **100% funcional** con SMTP real  
✅ **Soporte para Gmail, Outlook, Office 365** y cualquier servidor SMTP  
✅ **Plantillas HTML profesionales** con diseño responsive  
✅ **Manejo de errores** con try/catch (POO)  
✅ **Logging de errores** para debugging  
✅ **Método de prueba** incluido  
✅ **Configuración centralizada** en un solo archivo  

---

## 📚 Documentación adicional

- PHPMailer: https://github.com/PHPMailer/PHPMailer
- Gmail App Passwords: https://support.google.com/accounts/answer/185833
- SMTP Settings: https://www.arclab.com/en/kb/email/list-of-smtp-and-pop3-servers-mailserver-list.html

---

## 🆘 Soporte

Si tienes problemas:

1. Revisa los logs de PHP: `php_error.log`
2. Habilita debug en `src/config_email.php`: `'debug' => 2`
3. Usa la página de prueba: `public/test_email.php`
4. Verifica que Composer esté instalado: `composer --version`

---

**¡Listo!** Tu sistema ahora tiene un servicio de email 100% funcional y profesional. 🚀
