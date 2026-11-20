<?php
// Upload de imagens para o Supabase Storage (o disco da Vercel e somente leitura).
// Retorna a URL publica ou null em caso de falha.
$GLOBALS['STORAGE_LAST_ERROR'] = '';
function storage_upload(string $tmpPath, string $filename, string $contentType = 'application/octet-stream') {
    if (!SUPABASE_URL || !SUPABASE_SERVICE_ROLE_KEY) {
        error_log('storage_upload: SUPABASE_URL / SUPABASE_SERVICE_ROLE_KEY nao configuradas');
        $GLOBALS['STORAGE_LAST_ERROR'] = 'SUPABASE_URL ou SUPABASE_SERVICE_ROLE_KEY nao configuradas na Vercel';
        return null;
    }
    $data = file_get_contents($tmpPath);
    if ($data === false) return null;
    $url = SUPABASE_URL . '/storage/v1/object/' . rawurlencode(SUPABASE_BUCKET) . '/' . rawurlencode($filename);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
            'apikey: ' . SUPABASE_SERVICE_ROLE_KEY,
            'Content-Type: ' . $contentType,
            'x-upsert: true',
        ],
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300) {
        error_log("storage_upload falhou ($code): $res");
        $GLOBALS['STORAGE_LAST_ERROR'] = "Supabase Storage respondeu $code: " . substr((string)$res, 0, 200);
        return null;
    }
    return SUPABASE_URL . '/storage/v1/object/public/' . rawurlencode(SUPABASE_BUCKET) . '/' . rawurlencode($filename);
}

function storage_delete_by_url(string $publicUrl): void {
    if (!SUPABASE_URL || !SUPABASE_SERVICE_ROLE_KEY) return;
    $prefix = SUPABASE_URL . '/storage/v1/object/public/' . rawurlencode(SUPABASE_BUCKET) . '/';
    if (strpos($publicUrl, $prefix) !== 0) return;
    $name = substr($publicUrl, strlen($prefix));
    $ch = curl_init(SUPABASE_URL . '/storage/v1/object/' . rawurlencode(SUPABASE_BUCKET) . '/' . $name);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
            'apikey: ' . SUPABASE_SERVICE_ROLE_KEY,
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}
