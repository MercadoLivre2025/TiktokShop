<?php
// Conexao PostgreSQL (Supabase) + camada de compatibilidade com o SQL MySQL legado.
// Credenciais SEMPRE via variaveis de ambiente (veja config.php / .env.example).
require_once __DIR__ . '/config.php';

const APP_TZ = 'America/Sao_Paulo';

class CompatStatement extends PDOStatement {
    protected function __construct() {}

    public function execute(?array $params = null): bool {
        $ok = parent::execute($params);
        if ($ok && stripos($this->queryString, ' RETURNING id') !== false
            && stripos(ltrim($this->queryString), 'INSERT') === 0) {
            $row = $this->fetch(PDO::FETCH_NUM);
            CompatPDO::$lastId = $row ? (string)$row[0] : '0';
            $this->closeCursor();
        }
        return $ok;
    }
}

class CompatPDO extends PDO {
    public static $lastId = '0';
    private static $idTables = ['orders', 'customers', 'products', 'categories', 'chat_messages', 'admin_users'];

    public static function rewrite(string $sql): string {
        $sql = str_replace('`', '', $sql);
        $tz  = "(NOW() AT TIME ZONE '" . APP_TZ . "')";
        // DATE_SUB(NOW(), INTERVAL n UNIT)
        $sql = preg_replace_callback('/DATE_SUB\(\s*NOW\(\)\s*,\s*INTERVAL\s+(\d+)\s+(SECOND|MINUTE|HOUR|DAY)S?\s*\)/i',
            function ($m) use ($tz) { return "($tz - INTERVAL '" . $m[1] . ' ' . strtolower($m[2]) . "s')"; }, $sql);
        // NOW() - INTERVAL n UNIT
        $sql = preg_replace_callback('/NOW\(\)\s*-\s*INTERVAL\s+(\d+)\s+(SECOND|MINUTE|HOUR|DAY)S?/i',
            function ($m) use ($tz) { return "$tz - INTERVAL '" . $m[1] . ' ' . strtolower($m[2]) . "s'"; }, $sql);
        $sql = preg_replace('/CURDATE\(\)/i', "$tz::date", $sql);
        $sql = preg_replace('/NOW\(\)(?!\s+AT TIME ZONE)/i', $tz, $sql);
        // lastInsertId() -> RETURNING id
        if (preg_match('/^\s*INSERT\s+INTO\s+"?(\w+)"?/i', $sql, $m)
            && in_array(strtolower($m[1]), self::$idTables, true)
            && stripos($sql, 'RETURNING') === false) {
            $sql = rtrim(rtrim($sql), ';') . ' RETURNING id';
        }
        return $sql;
    }

    #[\ReturnTypeWillChange]
    public function prepare($query, $options = []) {
        return parent::prepare(self::rewrite($query), $options);
    }

    #[\ReturnTypeWillChange]
    public function query($query, $fetchMode = null, ...$fetchModeArgs) {
        $query = self::rewrite($query);
        $stmt = $fetchMode === null ? parent::query($query) : parent::query($query, $fetchMode, ...$fetchModeArgs);
        if ($stmt && stripos($query, ' RETURNING id') !== false && stripos(ltrim($query), 'INSERT') === 0) {
            $row = $stmt->fetch(PDO::FETCH_NUM);
            self::$lastId = $row ? (string)$row[0] : '0';
        }
        return $stmt;
    }

    #[\ReturnTypeWillChange]
    public function exec($statement) {
        $statement = self::rewrite($statement);
        if (stripos($statement, ' RETURNING id') !== false && stripos(ltrim($statement), 'INSERT') === 0) {
            $stmt = parent::query($statement);
            $row = $stmt->fetch(PDO::FETCH_NUM);
            self::$lastId = $row ? (string)$row[0] : '0';
            return $stmt->rowCount();
        }
        return parent::exec($statement);
    }

    #[\ReturnTypeWillChange]
    public function lastInsertId($name = null) {
        return self::$lastId;
    }
}

// Sessoes PHP guardadas no Postgres (o filesystem da Vercel e efemero).
class DbSessionHandler implements SessionHandlerInterface {
    private $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }
    public function read($id): string|false {
        $s = $this->pdo->prepare("SELECT data FROM php_sessions WHERE id = ?");
        $s->execute([$id]);
        $d = $s->fetchColumn();
        return $d === false ? '' : (string)$d;
    }
    public function write($id, $data): bool {
        $s = $this->pdo->prepare("INSERT INTO php_sessions (id, data, updated_at) VALUES (?, ?, NOW())
            ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, updated_at = NOW()");
        return $s->execute([$id, $data]);
    }
    public function destroy($id): bool {
        return $this->pdo->prepare("DELETE FROM php_sessions WHERE id = ?")->execute([$id]);
    }
    public function gc($max): int|false {
        $s = $this->pdo->prepare("DELETE FROM php_sessions WHERE updated_at < NOW() - INTERVAL '" . (int)$max . " seconds'");
        $s->execute();
        return $s->rowCount();
    }
}

function db_connect(): PDO {
    if (!DATABASE_URL) {
        throw new RuntimeException('Variavel de ambiente DATABASE_URL nao configurada.');
    }
    $u = parse_url(DATABASE_URL);
    if (!$u || empty($u['host'])) {
        throw new RuntimeException('DATABASE_URL invalida.');
    }
    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
        $u['host'], $u['port'] ?? 5432, ltrim($u['path'] ?? '/postgres', '/'));
    return new CompatPDO($dsn, urldecode($u['user'] ?? ''), urldecode($u['pass'] ?? ''), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Necessario para o pooler do Supabase em modo transaction (porta 6543)
        PDO::ATTR_EMULATE_PREPARES   => true,
        PDO::ATTR_STATEMENT_CLASS    => ['CompatStatement'],
    ]);
}

try {
    $pdo = db_connect();
} catch (Throwable $e) {
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(500);
    die('Erro de conexao com o banco de dados.');
}

// Sessao em banco
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.gc_maxlifetime', '604800');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_set_save_handler(new DbSessionHandler($pdo), true);
}

// Cria o usuario admin na primeira vez (senha = variavel ADMIN_PASSWORD, padrao "admin" - TROQUE!)
function ensure_admin_user($pdo) {
    try {
        $n = (int)$pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        if ($n === 0) {
            $pdo->prepare("INSERT INTO admin_users (username, password) VALUES ('admin', ?)")
                ->execute([password_hash(ADMIN_INITIAL_PASSWORD, PASSWORD_BCRYPT)]);
        }
    } catch (Exception $e) { error_log('ensure_admin_user: ' . $e->getMessage()); }
}

function check_auth() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

function get_setting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if ($row && isset($row['setting_value']) && trim($row['setting_value']) !== '') {
            return trim($row['setting_value']);
        }
    } catch (Exception $e) {}
    return $default;
}

function set_setting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
        ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");
    $stmt->execute([$key, $value]);
}
