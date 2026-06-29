<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class QueryResultCache
{
    /**
     * Cachea un valor escalar (int, string, array plano, etc.).
     */
    public static function remember(string $key, callable $resolver): mixed
    {
        $ttl = self::ttl();

        if ($ttl === 0) {
            return $resolver();
        }

        return Cache::remember($key, now()->addSeconds($ttl), $resolver);
    }

    /**
     * Cachea filas de consulta como arrays planos y devuelve objetos ligeros (id, name, …).
     * Evita serializar modelos Eloquent: Laravel 13 bloquea clases en caché por defecto.
     *
     * @param  callable(): EloquentCollection<int, object>|Collection<int, object>  $resolver
     * @return Collection<int, object>
     */
    public static function rememberRows(string $key, callable $resolver): Collection
    {
        $ttl = self::ttl();

        if ($ttl === 0) {
            return self::rowsToOptions(self::collectionToRows($resolver()));
        }

        $rows = Cache::remember($key, now()->addSeconds($ttl), function () use ($resolver): array {
            return self::collectionToRows($resolver());
        });

        return self::rowsToOptions($rows);
    }

    private static function ttl(): int
    {
        return max(0, (int) config('cache.query_ttl', 0));
    }

    /**
     * @param  EloquentCollection<int, object>|Collection<int, object>  $collection
     * @return list<array<string, mixed>>
     */
    private static function collectionToRows(EloquentCollection|Collection $collection): array
    {
        return $collection->map(function (object $model): array {
            $row = [
                'id' => $model->id,
                'name' => $model->name,
            ];

            if (property_exists($model, 'brand_id') || isset($model->brand_id)) {
                $row['brand_id'] = $model->brand_id;
            }

            if (isset($model->brand) && $model->brand !== null) {
                $row['brand'] = [
                    'id' => $model->brand->id,
                    'name' => $model->brand->name,
                ];
            }

            return $row;
        })->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, object>
     */
    private static function rowsToOptions(array $rows): Collection
    {
        return collect($rows)->map(function (array $row): object {
            $option = (object) [
                'id' => $row['id'],
                'name' => $row['name'],
            ];

            if (array_key_exists('brand_id', $row)) {
                $option->brand_id = $row['brand_id'];
            }

            if (! empty($row['brand'])) {
                $option->brand = (object) $row['brand'];
            }

            return $option;
        });
    }
}
