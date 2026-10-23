<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Support\BusinessContext;
use App\Support\Modules;
use Closure;

class PublicBusiness
{
    public function handle($request, Closure $next)
    {
        $business = Business::where('slug', $request->route('business'))->where('status', 'active')->firstOrFail();
        app(BusinessContext::class)->set($business->id, true);
        if (! in_array($request->route()->getName(), ['login', 'register', 'account', 'order'])) {
            Modules::require('website');
        }
        app()->setLocale(session('locale', 'en'));
        view()->share('business', $business);

        return $next($request);
    }
}
