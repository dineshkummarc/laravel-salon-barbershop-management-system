<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Support\BusinessContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function form(string $business, string $action = 'login')
    {
        return view('site.auth', ['action' => $action]);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($data + ['business_id' => app(BusinessContext::class)->id(), 'role' => 'customer'])) {
            throw ValidationException::withMessages(['email' => __('These credentials do not match a customer account.')]);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('account', ['business' => app(BusinessContext::class)->business()->slug]));
    }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:180|unique:users,email', 'phone' => ['required', 'regex:/^(?:\+?88)?01[3-9][0-9]{8}$/'], 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $user = DB::transaction(function () use ($data) {
            $phone = '+880'.substr(preg_replace('/\D/', '', $data['phone']), -10);
            if (Customer::where('phone', $phone)->exists()) {
                throw ValidationException::withMessages(['phone' => 'This phone is already registered. Contact the business to link your existing profile.']);
            }
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $phone, 'password' => $data['password'], 'business_id' => app(BusinessContext::class)->id(), 'role' => 'customer']);
            Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $phone]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account', ['business' => app(BusinessContext::class)->business()->slug]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function forgot()
    {
        return view('site.auth', ['action' => 'forgot']);
    }

    public function emailReset(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('success', 'If that account exists, a reset link has been sent. In local mode, check storage/logs/laravel.log.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('site.auth', ['action' => 'reset', 'token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)]);
        }

        return redirect('/')->with('success',__($status));
    }
}
