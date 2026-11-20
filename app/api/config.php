<?php
// Configuracao via variaveis de ambiente (Vercel > Settings > Environment Variables).
// NUNCA coloque senhas neste arquivo.
function env_value($key, $default = '') {
    $v = getenv($key);
    if ($v === false || $v === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? '';
    }
    return $v !== '' ? $v : $default;
}

// postgresql://postgres.xxxx:SENHA@aws-0-sa-east-1.pooler.supabase.com:6543/postgres
define('DATABASE_URL', env_value('DATABASE_URL'));
define('SUPABASE_URL', rtrim(env_value('SUPABASE_URL'), '/'));
define('SUPABASE_SERVICE_ROLE_KEY', env_value('SUPABASE_SERVICE_ROLE_KEY'));
define('SUPABASE_BUCKET', env_value('SUPABASE_BUCKET', 'uploads'));
define('PAYBERRY_PUBLIC_KEY', env_value('PAYBERRY_PUBLIC_KEY'));
define('PAYBERRY_SECRET_KEY', env_value('PAYBERRY_SECRET_KEY'));
define('ADMIN_INITIAL_PASSWORD', env_value('ADMIN_PASSWORD', 'admin'));
