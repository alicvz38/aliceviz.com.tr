<?php

namespace App\Models;

use App\Core\Model;

/**
 * Proje tanıtım görseli (/projeler/{slug}/tanitim): sıralı ekran görüntüsü, başlık ve açıklama.
 */
class ProjectShot extends Model
{
    protected static string $table = 'project_shots';
    protected static array $fillable = ['project_id', 'image', 'title', 'caption', 'sort_order'];

    /** @return static[] */
    public static function forProject(int $projectId): array
    {
        return static::where('project_id = ?', [$projectId], 'sort_order ASC, id ASC');
    }

    public static function countFor(int $projectId): int
    {
        return static::count('project_id = ?', [$projectId]);
    }

    public function imageUrl(): string
    {
        return preg_match('#^https?://#', (string) $this->image) ? (string) $this->image : url((string) $this->image);
    }
}
