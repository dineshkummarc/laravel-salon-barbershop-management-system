<?php

namespace App\Http\Controllers;

use App\Domain\Appointments;
use App\Domain\Checkout;
use App\Domain\Retention;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Content;
use App\Models\Customer;
use App\Models\CustomerPackage;
use App\Models\Employee;
use App\Models\Enquiry;
use App\Models\PackagePlan;
use App\Models\Product;
use App\Models\Review;
use App\Models\Sale;
use App\Models\Service;
use App\Support\BusinessContext;
use App\Support\Modules;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StorefrontController extends Controller
{
    private function customer(): Customer
    {
        abort_unless(auth()->check() && auth()->user()->role === 'customer' && auth()->user()->business_id === app(BusinessContext::class)->id(), 403, 'Sign in with a customer account for this business.');

        return Customer::where('user_id', auth()->id())->firstOrFail();
    }

    public function home()
    {
        Modules::require('website');

        return view('site.home', ['services' => Service::where('active', true)->take(6)->get(), 'branches' => Branch::where('active', true)->get(), 'products' => Modules::enabled('products') ? Product::where('active', true)->take(4)->get() : collect(), 'reviews' => Modules::enabled('reviews') ? Review::where('status', 'published')->with('customer')->latest()->take(3)->get() : collect(), 'team' => Employee::where('active', true)->take(4)->get()]);
    }

    public function services()
    {
        Modules::require('services');

        return view('site.services', ['services' => Service::where('active', true)->get(), 'packages' => Modules::enabled('packages') ? PackagePlan::where('active', true)->with('service')->get() : collect()]);
    }

    public function service(string $business, string $slug)
    {
        Modules::require('services');

        return view('site.detail', ['item' => Service::where('slug', $slug)->where('active', true)->firstOrFail(), 'kind' => 'service']);
    }

    public function shop(Request $request)
    {
        Modules::require('products');
        $q = Product::where('active', true);
        if ($request->filled('q')) {
            $q->where('name', 'like', '%'.$request->string('q').'%');
        } if ($request->filled('category')) {
            $q->where('category', $request->string('category'));
        }

return view('site.shop', ['products' => $q->paginate(12)->withQueryString(), 'categories' => Product::where('active', true)->distinct()->pluck('category')]);
    }

    public function product(string $business, string $slug)
    {
        Modules::require('products');

        return view('site.detail', ['item' => Product::where('slug', $slug)->where('active', true)->firstOrFail(), 'kind' => 'product']);
    }

    public function branches()
    {
        return view('site.branches', ['branches' => Branch::where('active', true)->get(), 'team' => Employee::where('active', true)->get()]);
    }

    public function page(string $business, string $slug)
    {
        return view('site.page', ['page' => Content::where('slug', $slug)->where('published', true)->firstOrFail()]);
    }

    public function blog()
    {
        return view('site.blog', ['posts' => Content::where('type', 'blog')->where('published', true)->latest()->paginate(10)]);
    }

    public function contact()
    {
        return view('site.contact');
    }

    public function enquiry(Request $request)
    {
        Modules::require('website');
        $data = $request->validate(['name' => 'required|string|max:120', 'phone' => 'required|string|max:30', 'message' => 'required|string|max:3000']);
        Enquiry::create($data);

        return back()->with('success', __('Your message has been sent. Our team will contact you.'));
    }

    public function booking()
    {
        Modules::require('appointments');

        return view('site.booking', ['branches' => Branch::where('active', true)->get(), 'services' => Service::where('active', true)->get(), 'employees' => Employee::where('active', true)->get()]);
    }

    public function book(Request $request)
    {
        $customer = $this->customer();
        $data = $request->validate(['branch_id' => 'required|integer', 'service_id' => 'required|integer', 'employee_id' => 'required|integer', 'starts_at' => 'required|date', 'notes' => 'nullable|string|max:1000', 'home_service' => 'nullable|boolean', 'home_address' => 'nullable|string|max:1000']);
        $data['customer_id'] = $customer->id;
        app(Appointments::class)->book($data);

        return redirect()->route('account', ['business' => app(BusinessContext::class)->business()->slug])->with('success', __('Your appointment is confirmed. See you soon!'));
    }

    public function cart()
    {
        Modules::require('ecommerce');
        $cart = $this->cartItems();
        session()->put('checkout_key', session('checkout_key', (string) Str::uuid()));

        return view('site.cart', ['items' => $cart, 'branches' => Branch::where('active', true)->get()]);
    }

    private function cartItems(): Collection
    {
        $cart = session('cart.'.app(BusinessContext::class)->id(), []);

        return Product::whereIn('id', array_keys($cart))->where('active', true)->get()->map(fn ($p) => ['product' => $p, 'quantity' => $cart[$p->id]]);
    }

    public function addCart(Request $request)
    {
        Modules::require('ecommerce');
        $data = $request->validate(['product_id' => 'required|integer', 'quantity' => 'required|integer|min:1|max:99']);
        $p = Product::where('active', true)->findOrFail($data['product_id']);
        $key = 'cart.'.app(BusinessContext::class)->id();
        $cart = session($key, []);
        $cart[$p->id] = min(99, ($cart[$p->id] ?? 0) + $data['quantity']);
        session()->put($key, $cart);

        return back()->with('success', __('Added to your bag.'));
    }

    public function removeCart(Request $request)
    {
        Modules::require('ecommerce');
        $key = 'cart.'.app(BusinessContext::class)->id();
        $cart = session($key, []);
        unset($cart[$request->input('product_id')]);
        session()->put($key, $cart);

        return back();
    }

    public function checkout(Request $request)
    {
        $customer = $this->customer();
        $data = $request->validate(['branch_id' => 'required|integer', 'delivery_method' => 'required|in:pickup,delivery', 'delivery_address' => 'required_if:delivery_method,delivery|nullable|string|max:1000', 'checkout_key' => 'required|uuid', 'coupon_code' => 'nullable|string|max:30', 'points_redeemed' => 'nullable|integer|min:0']);
        abort_unless(hash_equals((string) session('checkout_key'), $data['checkout_key']), 419);
        $sale = app(Checkout::class)->create(['branch_id' => $data['branch_id'], 'customer_id' => $customer->id, 'channel' => 'online', 'coupon_code' => $data['coupon_code'] ?? null, 'points_redeemed' => $data['points_redeemed'] ?? 0, 'delivery_method' => $data['delivery_method'], 'delivery_address' => $data['delivery_address'] ?? null, 'idempotency_key' => $data['checkout_key'], 'items' => $this->cartItems()->map(fn ($l) => ['type' => 'product', 'id' => $l['product']->id, 'quantity' => $l['quantity']])->all()]);
        session()->forget('cart.'.app(BusinessContext::class)->id());

        return redirect()->route('order', ['business' => app(BusinessContext::class)->business()->slug, 'token' => $sale->token])->with('success', __('Order placed. Pay on pickup or contact the branch for manually verified payment.'));
    }

    public function account()
    {
        $c = $this->customer();

        return view('site.account', ['customer' => $c, 'bookings' => Booking::where('customer_id', $c->id)->with('service', 'employee', 'branch')->latest('starts_at')->get(), 'orders' => Sale::where('customer_id', $c->id)->latest()->get(), 'packages' => CustomerPackage::where('customer_id', $c->id)->with('packagePlan')->get()]);
    }

    public function cancelBooking(string $business, int $id)
    {
        $c = $this->customer();
        $b = Booking::where('customer_id', $c->id)->findOrFail($id);
        abort_unless($b->starts_at->gt(now()->addHours(2)), 422, 'Please contact the branch for cancellations within two hours.');
        app(Appointments::class)->transition($b, 'cancelled');

        return back()->with('success', __('Appointment cancelled.'));
    }

    public function review(Request $request, string $business, int $sale)
    {
        $customer = $this->customer();
        $data = $request->validate(['rating' => 'required|integer|min:1|max:5', 'body' => 'required|string|min:10|max:1500']);
        app(Retention::class)->review($sale, $customer->id, (int) $data['rating'], $data['body']);

        return back()->with('success','Thank you. Your verified review is awaiting moderation.');
    }

    public function order(string $business,string $token)
    {
        $c = $this->customer();

        return view('site.order',['sale' => Sale::where('token',$token)->where('customer_id',$c->id)->with('items','branch')->firstOrFail()]);
    }
}
