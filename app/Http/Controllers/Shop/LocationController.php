<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Locations\Department;
use App\Models\Locations\District;
use App\Models\Locations\Province;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function departments(): JsonResponse
    {
        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
            ]);

        return response()->json(['data' => $departments]);
    }

    public function provinces(Department $department): JsonResponse
    {
        $provinces = $department->provinces()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Province $province) => [
                'id' => $province->id,
                'name' => $province->name,
            ]);

        return response()->json(['data' => $provinces]);
    }

    public function districts(Province $province): JsonResponse
    {
        $districts = $province->districts()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (District $district) => [
                'id' => $district->id,
                'name' => $district->name,
            ]);

        return response()->json(['data' => $districts]);
    }
}
