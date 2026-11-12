<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /** Root template yang dimuat pada kunjungan pertama. */
    protected $rootView = 'app';

    /**
     * Props yang dibagikan ke seluruh halaman React.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => ['name' => config('app.name')],
            'auth' => [
                'user' => fn () => $request->user()?->only(['id', 'name', 'username', 'role', 'photo_url']),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'ai_drafts' => fn () => $request->session()->get('ai_drafts'),
            ],
        ];
    }
}
