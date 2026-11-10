<?php

namespace App\Models;

use App\Core\Model;

/**
 * Özgeçmiş kalemleri (deneyim, eğitim, sertifika, yetenek, dil).
 */
class ResumeItem extends Model
{
    protected static string $table = 'resume_items';
    protected static array $fillable = [
        'type', 'title', 'organization', 'location', 'start_date', 'end_date',
        'is_current', 'description', 'level', 'url', 'sort_order',
    ];

    /** Tür => [başlık, ikon, "başlık" alanı etiketi, "kurum" alanı etiketi] */
    public const TYPES = [
        'experience'  => ['İş Deneyimi', 'briefcase', 'Pozisyon', 'Şirket'],
        'education'   => ['Eğitim', 'book-open', 'Bölüm / Program', 'Okul'],
        'certificate' => ['Sertifikalar', 'award', 'Sertifika Adı', 'Veren Kurum'],
        'skill'       => ['Yetenekler', 'cpu', 'Yetenek', 'Grup (Backend, Frontend...)'],
        'language'    => ['Yabancı Diller', 'globe', 'Dil', ''],
    ];

    public static function validType(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    /** @return self[] */
    public static function ofType(string $type): array
    {
        // Panelde sürükle-bırak ile verilen sıra (sort_order) her zaman önce gelir;
        // aynı sıradakiler (hiç sürüklenmemişler) tarihe / eklenme sırasına göre dizilir
        $order = in_array($type, ['experience', 'education', 'certificate'], true)
            ? 'sort_order ASC, is_current DESC, start_date DESC, id DESC'
            : 'sort_order ASC, id ASC';
        return static::where('type = ?', [$type], $order);
    }

    /** @return array<string, self[]> Tüm kalemler türe göre gruplanmış */
    public static function grouped(): array
    {
        $out = array_fill_keys(array_keys(self::TYPES), []);
        foreach (array_keys(self::TYPES) as $type) {
            $out[$type] = self::ofType($type);
        }
        return $out;
    }

    /** @return array<string, self[]> Yetenekler gruba göre */
    public static function skillsByGroup(): array
    {
        $groups = [];
        foreach (self::ofType('skill') as $skill) {
            $groups[$skill->organization ?: 'Diğer'][] = $skill;
        }
        return $groups;
    }

    /** "Ocak 2022 — Devam ediyor" */
    public function period(): string
    {
        $start = month_label($this->start_date);
        $end = $this->is_current ? 'Devam ediyor' : month_label($this->end_date);
        if ($start === '' && $end === '') {
            return '';
        }
        return $start !== '' && $end !== '' ? $start . ' — ' . $end : ($start ?: $end);
    }

    /** Toplam süre: "2 yıl 3 ay" */
    public function duration(): string
    {
        if (!$this->start_date) {
            return '';
        }
        $start = new \DateTime($this->start_date . '-01');
        $end = $this->is_current || !$this->end_date ? new \DateTime() : new \DateTime($this->end_date . '-01');
        $diff = $start->diff($end);
        $months = $diff->y * 12 + $diff->m + 1;
        $y = intdiv($months, 12);
        $m = $months % 12;
        return trim(($y ? $y . ' yıl ' : '') . ($m ? $m . ' ay' : ''));
    }

    public function levelLabel(): string
    {
        $l = (int) $this->level;
        return match (true) {
            $l >= 95 => 'Ana dil',
            $l >= 80 => 'İleri',
            $l >= 55 => 'Orta',
            $l >= 30 => 'Temel',
            default  => 'Başlangıç',
        };
    }
}
