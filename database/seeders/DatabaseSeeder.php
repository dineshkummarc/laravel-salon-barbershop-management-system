<?php

namespace Database\Seeders;

use App\Domain\Appointments;
use App\Domain\Checkout;
use App\Domain\Inventory;
use App\Domain\Operations;
use App\Domain\Retention;
use App\Models\AttendanceDevice;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Content;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\MembershipPlan;
use App\Models\PackagePlan;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\Sale;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\User;
use App\Support\BusinessContext;
use App\Support\Modules;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo seeding is forbidden in production.');
        }
        if (Business::exists()) {
            $this->command?->warn('Demo data already exists. Use php artisan salon:demo-reset for a fresh database.');

            return;
        }
        $password = env('DEMO_PASSWORD', 'DemoSalon2026!');
        User::create(['name' => 'Platform Administrator', 'email' => 'platform@example.test', 'password' => $password, 'role' => 'platform']);
        $definitions = [
            ['Dhaka Grooming & Salon', 'ঢাকা গ্রুমিং অ্যান্ড সেলুন', 'dhaka-grooming', ['Dhanmondi', 'Mirpur', 'Uttara'], array_keys(Modules::ALL), 'A neighborhood space for a fresh cut, thoughtful grooming, and a little time for yourself.'],
            ['Chattogram Classic Barber', 'চট্টগ্রাম ক্লাসিক বারবার', 'classic-barber', ['GEC Circle'], ['website', 'services', 'pos', 'crm', 'reports', 'hr', 'content'], 'A classic neighborhood barbershop. Good conversation. A great cut. Honest prices.'],
            ['Rong Beauty Studio', 'রং বিউটি স্টুডিও', 'rong-beauty', ['Banani'], array_keys(Modules::ALL), 'A calm little studio for beauty rituals, bridal care, and your everyday glow.'],
        ];
        foreach ($definitions as $index => [$name,$bn,$slug,$branchNames,$modules,$description]) {
            $business = Business::create(['name' => $name, 'name_bn' => $bn, 'slug' => $slug, 'phone' => '+88017000000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'email' => 'hello@'.$slug.'.example.test', 'description' => $description, 'modules' => $modules, 'plan' => $index === 1 ? 'basic' : 'professional']);
            app(BusinessContext::class)->set($business->id);
            $branches = [];
            foreach ($branchNames as $n => $branchName) {
                $branches[] = Branch::create(['name' => $branchName, 'address' => ($n + 12).'/A, '.$branchName.', '.($index === 1 ? 'Chattogram' : 'Dhaka'), 'phone' => '+88018000000'.($index + 1).$n, 'opens_at' => '09:00', 'closes_at' => '21:00']);
            }
            $owner = User::create(['name' => $name.' Owner', 'email' => $index === 0 ? 'owner@example.test' : ($index === 1 ? 'owner2@example.test' : 'beauty@example.test'), 'password' => $password, 'role' => 'owner', 'business_id' => $business->id]);
            if ($index === 0) {
                foreach (['manager', 'reception', 'cashier', 'stylist', 'inventory', 'accounts', 'hr'] as $role) {
                    User::create(['name' => ucfirst($role).' Demo', 'email' => $role.'@example.test', 'password' => $password, 'role' => $role, 'business_id' => $business->id, 'branch_id' => in_array($role, ['accounts', 'hr']) ? null : $branches[0]->id]);
                }
            }
            $employees = [];
            foreach ($branches as $n => $branch) {
                foreach (['Arif Hasan', 'Sadia Rahman'] as $s => $staffName) {
                    $employees[] = Employee::create(['branch_id' => $branch->id, 'name' => $staffName.($n ? ' '.$branch->name : ''), 'title' => $s ? 'Senior stylist' : 'Grooming specialist', 'phone' => '+8801900000'.str_pad((string) ($index * 100 + $n * 10 + $s), 3, '0', STR_PAD_LEFT), 'salary' => ($s ? 22000 : 18000) * 100, 'commission_basis_points' => 1000]);
                }
            }
            if ($index === 0) {
                $stylist = User::where('email', 'stylist@example.test')->first();
                $employees[0]->update(['user_id' => $stylist->id]);
            }
            $services = [];
            $menu = $index === 2 ? [['Signature facial', 'সিগনেচার ফেসিয়াল', 1500, 60, 'Skin care'], ['Hair styling', 'হেয়ার স্টাইলিং', 800, 45, 'Hair'], ['Bridal consultation', 'ব্রাইডাল পরামর্শ', 2000, 60, 'Bridal'], ['Manicure & care', 'ম্যানিকিউর', 600, 40, 'Beauty']] : [['Signature haircut', 'সিগনেচার হেয়ারকাট', 350, 30, 'Hair'], ['Beard trim & shape', 'দাড়ি ট্রিম ও শেপ', 200, 20, 'Grooming'], ['Cut & beard ritual', 'চুল ও দাড়ির যত্ন', 500, 50, 'Grooming'], ['Hair wash & styling', 'হেয়ার ওয়াশ ও স্টাইলিং', 300, 30, 'Hair'], ['Refreshing facial', 'রিফ্রেশিং ফেসিয়াল', 900, 50, 'Skin care'], ['Scalp care massage', 'মাথার ম্যাসাজ', 450, 30, 'Wellness']];
            foreach ($menu as [$title,$titleBn,$price,$duration,$category]) {
                $services[] = Service::create(['name' => $title, 'name_bn' => $titleBn, 'slug' => universal_slug($title), 'category' => $category, 'description' => 'A thoughtful '.$category.' service, tailored to you. Expert hands and careful finishing, every time.', 'price' => $price * 100, 'duration' => $duration]);
            }
            $customers = [];
            foreach (['Nafis Islam', 'Tanha Ahmed', 'Mehedi Hasan', 'Samira Khan', 'Rafi Chowdhury', 'Nusrat Jahan'] as $n => $customerName) {
                $user = null;
                if ($n === 0) {
                    $user = User::create(['name' => $customerName, 'email' => $index === 0 ? 'customer@example.test' : 'customer'.($index + 1).'@example.test', 'password' => $password, 'role' => 'customer', 'business_id' => $business->id]);
                }
                $customers[] = Customer::create(['user_id' => $user?->id, 'name' => $customerName, 'phone' => '+88016000'.str_pad((string) ($index * 100 + $n), 5, '0', STR_PAD_LEFT), 'email' => $user?->email, 'marketing_consent' => $n % 2 === 0]);
            }
            $products = [];
            if (in_array('products', $modules)) {
                $supplier = Supplier::create(['name' => 'Bengal Grooming Supply (Demo)', 'phone' => '+8801700012345', 'email' => 'orders@supplier.example.test', 'address' => 'Dhaka, Bangladesh']);
                foreach ([['Daily care shampoo', 'ডেইলি কেয়ার শ্যাম্পু', 650, 350, 'Hair care'], ['Matte styling clay', 'ম্যাট স্টাইলিং ক্লে', 850, 480, 'Styling'], ['Nourishing beard oil', 'বিয়ার্ড অয়েল', 550, 280, 'Grooming'], ['Gentle face cleanser', 'ফেস ক্লেনজার', 450, 220, 'Skin care']] as $n => [$title,$titleBn,$price,$cost,$category]) {
                    $p = Product::create(['name' => $title, 'name_bn' => $titleBn, 'slug' => universal_slug($title), 'sku' => 'GROOM-'.($n + 1), 'category' => $category, 'description' => 'Your daily dose of thoughtful care. A salon essential for your at-home routine. Demo catalog item.', 'price' => $price * 100, 'cost' => $cost * 100]);
                    $products[] = $p;
                    foreach ($branches as $branch) {
                        $purchase = Purchase::create(['branch_id' => $branch->id, 'supplier_id' => $supplier->id, 'product_id' => $p->id, 'quantity' => 30, 'unit_cost' => $p->cost]);
                        app(Inventory::class)->receive($purchase, 30);
                    }
                }
                Purchase::create(['branch_id' => $branches[0]->id, 'supplier_id' => $supplier->id, 'product_id' => $products[0]->id, 'quantity' => 20, 'unit_cost' => $products[0]->cost]);
            }
            // Seed sales through the same transactional service used by POS and storefront.
            foreach (range(0, 17) as $n) {
                $branch = $branches[$n % count($branches)];
                $service = $services[$n % count($services)];
                $employee = collect($employees)->firstWhere('branch_id', $branch->id);
                $items = [['type' => 'service', 'id' => $service->id, 'quantity' => 1, 'employee_id' => $employee->id]];
                if ($products && $n % 3 === 0) {
                    $items[] = ['type' => 'product', 'id' => $products[$n % count($products)]->id, 'quantity' => 1];
                }
                $sale = app(Checkout::class)->create(['branch_id' => $branch->id, 'customer_id' => $customers[$n % count($customers)]->id, 'items' => $items, 'idempotency_key' => 'seed:'.$business->id.':'.$n]);
                app(Checkout::class)->pay($sale, $n % 2 ? 'cash' : 'bank', $sale->total, 'seed-payment:'.$sale->id);
            }
            if (in_array('ecommerce', $modules)) {
                foreach (range(0, 2) as $n) {
                    app(Checkout::class)->create(['branch_id' => $branches[0]->id, 'customer_id' => $customers[$n]->id, 'channel' => 'online', 'items' => [['type' => 'product', 'id' => $products[$n]->id, 'quantity' => 1]], 'idempotency_key' => 'seed-order:'.$business->id.':'.$n]);
                }
            }
            if (in_array('appointments', $modules)) {
                foreach (range(0, 5) as $n) {
                    $employee = $employees[$n % count($employees)];
                    $branch = collect($branches)->firstWhere('id', $employee->branch_id);
                    $time = now('Asia/Dhaka')->addDays(1 + intdiv($n, 3))->setTime(10 + ($n % 3) * 2, 0);
                    app(Appointments::class)->book(['branch_id' => $branch->id, 'customer_id' => $customers[$n]->id, 'service_id' => $services[$n % count($services)]->id, 'employee_id' => $employee->id, 'starts_at' => $time->toIso8601String(), 'source' => $n % 2 ? 'walk_in' : 'online']);
                }
            }
            if (in_array('expenses', $modules)) {
                $expense = Expense::create(['branch_id' => $branches[0]->id, 'category' => 'Utilities', 'description' => 'Monthly internet connection', 'amount' => 150000, 'date' => today(), 'method' => 'bank']);
                app(Operations::class)->expense($expense);
                foreach ($employees as $employee) {
                    app(Operations::class)->attendance(['branch_id' => $employee->branch_id, 'employee_id' => $employee->id, 'check_in' => now()->startOfDay()->addHours(3), 'check_out' => now()->startOfDay()->addHours(11)]);
                }
                Coupon::create(['code' => 'FRESH10', 'name' => 'Your fresh start · 10%', 'percent' => 10, 'usage_limit' => 100, 'expires_at' => today()->addMonths(3)]);
                $membership = MembershipPlan::create(['name' => 'The care club', 'price' => 50000, 'days' => 30, 'discount_percent' => 10]);
                app(Retention::class)->gift(['branch_id' => $branches[0]->id, 'customer_id' => $customers[0]->id, 'issued_value' => 100000, 'expires_at' => today()->addYear()]);
                AttendanceDevice::create(['branch_id' => $branches[0]->id, 'name' => 'Demo attendance terminal', 'provider' => 'fake', 'port' => 4370, 'employee_map' => ['101' => $employees[0]->id, '102' => $employees[1]->id]]);
                Campaign::create(['title' => 'A little time for you', 'body' => 'Your next good hair day is waiting. Book a visit with your neighborhood team. Demo marketing draft.']);
                Review::create(['customer_id' => $customers[0]->id, 'sale_id' => Sale::where('customer_id', $customers[0]->id)->where('channel', 'pos')->first()->id, 'rating' => 5, 'body' => 'Sample demo review: A thoughtful visit and a careful finish.', 'status' => 'published']);
                $plan = PackagePlan::create(['name' => 'Your regular ritual · 5 visits', 'service_id' => $services[0]->id, 'uses' => 5, 'valid_days' => 90, 'price' => $services[0]->price * 4]);
                app(Operations::class)->issuePackage(['branch_id' => $branches[0]->id, 'customer_id' => $customers[0]->id, 'package_plan_id' => $plan->id]);
                app(Operations::class)->payroll($employees[0]->id, now()->format('Y-m'));
            }
            $pages = [
                'about' => ['A space that feels like you.', 'We believe good care starts with listening. Our team brings thoughtful service, honest pricing, and a welcoming neighborhood space to every visit. This is a fictional demo business.'],
                'gallery' => ['A look around our space.', 'A warm welcome, a comfortable chair, and room to slow down. The illustrations below represent our demo spaces.'],
                'faq' => ['A few things you might wonder.', 'Can I walk in? Yes, subject to availability. Book online for a reserved time.\nHow do I pay? Pay at the branch by cash or arrange a manually verified transfer.\nCan I cancel? Cancel from your account at least two hours before your visit. For later changes, please call your branch.\nCan I buy a package? Ask reception to activate a prepaid package.'],
                'privacy' => ['Privacy & terms · template', 'Policy template for owner review. We collect account, booking, and order details to provide our services. Contact the branch to request access, corrections, or deletion subject to lawful financial retention. Marketing consent is optional. Never submit sensitive identity or payment credentials through the contact form. Owners must review and adapt this policy before launch.'],
                'refunds' => ['Cancellations, returns & delivery · template', 'Policy template for owner review. Cancel appointments at least two hours before the scheduled visit. Contact your branch for product returns and payment refunds; eligibility depends on product condition and the agreed policy. Local delivery is confirmed manually by the branch. Demo checkout adds no delivery fee. Owners must finalize these terms before live trading.'],
                'everyday-care' => ['Small rituals. Better hair days.', 'A good routine starts with the basics: a gentle wash, the right amount of product, and a cut that works with your natural texture. Ask your stylist for a routine tailored to you.'],
            ];
            foreach ($pages as $slug => [$title,$body]) {
                Content::create(['slug' => $slug, 'title' => $title, 'body' => str_replace('\\n', "\n", $body), 'type' => $slug === 'everyday-care' ? 'blog' : 'page', 'published' => true]);
            }
        }
        app(BusinessContext::class)->businessId = null;
    }
}
