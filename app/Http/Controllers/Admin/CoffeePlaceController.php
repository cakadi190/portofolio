<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CafePriceTier;
use App\Enums\WifiSpeed;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CoffeePlaceRequest;
use App\Models\CoffeePlace;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CoffeePlaceController extends Controller
{
    use HandlesUploads;

    public function index(): Response
    {
        return Inertia::render('admin/coffee-places/index', [
            'coffeePlaces' => CoffeePlace::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/coffee-places/create', [
            'wifiSpeeds' => WifiSpeed::options(),
            'priceTiers' => CafePriceTier::options(),
        ]);
    }

    public function store(CoffeePlaceRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUpload($request->file('image'), 'coffee-places');
        }

        CoffeePlace::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kedai kopi berhasil ditambahkan.']);

        return to_route('admin.coffee-places.index');
    }

    public function edit(CoffeePlace $coffeePlace): Response
    {
        return Inertia::render('admin/coffee-places/edit', [
            'coffeePlace' => $coffeePlace,
            'wifiSpeeds' => WifiSpeed::options(),
            'priceTiers' => CafePriceTier::options(),
        ]);
    }

    public function update(CoffeePlaceRequest $request, CoffeePlace $coffeePlace): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $this->deleteUpload($coffeePlace->image);
            $data['image'] = $this->storeUpload($request->file('image'), 'coffee-places');
        }

        $coffeePlace->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kedai kopi berhasil diperbarui.']);

        return to_route('admin.coffee-places.index');
    }

    public function destroy(CoffeePlace $coffeePlace): RedirectResponse
    {
        $this->deleteUpload($coffeePlace->image);
        $coffeePlace->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kedai kopi berhasil dihapus.']);

        return to_route('admin.coffee-places.index');
    }
}
