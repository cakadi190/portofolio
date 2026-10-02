<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CafePriceTier;
use App\Enums\WifiSpeed;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CoffeePlaceRequest;
use App\Models\CoffeePlace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoffeePlaceController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/coffee-places/index', [
            'coffeePlaces' => $this->paginateTable(
                CoffeePlace::query()->orderBy('name'),
                $request,
                ['name', 'address'],
                ['name', 'address', 'price_tier', 'is_recommended'],
            ),
            'filters' => $this->tableFilters($request, ['name', 'address', 'price_tier', 'is_recommended']),
            'wifiSpeeds' => WifiSpeed::options(),
            'priceTiers' => CafePriceTier::options(),
        ]);
    }

    public function store(CoffeePlaceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        CoffeePlace::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kedai kopi berhasil ditambahkan.']);

        return to_route('admin.coffee-places.index');
    }

    public function update(CoffeePlaceRequest $request, CoffeePlace $coffeePlace): RedirectResponse
    {
        $data = $request->validated();

        $coffeePlace->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kedai kopi berhasil diperbarui.']);

        return to_route('admin.coffee-places.index');
    }

    public function destroy(CoffeePlace $coffeePlace): RedirectResponse
    {
        $coffeePlace->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kedai kopi berhasil dihapus.']);

        return to_route('admin.coffee-places.index');
    }
}
