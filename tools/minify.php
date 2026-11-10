<?php
/**
 * CSS / JS küçültücü — kaynak dosyaların yanına .min.css / .min.js üretir.
 *   php tools/minify.php
 *
 * Canlıda (config: debug => false) View::asset() ve shared_asset() otomatik olarak .min sürümü kullanır.
 * Bağımlılıksızdır ve "güvenli" küçültme yapar: yorumları ve gereksiz boşlukları atar, kodu yeniden yazmaz.
 * JS'de satır sonları korunur (otomatik noktalı virgül kuralları bozulmasın).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$dirs = [
    'assets/css', 'assets/js',
    'themes/default/assets/css', 'themes/default/assets/js',
    'themes/admin/assets/css', 'themes/admin/assets/js',
];

/** CSS: yorumlar, boşluklar, { } ; , > çevresindeki boşluklar ve son ; atılır (metin içleri korunur) */
function minify_css(string $css): string
{
    $out = '';
    $len = strlen($css);
    for ($i = 0; $i < $len; $i++) {
        $c = $css[$i];
        if ($c === '"' || $c === "'") {                       // metin: olduğu gibi
            $j = $i + 1;
            while ($j < $len && $css[$j] !== $c) {
                $j += $css[$j] === '\\' ? 2 : 1;
            }
            $out .= substr($css, $i, $j - $i + 1);
            $i = $j;
        } elseif ($c === '/' && ($css[$i + 1] ?? '') === '*') { // yorum
            $end = strpos($css, '*/', $i + 2);
            $i = $end === false ? $len : $end + 1;
            $out .= ' ';
        } elseif (ctype_space($c)) {
            $out .= ' ';
            while ($i + 1 < $len && ctype_space($css[$i + 1])) {
                $i++;
            }
        } else {
            $out .= $c;
        }
    }
    // Metinler dışında kalan noktalama çevresi (metinleri geçici olarak sakla)
    $strings = [];
    $out = preg_replace_callback('/"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'/', static function ($m) use (&$strings) {
        $strings[] = $m[0];
        return "\x00" . (count($strings) - 1) . "\x00";
    }, $out);
    $out = preg_replace('/\s*([{};,>])\s*/', '$1', $out);
    $out = preg_replace('/;}/', '}', $out);
    $out = preg_replace_callback("/\x00(\d+)\x00/", static fn ($m) => $strings[(int) $m[1]], $out);
    return trim($out) . "\n";
}

/** JS: yorumlar atılır, satır başı/sonu boşlukları ve boş satırlar temizlenir; metin, şablon ve regex içleri korunur */
function minify_js(string $js): string
{
    $out = '';
    $len = strlen($js);
    $last = '';                                                // son anlamlı karakter (regex mi bölme mi kararı için)
    $lastWord = '';
    for ($i = 0; $i < $len; $i++) {
        $c = $js[$i];
        $n = $js[$i + 1] ?? '';
        if ($c === '"' || $c === "'" || $c === '`') {          // metin / şablon
            $j = $i + 1;
            while ($j < $len && $js[$j] !== $c) {
                $j += $js[$j] === '\\' ? 2 : 1;
            }
            $out .= substr($js, $i, $j - $i + 1);
            $i = $j;
            $last = $c; $lastWord = '';
        } elseif ($c === '/' && $n === '/') {                  // satır yorumu
            $end = strpos($js, "\n", $i);
            $i = ($end === false ? $len : $end) - 1;
        } elseif ($c === '/' && $n === '*') {                  // blok yorumu
            $end = strpos($js, '*/', $i + 2);
            $i = $end === false ? $len : $end + 1;
            $out .= ' ';
        } elseif ($c === '/' && ($last === '' || strpbrk($last, '(,=:[!&|?{};+-*%~^<>') !== false
                  || in_array($lastWord, ['return', 'typeof', 'case', 'do', 'else', 'in', 'of', 'void', 'delete', 'throw', 'new'], true))) {
            $j = $i + 1; $class = false;                       // düzenli ifade (regex)
            while ($j < $len) {
                $d = $js[$j];
                if ($d === '\\') { $j += 2; continue; }
                if ($d === '[') $class = true;
                elseif ($d === ']') $class = false;
                elseif ($d === '/' && !$class) break;
                elseif ($d === "\n") break;
                $j++;
            }
            $j++;
            while ($j < $len && ctype_alpha($js[$j])) $j++;    // bayraklar (g, i, m...)
            $out .= substr($js, $i, $j - $i);
            $i = $j - 1;
            $last = '/'; $lastWord = '';
        } elseif (ctype_space($c)) {
            $nl = false;
            while ($i < $len && ctype_space($js[$i])) {
                $nl = $nl || $js[$i] === "\n";
                $i++;
            }
            $i--;
            $out .= $nl ? "\n" : ' ';
        } else {
            $out .= $c;
            $last = $c;
            $lastWord = (ctype_alnum($c) || $c === '_' || $c === '$')
                ? (preg_match('/[A-Za-z0-9_$]+$/', $out, $m) ? $m[0] : '')
                : '';
        }
    }
    $lines = array_filter(array_map('trim', explode("\n", $out)), static fn ($l) => $l !== '');
    return implode("\n", $lines) . "\n";
}

$total = [0, 0];
foreach ($dirs as $dir) {
    foreach (glob($root . '/' . $dir . '/*.{css,js}', GLOB_BRACE) ?: [] as $file) {
        if (preg_match('/\.min\.(css|js)$/', $file)) {
            continue;
        }
        $src = file_get_contents($file);
        $isCss = str_ends_with($file, '.css');
        $min = $isCss ? minify_css($src) : minify_js($src);
        $target = preg_replace('/\.(css|js)$/', '.min.$1', $file);
        file_put_contents($target, $min);
        touch($target, max(filemtime($file), time()));
        $total[0] += strlen($src);
        $total[1] += strlen($min);
        printf("%-48s %7s -> %7s  (%%%d)\n", substr($file, strlen($root) + 1), number_format(strlen($src)), number_format(strlen($min)), round(100 - strlen($min) / max(1, strlen($src)) * 100));
    }
}
printf("Toplam: %s -> %s bayt (%%%d küçüldü)\n", number_format($total[0]), number_format($total[1]), round(100 - $total[1] / max(1, $total[0]) * 100));
