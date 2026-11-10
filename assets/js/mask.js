/**
 * GİRİŞ MASKESİ — site ve panelin ortak kütüphanesi
 * ------------------------------------------------------------------
 *   <input data-mask="phone">                 -> 0 (532) 123 45 67
 *   <input data-mask="99.99.9999">            -> kendi deseniniz (9 = rakam, a = harf, * = herhangi)
 *
 * - Yalnızca desene uyan karakterler yazılabilir, ayraçlar otomatik eklenir
 * - "+90 532 123 45 67", "05321234567", "532 123 4567" gibi yapıştırmalar düzeltilir
 * - Eksik girilen numara için tarayıcı doğrulama mesajı ayarlanır
 * - Sonradan eklenen alanlarda da çalışır (olay delegasyonu)
 *
 *   Mask.format('5321234567', 'phone')  -> "0 (532) 123 45 67"
 *   Mask.isComplete(input)              -> true / false
 */
(function (window, document) {
    'use strict';

    var PRESETS = {
        phone: { pattern: '0 (999) 999 99 99', placeholder: '0 (5__) ___ __ __', message: 'Telefon numarasını eksiksiz girin: 0 (5XX) XXX XX XX' }
    };

    var TOKENS = {
        '9': /\d/,
        'a': /[A-Za-zÇĞİÖŞÜçğıöşü]/,
        '*': /[A-Za-z0-9ÇĞİÖŞÜçğıöşü]/
    };

    function config(name) {
        return PRESETS[name] || { pattern: name, placeholder: '', message: 'Lütfen alanı eksiksiz doldurun.' };
    }

    /** Ham değerden yalnızca desene girecek karakterleri çıkarır */
    function clean(value, name) {
        value = String(value || '');
        if (name === 'phone') {
            var digits = value.replace(/\D/g, '');
            if (digits.indexOf('90') === 0 && digits.length > 10) digits = digits.slice(2); // +90 ülke kodu
            if (digits.charAt(0) === '0') digits = digits.slice(1);                         // baştaki 0 desende sabit
            return digits.slice(0, 10);
        }
        return value.replace(/[^A-Za-z0-9ÇĞİÖŞÜçğıöşü]/g, '');
    }

    function format(value, name) {
        var pattern = config(name).pattern;
        var raw = clean(value, name);
        var out = '';
        var i = 0;
        for (var p = 0; p < pattern.length && i < raw.length; p++) {
            var token = TOKENS[pattern[p]];
            if (token) {
                // Desene uymayan karakteri atla
                while (i < raw.length && !token.test(raw[i])) i++;
                if (i < raw.length) out += raw[i++];
            } else {
                out += pattern[p]; // ayraç (boşluk, parantez, nokta...)
            }
        }
        return out;
    }

    function slots(name) {
        return config(name).pattern.split('').filter(function (c) { return TOKENS[c]; }).length;
    }

    function isComplete(input) {
        var name = input.dataset.mask;
        return input.value === '' || clean(input.value, name).length === slots(name);
    }

    function validate(input) {
        input.setCustomValidity(isComplete(input) ? '' : config(input.dataset.mask).message);
    }

    function apply(input) {
        var name = input.dataset.mask;
        var before = input.value;
        var caret = input.selectionStart == null ? before.length : input.selectionStart;
        // İmleçten önceki "anlamlı" karakter sayısını koru
        var meaningful = clean(before.slice(0, caret), name).length;

        var formatted = format(before, name);
        if (formatted !== before) {
            input.value = formatted;
            // Yeni imleç konumu: aynı sayıda anlamlı karakterin hemen sonrası
            var pos = 0, count = 0, pattern = config(name).pattern;
            while (pos < formatted.length && count < meaningful) {
                if (TOKENS[pattern[pos]]) count++;
                pos++;
            }
            if (document.activeElement === input) {
                try { input.setSelectionRange(pos, pos); } catch (e) { /* bazı türler desteklemez */ }
            }
        }
        validate(input);
    }

    function init(input) {
        if (input.dataset.maskReady) return;
        input.dataset.maskReady = '1';
        var c = config(input.dataset.mask);
        if (input.dataset.mask === 'phone') {
            input.setAttribute('inputmode', 'tel');
            if (!input.getAttribute('autocomplete')) input.setAttribute('autocomplete', 'tel');
        }
        if (c.placeholder && !input.getAttribute('placeholder')) input.setAttribute('placeholder', c.placeholder);
        input.setAttribute('maxlength', c.pattern.length);
        if (input.value) {
            input.value = format(input.value, input.dataset.mask);
            validate(input);
        }
    }

    function initAll(root) {
        (root || document).querySelectorAll('input[data-mask]').forEach(init);
    }

    // Olay delegasyonu: sonradan eklenen alanlar da maskelenir
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (el && el.matches && el.matches('input[data-mask]')) {
            init(el);
            apply(el);
        }
    });
    document.addEventListener('focusin', function (e) {
        var el = e.target;
        if (el && el.matches && el.matches('input[data-mask="phone"]')) {
            init(el);
            if (el.value === '') {
                el.value = '0 (';
                try { el.setSelectionRange(3, 3); } catch (err) { /* yoksay */ }
            }
        }
    });
    document.addEventListener('focusout', function (e) {
        var el = e.target;
        if (el && el.matches && el.matches('input[data-mask="phone"]') && clean(el.value, 'phone') === '') {
            el.value = '';
            validate(el);
        }
    });
    // Geri silme ayraçta takılmasın: "0 (532) |" -> bir rakam sil
    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (e.key !== 'Backspace' || !el.matches || !el.matches('input[data-mask]')) return;
        var pos = el.selectionStart, end = el.selectionEnd;
        if (pos !== end || pos === 0) return;
        var pattern = config(el.dataset.mask).pattern;
        if (!TOKENS[pattern[pos - 1]]) {
            var p = pos - 1;
            while (p > 0 && !TOKENS[pattern[p - 1]]) p--;
            if (p > 0) {
                e.preventDefault();
                el.value = el.value.slice(0, p - 1) + el.value.slice(pos);
                el.setSelectionRange(p - 1, p - 1);
                apply(el);
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function () { initAll(); });

    window.Mask = { format: format, clean: clean, isComplete: isComplete, init: initAll, presets: PRESETS };
})(window, document);
