<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\BusinessContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ConsentController extends Controller
{
    public function unsubscribe(Request $request, string $token)
    {
        [$business,$id] = explode(':', Crypt::decryptString($token));
        app(BusinessContext::class)->set((int) $business, true);
        $customer = Customer::findOrFail($id);
        if ($request->isMethod('post')) {
            $customer->update(['marketing_consent' => false]);

            return response('You have been unsubscribed. You can close this page.');
        }

        return view('site.unsubscribe', ['business' => app(BusinessContext::class)->business()]);
    }
}
