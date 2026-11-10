<?php
/**
 * Sayfa başlığı ve arama motoru / paylaşım etiketleri
 * @var App\Core\View $this
 * @var array $site
 * @var string|null $pageTitle
 * @var string|null $metaDescription
 * @var string|null $ogImage   sayfaya özel paylaşım görseli (ör. proje kapağı)
 * @var string|null $ogType    website | article
 * @var array|null  $schema    sayfaya özel schema.org verisi (proje, hizmet)
 * @var bool        $noindex   arama / filtre sonuçları gibi dizine eklenmeyecek sayfalar
 */
$isHome = empty($pageTitle);
$title = $isHome
    ? (setting('seo_home_title', '') ?: $site['title'] . ($site['job'] ? ' — ' . $site['job'] : ''))
    : $pageTitle . ' | ' . $site['title'];
$description = trim((string) ($metaDescription ?: ($isHome ? (setting('seo_home_description', '') ?: $site['description']) : $site['description'])));
$description = mb_strlen($description) > 170 ? mb_substr($description, 0, 167) . '…' : $description;
$canonical = canonical_url();
$shareImage = absolute_url($ogImage ?: (setting('seo_og_image', '') !== '' && is_file(ROOT_PATH . '/' . setting('seo_og_image')) ? url(setting('seo_og_image')) : shared_asset('img/og-image.jpg')));
$home = absolute_url();

// schema.org: site + kişi (her sayfada), sayfaya özel veri varsa eklenir
$sameAs = array_values(array_filter($site['social'] ?? []));
$graph = [
    [
        '@type'       => 'WebSite',
        '@id'         => $home . '#site',
        'url'         => $home,
        'name'        => $site['domain'] ?: $site['title'],
        'description' => $site['description'],
        'inLanguage'  => 'tr-TR',
        'publisher'   => ['@id' => $home . '#kisi'],
    ],
    array_filter([
        '@type'       => 'Person',
        '@id'         => $home . '#kisi',
        'name'        => $site['title'],
        'jobTitle'    => $site['job'] ?: null,
        'url'         => $home,
        'image'       => $site['avatar'] ? absolute_url($site['avatar']) : null,
        'email'       => $site['email'] ? 'mailto:' . $site['email'] : null,
        'address'     => $site['location'] ? ['@type' => 'PostalAddress', 'addressCountry' => $site['location']] : null,
        'sameAs'      => $sameAs ?: null,
        'knowsAbout'  => ['Web sitesi geliştirme', 'Masaüstü uygulama geliştirme', 'PHP', 'Python', 'C#', 'C++', 'JavaScript'],
    ]),
];
if (!empty($schema)) {
    $graph[] = array_filter($schema, static fn ($v) => $v !== null && $v !== '');
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<?php if (!empty($noindex)): ?>
<meta name="robots" content="noindex, follow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
<?php if ($v = setting('seo_google_verification', '')): ?><meta name="google-site-verification" content="<?= e($v) ?>">
<?php endif; ?>
<?php if ($v = setting('seo_bing_verification', '')): ?><meta name="msvalidate.01" content="<?= e($v) ?>">
<?php endif; ?>
<?php if ($ads = adsense_client()): ?>
<meta name="google-adsense-account" content="<?= e($ads) ?>">
<?php if (http_response_code() < 400): /* bakım (503) ve 404 sayfalarında reklam gösterilmez */ ?>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e($ads) ?>" crossorigin="anonymous"></script>
<?php endif; ?>
<?php endif; ?>
<meta name="author" content="<?= e($site['title']) ?>">
<meta name="theme-color" content="#0b0f19">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">

<!-- Paylaşım (WhatsApp, LinkedIn, Facebook, X) -->
<meta property="og:type" content="<?= e($ogType ?: 'website') ?>">
<meta property="og:site_name" content="<?= e($site['domain'] ?: $site['title']) ?>">
<meta property="og:locale" content="tr_TR">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:title" content="<?= e($isHome ? $title : $pageTitle) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:image" content="<?= e($shareImage) ?>">
<meta property="og:image:alt" content="<?= e($isHome ? $title : $pageTitle) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($isHome ? $title : $pageTitle) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e($shareImage) ?>">
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>

<link rel="stylesheet" href="<?= e(shared_asset('css/ajax.css')) ?>">
<?= favicon_tags() ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
