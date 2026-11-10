<?php

namespace App\Core;

/**
 * Basit Active Record taban sınıfı.
 * Alt sınıflar $table ve $fillable tanımlar; kayıtlar nesne olarak döner.
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    /** Toplu atamaya izin verilen sütunlar */
    protected static array $fillable = [];

    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public static function db(): Database
    {
        return Database::getInstance();
    }

    /** @return static[] */
    protected static function hydrate(array $rows): array
    {
        return array_map(static fn (array $row) => new static($row), $rows);
    }

    protected static function filter(array $data): array
    {
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    public static function find(int $id): ?static
    {
        $row = static::db()->fetch(
            sprintf('SELECT * FROM `%s` WHERE `%s` = ? LIMIT 1', static::$table, static::$primaryKey),
            [$id]
        );
        return $row ? new static($row) : null;
    }

    /** $column koddan gelmelidir, kullanıcı girdisi olmamalıdır. */
    public static function findBy(string $column, mixed $value): ?static
    {
        $row = static::db()->fetch(sprintf('SELECT * FROM `%s` WHERE `%s` = ? LIMIT 1', static::$table, $column), [$value]);
        return $row ? new static($row) : null;
    }

    /** @return static[] */
    public static function all(string $orderBy = 'id DESC'): array
    {
        return static::hydrate(static::db()->fetchAll(sprintf('SELECT * FROM `%s` ORDER BY %s', static::$table, $orderBy)));
    }

    /** @return static[] */
    public static function where(string $where, array $params = [], string $orderBy = 'id DESC', ?int $limit = null, int $offset = 0): array
    {
        $sql = sprintf('SELECT * FROM `%s` WHERE %s ORDER BY %s', static::$table, $where, $orderBy);
        if ($limit !== null) {
            $sql .= sprintf(' LIMIT %d OFFSET %d', $limit, max(0, $offset));
        }
        return static::hydrate(static::db()->fetchAll($sql, $params));
    }

    public static function count(string $where = '1', array $params = []): int
    {
        return (int) static::db()->fetchColumn(sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', static::$table, $where), $params);
    }

    public static function create(array $data): int
    {
        return static::db()->insert(static::$table, static::filter($data));
    }

    public static function updateById(int $id, array $data): bool
    {
        $data = static::filter($data);
        if (!$data) {
            return false;
        }
        static::db()->update(static::$table, $data, sprintf('`%s` = :pk', static::$primaryKey), ['pk' => $id]);
        return true;
    }

    public static function deleteById(int $id): bool
    {
        return static::db()->delete(static::$table, sprintf('`%s` = ?', static::$primaryKey), [$id]) > 0;
    }
}
