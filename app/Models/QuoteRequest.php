<?php

namespace App\Models;

use App\Core\Model;
use App\Support\ServiceCatalog;

/**
 * Web sitesi / masaüstü uygulama teklif talepleri.
 */
class QuoteRequest extends Model
{
    protected static string $table = 'quote_requests';
    protected static array $fillable = [
        'service', 'package', 'name', 'email', 'phone', 'company', 'budget',
        'deadline', 'message', 'status', 'admin_note', 'ip',
    ];

    /** durum => [etiket, Equation rozet rengi] */
    public const STATUSES = [
        'new'         => ['Yeni', 'danger'],
        'seen'        => ['İncelendi', 'info'],
        'contacted'   => ['Görüşüldü', 'primary'],
        'in_progress' => ['Geliştiriliyor', 'warning'],
        'won'         => ['Tamamlandı', 'success'],
        'lost'        => ['İptal / Olumsuz', 'dark'],
    ];

    public static function countNew(): int
    {
        try {
            return static::count('status = ?', ['new']);
        } catch (\Throwable) {
            return 0;
        }
    }

    /** Aynı IP'den son 1 saatte gelen talep sayısı (spam koruması) */
    public static function recentFromIp(string $ip): int
    {
        return static::count('ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', [$ip]);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? (string) $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'dark';
    }

    public function serviceLabel(): string
    {
        return ServiceCatalog::find((string) $this->service)['short'] ?? 'Diğer';
    }

    public function packageLabel(): string
    {
        $service = ServiceCatalog::find((string) $this->service);
        return $service['packages'][(string) $this->package]['name'] ?? ((string) $this->package ?: '-');
    }
}
