<?php
/**
 * Canlı destek penceresi (sağ alt). Mantık: themes/default/assets/js/chat.js
 * @var App\Core\View $this
 */
if (!App\Services\LiveChat::enabled()) return;
$initials = implode('', array_map(static fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice(preg_split('/\s+/u', trim((string) $site['title'])) ?: [], 0, 2)));
?>
<div class="chat-widget" id="chatWidget" data-base="<?= e(url('destek')) ?>">
    <section class="chat-panel" id="chatPanel" role="dialog" aria-label="Canlı destek" aria-hidden="true">
        <header class="chat-head">
            <span class="chat-avatar">
                <?php if ($site['avatar']): ?><img src="<?= e($site['avatar']) ?>" alt=""><?php else: ?><?= e($initials ?: 'A') ?><?php endif; ?>
                <i class="chat-presence" id="chatPresence"></i>
            </span>
            <div class="chat-head-text">
                <strong><?= e($site['title']) ?></strong>
                <small id="chatStatus">Bağlanıyor...</small>
            </div>
            <button type="button" class="chat-icon-btn" id="chatEnd" title="Sohbeti bitir" hidden><?= icon('log-out', 16) ?></button>
            <button type="button" class="chat-icon-btn" id="chatClose" title="Küçült" aria-label="Pencereyi kapat"><?= icon('chevron-down', 18) ?></button>
        </header>

        <div class="chat-body" id="chatBody" aria-live="polite">
            <div class="chat-msg them welcome" id="chatWelcome"><div class="chat-bubble"></div></div>
        </div>
        <div class="chat-typing" id="chatTyping" hidden><span></span><span></span><span></span> yazıyor</div>

        <!-- Sohbet başlatma -->
        <form class="chat-start" id="chatStart" method="post" action="<?= e(url('destek/baslat')) ?>" data-ajax data-ajax-success="none" novalidate>
            <?= csrf_field() ?>
            <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <input type="hidden" name="page" id="chatPage">
            <div class="chat-field">
                <input type="text" name="name" placeholder="Adınız *" maxlength="100" autocomplete="name" required>
            </div>
            <div class="chat-field">
                <input type="email" name="email" id="chatEmail" placeholder="E-posta (isteğe bağlı)" maxlength="150" autocomplete="email">
            </div>
            <div class="chat-field">
                <textarea name="message" rows="3" placeholder="Mesajınız *" maxlength="2000" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-sm"><span><?= icon('send', 16) ?> Sohbeti başlat</span></button>
        </form>

        <!-- Sohbet bitti: değerlendirme + yeni sohbet (kapanan sohbete geri dönülmez) -->
        <div class="chat-ended" id="chatEnded" hidden>
            <div class="chat-rate" id="chatRate">
                <p class="chat-rate-title">Görüşmeyi nasıl değerlendirirsiniz?</p>
                <div class="chat-stars" id="chatStars" role="radiogroup" aria-label="Puan">
                    <?php foreach (App\Models\ChatConversation::RATING_LABELS as $n => $label): ?>
                        <button type="button" class="chat-star" data-value="<?= $n ?>" role="radio" aria-checked="false" aria-label="<?= $n ?> yıldız: <?= e($label) ?>" title="<?= e($label) ?>">★</button>
                    <?php endforeach; ?>
                </div>
                <small class="chat-rate-label" id="chatRateLabel">Bir yıldız seçin</small>
                <textarea id="chatRateComment" rows="2" maxlength="500" placeholder="Eklemek istedikleriniz (isteğe bağlı)"></textarea>
                <button type="button" class="btn btn-primary btn-sm btn-block" id="chatRateSend" disabled><span>Değerlendirmeyi gönder</span></button>
            </div>
            <div class="chat-rated" id="chatRated" hidden>
                <span class="chat-rated-stars" id="chatRatedStars"></span>
                <strong>Teşekkürler!</strong>
                <small>Değerlendirmeniz alındı.</small>
            </div>
            <button type="button" class="btn btn-ghost btn-sm btn-block" id="chatNew"><span><?= icon('message-square', 15) ?> Yeni sohbet başlat</span></button>
        </div>

        <!-- Mesaj yazma -->
        <form class="chat-compose" id="chatCompose" hidden>
            <textarea id="chatInput" rows="1" placeholder="Mesajınızı yazın..." maxlength="2000" aria-label="Mesaj"></textarea>
            <button type="submit" class="chat-send" title="Gönder (Enter)" aria-label="Gönder"><?= icon('send', 18) ?></button>
        </form>
    </section>

    <button type="button" class="chat-fab" id="chatFab" aria-label="Canlı destek" aria-expanded="false" aria-controls="chatPanel">
        <span class="chat-fab-open"><?= icon('message-square', 24) ?></span>
        <span class="chat-fab-close"><?= icon('x', 24) ?></span>
        <span class="chat-fab-badge" id="chatBadge" hidden>1</span>
        <span class="chat-fab-pulse" aria-hidden="true"></span>
    </button>
</div>
