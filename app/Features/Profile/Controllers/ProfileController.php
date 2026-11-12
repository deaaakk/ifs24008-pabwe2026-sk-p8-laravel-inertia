<?php

namespace App\Features\Profile\Controllers;

use App\Features\Profile\Services\ProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $service) {}

    /** GET /profile */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'profile' => $request->user()->only(['id', 'name', 'username', 'role', 'photo_url']),
        ]);
    }

    /** PUT /profile */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->service->update($user, $data, $request->file('photo'));

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
