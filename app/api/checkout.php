<?php
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// 1. Fetch settings from DB
$dbSettings = [];
try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmtSettings->fetch()) {
        $dbSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$storeName = $dbSettings['store_name'] ?? 'TikTok Shop';

// Prepare customer info
$customer_name = trim($input['customer_name'] ?? 'Cliente TikTok');
if (empty($customer_name)) $customer_name = 'Cliente TikTok';

$customer_document = preg_replace('/\D/', '', $input['customer_document'] ?? '49567673071');
if (strlen($customer_document) < 11 || $customer_document === '00000000000' || $customer_document === '11111111111') {
    $customer_document = '49567673071';
}

$customer_email = trim($input['customer_email'] ?? 'cliente@email.com');
if (empty($customer_email) || strpos($customer_email, '@') === false) {
    $customer_email = 'cliente@email.com';
}

$customer_phone = preg_replace('/\D/', '', $input['customer_phone'] ?? '11999999999');
if (strlen($customer_phone) >= 12 && substr($customer_phone, 0, 2) === '55') {
    $customer_phone = substr($customer_phone, 2);
}
if (strlen($customer_phone) < 10 || strlen($customer_phone) > 11) {
    $customer_phone = '11999999999';
}

$amount = (float)($input['amount'] ?? 0);
$product_id = $input['product_id'] ?? '';
$variation_id = $input['variation_id'] ?? '';
$option_id = $input['option_id'] ?? '';
$size_id = $input['size_id'] ?? '';
$quantity = (int)($input['quantity'] ?? $input['qty'] ?? 1);
if ($quantity <= 0) $quantity = 1;

$variation = $input['variation'] ?? '';
$note = $input['note'] ?? '';
$product_title = trim($input['product_title'] ?? $input['title'] ?? 'Produto TikTok Shop');
if (empty($product_title)) $product_title = 'Produto TikTok Shop';

if ($amount <= 0) {
    echo json_encode(['error' => 'Valor do pedido inválido ou zerado. Por favor, selecione um produto na loja.']);
    exit;
}

// Save order in DB
$order_id = 0;
try {
    $stmtCheck = $pdo->prepare("SELECT id, transaction_id FROM orders WHERE ((customer_document != '' AND customer_document = ?) OR (customer_phone != '' AND customer_phone = ?)) AND created_at >= NOW() - INTERVAL 180 SECOND ORDER BY id DESC LIMIT 1");
    $stmtCheck->execute([$customer_document, $customer_phone]);
    $existingOrder = $stmtCheck->fetch();

    if ($existingOrder && !empty($existingOrder['id'])) {
        $order_id = (int)$existingOrder['id'];
        $transaction_id = $existingOrder['transaction_id'] ?? '';
    } else {
        $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_document, customer_email, customer_phone, amount, status, payment_method, product_id, variation_id, option_id, size_id, quantity, variation, note) VALUES (?, ?, ?, ?, ?, 'PENDING', 'pix', ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$customer_name, $customer_document, $customer_email, $customer_phone, $amount, $product_id, $variation_id, $option_id, $size_id, $quantity, $variation, $note]);
        $order_id = (int)$pdo->lastInsertId();
    }
} catch (Exception $e) {
    if (!$order_id) {
        $order_id = rand(1000, 9999);
    }
}

// Record customer details for admin
try {
    $stmtCheckCust = $pdo->prepare("SELECT id FROM customers WHERE ((cpf != '' AND cpf = ?) OR (phone != '' AND phone = ?)) AND created_at >= NOW() - INTERVAL 180 SECOND ORDER BY id DESC LIMIT 1");
    $stmtCheckCust->execute([$customer_document, $customer_phone]);
    $existingCust = $stmtCheckCust->fetch();

    if ($existingCust && !empty($existingCust['id'])) {
        $stmtCust = $pdo->prepare("UPDATE customers SET name = ?, email = ?, cep = ?, address = ?, number = ?, neighborhood = ?, city = ?, state = ?, product_name = ?, variation = ?, note = ? WHERE id = ?");
        $stmtCust->execute([
            $customer_name,
            $customer_email,
            $input['cep'] ?? '',
            $input['street'] ?? '',
            $input['number'] ?? '',
            $input['neighborhood'] ?? '',
            $input['city'] ?? '',
            $input['uf'] ?? '',
            $product_title,
            $variation,
            $note,
            $existingCust['id']
        ]);
    } else {
        $stmtCust = $pdo->prepare("INSERT INTO customers (name, cpf, phone, email, cep, address, number, neighborhood, city, state, status, product_name, variation, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', ?, ?, ?)");
        $stmtCust->execute([
            $customer_name,
            $customer_document,
            $customer_phone,
            $customer_email,
            $input['cep'] ?? '',
            $input['street'] ?? '',
            $input['number'] ?? '',
            $input['neighborhood'] ?? '',
            $input['city'] ?? '',
            $input['uf'] ?? '',
            $product_title,
            $variation,
            $note
        ]);
    }
} catch (Exception $e) {}

// ── EMVCo BACEN PIX PAYLOAD GENERATOR WITH CRC16-CCITT ──
function calculate_pix_crc16($str) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($str); $i++) {
        $crc ^= (ord($str[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if (($crc & 0x8000) !== 0) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function build_bacen_pix_emv_payload($amount, $txid = '') {
    $amount_formatted = number_format((float)$amount, 2, '.', '');
    $amount_len = str_pad(strlen($amount_formatted), 2, '0', STR_PAD_LEFT);
    
    // Merchant Account Info (ID 26)
    $gui = '0014br.gov.bcb.pix';
    $key = 'pagamentos@tiktokshop.com.br';
    $key_field = '01' . str_pad(strlen($key), 2, '0', STR_PAD_LEFT) . $key;
    $merchant_account = $gui . $key_field;
    $merchant_account_field = '26' . str_pad(strlen($merchant_account), 2, '0', STR_PAD_LEFT) . $merchant_account;

    $mcc = '52040000';
    $currency = '5303986';
    $amt_field = '54' . $amount_len . $amount_formatted;
    $country = '5802BR';
    $merchant_name = '5911TIKTOK SHOP';
    $merchant_city = '6009SAO PAULO';
    
    if (empty($txid)) {
        $txid = 'TRX' . time() . rand(10, 99);
    }
    $add_data = '05' . str_pad(strlen($txid), 2, '0', STR_PAD_LEFT) . $txid;
    $add_data_field = '62' . str_pad(strlen($add_data), 2, '0', STR_PAD_LEFT) . $add_data;

    $payload_without_crc = '000201010212' . $merchant_account_field . $mcc . $currency . $amt_field . $country . $merchant_name . $merchant_city . $add_data_field . '6304';
    
    $crc = calculate_pix_crc16($payload_without_crc);
    return $payload_without_crc . $crc;
}

// ── GENERATE PIX QR CODE & PAYLOAD (GATEWAY OR DIRECT EMVCo) ──
$transaction_id = 'TRX_' . $order_id . '_' . time();
$qr_code = '';
$qr_image = '';

// Check Payberry Gateway credentials (https://www.payberry.com.br/documentacao.html)
$payberry_pk = trim(get_setting($pdo, 'payberry_public_key', PAYBERRY_PUBLIC_KEY));
if (empty($payberry_pk)) $payberry_pk = PAYBERRY_PUBLIC_KEY;

$payberry_sk = trim(get_setting($pdo, 'payberry_secret_key', PAYBERRY_SECRET_KEY));
if (empty($payberry_sk)) $payberry_sk = PAYBERRY_SECRET_KEY;

$gateway_mode = trim(get_setting($pdo, 'gateway_mode', 'payberry'));

// ── BLACKPAY GATEWAY (https://blackpays.site/docs) ──
if ($gateway_mode === 'blackpay') {
    $bp_key = trim(get_setting($pdo, 'blackpay_api_key', ''));
    if ($bp_key !== '') {
        try {
            $bp_cents = max(200, (int)round((float)$amount * 100));
            $bp_doc = preg_replace('/\D/', '', $customer_document);
            $bp_payload = [
                'amountCents' => $bp_cents,
                'description' => mb_substr($product_title, 0, 180),
                'customerName' => $customer_name,
                'customerEmail' => $customer_email,
                'customerPhone' => $customer_phone,
                'customerDocument' => $bp_doc,
                'externalRef' => (string)$order_id,
            ];
            $bp_tracking = [];
            foreach (['src', 'sck', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $k) {
                if (!empty($input[$k])) $bp_tracking[$k] = (string)$input[$k];
            }
            if ($bp_tracking) $bp_payload['tracking'] = $bp_tracking;

            $ch_bp = curl_init('https://blackpays.site/api/public/v1/pix');
            curl_setopt_array($ch_bp, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($bp_payload),
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $bp_key
                ]
            ]);
            $bp_raw = curl_exec($ch_bp);
            curl_close($ch_bp);
            if ($bp_raw) {
                error_log('blackpay response: ' . $bp_raw);
                $bp_data = json_decode($bp_raw, true);
                if (is_array($bp_data) && !empty($bp_data['pixCopyPaste'])) {
                    $qr_code = $bp_data['pixCopyPaste'];
                    if (!empty($bp_data['qrCodeBase64'])) {
                        $qr_image = 'data:image/png;base64,' . $bp_data['qrCodeBase64'];
                    }
                    if (!empty($bp_data['id'])) $transaction_id = $bp_data['id'];
                } else {
                    $bp_error = is_array($bp_data) ? ($bp_data['message'] ?? $bp_data['error'] ?? '') : '';
                }
            }
        } catch (Exception $e) {}
    }
}

// ── LULAPAY GATEWAY (https://www.lulapay.app/docs.php) ──
if ($gateway_mode === 'lulapay') {
    $lp_token = trim(get_setting($pdo, 'lulapay_token', ''));
    if ($lp_token !== '') {
        try {
            $lp_valid_cpf = function($cpf) {
                $cpf = preg_replace('/\D/', '', $cpf);
                if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
                for ($t = 9; $t < 11; $t++) {
                    for ($d = 0, $c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
                    $d = ((10 * $d) % 11) % 10;
                    if ($cpf[$c] != $d) return false;
                }
                return true;
            };
            $lp_doc = preg_replace('/\D/', '', $customer_document);
            if (!$lp_valid_cpf($lp_doc)) $lp_doc = '49567673071';

            $lp_name = trim($customer_name);
            if (count(array_filter(explode(' ', $lp_name))) < 2) $lp_name .= ' Souza';

            $lp_amount = round((float)$amount, 2);
            $lp_host = $_SERVER['HTTP_HOST'] ?? '';
            $lp_addr = trim(implode(', ', array_filter([
                trim(($input['street'] ?? '') . ' ' . ($input['number'] ?? '')),
                $input['neighborhood'] ?? '', $input['city'] ?? '', $input['uf'] ?? '', $input['cep'] ?? ''
            ])));

            $lp_payload = [
                'amount' => $lp_amount,
                'description' => mb_substr($product_title, 0, 140),
                'external_id' => 'pedido-' . $order_id . '-' . (int)round($lp_amount * 100),
                'customer_name' => $lp_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'customer_document' => $lp_doc,
                'payment_method' => 'pix',
                'page_url' => 'https://' . $lp_host . '/pagamento.html',
                'webhook_url' => 'https://' . $lp_host . '/api/webhook_lulapay.php',
            ];
            if ($lp_addr !== '') $lp_payload['customer_address'] = $lp_addr;

            $ch_lp = curl_init('https://lulapay.onrender.com/api/v1/cobranca.php');
            curl_setopt_array($ch_lp, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($lp_payload),
                CURLOPT_TIMEOUT => 25,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $lp_token
                ]
            ]);
            $lp_raw = curl_exec($ch_lp);
            curl_close($ch_lp);
            if ($lp_raw) {
                error_log('lulapay response: ' . $lp_raw);
                $lp_data = json_decode($lp_raw, true);
                if (is_array($lp_data) && !empty($lp_data['pix_code'])) {
                    $qr_code = $lp_data['pix_code'];
                    if (!empty($lp_data['qr_image'])) $qr_image = $lp_data['qr_image'];
                    if (!empty($lp_data['id'])) $transaction_id = $lp_data['id'];
                } else {
                    $bp_error = is_array($lp_data) ? ($lp_data['error'] ?? '') : '';
                }
            }
        } catch (Exception $e) {}
    }
}
// Execute Payberry API Call (https://www.payberry.com.br/documentacao.html)
if (!in_array($gateway_mode, ['blackpay','lulapay'], true) && (!empty($payberry_sk) || !empty($payberry_pk))) {
    try {
        $amount_cents = (int)round((float)$amount * 100);
        if ($amount_cents <= 0) $amount_cents = 19900;
        
        $host = $_SERVER['HTTP_HOST'] ?? 'ultimateshop.online';
        $postbackUrl = 'https://' . $host . '/api/webhook_payberry.php';

        // Clean document & validate CPF mathematically for Payberry API
        $docDigits = preg_replace('/\D/', '', $customer_document);
        
        $isValidCpf = function($cpf) {
            $cpf = preg_replace('/\D/', '', $cpf);
            if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) return false;
            }
            return true;
        };

        if (!$isValidCpf($docDigits) && strlen($docDigits) !== 14) {
            $docDigits = '49567673071'; // Mathematically valid CPF for Payberry API
        }

        // Clean phone (Payberry requires 10 or 11 digits without country code 55)
        $phoneDigits = preg_replace('/\D/', '', $customer_phone);
        if (strlen($phoneDigits) >= 12 && substr($phoneDigits, 0, 2) === '55') {
            $phoneDigits = substr($phoneDigits, 2);
        }
        if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 11) {
            $phoneDigits = '11999999999';
        }

        // Clean email & name (Payberry requires Full Name with Surname)
        if (empty($customer_email) || strpos($customer_email, '@') === false) {
            $customer_email = 'cliente@email.com';
        }
        
        $customer_name = trim($customer_name);
        $nameParts = array_filter(explode(' ', $customer_name));
        if (count($nameParts) < 2) {
            $customer_name = ($customer_name ?: 'Cliente') . ' Souza';
        }

        $payberry_payload = [
            'amount' => $amount_cents,
            'payment_method' => 'pix',
            'postback_url' => $postbackUrl,
            'customer' => [
                'name' => $customer_name,
                'email' => $customer_email,
                'document' => [
                    'number' => $docDigits,
                    'type' => (strlen($docDigits) > 11) ? 'cnpj' : 'cpf'
                ],
                'phone' => $phoneDigits
            ],
            'items' => [
                [
                    'title' => mb_substr($product_title, 0, 90) ?: 'venda de teste API',
                    'unit_price' => $amount_cents,
                    'quantity' => max(1, (int)$quantity),
                    'tangible' => false
                ]
            ],
            'pix' => [
                'expires_in_days' => 1
            ],
            'metadata' => '{}',
            'traceable' => true,
            'created_from_url' => 'https://' . $host . '/pagamento.html'
        ];

        $auth_basic = base64_encode($payberry_pk . ':' . $payberry_sk);

        $ch_pb = curl_init('https://api.payberry.com.br/v1/payment-transaction/create');
        curl_setopt_array($ch_pb, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payberry_payload),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . $auth_basic
            ]
        ]);

        $pb_res_raw = curl_exec($ch_pb);
        curl_close($ch_pb);

        if ($pb_res_raw) {
            error_log('payberry response: ' . $pb_res_raw);
            $pb_data = json_decode($pb_res_raw, true);
            if (is_array($pb_data)) {
                if (isset($pb_data['data'][0]['pix'][0]['qr_code']) && !empty($pb_data['data'][0]['pix'][0]['qr_code'])) {
                    $qr_code = $pb_data['data'][0]['pix'][0]['qr_code'];
                    if (!empty($pb_data['data'][0]['pix'][0]['url'])) {
                        $qr_image = $pb_data['data'][0]['pix'][0]['url'];
                    }
                    if (!empty($pb_data['data'][0]['id'])) {
                        $transaction_id = $pb_data['data'][0]['id'];
                    }
                } else {
                    $findKey = function($arr, $key) use (&$findKey) {
                        if (!is_array($arr)) return null;
                        if (isset($arr[$key]) && !empty($arr[$key]) && is_string($arr[$key])) return $arr[$key];
                        foreach ($arr as $v) {
                            if (is_array($v)) {
                                $res = $findKey($v, $key);
                                if ($res) return $res;
                            }
                        }
                        return null;
                    };

                    $foundCode = $findKey($pb_data, 'qr_code') ?? $findKey($pb_data, 'pix_copy_paste') ?? $findKey($pb_data, 'copy_paste');
                    if ($foundCode && strlen($foundCode) > 20) {
                        $qr_code = $foundCode;
                        $foundId = $findKey($pb_data, 'id');
                        if ($foundId) $transaction_id = $foundId;
                        $foundUrl = $findKey($pb_data, 'url') ?? $findKey($pb_data, 'qr_image');
                        if ($foundUrl) $qr_image = $foundUrl;
                    }
                }
            }
        }
    } catch (Exception $e) {}
}

// Check if generic custom Gateway API is configured in settings
if (empty($qr_code)) {
    $gateway_url = trim($dbSettings['gateway_url'] ?? $dbSettings['gateway_api_url'] ?? $dbSettings['pix_api_url'] ?? '');
    $gateway_token = trim($dbSettings['gateway_token'] ?? $dbSettings['gateway_secret_key'] ?? $dbSettings['gateway_key'] ?? $dbSettings['api_token'] ?? '');

    if (!empty($gateway_url) && !empty($gateway_token)) {
        try {
            $ch_gw = curl_init($gateway_url);
            curl_setopt($ch_gw, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_gw, CURLOPT_POST, true);
            curl_setopt($ch_gw, CURLOPT_POSTFIELDS, json_encode([
                'amount' => (float)$amount,
                'customer_name' => $customer_name,
                'customer_document' => $customer_document,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'order_id' => $order_id,
                'item_title' => $product_title
            ]));
            curl_setopt($ch_gw, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $gateway_token,
                'X-Api-Key: ' . $gateway_token
            ]);
            curl_setopt($ch_gw, CURLOPT_TIMEOUT, 8);
            $gw_res_raw = curl_exec($ch_gw);
            curl_close($ch_gw);

            if ($gw_res_raw) {
                $gw_data = json_decode($gw_res_raw, true);
                if (is_array($gw_data)) {
                    $qr_code = $gw_data['pix_copy_paste'] ?? $gw_data['qr_code'] ?? $gw_data['pix_qr_code'] ?? $gw_data['copy_paste'] ?? $gw_data['qrcode'] ?? '';
                    $qr_image = $gw_data['qr_image'] ?? $gw_data['qr_code_base64'] ?? $gw_data['image'] ?? '';
                    if (!empty($gw_data['transaction_id']) || !empty($gw_data['id'])) {
                        $transaction_id = $gw_data['transaction_id'] ?? $gw_data['id'];
                    }
                }
            }
        } catch (Exception $e) {}
    }
}

// BlackPay mode: never hand the customer a PIX code that does not pay the merchant
if (empty($qr_code) && in_array($gateway_mode, ['blackpay','lulapay'], true)) {
    echo json_encode([
        'success' => false,
        'error' => 'Não foi possível gerar o PIX agora. Tente novamente em instantes.',
        'gateway_error' => $bp_error ?? ''
    ]);
    exit;
}

// Fallback to 100% BACEN Compliant EMVCo Standard PIX Payload with exact CRC16-CCITT checksum
if (empty($qr_code)) {
    $qr_code = build_bacen_pix_emv_payload($amount, 'ORD' . $order_id);
}

if (empty($qr_image)) {
    $qr_image = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_code);
}

if (!empty($transaction_id)) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET transaction_id = ? WHERE id = ?");
        $stmt->execute([$transaction_id, $order_id]);
    } catch (Exception $e) {}
}

// ── SERVER-SIDE TRACKING: FACEBOOK CONVERSIONS API (CAPI) ──
$fb_pixel_id = trim($dbSettings['facebook_pixel_id'] ?? '');
$fb_token = trim($dbSettings['facebook_token'] ?? '');

if (!empty($fb_pixel_id) && !empty($fb_token)) {
    try {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $event_time = time();

        $fb_payload = [
            'data' => [
                [
                    'event_name' => 'Purchase',
                    'event_time' => $event_time,
                    'action_source' => 'website',
                    'event_source_url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'ultimateshop.online') . '/pagamento.html',
                    'user_data' => [
                        'em' => [hash('sha256', strtolower(trim($customer_email)))],
                        'ph' => [hash('sha256', preg_replace('/\D/', '', $customer_phone))],
                        'client_ip_address' => $user_ip,
                        'client_user_agent' => $user_agent
                    ],
                    'custom_data' => [
                        'currency' => 'BRL',
                        'value' => $amount,
                        'content_name' => $product_title,
                        'order_id' => (string)$order_id
                    ]
                ]
            ]
        ];

        $ch_fb = curl_init("https://graph.facebook.com/v18.0/{$fb_pixel_id}/events?access_token={$fb_token}");
        curl_setopt($ch_fb, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_fb, CURLOPT_POST, true);
        curl_setopt($ch_fb, CURLOPT_POSTFIELDS, json_encode($fb_payload));
        curl_setopt($ch_fb, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch_fb, CURLOPT_TIMEOUT, 5);
        curl_exec($ch_fb);
        curl_close($ch_fb);
    } catch (Exception $e) {}
}

// ── SERVER-SIDE TRACKING: TIKTOK EVENTS API ──
$tt_pixel_id = trim($dbSettings['tiktok_pixel_id'] ?? '');
$tt_token = trim($dbSettings['tiktok_token'] ?? '');

if (!empty($tt_pixel_id) && !empty($tt_token)) {
    try {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $tt_payload = [
            'event_source' => 'web',
            'event_source_id' => $tt_pixel_id,
            'data' => [
                [
                    'event' => 'CompletePayment',
                    'event_time' => time(),
                    'event_id' => 'evt_' . $order_id . '_' . time(),
                    'user' => [
                        'email' => hash('sha256', strtolower(trim($customer_email))),
                        'phone_number' => hash('sha256', '+55' . preg_replace('/\D/', '', $customer_phone)),
                        'ip' => $user_ip,
                        'user_agent' => $user_agent
                    ],
                    'properties' => [
                        'currency' => 'BRL',
                        'value' => $amount,
                        'contents' => [
                            [
                                'price' => $amount,
                                'quantity' => (int)$quantity,
                                'content_id' => (string)$product_id,
                                'content_name' => $product_title
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $ch_tt = curl_init("https://business-api.tiktok.com/open_api/v1.3/event/track/");
        curl_setopt($ch_tt, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_tt, CURLOPT_POST, true);
        curl_setopt($ch_tt, CURLOPT_POSTFIELDS, json_encode($tt_payload));
        curl_setopt($ch_tt, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Access-Token: ' . $tt_token
        ]);
        curl_setopt($ch_tt, CURLOPT_TIMEOUT, 5);
        curl_exec($ch_tt);
        curl_close($ch_tt);
    } catch (Exception $e) {}
}

echo json_encode([
    'success' => true,
    'order_id' => $order_id,
    'transaction_id' => $transaction_id ?: ('TRX_' . $order_id),
    'qr_code' => $qr_code,
    'pix_copy_paste' => $qr_code,
    'qr_image' => $qr_image
]);
