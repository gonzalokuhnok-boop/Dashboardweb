<?php
// ============================================================
//  api.php  –  API REST para Trading Journal Pro
//  Reemplaza Firebase Auth + Firestore con MySQL + PHP
// ============================================================

require_once 'config.php';

// --- Headers CORS y JSON ---
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');          // En producción: reemplaza * por tu dominio
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// --- Leer body JSON ---
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================================
//  FUNCIONES DE TOKEN (JWT simple sin librería)
// ============================================================
function createToken(int $userId, string $email): string {
    $header  = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'sub' => $userId,
        'email' => $email,
        'exp' => time() + 86400 * 30  // 30 días
    ]));
    $sig = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

function verifyToken(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;
    $expectedSig = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expectedSig, $sig)) return null;
    $data = json_decode(base64_decode($payload), true);
    if (!$data || $data['exp'] < time()) return null;
    return $data;
}

function requireAuth(): array {
    // 1. Intentar desde header Authorization (hosting normal)
    $token = '';
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
               ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
               ?? getallheaders()['Authorization']
               ?? '';
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
    }
    // 2. Fallback: token en query string ?token=  (InfinityFree bloquea el header)
    if (!$token && !empty($_GET['token'])) {
        $token = $_GET['token'];
    }
    // 3. Fallback: token en el body JSON
    global $body;
    if (!$token && !empty($body['_token'])) {
        $token = $body['_token'];
    }

    if (!$token) {
        http_response_code(401);
        die(json_encode(['error' => 'No autenticado.']));
    }
    $data = verifyToken($token);
    if (!$data) {
        http_response_code(401);
        die(json_encode(['error' => 'Token inválido o expirado.']));
    }
    return $data;
}

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function respondError(string $msg, int $code = 400): void {
    respond(['error' => $msg], $code);
}

// ============================================================
//  RUTAS
// ============================================================

switch ($action) {

    // ----------------------------------------------------------
    // POST /api.php?action=register
    // ----------------------------------------------------------
    case 'register':
        if ($method !== 'POST') respondError('Método no permitido.', 405);

        $email     = trim($body['email'] ?? '');
        $password  = $body['password'] ?? '';
        $firstName = trim($body['first_name'] ?? '');
        $lastName  = trim($body['last_name'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respondError('Email inválido.');
        if (strlen($password) < 6) respondError('La contraseña debe tener al menos 6 caracteres.');

        $db = getDB();

        // Verificar duplicado
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) respondError('Este correo ya está registrado.');

        // Insertar usuario
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO users (email, password, first_name, last_name) VALUES (?, ?, ?, ?)');
        $stmt->execute([$email, $hash, $firstName, $lastName]);
        $userId = (int) $db->lastInsertId();

        // Crear configuración por defecto
        $stmt = $db->prepare('INSERT INTO user_settings (user_id, initial_capital, goal_target, max_drawdown_limit) VALUES (?, 50000, 3000, 1440)');
        $stmt->execute([$userId]);

        $token = createToken($userId, $email);
        respond([
            'token'      => $token,
            'user_id'    => $userId,
            'email'      => $email,
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ], 201);
        break;

    // ----------------------------------------------------------
    // POST /api.php?action=login
    // ----------------------------------------------------------
    case 'login':
        if ($method !== 'POST') respondError('Método no permitido.', 405);

        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$email || !$password) respondError('Email y contraseña requeridos.');

        $db   = getDB();
        $stmt = $db->prepare('SELECT id, email, password, first_name, last_name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            respondError('Credenciales incorrectas o usuario no registrado.', 401);
        }

        $token = createToken((int) $user['id'], $user['email']);
        respond([
            'token'      => $token,
            'user_id'    => $user['id'],
            'email'      => $user['email'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
        ]);
        break;

    // ----------------------------------------------------------
    // GET /api.php?action=settings
    // ----------------------------------------------------------
    case 'settings':
        $auth = requireAuth();
        if ($method !== 'GET') respondError('Método no permitido.', 405);

        $db   = getDB();
        $stmt = $db->prepare('SELECT initial_capital, goal_target, max_drawdown_limit FROM user_settings WHERE user_id = ?');
        $stmt->execute([$auth['sub']]);
        $settings = $stmt->fetch();

        if (!$settings) {
            // crear defaults si no existen
            $ins = $db->prepare('INSERT INTO user_settings (user_id) VALUES (?)');
            $ins->execute([$auth['sub']]);
            $settings = ['initial_capital' => 50000, 'goal_target' => 3000, 'max_drawdown_limit' => 1440];
        }

        respond([
            'initialCapital'    => (float) $settings['initial_capital'],
            'goalTarget'        => (float) $settings['goal_target'],
            'maxDrawdownLimit'  => (float) $settings['max_drawdown_limit'],
        ]);
        break;

    // ----------------------------------------------------------
    // PUT /api.php?action=settings
    // ----------------------------------------------------------
    case 'update_settings':
        $auth = requireAuth();
        if ($method !== 'PUT') respondError('Método no permitido.', 405);

        $db = getDB();
        $allowed = ['initial_capital', 'goal_target', 'max_drawdown_limit'];
        $updates = [];
        $params  = [];

        foreach ($allowed as $field) {
            $jsKey = lcfirst(str_replace('_', '', ucwords($field, '_')));
            // map: initial_capital <-> initialCapital, etc.
        }

        // Mapeo JS → SQL
        $map = [
            'initialCapital'   => 'initial_capital',
            'goalTarget'       => 'goal_target',
            'maxDrawdownLimit' => 'max_drawdown_limit',
        ];

        foreach ($map as $jsKey => $sqlCol) {
            if (isset($body[$jsKey])) {
                $updates[] = "$sqlCol = ?";
                $params[]  = (float) $body[$jsKey];
            }
        }

        if (empty($updates)) respondError('Nada que actualizar.');

        $params[] = $auth['sub'];
        $db->prepare("UPDATE user_settings SET " . implode(', ', $updates) . " WHERE user_id = ?")->execute($params);
        respond(['success' => true]);
        break;

    // ----------------------------------------------------------
    // GET /api.php?action=trades
    // ----------------------------------------------------------
    case 'trades':
        $auth = requireAuth();
        if ($method !== 'GET') respondError('Método no permitido.', 405);

        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT client_id AS id, date, time, pair, type, quality, score, notes, image, result, pnl, balance
             FROM trades WHERE user_id = ? ORDER BY date ASC, time ASC'
        );
        $stmt->execute([$auth['sub']]);
        $rows = $stmt->fetchAll();

        // Convertir tipos numéricos
        $trades = array_map(function ($t) {
            $t['pnl']     = (float) $t['pnl'];
            $t['balance'] = (float) $t['balance'];
            $t['score']   = (int)   $t['score'];
            return $t;
        }, $rows);

        respond(['trades' => $trades]);
        break;

    // ----------------------------------------------------------
    // POST /api.php?action=save_trade
    // ----------------------------------------------------------
    case 'save_trade':
        $auth = requireAuth();
        if ($method !== 'POST') respondError('Método no permitido.', 405);

        $t = $body;
        if (empty($t['id']))   respondError('El campo id es requerido.');
        if (empty($t['date'])) respondError('El campo date es requerido.');

        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO trades (user_id, client_id, date, time, pair, type, quality, score, notes, image, result, pnl, balance)
             VALUES (:uid, :cid, :date, :time, :pair, :type, :quality, :score, :notes, :image, :result, :pnl, :balance)
             ON DUPLICATE KEY UPDATE
               date=VALUES(date), time=VALUES(time), pair=VALUES(pair), type=VALUES(type),
               quality=VALUES(quality), score=VALUES(score), notes=VALUES(notes),
               image=VALUES(image), result=VALUES(result), pnl=VALUES(pnl), balance=VALUES(balance)'
        );
        $stmt->execute([
            ':uid'     => $auth['sub'],
            ':cid'     => (string) $t['id'],
            ':date'    => $t['date'],
            ':time'    => $t['time']    ?? '00:00',
            ':pair'    => strtoupper($t['pair'] ?? 'UNKNOWN'),
            ':type'    => in_array($t['type'] ?? '', ['BUY','SELL']) ? $t['type'] : 'BUY',
            ':quality' => in_array($t['quality'] ?? '', ['A+','B','FOMO']) ? $t['quality'] : 'B',
            ':score'   => (int)($t['score'] ?? 0),
            ':notes'   => $t['notes']  ?? null,
            ':image'   => $t['image']  ?? null,
            ':result'  => in_array($t['result'] ?? '', ['Ganada','Perdida','Pendiente']) ? $t['result'] : 'Pendiente',
            ':pnl'     => (float)($t['pnl'] ?? 0),
            ':balance' => (float)($t['balance'] ?? 0),
        ]);

        respond(['success' => true, 'id' => $t['id']]);
        break;

    // ----------------------------------------------------------
    // DELETE /api.php?action=delete_trade&id=CLIENT_ID
    // ----------------------------------------------------------
    case 'delete_trade':
        $auth = requireAuth();
        if ($method !== 'DELETE') respondError('Método no permitido.', 405);

        $clientId = $_GET['id'] ?? ($body['id'] ?? '');
        if (!$clientId) respondError('ID requerido.');

        $db   = getDB();
        $stmt = $db->prepare('DELETE FROM trades WHERE user_id = ? AND client_id = ?');
        $stmt->execute([$auth['sub'], (string) $clientId]);

        respond(['success' => true, 'deleted' => $stmt->rowCount()]);
        break;

    // ----------------------------------------------------------
    // POST /api.php?action=import_trades
    // Importa un backup JSON completo
    // ----------------------------------------------------------
    case 'import_trades':
        $auth = requireAuth();
        if ($method !== 'POST') respondError('Método no permitido.', 405);

        $importedTrades   = $body['trades'] ?? (is_array($body) ? $body : []);
        $importedSettings = $body; // puede traer initialCapital, goalTarget, etc.

        if (empty($importedTrades)) respondError('El archivo no contiene trades.');

        $db = getDB();
        $db->beginTransaction();
        $count = 0;
        try {
            $stmt = $db->prepare(
                'INSERT INTO trades (user_id, client_id, date, time, pair, type, quality, score, notes, image, result, pnl, balance)
                 VALUES (:uid, :cid, :date, :time, :pair, :type, :quality, :score, :notes, :image, :result, :pnl, :balance)
                 ON DUPLICATE KEY UPDATE
                   date=VALUES(date), time=VALUES(time), pair=VALUES(pair), type=VALUES(type),
                   quality=VALUES(quality), score=VALUES(score), notes=VALUES(notes),
                   image=VALUES(image), result=VALUES(result), pnl=VALUES(pnl), balance=VALUES(balance)'
            );

            foreach ($importedTrades as $t) {
                $cid = $t['id'] ? (string) $t['id'] : (string) (time() . rand(100, 999));
                $stmt->execute([
                    ':uid'     => $auth['sub'],
                    ':cid'     => $cid,
                    ':date'    => $t['date']    ?? date('Y-m-d'),
                    ':time'    => $t['time']    ?? '00:00',
                    ':pair'    => strtoupper($t['pair'] ?? 'UNKNOWN'),
                    ':type'    => in_array($t['type'] ?? '', ['BUY','SELL']) ? $t['type'] : 'BUY',
                    ':quality' => in_array($t['quality'] ?? '', ['A+','B','FOMO']) ? $t['quality'] : 'B',
                    ':score'   => (int)($t['score'] ?? 0),
                    ':notes'   => $t['notes']  ?? null,
                    ':image'   => $t['image']  ?? null,
                    ':result'  => in_array($t['result'] ?? '', ['Ganada','Perdida','Pendiente']) ? $t['result'] : 'Pendiente',
                    ':pnl'     => (float)($t['pnl'] ?? 0),
                    ':balance' => (float)($t['balance'] ?? 0),
                ]);
                $count++;
            }

            // Actualizar settings si vienen en el backup
            $sUpdates = [];
            $sParams  = [];
            if (isset($importedSettings['initialCapital']))   { $sUpdates[] = 'initial_capital = ?';    $sParams[] = (float)$importedSettings['initialCapital']; }
            if (isset($importedSettings['goalTarget']))       { $sUpdates[] = 'goal_target = ?';         $sParams[] = (float)$importedSettings['goalTarget']; }
            if (isset($importedSettings['maxDrawdownLimit'])) { $sUpdates[] = 'max_drawdown_limit = ?';  $sParams[] = (float)$importedSettings['maxDrawdownLimit']; }
            if ($sUpdates) {
                $sParams[] = $auth['sub'];
                $db->prepare("UPDATE user_settings SET " . implode(', ', $sUpdates) . " WHERE user_id = ?")->execute($sParams);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            respondError('Error al importar: ' . $e->getMessage(), 500);
        }

        respond(['success' => true, 'imported' => $count]);
        break;

    default:
        respondError('Acción desconocida: ' . htmlspecialchars($action), 404);
}
