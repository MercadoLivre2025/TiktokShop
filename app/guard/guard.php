<?php
/**
 * Guard: rate limiting, IP blocklist and abuse protection (file-based, no DB).
 * Usage: require __DIR__.'/guard/guard.php'; Guard::protect();  // at top of any PHP page/endpoint
 * Every visitor gets the SAME content; abusive clients simply get 403/429.
 */
final class Guard
{
    const CONFIG = [
        'storage'        => '',  // auto: /tmp on Vercel (read-only FS)
        'window'         => 60,     // seconds
        'max_requests'   => 120,    // per IP per window
        'strike_limit'   => 5,      // rate-limit hits before auto-ban
        'ban_seconds'    => 3600,   // auto-ban duration
        'allow_ips'      => ['127.0.0.1', '::1'],
        'block_ips'      => [],     // static list, supports CIDR e.g. '203.0.113.0/24'
        'block_agents'   => ['/curl\//i', '/python-requests/i', '/scrapy/i', '/wget/i', '/httpclient/i', '/go-http-client/i', '/libwww/i', '/headlesschrome/i'],
        'allow_agents'   => ['/googlebot/i', '/bingbot/i'], // legit crawlers bypass UA block
        'block_empty_ua' => true,
        'trust_proxy'    => false,  // true only behind Cloudflare/known proxy
        'honeypot_path'  => '/wp-login.php', // fake path; anyone requesting it is banned (see api/index.php)
        'log'            => true,
    ];

    public static function protect(): void
    {
        $c  = self::CONFIG;
        $ip = self::ip();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (in_array($ip, $c['allow_ips'], true)) return;
        self::dir();

        if (self::inList($ip, $c['block_ips']) || self::isBanned($ip)) self::deny(403, 'blocked', $ip);

        $trusted = false;
        foreach ($c['allow_agents'] as $re) if (preg_match($re, $ua)) $trusted = true;
        if (!$trusted) {
            if ($c['block_empty_ua'] && trim($ua) === '') self::deny(403, 'empty-ua', $ip);
            foreach ($c['block_agents'] as $re) if (preg_match($re, $ua)) self::deny(403, 'bad-ua', $ip);
        }

        if (!self::hit($ip)) {
            self::strike($ip);
            header('Retry-After: ' . $c['window']);
            self::deny(429, 'rate-limit', $ip);
        }
    }

    /** Call from the honeypot page: bans the IP immediately. */
    public static function ban(string $ip = '', ?int $seconds = null): void
    {
        $ip = $ip ?: self::ip();
        self::withFile('bans.json', function (&$d) use ($ip, $seconds) {
            $d[$ip] = time() + ($seconds ?? self::CONFIG['ban_seconds']);
        });
        self::log("ban $ip");
    }

    public static function ip(): string
    {
        if (self::CONFIG['trust_proxy']) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
                if (!empty($_SERVER[$h])) {
                    $v = trim(explode(',', $_SERVER[$h])[0]);
                    if (filter_var($v, FILTER_VALIDATE_IP)) return $v;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function dir(): string
    {
        static $d = null;
        if ($d !== null) return $d;
        $d = self::CONFIG['storage'] ?: sys_get_temp_dir() . '/guard';
        if (!is_dir($d)) @mkdir($d, 0700, true);
        return $d;
    }

    private static function hit(string $ip): bool
    {
        $ok = true;
        self::withFile('rate.json', function (&$d) use ($ip, &$ok) {
            $now = time(); $w = self::CONFIG['window'];
            foreach ($d as $k => $v) if ($v['t'] + $w < $now) unset($d[$k]); // gc
            if (!isset($d[$ip])) $d[$ip] = ['t' => $now, 'n' => 0, 's' => 0];
            $d[$ip]['n']++;
            if ($d[$ip]['n'] > self::CONFIG['max_requests']) $ok = false;
        });
        return $ok;
    }

    private static function strike(string $ip): void
    {
        $ban = false;
        self::withFile('rate.json', function (&$d) use ($ip, &$ban) {
            $d[$ip]['s'] = ($d[$ip]['s'] ?? 0) + 1;
            if ($d[$ip]['s'] >= self::CONFIG['strike_limit']) { $ban = true; $d[$ip]['s'] = 0; }
        });
        if ($ban) self::ban($ip);
    }

    private static function isBanned(string $ip): bool
    {
        $banned = false;
        self::withFile('bans.json', function (&$d) use ($ip, &$banned) {
            $now = time();
            foreach ($d as $k => $until) if ($until < $now) unset($d[$k]);
            $banned = isset($d[$ip]);
        });
        return $banned;
    }

    private static function inList(string $ip, array $list): bool
    {
        foreach ($list as $e) {
            if ($e === $ip) return true;
            if (strpos($e, '/') !== false && self::cidr($ip, $e)) return true;
        }
        return false;
    }

    private static function cidr(string $ip, string $cidr): bool
    {
        [$sub, $bits] = explode('/', $cidr);
        $a = @inet_pton($ip); $b = @inet_pton($sub);
        if ($a === false || $b === false || strlen($a) !== strlen($b)) return false;
        $bits = (int)$bits; $bytes = intdiv($bits, 8); $rem = $bits % 8;
        if ($bytes && substr($a, 0, $bytes) !== substr($b, 0, $bytes)) return false;
        if ($rem) {
            $mask = (0xFF << (8 - $rem)) & 0xFF;
            return (ord($a[$bytes]) & $mask) === (ord($b[$bytes]) & $mask);
        }
        return true;
    }

    private static function withFile(string $name, callable $fn): void
    {
        $path = self::dir() . '/' . $name;
        $fh = fopen($path, 'c+');
        if (!$fh) return;
        flock($fh, LOCK_EX);
        $d = json_decode(stream_get_contents($fh), true) ?: [];
        $fn($d);
        ftruncate($fh, 0); rewind($fh);
        fwrite($fh, json_encode($d));
        flock($fh, LOCK_UN); fclose($fh);
    }

    private static function deny(int $code, string $reason, string $ip): never
    {
        self::log("$code $reason $ip " . ($_SERVER['REQUEST_URI'] ?? ''));
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        echo $code === 429 ? 'Too many requests.' : 'Access denied.';
        exit;
    }

    private static function log(string $msg): void
    {
        if (!self::CONFIG['log']) return;
        @file_put_contents(self::dir() . '/guard.log', date('c') . " $msg\n", FILE_APPEND | LOCK_EX);
    }
}
