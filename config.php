<?php
// ============================================================
//  config.php  –  Configuración de conexión a MySQL
//  *** EDITAR ESTOS VALORES ANTES DE SUBIR A CPANEL ***
// ============================================================

define('DB_HOST', 'sql104.infinityfree.com');          // En cPanel casi siempre es "localhost"
define('DB_NAME', 'if0_42023825_trading');  // ⚠️ Formato cPanel: prefijo_nombre_db
define('DB_USER', 'if0_42023825');       // ⚠️ Formato cPanel: prefijo_usuario
define('DB_PASS', 'o7bnNkWAO0U3'); // ⚠️ La contraseña que pusiste al crear el user

define('JWT_SECRET', 'M1Tr@d1ngJournal#Secr3t!2024XYZ'); // Clave para firmar tokens

// ---- No tocar de aquí para abajo ----
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Error de conexión a la base de datos.']));
        }
    }
    return $pdo;
}
