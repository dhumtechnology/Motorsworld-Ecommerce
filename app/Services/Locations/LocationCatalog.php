<?php

namespace App\Services\Locations;

use App\Models\Locations\Department;
use App\Models\Locations\District;
use App\Models\Locations\Province;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LocationCatalog
{
    /**
     * @return Collection<int, Department>
     */
    public function departments(): Collection
    {
        return Department::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, object{id: int, name: string}>
     */
    public function provincesForSelect(): Collection
    {
        return Province::query()
            ->with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id'])
            ->map(fn (Province $province) => (object) [
                'id' => $province->id,
                'name' => $province->name.' — '.$province->department?->name,
            ]);
    }

    /**
     * @param  list<int|string>  $ids
     * @return Collection<int, object{id: int, name: string}>
     */
    public function districtsByIds(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return collect();
        }

        return District::query()
            ->with(['province:id,name', 'department:id,name'])
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'province_id', 'department_id'])
            ->sortBy(fn (District $district) => array_search((int) $district->id, $ids, true))
            ->values()
            ->map(fn (District $district) => (object) [
                'id' => $district->id,
                'name' => $district->displayName(),
            ]);
    }

    /**
     * @return LengthAwarePaginator<int, District>
     */
    public function paginateDistricts(string $search = '', int $page = 1, int $perPage = 40): LengthAwarePaginator
    {
        $query = District::query()
            ->with(['province:id,name', 'department:id,name'])
            ->orderBy('name');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $searchQuery) use ($like) {
                $searchQuery
                    ->where('name', 'like', $like)
                    ->orWhereHas('province', fn (Builder $provinceQuery) => $provinceQuery->where('name', 'like', $like))
                    ->orWhereHas('department', fn (Builder $departmentQuery) => $departmentQuery->where('name', 'like', $like));
            });
        }

        return $query->paginate(
            $perPage,
            ['id', 'name', 'province_id', 'department_id'],
            'page',
            max(1, $page),
        );
    }
}
