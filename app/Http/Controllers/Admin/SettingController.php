<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage', Setting::class);

        return view('admin.settings.edit', [
            'rankingEnabled' => Setting::rankingEnabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('manage', Setting::class);

        Setting::setRankingEnabled($request->boolean('ranking_enabled'));

        return redirect()->route('admin.settings.edit')->with('success', 'Settings saved.');
    }
}
