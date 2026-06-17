<?php
// ============================================================
//  sync.php  –  Proxy de APIs de Plataformas de Trading
//  Compatible con PHP 7.4+  (InfinityFree, cPanel básico)
// ============================================================
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$body   = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ============================================================
//  HELPERS PHP 7.4 compatibles
// ============================================================
function strhas($haystack, $needle) {
    return strpos($haystack, $needle) !== false;
}
function strstarts($haystack, $needle) {
    return strpos($haystack, $needle) === 0;
}

// ============================================================
//  AUTH: mismo sistema que api.php
// ============================================================
function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    $h = $parts[0]; $p = $parts[1]; $s = $parts[2];
    $expected = base64_encode(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    if (!hash_equals($expected, $s)) return null;
    $data = json_decode(base64_decode($p), true);
    if (!$data || $data['exp'] < time()) return null;
    return $data;
}

function requireAuth() {
    global $body;
    $token = '';
    $allHeaders = function_exists('getallheaders') ? getallheaders() : [];
    $ah = isset($_SERVER['HTTP_AUTHORIZATION'])          ? $_SERVER['HTTP_AUTHORIZATION']
        : (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        : (isset($allHeaders['Authorization'])            ? $allHeaders['Authorization'] : ''));

    if (strstarts($ah, 'Bearer ')) $token = substr($ah, 7);
    if (!$token && !empty($_GET['token']))    $token = $_GET['token'];
    if (!$token && !empty($body['_token']))  $token = $body['_token'];

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

function respond($d, $c = 200) {
    http_response_code($c);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}
function respondError($m, $c = 400) {
    respond(['error' => $m], $c);
}

// ============================================================
//  cURL helper (con verificación de disponibilidad)
// ============================================================
function httpRequest($url, $method = 'GET', $payload = [], $headers = []) {
    if (!function_exists('curl_init')) {
        return ['error' => 'cURL no está disponible en este servidor. Contacta a tu hosting para habilitarlo.', 'code' => 0];
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $h = array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);
    if ($err) return ['error' => 'cURL error: ' . $err, 'code' => 0];
    $decoded = json_decode($response, true);
    return ['data' => ($decoded !== null ? $decoded : $response), 'code' => $httpCode];
}

// ============================================================
//  RUTAS
// ============================================================
switch ($action) {

    // ----------------------------------------------------------
    // GET  ?action=connections
    // ----------------------------------------------------------
    case 'connections':
        $auth = requireAuth();
        if ($method !== 'GET') respondError('Método no permitido.', 405);
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT id, platform, account_name, status, last_sync, token_expires, account_id_remote
             FROM platform_connections WHERE user_id = ?'
        );
        $stmt->execute([$auth['sub']]);
        respond(['connections' => $stmt->fetchAll()]);
        break;

    // ----------------------------------------------------------
    // DELETE  ?action=disconnect&id=X
    // ----------------------------------------------------------
    case 'disconnect':
        $auth = requireAuth();
        if ($method !== 'DELETE') respondError('Método no permitido.', 405);
        $connId = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
        if (!$connId) respondError('ID requerido.');
        $db = getDB();
        $db->prepare('DELETE FROM platform_connections WHERE id = ? AND user_id = ?')
           ->execute([$connId, $auth['sub']]);
        respond(['success' => true]);
        break;

    // ----------------------------------------------------------
    // POST  ?action=tradovate_auth
    // ----------------------------------------------------------
    case 'tradovate_auth':
        $auth = requireAuth();
        if ($method !== 'POST') respondError('Método no permitido.', 405);

        $tdUser   = isset($body['td_user'])      ? trim($body['td_user'])      : '';
        $tdPass   = isset($body['td_pass'])      ? trim($body['td_pass'])      : '';
        $env      = isset($body['env'])          ? $body['env']                : 'demo';
        $env      = ($env === 'live') ? 'live' : 'demo';
        $accLabel = isset($body['account_name']) ? trim($body['account_name']) : 'Tradovate';

        if (!$tdUser || !$tdPass) respondError('Usuario y contraseña de Tradovate requeridos.');

        $baseUrl = ($env === 'live')
            ? 'https://trader.tradovate.com/welcome'
            : 'https://trader.tradovate.com/welcome';

        // Verificar cURL antes de intentar
        if (!function_exists('curl_init')) {
            respondError('Este servidor no tiene cURL habilitado. Sube el CSV manualmente o usa otro hosting.', 503);
        }

        $authRes = httpRequest("$baseUrl/auth/accesstokenrequest", 'POST', [
            'name'       => $tdUser,
            'password'   => $tdPass,
            'appId'      => 'TradingJournalPro',
            'appVersion' => '1.0',
            'cid'        => 0,
            'sec'        => '',
            'deviceId'   => 'tj_' . md5($tdUser . JWT_SECRET),
        ]);

        if (!empty($authRes['error'])) respondError('Sin conexión a Tradovate: ' . $authRes['error'], 502);

        $td = $authRes['data'];
        if (is_string($td)) respondError('Tradovate devolvió respuesta inesperada. Verifica tus credenciales.');
        if (isset($td['errorText']))   respondError('Tradovate: ' . $td['errorText'], 401);
        if (empty($td['accessToken'])) respondError('No se recibió token. Verifica usuario y contraseña.');

        $accessToken = $td['accessToken'];
        $expiresIn   = isset($td['expirationTime']) ? $td['expirationTime'] : (time() * 1000 + 3600000);
        $expiresAt   = date('Y-m-d H:i:s', (int)($expiresIn / 1000));

        // Obtener cuentas
        $accsRes      = httpRequest("$baseUrl/account/list", 'GET', [], ["Authorization: Bearer $accessToken"]);
        $remoteAccId  = null;
        $remoteAccName = $accLabel;
        if (!empty($accsRes['data']) && is_array($accsRes['data'])) {
            $firstAcc = $accsRes['data'][0];
            if ($firstAcc) {
                $remoteAccId   = isset($firstAcc['id'])   ? $firstAcc['id']   : null;
                $remoteAccName = isset($firstAcc['name']) ? $firstAcc['name'] : $accLabel;
            }
        }

        $db = getDB();
        $existing = $db->prepare('SELECT id FROM platform_connections WHERE user_id = ? AND platform = ? AND account_id_remote = ?');
        $existing->execute([$auth['sub'], 'tradovate_' . $env, $remoteAccId]);
        $row = $existing->fetch();

        if ($row) {
            $db->prepare('UPDATE platform_connections SET api_token=?, token_expires=?, status="connected", last_sync=NOW(), account_name=? WHERE id=?')
               ->execute([$accessToken, $expiresAt, $remoteAccName, $row['id']]);
            $connId = $row['id'];
        } else {
            $db->prepare('INSERT INTO platform_connections (user_id, platform, account_name, api_user, api_token, token_expires, status, account_id_remote) VALUES (?,?,?,?,?,?,"connected",?)')
               ->execute([$auth['sub'], 'tradovate_' . $env, $remoteAccName, $tdUser, $accessToken, $expiresAt, $remoteAccId]);
            $connId = (int)$db->lastInsertId();
        }

        respond([
            'success'       => true,
            'connection_id' => (int)$connId,
            'account_name'  => $remoteAccName,
            'expires_at'    => $expiresAt,
            'env'           => $env,
        ]);
        break;

    // ----------------------------------------------------------
    // POST  ?action=tradovate_sync
    // ----------------------------------------------------------
    case 'tradovate_sync':
        $auth = requireAuth();
        if ($method !== 'POST') respondError('Método no permitido.', 405);

        $connId   = (int)(isset($body['connection_id']) ? $body['connection_id'] : 0);
        $daysBack = min((int)(isset($body['days_back']) ? $body['days_back'] : 30), 90);

        if (!$connId) respondError('connection_id requerido.');

        $db   = getDB();
        $conn = $db->prepare('SELECT * FROM platform_connections WHERE id = ? AND user_id = ?');
        $conn->execute([$connId, $auth['sub']]);
        $connection = $conn->fetch();

        if (!$connection) respondError('Conexión no encontrada.', 404);
        if ($connection['status'] !== 'connected') respondError('La conexión no está activa. Reconecta.');
        if (!$connection['api_token']) respondError('Token no disponible. Reconecta la cuenta.');

        $env     = strhas($connection['platform'], 'live') ? 'live' : 'demo';
        $baseUrl = ($env === 'live') ? 'https://live.tradovate.com/v1' : 'https://demo.tradovate.com/v1';
        $token   = $connection['api_token'];
        $remAccId = $connection['account_id_remote'];
        $sinceTs  = strtotime("-{$daysBack} days");

        $fillsRes = httpRequest(
            "$baseUrl/fill/list?accountId={$remAccId}",
            'GET', [],
            ["Authorization: Bearer $token"]
        );

        if (!empty($fillsRes['error'])) respondError('Error al obtener fills: ' . $fillsRes['error'], 502);
        $fills = is_array($fillsRes['data']) ? $fillsRes['data'] : [];

        if (empty($fills)) respond(['success' => true, 'imported' => 0, 'message' => 'No hay operaciones nuevas.']);

        // Agrupar fills por orderId
        $orderGroups = [];
        foreach ($fills as $fill) {
            $oid = isset($fill['orderId']) ? $fill['orderId'] : $fill['id'];
            if (!isset($orderGroups[$oid])) $orderGroups[$oid] = [];
            $orderGroups[$oid][] = $fill;
        }

        $imported = 0;
        $stmt = $db->prepare(
            'INSERT INTO trades (user_id, client_id, date, time, pair, type, quality, score, notes, result, pnl, balance)
             VALUES (:uid, :cid, :date, :time, :pair, :type, :quality, :score, :notes, :result, :pnl, :balance)
             ON DUPLICATE KEY UPDATE pnl=VALUES(pnl), result=VALUES(result)'
        );

        $balRow = $db->prepare('SELECT COALESCE(MAX(balance), 0) AS bal FROM trades WHERE user_id = ?');
        $balRow->execute([$auth['sub']]);
        $runningBal = (float)$balRow->fetchColumn();

        foreach ($orderGroups as $oid => $groupFills) {
            $firstFill = $groupFills[0];
            $rawTime   = isset($firstFill['timestamp']) ? $firstFill['timestamp']
                        : (isset($firstFill['tradeTime']) ? $firstFill['tradeTime'] : 'now');
            $fillTime  = strtotime($rawTime);
            if ($fillTime < $sinceTs) continue;

            $clientId = 'tv_' . $oid;
            $date     = date('Y-m-d', $fillTime);
            $time     = date('H:i:s', $fillTime);
            $symbol   = strtoupper(isset($firstFill['symbol']) ? $firstFill['symbol']
                        : (isset($firstFill['contractId']) ? $firstFill['contractId'] : 'UNKNOWN'));
            $rawSide  = strtoupper(isset($firstFill['action']) ? $firstFill['action'] : 'BUY');
            $type     = ($rawSide === 'BUY' || $rawSide === 'B') ? 'BUY' : 'SELL';
            $pnl      = 0;
            foreach ($groupFills as $f) $pnl += (float)(isset($f['realizedPl']) ? $f['realizedPl'] : (isset($f['pnl']) ? $f['pnl'] : 0));
            $result   = $pnl > 0 ? 'Ganada' : ($pnl < 0 ? 'Perdida' : 'Pendiente');
            $runningBal += $pnl;

            $stmt->execute([
                ':uid' => $auth['sub'], ':cid' => $clientId,
                ':date' => $date, ':time' => $time, ':pair' => $symbol,
                ':type' => $type, ':quality' => 'B', ':score' => 0,
                ':notes' => 'Tradovate Auto-Sync #' . $oid,
                ':result' => $result, ':pnl' => $pnl, ':balance' => $runningBal,
            ]);
            $imported++;
        }

        $db->prepare('UPDATE platform_connections SET last_sync = NOW() WHERE id = ?')->execute([$connId]);
        respond(['success' => true, 'imported' => $imported]);
        break;

    // ----------------------------------------------------------
    // POST  ?action=import_csv
    // ----------------------------------------------------------
    case 'import_csv':
        $auth = requireAuth();
        if ($method !== 'POST') respondError('Método no permitido.', 405);

        $platform   = strtolower(isset($body['platform']) ? $body['platform'] : 'auto');
        $csvContent = isset($body['csv_content']) ? $body['csv_content'] : '';

        if (!$csvContent) respondError('Contenido CSV vacío.');

        $lines = array_values(array_filter(explode("\n", str_replace("\r\n", "\n", $csvContent))));
        if (count($lines) < 2) respondError('El CSV no tiene suficientes filas.');

        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO trades (user_id, client_id, date, time, pair, type, quality, score, notes, result, pnl, balance)
             VALUES (:uid, :cid, :date, :time, :pair, :type, :quality, :score, :notes, :result, :pnl, :balance)
             ON DUPLICATE KEY UPDATE pnl=VALUES(pnl), result=VALUES(result)'
        );

        $balRow = $db->prepare('SELECT COALESCE(MAX(balance), 0) AS bal FROM trades WHERE user_id = ?');
        $balRow->execute([$auth['sub']]);
        $runBal = (float)$balRow->fetchColumn();

        // Construir mapa de columnas
        $headerRaw = str_getcsv($lines[0]);
        $colMap    = [];
        foreach ($headerRaw as $i => $col) {
            $colMap[strtolower(trim($col))] = $i;
        }
        $headerStr = strtolower(implode(',', $headerRaw));

        // Detección de plataforma
        if ($platform === 'auto') {
            if (strhas($headerStr, 'trade number') || strhas($headerStr, 'market pos'))
                $platform = 'ninjatrader';
            elseif (strhas($headerStr, 'open time') || strhas($headerStr, 'ticket'))
                $platform = 'mt4';
            elseif (strhas($headerStr, 'buysell') || strhas($headerStr, 'accountid'))
                $platform = 'rithmic';
            else
                $platform = 'generic';
        }

        $valF = function($row, $name) use ($colMap) {
            return isset($colMap[$name]) ? trim(isset($row[$colMap[$name]]) ? $row[$colMap[$name]] : '') : '';
        };

        $imported = 0;
        $errors   = [];

        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i]);
            if (count($row) < 3) continue;

            try {
                $date = $time = $pair = $type = $notes = '';
                $pnl  = 0.0;

                if ($platform === 'ninjatrader') {
                    $entryTime = $valF($row, 'entry time') ?: $valF($row, 'entry datetime');
                    $ts = strtotime(str_replace('/', '-', $entryTime));
                    if (!$ts) continue;
                    $date  = date('Y-m-d', $ts);
                    $time  = date('H:i:s', $ts);
                    $pair  = strtoupper($valF($row, 'instrument') ?: 'UNKNOWN');
                    $rawP  = strtolower($valF($row, 'market pos.') ?: $valF($row, 'direction'));
                    $type  = strhas($rawP, 'long') ? 'BUY' : 'SELL';
                    $pnl   = (float)str_replace(['$', ',', ' '], '', $valF($row, 'profit') ?: $valF($row, 'p&l'));
                    $notes = 'NinjaTrader Import';

                } elseif ($platform === 'mt4') {
                    $openTime = $valF($row, 'open time') ?: $valF($row, 'time');
                    $ts = strtotime(str_replace('.', '-', $openTime));
                    if (!$ts) continue;
                    $rawType = strtolower($valF($row, 'type') ?: '');
                    if (!strhas($rawType, 'buy') && !strhas($rawType, 'sell')) continue;
                    $date  = date('Y-m-d', $ts);
                    $time  = date('H:i:s', $ts);
                    $pair  = strtoupper($valF($row, 'item') ?: $valF($row, 'symbol') ?: 'UNKNOWN');
                    $type  = strhas($rawType, 'buy') ? 'BUY' : 'SELL';
                    $pnl   = (float)str_replace([' ', ','], '', $valF($row, 'profit'));
                    $notes = 'MetaTrader Import';

                } elseif ($platform === 'rithmic') {
                    $rawDate = $valF($row, 'tradedate') ?: $valF($row, 'date') ?: $valF($row, 'trade date');
                    $ts = strtotime($rawDate);
                    if (!$ts) continue;
                    $date    = date('Y-m-d', $ts);
                    $rawTime = $valF($row, 'tradetime') ?: $valF($row, 'time');
                    $time    = $rawTime ?: '12:00:00';
                    $pair    = strtoupper($valF($row, 'symbol') ?: $valF($row, 'instrument') ?: 'UNKNOWN');
                    $rawSide = strtoupper($valF($row, 'buysell') ?: $valF($row, 'side') ?: 'B');
                    $type    = ($rawSide === 'B' || $rawSide === 'BUY') ? 'BUY' : 'SELL';
                    $pnl     = (float)str_replace([' ', ','], '', $valF($row, 'pnl') ?: $valF($row, 'p&l') ?: $valF($row, 'realizedpl'));
                    $notes   = 'Rithmic Import (Lucid/TopStep/etc.)';

                } else {
                    // Genérico: buscar columnas conocidas
                    $dateKeys = ['date','datetime','time','entry time','open time','tradedate'];
                    $pairKeys = ['symbol','instrument','pair','item','asset','ticker'];
                    $typeKeys = ['type','side','direction','action','market pos.','buysell'];
                    $pnlKeys  = ['profit','p&l','pnl','net p&l','realized p&l','realizedpl'];

                    $foundDate = '';
                    foreach ($dateKeys as $k) {
                        if (isset($colMap[$k]) && isset($row[$colMap[$k]]) && trim($row[$colMap[$k]])) {
                            $foundDate = $row[$colMap[$k]]; break;
                        }
                    }
                    $ts = $foundDate ? strtotime(str_replace(['/', '.'], '-', $foundDate)) : 0;
                    if (!$ts) continue;

                    $foundPair = 'UNKNOWN';
                    foreach ($pairKeys as $k) { if (isset($colMap[$k]) && isset($row[$colMap[$k]])) { $foundPair = $row[$colMap[$k]]; break; } }
                    $foundType = 'BUY';
                    foreach ($typeKeys as $k) {
                        if (isset($colMap[$k]) && isset($row[$colMap[$k]])) {
                            $rawT = strtolower($row[$colMap[$k]]);
                            $foundType = (strhas($rawT, 'sell') || $rawT === 's') ? 'SELL' : 'BUY';
                            break;
                        }
                    }
                    $pnl = 0;
                    foreach ($pnlKeys as $k) {
                        if (isset($colMap[$k]) && isset($row[$colMap[$k]])) {
                            $pnl = (float)str_replace([' ', ',', '$'], '', $row[$colMap[$k]]); break;
                        }
                    }
                    $date  = date('Y-m-d', $ts);
                    $time  = date('H:i:s', $ts);
                    $pair  = strtoupper(trim($foundPair));
                    $type  = $foundType;
                    $notes = 'CSV Import (auto-detect)';
                }

                $result  = $pnl > 0 ? 'Ganada' : ($pnl < 0 ? 'Perdida' : 'Pendiente');
                $runBal += $pnl;
                $cid     = 'csv_' . md5($platform . $date . $time . $pair . $pnl . $i);

                $stmt->execute([
                    ':uid'     => $auth['sub'], ':cid'     => $cid,
                    ':date'    => $date,        ':time'    => $time,
                    ':pair'    => $pair,        ':type'    => $type,
                    ':quality' => 'B',          ':score'   => 0,
                    ':notes'   => $notes,       ':result'  => $result,
                    ':pnl'     => $pnl,         ':balance' => $runBal,
                ]);
                $imported++;

            } catch (Exception $ex) {
                $errors[] = "Fila $i: " . $ex->getMessage();
            }
        }

        respond(['success' => true, 'imported' => $imported, 'platform' => $platform, 'errors' => $errors]);
        break;

    default:
        respondError('Acción desconocida: ' . htmlspecialchars($action), 404);
}
