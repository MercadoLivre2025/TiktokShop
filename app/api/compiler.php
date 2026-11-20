<?php

function parse_price_to_float_helper($val) {
    if (is_numeric($val)) return (float)$val;
    $str = trim(str_ireplace(['R$', ' '], '', (string)$val));
    if (empty($str)) return 0.0;
    if (strpos($str, '.') !== false && strpos($str, ',') !== false) {
        $str = str_replace('.', '', $str);
        $str = str_replace(',', '.', $str);
    } elseif (strpos($str, ',') !== false) {
        $str = str_replace(',', '.', $str);
    }
    return (float)$str;
}

function get_db_settings($pdo) {
    $settings = [];
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch(Exception $e) {}
    // Logo fixo da loja (ignora o que estiver salvo no banco)
    $settings['store_logo'] = '/assets/logo-loja-bf.webp';
    $settings['store_profile'] = '/assets/logo-loja-bf.webp';
    return $settings;
}

function normalize_reviews_array($raw, $total_reviews_setting = '') {
    if (is_string($raw)) {
        $raw = json_decode($raw, true) ?: [];
    }
    if (!is_array($raw)) {
        $raw = [];
    }

    $normalized = [];
    foreach ($raw as $rev) {
        if (!is_array($rev)) continue;
        $name = $rev['name'] ?? $rev['n'] ?? 'Cliente';
        $text = $rev['text'] ?? $rev['t'] ?? '';
        $avatar = !empty($rev['avatar']) ? $rev['avatar'] : 'https://p16-common-sign.tiktokcdn.com/tos-maliva-avt-0068/c1408d9abdf3d3a34c5c09f27f32016a~tplv-tiktokx-cropcenter:100:100.jpg';
        $rating = isset($rev['rating']) ? (int)$rev['rating'] : 5;
        $photos = $rev['photos'] ?? $rev['imgs'] ?? [];
        if (!is_array($photos)) $photos = [];

        $normalized[] = [
            'name' => (string)$name, 'n' => (string)$name, 'rating' => $rating,
            'text' => (string)$text, 't' => (string)$text, 'd' => (string)($rev['d'] ?? $rev['date'] ?? 'Compra confirmada'),
            'avatar' => (string)$avatar, 'photos' => array_values($photos), 'imgs' => array_values($photos)
        ];
    }

    $num_revs = !empty($total_reviews_setting) ? (int)preg_replace('/[^0-9]/', '', (string)$total_reviews_setting) : 0;
    // Cap generated reviews array to maximum 15 items to keep memory under 2MB
    if ($num_revs > 15) $num_revs = 15;
    
    if ($num_revs > 0) {
        if (count($normalized) > $num_revs) {
            $normalized = array_slice($normalized, 0, $num_revs);
        } else if (count($normalized) < $num_revs) {
            $needed = min($num_revs - count($normalized), 15);
            $menNames = ["Marcos Silva", "Carlos Eduardo", "Rafael Machado", "Roberto K.", "Lucas Almeida", "Thiago Pereira", "Felipe Costa", "Bruno Souza", "Gustavo Henrique", "Diego Augusto"];
            $womenNames = ["Ana Lúcia Silva", "Juliana Costa", "Fernanda Sousa", "Mariana Torres", "Camila Rodrigues", "Beatriz Vieira", "Amanda Gomes", "Bruna Alves", "Larissa Martins", "Letícia Ferreira"];
            $positiveTexts = [
                "Produto excelente, chegou super rápido e muito bem embalado. Recomendo muito!",
                "Qualidade impecável, exatamente como descrito no anúncio. Muito satisfeito.",
                "Comprei com receio, mas me surpreendi positivamente. Vale cada centavo!",
                "Muito bom, cumpre o que promete. Uso todos os dias e não tenho do que reclamar.",
                "Entrega super rápida, antes do prazo. O produto é de altíssima qualidade.",
                "Perfeito! Material ótimo, acabamento impecável. Compraria novamente com certeza."
            ];
            shuffle($womenNames); shuffle($menNames); shuffle($positiveTexts);
            $womenAvatars = range(1, 50); shuffle($womenAvatars);
            $menAvatars = range(1, 50); shuffle($menAvatars);
            $w_i = 0; $m_i = 0;

            for ($r_idx = 0; $r_idx < $needed; $r_idx++) {
                $is_woman = rand(0, 1) === 1;
                if ($is_woman) {
                    $name = $womenNames[$w_i % count($womenNames)];
                    $avatar = "https://randomuser.me/api/portraits/women/" . $womenAvatars[$w_i % count($womenAvatars)] . ".jpg";
                    $w_i++;
                } else {
                    $name = $menNames[$m_i % count($menNames)];
                    $avatar = "https://randomuser.me/api/portraits/men/" . $menAvatars[$m_i % count($menAvatars)] . ".jpg";
                    $m_i++;
                }
                $text = $positiveTexts[$r_idx % count($positiveTexts)];
                $date = date('d/m/Y', strtotime('-' . rand(1, 30) . ' days'));

                $normalized[] = [
                    'name' => $name, 'n' => $name, 'rating' => 5, 'text' => $text, 't' => $text,
                    'd' => $date, 'avatar' => $avatar, 'photos' => [], 'imgs' => []
                ];
            }
        }
    } else if (empty($normalized)) {
        $num_revs = 5;
        $menNames = ["Marcos Silva", "Carlos Eduardo", "Rafael Machado", "Roberto K.", "Lucas Almeida", "Thiago Pereira", "Felipe Costa", "Bruno Souza"];
        $womenNames = ["Ana Lúcia Silva", "Juliana Costa", "Fernanda Sousa", "Mariana Torres", "Camila Rodrigues", "Beatriz Vieira", "Amanda Gomes", "Bruna Alves"];
        $positiveTexts = [
            "Produto excelente, chegou super rápido e muito bem embalado. Recomendo muito!",
            "Qualidade impecável, exatamente como descrito no anúncio. Muito satisfeito.",
            "Comprei com receio, mas me surpreendi positivamente. Vale cada centavo!",
            "Muito bom, cumpre o que promete. Uso todos os dias e não tenho do que reclamar.",
            "Entrega super rápida, antes do prazo. O produto é de altíssima qualidade.",
            "Perfeito! Material ótimo, acabamento impecável. Compraria novamente com certeza.",
            "Melhor investimento que fiz. Facilita muito no dia a dia. Nota 10!",
            "Recomendo de olhos fechados. Excelente custo-benefício."
        ];
        shuffle($womenNames); shuffle($menNames); shuffle($positiveTexts);
        $womenAvatars = range(1, 99); shuffle($womenAvatars);
        $menAvatars = range(1, 99); shuffle($menAvatars);
        $w_i = 0; $m_i = 0;

        for ($r_idx = 0; $r_idx < $num_revs; $r_idx++) {
            $is_woman = rand(0, 1) === 1;
            if ($is_woman) {
                $name = $womenNames[$w_i % count($womenNames)];
                $avatar = "https://randomuser.me/api/portraits/women/" . $womenAvatars[$w_i % count($womenAvatars)] . ".jpg";
                $w_i++;
            } else {
                $name = $menNames[$m_i % count($menNames)];
                $avatar = "https://randomuser.me/api/portraits/men/" . $menAvatars[$m_i % count($menAvatars)] . ".jpg";
                $m_i++;
            }
            $text = $positiveTexts[$r_idx % count($positiveTexts)];
            $date = date('d/m/Y', strtotime('-' . rand(1, 30) . ' days'));

            $normalized[] = [
                'name' => $name, 'n' => $name, 'rating' => 5, 'text' => $text, 't' => $text,
                'd' => $date, 'avatar' => $avatar, 'photos' => [], 'imgs' => []
            ];
        }
    }

    return array_values($normalized);
}

function normalize_product_data($prod_data) {
    if (!is_array($prod_data)) return [];

    // 1. Normalizar Imagens
    $raw_imgs = [];
    foreach (['images', 'image', 'carousel_images'] as $img_field) {
        if (isset($prod_data[$img_field])) {
            $val = $prod_data[$img_field];
            if (is_array($val)) {
                $raw_imgs = array_merge($raw_imgs, $val);
            } elseif (is_string($val) && trim($val) !== '') {
                $dec = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
                    $raw_imgs = array_merge($raw_imgs, $dec);
                } else {
                    $raw_imgs[] = trim($val);
                }
            }
        }
    }
    $clean_imgs = [];
    $seen = [];
    array_walk_recursive($raw_imgs, function($item) use (&$clean_imgs, &$seen) {
        if (is_string($item)) {
            $url = trim(stripslashes($item));
            if ($url !== '' && !isset($seen[$url])) {
                $seen[$url] = true;
                $clean_imgs[] = $url;
            }
        }
    });

    $prod_data['images'] = $clean_imgs;
    $prod_data['carousel_images'] = $clean_imgs;
    $prod_data['product_image'] = $clean_imgs[0] ?? '';
    $prod_data['image'] = $clean_imgs[0] ?? '';

    // 2. Normalizar Features e Packing List (garantir que sejam listas indexadas de strings)
    foreach (['features', 'packing_list'] as $list_field) {
        $val = $prod_data[$list_field] ?? [];
        if (is_string($val)) {
            $trimmed = trim($val);
            if (strpos($trimmed, '[') === 0 || strpos($trimmed, '{') === 0) {
                $dec = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $val = $dec;
                }
            }
        }
        $clean_list = [];
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                if (is_array($v)) {
                    $clean_list[] = implode(': ', array_map('strval', $v));
                } elseif (!is_numeric($k)) {
                    $clean_list[] = trim((string)$k) . ': ' . trim((string)$v);
                } else {
                    $str = trim((string)$v);
                    if ($str !== '') $clean_list[] = $str;
                }
            }
        } elseif (is_string($val) && trim($val) !== '') {
            $lines = explode("\n", $val);
            foreach ($lines as $line) {
                $str = trim($line);
                if ($str !== '') $clean_list[] = $str;
            }
        }
        $prod_data[$list_field] = array_values($clean_list);
    }

    // 3. Normalizar Benefits
    $benefits_val = $prod_data['benefits'] ?? [];
    if (is_string($benefits_val)) {
        $trimmed = trim($benefits_val);
        if (strpos($trimmed, '[') === 0 || strpos($trimmed, '{') === 0) {
            $dec = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $benefits_val = $dec;
            }
        }
    }
    $clean_benefits = [];
    if (is_array($benefits_val)) {
        foreach ($benefits_val as $k => $v) {
            if (is_array($v)) {
                $clean_benefits[] = array_values(array_map('strval', $v));
            } elseif (!is_numeric($k)) {
                $clean_benefits[] = [trim((string)$k), trim((string)$v)];
            } elseif (is_string($v) && trim($v) !== '') {
                if (strpos($v, ':') !== false) {
                    $parts = explode(':', $v, 2);
                    $clean_benefits[] = [trim($parts[0]), trim($parts[1])];
                } else {
                    $clean_benefits[] = [trim($v), ''];
                }
            }
        }
    }
    $prod_data['benefits'] = array_values($clean_benefits);

    // 4. Normalizar Descrição (remover apenas tags <img> inválidas e manter todo o texto da descrição)
    if (isset($prod_data['description']) && is_string($prod_data['description'])) {
        $desc = preg_replace('/<img[^>]*>/i', '', $prod_data['description']);
        $prod_data['description'] = trim($desc);
    }

    // 5. Normalizar Avaliações (Reviews)
    $raw_revs = $prod_data['reviews'] ?? $prod_data['reviews_json'] ?? [];
    $tot_revs = $prod_data['total_reviews'] ?? '';
    $prod_data['reviews'] = normalize_reviews_array($raw_revs, $tot_revs);
    $prod_data['reviews_json'] = $prod_data['reviews'];

    return $prod_data;
}

function get_db_products($pdo) {
    $products = [];
    try {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
        while ($p = $stmt->fetch()) {
            $prod_data = [];
            foreach ($p as $key => $value) {
                if (in_array(strtolower($key), ['password', 'token', 'secret', 'cost', 'private_key'])) {
                    continue;
                }
                
                $isJson = (is_string($value) && (strpos(trim($value), '{') === 0 || strpos(trim($value), '[') === 0));
                if ($isJson) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $prod_data[$key] = $decoded;
                        continue;
                    }
                }
                
                $prod_data[$key] = $value;
            }

            $prod_data = normalize_product_data($prod_data);
            $prod_data['discount'] = $prod_data['discount_price'] ?? '';
            $prod_data['flash_sale_text'] = $prod_data['flash_sale'] ?? '';

            if (empty($prod_data['rating'])) $prod_data['rating'] = '4.9';
            if (empty($prod_data['sold'])) $prod_data['sold'] = '12.4K';

            if (!empty($prod_data['flash_sale_timer'])) {
                if (stripos($prod_data['flash_sale_timer'], 'PROMO') !== false) {
                    $promo_text = substr($prod_data['flash_sale_timer'], stripos($prod_data['flash_sale_timer'], 'PROMO'));
                    $prod_data['flash_sale_timer'] = '⚡ ' . $promo_text;
                }
            }
            if (!empty($prod_data['bottom_bar_text'])) {
                $cleaned = trim(str_replace(['?', '', '??', '???'], '', $prod_data['bottom_bar_text']));
                if (strpos($cleaned, '🔥') === false && stripos($cleaned, 'PROMO') !== false) {
                    $prod_data['bottom_bar_text'] = '🔥 ' . ltrim($cleaned);
                } else {
                    $prod_data['bottom_bar_text'] = str_replace(['?', ''], '', $prod_data['bottom_bar_text']);
                }
            }

            $products[] = $prod_data;
        }
    } catch(Exception $e) {}
    return $products;
}

function get_lightweight_products_list($products) {
    $lightweight = [];
    foreach ((array)$products as $p) {
        if (!is_array($p)) continue;
        $lightweight[] = [
            'id' => $p['id'] ?? '',
            'title' => $p['title'] ?? '',
            'price' => $p['price'] ?? '',
            'discount_price' => $p['discount_price'] ?? $p['discount'] ?? '',
            'discount' => $p['discount'] ?? $p['discount_price'] ?? '',
            'image' => $p['image'] ?? ($p['images'][0] ?? ''),
            'images' => array_slice((array)($p['images'] ?? []), 0, 3),
            'rating' => $p['rating'] ?? '4.9',
            'sold' => $p['sold'] ?? '12.4K',
            'discount_percent' => $p['discount_percent'] ?? '-50%',
            'flash_sale' => $p['flash_sale'] ?? '1',
            'badge' => $p['badge'] ?? '',
            'category' => $p['category'] ?? ''
        ];
    }
    return $lightweight;
}

function get_shop_config($pdo) {
    $settings = get_db_settings($pdo);
    $products = get_db_products($pdo);

    $categories_list = [];
    try {
        $stmt_cat = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        $categories_list = $stmt_cat->fetchAll();
    } catch(Exception $e) {}

    $lightweight_prods = get_lightweight_products_list($products);

    $base_config = [
        'store_name' => $settings['store_name'] ?? 'Minha Loja',
        'store_logo' => $settings['store_logo'] ?? '/assets/bighome-logo-BXgKvfO0.png',
        'store_profile' => $settings['store_profile'] ?? '/assets/bighome-logo-BXgKvfO0.png',
        'store_cover' => $settings['store_cover'] ?? '',
        'store_banner' => $settings['store_banner'] ?? '',
        'store_phone' => $settings['store_phone'] ?? '',
        'store_whatsapp' => $settings['store_whatsapp'] ?? '',
        'store_email' => $settings['store_email'] ?? 'contato@minhaloja.com',
        'store_address' => $settings['store_address'] ?? '',
        'store_instagram' => $settings['store_instagram'] ?? '',
        'store_facebook' => $settings['store_facebook'] ?? '',
        'store_tiktok' => $settings['store_tiktok'] ?? '',
        'store_sales' => $settings['store_sales'] ?? '128.4K',
        'sold' => $settings['store_sales'] ?? '128.4K',
        'store_response' => $settings['store_response'] ?? '93%',
        'store_shipping' => $settings['store_shipping'] ?? '95%',
        'primary_color' => $settings['primary_color'] ?? 'hsl(347, 100%, 62%)',
        'secondary_color' => $settings['secondary_color'] ?? 'hsl(4, 100%, 63%)',
        'button_color' => $settings['button_color'] ?? 'hsl(211, 90%, 56%)',
        'highlight_color' => $settings['highlight_color'] ?? '#10b981',
        'pix_discount_text' => $settings['pix_discount'] ?? '',
        'categories' => $categories_list,
        'store_products' => $lightweight_prods
    ];

    $config_fallback = array_merge($base_config, [
        'product_name' => '',
        'product_subtitle' => '',
        'original_price' => '',
        'discounted_price' => '',
        'shipping_discount' => '17.00',
        'tiktok_discount' => '2.40',
        'cashback' => '45',
        'rating' => '4.9',
        'product_image' => '',
        'carousel_images' => []
    ]);

    if (!empty($products)) {
        $config_fallback = array_merge($config_fallback, $products[0]);
        $config_fallback['product_name'] = $products[0]['title'] ?? '';
        $config_fallback['original_price'] = $products[0]['discount'] ?? '';
        $config_fallback['discounted_price'] = $products[0]['price'] ?? '';
        $config_fallback['product_image'] = $products[0]['image'] ?? '';
        $config_fallback['carousel_images'] = $products[0]['images'] ?? [];
    }

    return $config_fallback;
}

function render_dynamic_product_page($pdo, $product_or_id) {
    $baseDir = dirname(__DIR__);
    $index_orig = $baseDir . '/index.original.html';
    if (!file_exists($index_orig)) {
        $index_orig = $baseDir . '/index.html';
    }

    $settings = get_db_settings($pdo);
    $products = get_db_products($pdo);
    $base_config = get_shop_config($pdo);

    $prod = null;
    if (is_array($product_or_id)) {
        $prod = normalize_product_data($product_or_id);
    } else {
        $id = (int)$product_or_id;
        foreach ($products as $p) {
            if ($p['id'] == $id) {
                $prod = $p;
                break;
            }
        }
    }

    if (!$prod && !empty($products)) {
        $prod = $products[0];
    }

    if (!$prod || !file_exists($index_orig)) {
        if (file_exists($baseDir . '/loja.html')) {
            return file_get_contents($baseDir . '/loja.html');
        }
        return "<h1>Produto não encontrado</h1>";
    }

    $html = file_get_contents($index_orig);
    $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);
    
    $html = str_replace('<link rel="stylesheet" href="/assets/styles-PfJ5foin.css" data-precedence="default"/>', '', $html);
    $html = preg_replace('/<link rel="modulepreload" href="\/assets\/[^"]+">/i', '', $html);
    $html = preg_replace('/<script type="module" src="\/assets\/[^"]+"><\/script>/i', '', $html);

    if (strpos($html, 'cdn.tailwindcss.com') === false) {
        $html = str_replace('<head>', '<head><script src="https://cdn.tailwindcss.com"></script>', $html);
    }
    
    $html = str_replace('Flora Decora', $settings['store_name'] ?? 'Flora Decora', $html);
    $html = str_replace('Desconto de 41% no Pix e aprovação imediata', $settings['pix_discount'] ?? '', $html);
    $html = str_replace('aGásto', 'agosto', $html);
    $html = str_replace('APeças', 'Apenas', $html);
    $html = str_replace('fonts.Gástatic.com', 'fonts.gstatic.com', $html);

    $html = preg_replace('/<div class="mt-2\.5 inline-flex items-center gap-1\.5 text-\[12\.5px\] font-bold text-red-600 bg-red-50 px-2\.5 py-1 rounded-full ring-1 ring-red-100">.*?<\/style>/s', '', $html);
    $html = preg_replace('/<div class="mt-2\.5 inline-flex items-center gap-1\.5 text-\[12\.5px\] font-bold text-red-600 bg-red-50 px-2\.5 py-1 rounded-full ring-1 ring-red-100">.*?<\/div>/s', '', $html);
    $html = preg_replace('/<div class="text-xs font-semibold mt-2">Detalhes<\/div><div class="mt-2 grid grid-cols-2 gap-y-2 gap-x-6 text-\[13px\]">.*?<\/div><\/div>/s', '', $html);
    $html = preg_replace('/<p class="whitespace-pre-wrap">Dê um toque de sofisticação.*?<\/p>/s', '', $html);
    $html = preg_replace('/<p class="mt-3">Transforme sua cozinha.*?<\/p>/s', '', $html);
    $html = preg_replace('/<div class="mt-3 flex flex-col gap-2">\s*<img[^>]*product-desc-1.*?<\/div>/s', '', $html);
    $html = preg_replace('/<img[^>]*product-desc-[12][^>]*>/i', '', $html);

    // Remove loading overlay if present
    $html = preg_replace('/<div class="fixed inset-0 z-\[9999\].*?<\/div>/s', '', $html);

    // Keep search placeholder identical to TikTok Shop screenshot
    $html = str_replace('Jogo De Panelas Antiaderente Cerâmica', htmlspecialchars($prod['title'] ?? ''), $html);
    $html = str_replace('teste Mimo/Colinox Style 10 Peças', htmlspecialchars($prod['title'] ?? ''), $html);

    $prod_price_clean = str_replace('R$', '', $prod['price'] ?? '');
    $prod_disc_clean = str_replace('R$', '', $prod['discount'] ?? '');
    $html = str_replace('R$ 147,00', htmlspecialchars($prod['price'] ?? ''), $html);
    $html = str_replace('R$ 468,90', htmlspecialchars($prod['discount'] ?? ''), $html);
    $html = str_replace('61,74', trim(htmlspecialchars($prod_price_clean)), $html);
    $html = str_replace('289,00', trim(htmlspecialchars($prod_disc_clean)), $html);

    $prod_imgs = !empty($prod['images']) && is_array($prod['images']) ? $prod['images'] : (!empty($prod['image']) ? [$prod['image']] : []);
    if (!empty($prod_imgs)) {
        $carousel_slides = [];
        foreach ($prod_imgs as $idx => $img_url) {
            $eager = ($idx === 0) ? 'loading="eager" decoding="sync" fetchPriority="high"' : 'loading="lazy" decoding="async" fetchPriority="low"';
            $carousel_slides[] = '<div class="w-full flex-[0_0_100%] shrink-0 min-w-full [scroll-snap-align:center]" style="flex: 0 0 100%; width: 100%; min-width: 100%; scroll-snap-align: center;"><div class="aspect-square bg-white flex items-center justify-center overflow-hidden" style="aspect-ratio: 1/1; background-color: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden;"><img src="' . htmlspecialchars($img_url) . '" alt="' . htmlspecialchars($prod['title'] ?? '') . '" ' . $eager . ' width="480" height="480" class="w-full h-full object-contain" style="width: 100%; height: 100%; object-fit: contain;"/></div></div>';
        }
        $slides_html = '<div id="product-carousel-slider" class="flex flex-row flex-nowrap w-full overflow-x-auto overflow-y-hidden scroll-smooth [scroll-snap-type:x_mandatory] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" style="display: flex; flex-direction: row; flex-wrap: nowrap; width: 100%; overflow-x: auto; overflow-y: hidden; scroll-snap-type: x mandatory;">' . implode('', $carousel_slides) . '</div>';
        
        $indicator_html = '<div class="absolute bottom-2 right-2 text-[11px] px-2 py-1 rounded-full bg-black/60 text-white" style="z-index:30;"><span id="carousel-indicator">1/' . count($prod_imgs) . '</span></div>';

        $full_carousel_section = '<section class="relative" style="position:relative; width:100%; overflow:hidden;">' . $slides_html . $indicator_html . '</section>';
        $html = preg_replace('/<section\b[^>]*\bclass="[^"]*relative[^"]*"[^>]*>.*?<\/section>/s', $full_carousel_section, $html, 1);
    }

    $disp_rev_count = count($prod['reviews'] ?? []);
    $disp_tot_reviews = !empty($prod['total_reviews']) ? $prod['total_reviews'] : $disp_rev_count;
    if (is_numeric($disp_tot_reviews) && (int)$disp_tot_reviews >= 1000) {
        $disp_tot_reviews = number_format(((int)$disp_tot_reviews) / 1000, 1, ',', '.') . ' mil';
    }

    $disp_sold = !empty($prod['sold']) ? $prod['sold'] : (!empty($prod['sales_quantity']) ? $prod['sales_quantity'] : '12.4K');
    if (is_numeric($disp_sold) && (int)$disp_sold >= 1000) {
        $disp_sold = number_format(((int)$disp_sold) / 1000, 1, '.', '') . 'K';
    }

    $html = str_replace('14.9 mil', $disp_tot_reviews, $html);
    $html = str_replace('14,9 mil', $disp_tot_reviews, $html);
    $html = str_replace('2,8 mil', $disp_tot_reviews, $html);
    $html = str_replace('2.8 mil', $disp_tot_reviews, $html);
    $html = str_replace('14.4K', $disp_sold, $html);
    $html = str_replace('14,4K', $disp_sold, $html);

    $safe_html = preg_replace('/>[^><]{0,50}PROMO/i', '>&#9889; PROMO', $html);
    if ($safe_html !== null) $html = $safe_html;
    
    $promo_raw = $prod['price'] ?? $prod['discounted_price'] ?? '61,74';
    $orig_raw = $prod['discount'] ?? $prod['discount_price'] ?? $prod['original_price'] ?? '289,00';

    $promo_price_float = parse_price_to_float_helper($promo_raw);
    $orig_price_float = parse_price_to_float_helper($orig_raw);

    if ($promo_price_float <= 0) $promo_price_float = 61.74;
    if ($orig_price_float <= 0 || $orig_price_float <= $promo_price_float) {
        $orig_price_float = round($promo_price_float * 2.5, 2);
    }

    $promo_formatted = number_format($promo_price_float, 2, ',', '.');
    $orig_formatted = number_format($orig_price_float, 2, ',', '.');

    $discount_pct = round((($orig_price_float - $promo_price_float) / $orig_price_float) * 100);
    $discount_percent_formatted = '-' . $discount_pct . '%';
    $savings = $orig_price_float - $promo_price_float;
    $savings_formatted = 'R$ ' . number_format($savings, 2, ',', '.');

    $raw_title = !empty($prod['bottom_bar_text']) ? $prod['bottom_bar_text'] : (!empty($prod['flash_sale_text']) ? $prod['flash_sale_text'] : ($settings['scarcity_timer_title'] ?? 'Oferta Relâmpago'));
    $clean_title = trim(str_replace(['⚡', '⏱️'], '', $raw_title));
    if (empty($clean_title)) $clean_title = 'Oferta Relâmpago';
    $scarcity_title = htmlspecialchars($clean_title);

    // Substituição da Seção Laranja (Flash Sale / Oferta Relâmpago)
    $orange_section_html = '
<section style="background:#ff4500; padding:10px 12px; color:#fff; font-family:Inter,sans-serif;">
    <div style="display:flex; align-items:center; justify-content:space-between; width:100%; font-family:Inter,sans-serif;">
        <div style="min-width:0; flex:1;">
            <div style="display:flex; align-items:center; gap:5px; white-space:nowrap;">
                <span style="background:#fff; color:#e11d48; border-radius:12px; padding:2px 7px; font-weight:800; font-size:12px; flex-shrink:0;">' . $discount_percent_formatted . '</span>
                <span style="font-size:13px; font-weight:500; opacity:0.95; white-space:nowrap; display:inline-flex; align-items:baseline; gap:3px;">R$ <strong style="font-size:22px; font-weight:800; line-height:1;">' . $promo_formatted . '</strong></span>
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-left:1px;"><path d="M15 5l0 2"/><path d="M15 11l0 2"/><path d="M15 17l0 2"/><path d="M5 5h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-3a2 2 0 0 0 0 -4v-3a2 2 0 0 1 2 -2"/><path d="M9 12l2 2l4 -4"/></svg>
            </div>
            <div style="font-size:12px; opacity:0.75; text-decoration:line-through; margin-top:2px; white-space:nowrap;">R$ ' . $orig_formatted . '</div>
        </div>
        <div style="text-align:right; flex-shrink:0; margin-left:8px;">
            <div style="display:inline-flex; align-items:center; gap:3px; font-weight:800; font-size:13.5px; white-space:nowrap;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="#fff" stroke="none"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                <span>' . $scarcity_title . '</span>
            </div>
            <div class="flash-sale-timer-display" style="font-size:11.5px; opacity:0.95; margin-top:2px; white-space:nowrap;">Termina em --:--</div>
        </div>
    </div>
</section>';

    $html = preg_replace('/<section style="background:#ff4500;.*?<\/section>/s', $orange_section_html, $html);

    // Cálculo de parcelamento em 3x para exibição no template
    $installment_value = $promo_price_float / 3;
    $installment_formatted = number_format($installment_value, 2, ',', '.');
    $html = str_replace('<span id="pann-inst-val">--</span>', '<span id="pann-inst-val">' . $installment_formatted . '</span>', $html);

    $prod_variations_raw = $prod['variations'] ?? null;
    $parsed_variations = null;

    if (!empty($prod_variations_raw)) {
        $parsed_variations = [
            'options' => [],
            'sizes' => [],
            'voltages' => []
        ];
        if (is_string($prod_variations_raw)) {
            $decoded = json_decode($prod_variations_raw, true);
            if (is_array($decoded)) {
                $parsed_variations = array_merge($parsed_variations, $decoded);
            } else {
                $parts = explode(',', $prod_variations_raw);
                $parsed_variations['sizes'] = array_map('trim', $parts);
            }
        } elseif (is_array($prod_variations_raw)) {
            $parsed_variations = array_merge($parsed_variations, $prod_variations_raw);
        }
    }

    $prod_timer_mins = !empty($prod['flash_sale_timer']) ? (int)$prod['flash_sale_timer'] : (int)($settings['scarcity_timer_minutes'] ?? 10);

    $product_config_array = array_merge($base_config, $prod, [
        'product_name' => $prod['title'] ?? '',
        'product_image' => $prod['image'] ?? '',
        'carousel_images' => $prod['images'] ?? [],
        'original_price' => $orig_formatted,
        'discounted_price' => $promo_formatted,
        'discount' => $orig_formatted,
        'price' => $promo_formatted,
        'discount_percent' => $discount_percent_formatted,
        'discount_pct' => $discount_pct,
        'savings_formatted' => $savings_formatted,
        'scarcity_timer_minutes' => $prod_timer_mins,
        'variations' => $parsed_variations,
        'checkout_url' => $prod['checkout_url'] ?? $base_config['checkout_url'] ?? '',
        'store_products' => get_lightweight_products_list($products)
    ]);
    
    $product_config = json_encode($product_config_array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    
    $html = str_replace('<script src="data/config.js"></script>', '', $html);
    $html = preg_replace('/<script>window\.ShopConfig = .*?<\/script>/s', '', $html);
    
    $html = preg_replace('/<div class="mt-3 flex flex-col gap-2"><img src="\/assets\/product-desc-1[^>]+><img src="\/assets\/product-desc-2[^>]+><\/div>/i', '', $html);
    $html = preg_replace('/<img[^>]*product-desc-[12][^>]*>/i', '', $html);
    
    // 1. Card da Loja (Acima do "Sobre este produto" e abaixo das Avaliações dos clientes)
    $st_name = htmlspecialchars($base_config['store_name'] ?? 'Minha Loja');
    $st_logo = htmlspecialchars($base_config['store_logo'] ?? '/assets/bighome-logo-BXgKvfO0.png');
    $st_sales = htmlspecialchars($base_config['store_sales'] ?? '128.4K');
    $st_response = htmlspecialchars($base_config['store_response'] ?? '93%');
    $st_shipping = htmlspecialchars($base_config['store_shipping'] ?? '95%');

    $store_card_html = '
    <div id="custom-store-profile" style="background:#fff; padding:12px 14px; margin-top:8px; border-top:8px solid #f4f4f5; font-family:Inter,sans-serif;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div style="display:flex; align-items:center; gap:10px; min-width:0; cursor:pointer;" onclick="window.location.href=\'/loja.html\';">
                <div style="width:44px; height:44px; border-radius:50%; border:1px solid #e5e7eb; overflow:hidden; flex-shrink:0; background:#fff;">
                    <img src="' . $st_logo . '" style="width:100%; height:100%; object-fit:cover;" alt="Logo">
                </div>
                <div style="min-width:0;">
                    <div style="font-weight:700; font-size:14.5px; color:#111; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' . $st_name . '</div>
                    <div style="font-size:11.5px; color:#6b7280; margin-top:2px;">' . $st_sales . ' vendido(s)</div>
                </div>
            </div>
            <button onclick="window.location.href=\'/loja.html\';" style="background:#fe2c55; color:#fff; font-weight:700; font-size:13px; padding:7px 20px; border-radius:999px; border:none; cursor:pointer; flex-shrink:0; outline:none;">Visitar</button>
        </div>
        <div style="display:flex; gap:16px; font-size:11.5px; color:#6b7280; margin-top:10px; padding-top:10px; border-top:1px solid #f3f4f6;">
            <div><strong style="color:#111;">' . $st_response . '</strong> responde em 24h</div>
            <div><strong style="color:#111;">' . $st_shipping . '</strong> envios no prazo</div>
        </div>
    </div>';

    // 2. Descrição do Produto ("Sobre este produto")
    $desc_text = trim($prod['description'] ?? '');
    $features_list = $prod['features'] ?? [];
    $packing_list = $prod['packing_list'] ?? [];

    // Extrai "Especificações Técnicas" da descrição caso venham juntas no texto da descrição
    $extracted_specs = [];
    $clean_desc_text = $desc_text;

    if (preg_match('/(Especifica[çc][oõ]es\s*T[eé]cnicas?:?|Especifica[çc][oõ]es?:?)/i', $desc_text, $matches, PREG_OFFSET_CAPTURE)) {
        $offset = $matches[0][1];
        $clean_desc_text = trim(substr($desc_text, 0, $offset));
        $specs_block = trim(substr($desc_text, $offset + strlen($matches[0][0])));

        $lines = preg_split('/\r\n|\r|\n/', $specs_block);
        foreach ($lines as $line) {
            $l = trim($line);
            if (!empty($l)) {
                $extracted_specs[] = $l;
            }
        }
    }

    $all_specs = array_merge((array)$features_list, $extracted_specs);

    // Limpa quebras de linha excessivas (no máximo 2 quebras de linha entre parágrafos)
    $clean_desc_text = preg_replace('/(\r\n|\n|\r){3,}/', "\n\n", $clean_desc_text);
    $clean_desc_text = trim($clean_desc_text);

    $desc_html = '<div id="dyn-product-description-wrapper" style="background:#fff; font-family:Inter,sans-serif; margin-top:8px; padding:14px; border-top:8px solid #f4f4f5;">';
    $desc_html .= '<h2 style="font-size:15px; font-weight:700; color:#111; margin:0 0 10px 0;">Sobre este produto</h2>';

    // Texto da Descrição (Primeiro)
    if (!empty($clean_desc_text)) {
        $desc_html .= '<div style="font-size:13.5px; line-height:1.5; color:#374151; white-space:pre-line; word-break:break-word; margin-bottom:12px;">' . htmlspecialchars($clean_desc_text) . '</div>';
    } else if (empty($all_specs) && empty($packing_list)) {
        $desc_html .= '<div style="font-size:13.5px; line-height:1.5; color:#374151; margin-bottom:12px;">Produto de excelente qualidade, com envio rápido e garantia de satisfação.</div>';
    }

    // Especificações Técnicas Organizadas (Abaixo da Descrição)
    if (!empty($all_specs)) {
        $desc_html .= '<div style="margin-top:12px; margin-bottom:12px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.02); font-family:Inter,sans-serif;">';
        $desc_html .= '<div style="background:#f8fafc; padding:11px 16px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:8px;">';
        $desc_html .= '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fe2c55" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>';
        $desc_html .= '<span style="font-weight:700; color:#0f172a; font-size:13.5px;">Especificações Técnicas</span>';
        $desc_html .= '</div>';
        $desc_html .= '<div style="display:flex; flex-direction:column;">';

        $spec_idx = 0;
        $total_specs = count($all_specs);
        foreach ($all_specs as $spec_item) {
            $spec_item = trim((string)$spec_item);
            if (empty($spec_item)) continue;
            $spec_item = ltrim($spec_item, "-•* ");
            if (empty($spec_item)) continue;

            $bg_color = ($spec_idx % 2 === 0) ? '#ffffff' : '#f8fafc';
            $border_style = ($spec_idx === $total_specs - 1) ? 'none' : '1px solid #f1f5f9';

            if (strpos($spec_item, ':') !== false) {
                $parts = explode(':', $spec_item, 2);
                $s_key = trim($parts[0]);
                $s_val = trim($parts[1]);

                $desc_html .= '<div style="display:flex; align-items:flex-start; justify-content:space-between; padding:10px 16px; background:' . $bg_color . '; border-bottom:' . $border_style . '; gap:16px; box-sizing:border-box;">';
                $desc_html .= '<span style="color:#64748b; font-size:12.5px; font-weight:500; width:44%; flex-shrink:0; line-height:1.4;">' . htmlspecialchars($s_key) . '</span>';
                $desc_html .= '<span style="color:#0f172a; font-size:13px; font-weight:600; text-align:right; flex:1; line-height:1.4; word-break:break-word;">' . htmlspecialchars($s_val) . '</span>';
                $desc_html .= '</div>';
            } else {
                $desc_html .= '<div style="display:flex; align-items:center; gap:8px; padding:10px 16px; background:' . $bg_color . '; border-bottom:' . $border_style . '; color:#374151; font-size:13px; box-sizing:border-box;">';
                $desc_html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                $desc_html .= '<span style="font-weight:500; color:#0f172a; line-height:1.4;">' . htmlspecialchars($spec_item) . '</span>';
                $desc_html .= '</div>';
            }
            $spec_idx++;
        }
        $desc_html .= '</div></div>';
    }

    // Conteúdo da Embalagem (Terceiro)
    if (!empty($packing_list)) {
        $desc_html .= '<div style="margin-top:12px; padding-top:10px; border-top:1px solid #f3f4f6; font-size:13px; color:#4b5563;">';
        $desc_html .= '<strong style="color:#111;">Conteúdo da Embalagem:</strong>';
        $desc_html .= '<ul style="margin:4px 0 0 0; padding-left:18px; line-height:1.5;">';
        foreach ($packing_list as $pack_item) {
            $desc_html .= '<li>' . htmlspecialchars($pack_item) . '</li>';
        }
        $desc_html .= '</ul></div>';
    }
    $desc_html .= '</div>';

    // 0. Seção Dinâmica de Avaliações dos Clientes (respeita quantidade exata do admin)
    $prod_reviews = $prod['reviews'] ?? $prod['reviews_json'] ?? [];
    if (!is_array($prod_reviews)) {
        $prod_reviews = [];
    }

    $rev_count_display = count($prod_reviews);
    if (!empty($prod['total_reviews'])) {
        $rev_count_display = $prod['total_reviews'];
    }

    $main_page_reviews = array_slice($prod_reviews, 0, 5);

    $diverse_avatars = [
        'https://randomuser.me/api/portraits/women/44.jpg',
        'https://randomuser.me/api/portraits/women/68.jpg',
        'https://randomuser.me/api/portraits/women/32.jpg',
        'https://randomuser.me/api/portraits/women/12.jpg',
        'https://randomuser.me/api/portraits/women/65.jpg',
        'https://randomuser.me/api/portraits/women/29.jpg',
        'https://randomuser.me/api/portraits/women/47.jpg',
        'https://randomuser.me/api/portraits/women/90.jpg',
        'https://randomuser.me/api/portraits/women/24.jpg',
        'https://randomuser.me/api/portraits/women/54.jpg',
        'https://randomuser.me/api/portraits/men/32.jpg',
        'https://randomuser.me/api/portraits/men/46.jpg',
        'https://randomuser.me/api/portraits/men/75.jpg',
        'https://randomuser.me/api/portraits/men/22.jpg',
        'https://randomuser.me/api/portraits/men/85.jpg',
        'https://randomuser.me/api/portraits/women/63.jpg',
        'https://randomuser.me/api/portraits/women/35.jpg',
        'https://randomuser.me/api/portraits/women/89.jpg',
        'https://randomuser.me/api/portraits/women/79.jpg',
        'https://randomuser.me/api/portraits/men/64.jpg'
    ];

    $diverse_names = [
        'Isabela Freitas',
        'Camila Santos',
        'Fernanda Machado',
        'Juliana Costa',
        'Patrícia Lima',
        'Ana Clara Dias',
        'Beatriz Castro',
        'Gabriela Pereira',
        'Amanda Rocha',
        'Carolina Vieira',
        'Mariana Souza',
        'Lucas Mendes',
        'Rodrigo Alves',
        'Vanessa Oliveira',
        'Larissa Ramos',
        'Matheus Fernandes',
        'Renata Barbosa',
        'Tainá Ribeiro',
        'Priscila Duarte',
        'Thiago Martins'
    ];

    $reviews_section_articles_html = '';
    foreach ($main_page_reviews as $idx => $rev_item) {
        $rawName = trim($rev_item['name'] ?? $rev_item['n'] ?? '');
        $rawAvatar = trim($rev_item['avatar'] ?? '');

        $is_default_avatar = empty($rawAvatar) || strpos($rawAvatar, 'c1408d9abdf3d3a34c5c09f27f32016a') !== false || strpos($rawAvatar, 'tos-maliva-avt') !== false;
        $is_default_name = empty($rawName) || $rawName === 'Cliente' || $rawName === 'Cliente ML';

        $rName = htmlspecialchars($is_default_name ? $diverse_names[$idx % count($diverse_names)] : $rawName);
        $rAvatar = htmlspecialchars($is_default_avatar ? $diverse_avatars[$idx % count($diverse_avatars)] : $rawAvatar);
        $rText = htmlspecialchars($rev_item['text'] ?? $rev_item['t'] ?? '');
        $rDate = htmlspecialchars($rev_item['d'] ?? 'Compra confirmada');
        $rRating = (int)($rev_item['rating'] ?? 5);

        $stars_svgs = '';
        for ($s = 0; $s < 5; $s++) {
            $opacity = $s < $rRating ? '1' : '0.3';
            $stars_svgs .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="opacity:' . $opacity . '"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
        }

        $reviews_section_articles_html .= '<article class="mt-4"><div class="flex items-center gap-2 flex-wrap"><img src="' . $rAvatar . '" alt="' . $rName . '" width="32" height="32" class="w-8 h-8 rounded-full object-cover bg-[color:var(--pann-muted)] shrink-0" loading="lazy" decoding="async"/><div class="text-sm font-medium">' . $rName . '</div><span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-[1px] rounded-full text-[color:var(--pann-success)] border" style="border-color:hsla(158,64%,42%,0.3);background:hsla(158,64%,42%,0.05)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' . $rDate . '</span></div><div class="mt-1.5"><div class="flex items-center gap-[2px] text-[color:var(--pann-warning)]">' . $stars_svgs . '</div></div><p class="mt-2 text-[13px] leading-[1.5]">' . $rText . '</p></article>';
    }

    $reviews_section_articles_html .= '
    <div style="margin-top:14px;">
        <button onclick="if(window.openReviewsModal) window.openReviewsModal();" style="width:100%; padding:10px; background:#fff; border:1px solid #e2e8f0; border-radius:999px; color:#334155; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:4px; outline:none; box-shadow:0 1px 2px rgba(0,0,0,0.03);">
            <span>Ver mais avaliações</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
    </div>';

    $reviews_section_full_html = '<section class="p-3"><div class="flex items-center justify-between"><h2 class="text-sm font-semibold">Avaliações dos clientes <span class="text-[color:var(--pann-muted-fg)] font-normal">(' . htmlspecialchars($rev_count_display) . ')</span></h2><button class="inline-flex items-center gap-1 text-xs text-[color:var(--pann-muted-fg)]" onclick="if(window.openReviewsModal) window.openReviewsModal();">Ver mais <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button></div><div class="mt-1 flex items-center gap-2 text-[13px]"><span class="font-semibold">4.9</span><span class="text-[color:var(--pann-muted-fg)]">/ 5</span><div class="ml-1"><div class="flex items-center gap-[2px] text-[color:var(--pann-warning)]"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div></div></div>' . $reviews_section_articles_html . '</section>';

    // Insere na ordem exata: Avaliações dos Clientes -> Card da Loja -> Sobre este Produto
    $combined_sections = $store_card_html . $desc_html;

    $body_pos = strpos($html, '<body');
    $search_start = ($body_pos !== false) ? $body_pos : 0;

    if (($rev_pos = strpos($html, 'Avalia', $search_start)) !== false) {
        $sec_start = strrpos(substr($html, 0, $rev_pos), '<section');
        $sec_end = strpos($html, '</section>', $rev_pos);
        if ($sec_start !== false && $sec_end !== false) {
            $html = substr($html, 0, $sec_start) . $reviews_section_full_html . $combined_sections . substr($html, $sec_end + strlen('</section>'));
        }
    } else if (($of_pos = strpos($html, 'id="ofertas-section-wrapper"', $search_start)) !== false) {
        if (($end_of_pos = strpos($html, '</section></div>', $of_pos)) !== false) {
            $end_of_pos += strlen('</section></div>');
            $html = substr($html, 0, $end_of_pos) . $reviews_section_full_html . $combined_sections . substr($html, $end_of_pos);
        }
    }
    
    $css_injection = "
    <script src=\"https://cdn.tailwindcss.com\"></script>
    <style>
        :root {
            --pann-primary: {$base_config['primary_color']};
            --pann-secondary: {$base_config['secondary_color']};
            --pann-warning: #f59e0b;
            --pann-success: {$base_config['highlight_color']};
            --pann-fg: #111827;
            --pann-border: #e5e7eb;
            --pann-muted: #f3f4f6;
            --pann-muted-fg: #6b7280;
            --pann-success-light: #ecfdf5;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
            color: #111827;
            -webkit-tap-highlight-color: transparent;
        }
        .max-w-\\[480px\\] {
            max-width: 480px !important;
        }
        .mx-auto {
            margin-left: auto !important;
            margin-right: auto !important;
        }
        .aspect-square {
            aspect-ratio: 1 / 1 !important;
        }
        #product-carousel-slider {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            width: 100% !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            scroll-snap-type: x mandatory !important;
            scrollbar-width: none;
            -ms-overflow-style: none;
            -webkit-overflow-scrolling: touch;
        }
        #product-carousel-slider::-webkit-scrollbar {
            display: none !important;
        }
        #product-carousel-slider > div {
            flex: 0 0 100% !important;
            width: 100% !important;
            min-width: 100% !important;
            scroll-snap-align: center !important;
            box-sizing: border-box;
        }
        img { max-width: 100%; }
    </style>";
    
    $script_injection = "<script>window.ShopConfig = $product_config;</script>";
    if (file_exists(__DIR__ . '/product_details_override.html')) {
        $script_injection .= file_get_contents(__DIR__ . '/product_details_override.html');
    }
    
    $prod_title_json = json_encode($prod['title'] ?? 'Produto', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $prod_price_num = (float)str_replace(['R$', ' ', '.'], ['', '', ''], str_replace(',', '.', $prod['price'] ?? '0'));
    $prod_id = (string)($prod['id'] ?? '');

    if (!empty($settings['facebook_pixel_id'])) {
        $fb_id = htmlspecialchars($settings['facebook_pixel_id']);
        $script_injection .= "
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$fb_id}');
fbq('track', 'PageView');
fbq('track', 'ViewContent', {
    content_name: {$prod_title_json},
    content_ids: ['{$prod_id}'],
    content_type: 'product',
    value: {$prod_price_num},
    currency: 'BRL'
});
</script>
<noscript><img height=\"1\" width=\"1\" style=\"display:none\" src=\"https://www.facebook.com/tr?id={$fb_id}&ev=PageView&noscript=1\"/></noscript>
<!-- End Facebook Pixel Code -->";
    }

    $tt_id = !empty($settings['tiktok_pixel_id']) ? htmlspecialchars($settings['tiktok_pixel_id']) : 'D9QVVQ3C77U97D5QE3LG';
    $script_injection .= "
<!-- TikTok Pixel Code -->
<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=[\"page\",\"track\",\"identify\",\"instances\",\"debug\",\"on\",\"off\",\"once\",\"ready\",\"alias\",\"group\",\"enableCookie\",\"disableCookie\"],ttq.setAndVerify=function(t,e){for(var n=0;n<ttq.methods.length;n++)ttq[t][e[n]]=ttq[t][e[n]]||function(t){return function(){t.push([e[n]].concat(Array.prototype.slice.call(arguments,0)))}}(t)};for(var e=0;e<ttq.methods.length;e++)ttq.setAndVerify(ttq,ttq.methods[e]);ttq.instance=function(t){for(var e=ttq.methods,n=0;n<e.length;n++)ttq.setAndVerify(t,e[n]);return t},ttq.load=function(e,n){var i=\"https://analytics.tiktok.com/i18n/pixel/events.js\";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._t[e]._p=n||{},ttq._t[e]._i=ttq._i[e];var o=d.createElement(\"script\");o.type=\"text/javascript\",o.async=!0,o.src=i+\"?sdkid=\"+e+\"&lib=\"+t;var a=d.getElementsByTagName(\"script\")[0];a.parentNode.insertBefore(o,a)};
  ttq.load('{$tt_id}');
  ttq.page();
  ttq.track('ViewContent', {
      contents: [{
          content_id: '{$prod_id}',
          content_name: {$prod_title_json},
          quantity: 1,
          price: {$prod_price_num}
      }],
      content_name: {$prod_title_json},
      content_id: '{$prod_id}',
      content_type: 'product',
      value: {$prod_price_num},
      currency: 'BRL'
  });
}(window, document, 'ttq');
</script>
<!-- Live Visitor Tracking -->
<script>
(function() {
    let sid = '';
    try {
        sid = localStorage.getItem('_vsid');
        if (!sid) sid = sessionStorage.getItem('_vsid');
    } catch(e) {}
    if (!sid) {
        sid = 'v_' + Math.random().toString(36).substring(2, 10) + Date.now().toString(36);
    }
    try {
        localStorage.setItem('_vsid', sid);
        sessionStorage.setItem('_vsid', sid);
    } catch(e) {}

    const page = 'index';
    const baseUrl = '/api/track.php';

    function ping() {
        if (document.visibilityState === 'hidden') return;
        const url = baseUrl + '?session_id=' + encodeURIComponent(sid) + '&page=' + page + '&action=ping&t=' + Date.now();
        if (navigator.sendBeacon) {
            navigator.sendBeacon(url);
        } else {
            fetch(url, { method: 'GET', keepalive: true }).catch(function() {});
        }
    }

    function leave() {
        const url = baseUrl + '?session_id=' + encodeURIComponent(sid) + '&page=' + page + '&action=leave&t=' + Date.now();
        if (navigator.sendBeacon) {
            navigator.sendBeacon(url);
        } else {
            fetch(url, { method: 'GET', keepalive: true }).catch(function() {});
        }
    }

    ping();
    setInterval(ping, 5000);

    window.addEventListener('pagehide', leave);
    window.addEventListener('beforeunload', leave);
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') leave();
        else if (document.visibilityState === 'visible') ping();
    });
})();
</script>";

    // Build WhatsApp Open Graph Meta Tags for Product Link Previews
    $prod_name_clean = trim($prod['title'] ?? 'Produto Especial');
    $og_title = htmlspecialchars("🔥 OFERTA TIKTOK SHOP | " . $prod_name_clean);
    $og_price_display = htmlspecialchars($prod['price'] ?? '');
    $og_desc = htmlspecialchars("⚡ Desconto Exclusivo + Frete Grátis por R$ {$og_price_display} no PIX! Compre com segurança na TikTok Shop Oficial.");

    // Extract primary product image URL for WhatsApp card
    $raw_img = '';
    if (!empty($prod['image']) && is_string($prod['image'])) {
        $raw_img = trim($prod['image']);
    }
    if (empty($raw_img) && !empty($prod['images'])) {
        if (is_array($prod['images']) && !empty($prod['images'][0])) {
            $raw_img = trim($prod['images'][0]);
        } else if (is_string($prod['images'])) {
            $dec_imgs = json_decode($prod['images'], true);
            if (is_array($dec_imgs) && !empty($dec_imgs[0])) {
                $raw_img = trim($dec_imgs[0]);
            }
        }
    }

    if (!empty($raw_img)) {
        if (strpos($raw_img, 'http://') !== 0 && strpos($raw_img, 'https://') !== 0) {
            $host = $_SERVER['HTTP_HOST'] ?? 'ultimateshop.online';
            $raw_img = 'https://' . $host . '/' . ltrim($raw_img, '/');
        }
    } else {
        $raw_img = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'ultimateshop.online') . '/assets/bighome-logo-BXgKvfO0.png';
    }
    $og_image = htmlspecialchars($raw_img);
    $page_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'ultimateshop.online') . '/produto-' . $prod['id'] . '.html';

    $og_meta_tags = "
<!-- TikTok Favicon / Browser Icon -->
<link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"/favicon.png?v=3\">
<link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"/favicon.png?v=3\">
<link rel=\"shortcut icon\" href=\"/favicon.ico?v=3\">
<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/apple-touch-icon.png?v=3\">
<!-- WhatsApp / Social Media Link Preview Meta Tags -->
<title>{$og_title}</title>
<meta name=\"description\" content=\"{$og_desc}\">
<meta property=\"og:type\" content=\"product\">
<meta property=\"og:site_name\" content=\"TikTok Shop\">
<meta property=\"og:title\" content=\"{$og_title}\">
<meta property=\"og:description\" content=\"{$og_desc}\">
<meta property=\"og:image\" content=\"{$og_image}\">
<meta property=\"og:image:secure_url\" content=\"{$og_image}\">
<meta property=\"og:image:type\" content=\"image/jpeg\">
<meta property=\"og:image:width\" content=\"600\">
<meta property=\"og:image:height\" content=\"600\">
<meta property=\"og:url\" content=\"{$page_url}\">
<meta name=\"twitter:card\" content=\"summary_large_image\">
<meta name=\"twitter:title\" content=\"{$og_title}\">
<meta name=\"twitter:description\" content=\"{$og_desc}\">
<meta name=\"twitter:image\" content=\"{$og_image}\">
";

    // Clean old title and og meta tags to prevent duplicates
    $html = preg_replace('/<title>.*?<\/title>/i', '', $html);
    $html = preg_replace('/<meta\s+property=["\']og:[^"\']+["\'][^>]*>/i', '', $html);
    $html = preg_replace('/<meta\s+name=["\']twitter:[^"\']+["\'][^>]*>/i', '', $html);

    $html = str_replace('</head>', $og_meta_tags . $css_injection . $script_injection . '</head>', $html);
    return $html;
}

// Function called by admin actions.
// Compiles database products into static config and product pages for instant store loading.
function compile_static_site($pdo) {
    // Na Vercel o disco e somente leitura: config.js, products.json e as paginas de
    // produto sao servidos dinamicamente pelo roteador (api/index.php) a partir do Supabase.
    return true;
    try {
        @ini_set('memory_limit', '512M');
        $baseDir = dirname(__DIR__);
        if (!is_dir($baseDir . '/data')) {
            @mkdir($baseDir . '/data', 0755, true);
        }

        // 1. Fetch full store config & products from DB
        $shop_config = get_shop_config($pdo);

        // 2. Write data/config.js
        $config_js_content = "window.ShopConfig = " . json_encode($shop_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";";
        file_put_contents($baseDir . '/data/config.js', $config_js_content);
        unset($config_js_content);

        // 3. Write data/products.json
        file_put_contents($baseDir . '/data/products.json', json_encode($shop_config['store_products'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // 4. Generate static product pages for all products (up to 100)
        if (!empty($shop_config['store_products'])) {
            $prodsToCompile = array_slice($shop_config['store_products'], 0, 100);
            foreach ($prodsToCompile as $prod) {
                $p_id = $prod['id'];
                $prod_html = render_dynamic_product_page($pdo, $prod);
                file_put_contents($baseDir . "/produto-{$p_id}.html", $prod_html);
                file_put_contents($baseDir . "/p_{$p_id}.html", $prod_html);
                unset($prod_html);
            }

            // Update index.html to be the main store profile (loja.html)
            if (file_exists($baseDir . '/loja.html')) {
                copy($baseDir . '/loja.html', $baseDir . '/index.html');
            }
        }

        unset($shop_config);
        if (function_exists('gc_collect_cycles')) { @gc_collect_cycles(); }
        if (function_exists('opcache_reset')) { @opcache_reset(); }
        return true;
    } catch (Exception $e) {
        error_log("compile_static_site error: " . $e->getMessage());
        return false;
    }
}
?>
