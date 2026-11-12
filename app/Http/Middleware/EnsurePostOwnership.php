<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsurePostOwnership
{
    /**
     * Pastikan artikel pada route ({post}) milik pengguna yang login (lewat PostPolicy@manage).
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(Gate::denies('manage', $request->route('post')), 403, 'Artikel ini bukan milik Anda.');

        return $next($request);
    }
}
