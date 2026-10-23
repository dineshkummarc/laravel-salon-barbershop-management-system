<?php

namespace App\Support;

use App\Models;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Validation\Rule;

class ResourceSchema
{
    public static function branch(string $key = 'branch_id'): Select
    {
        return Select::make($key)->label($key === 'destination_id' ? 'Destination branch' : 'Branch')->options(fn () => Models\Branch::where('active', true)->pluck('name', 'id'))->required()->searchable()->default(fn () => app(BusinessContext::class)->branchId())->rules([Rule::exists('branches', 'id')->where('business_id', app(BusinessContext::class)->id())]);
    }

    public static function relation(string $name, string $model, string $label = 'name'): Select
    {
        return Select::make($name)->options(fn () => $model::query()->pluck($label, 'id'))->searchable()->required()->rules([Rule::exists((new $model)->getTable(), 'id')->where('business_id', app(BusinessContext::class)->id())]);
    }

    public static function money(string $name): TextInput
    {
        return TextInput::make($name)->label(ucwords(str_replace('_', ' ', $name)).' (BDT)')->numeric()->minValue(0)->step('0.01')->required()->formatStateUsing(fn ($state) => $state === null ? null : number_format($state / 100, 2, '.', ''))->dehydrateStateUsing(fn ($state) => Money::parse($state));
    }

    public static function fields(string $model): array
    {
        $text = fn ($n) => TextInput::make($n)->required()->maxLength(180);
        $num = fn ($n, $default = 1) => TextInput::make($n)->integer()->minValue(1)->default($default)->required();

        return match ($model) {
            'Branch' => [$text('name'), $text('address'), $text('phone'), TimePicker::make('opens_at')->seconds(false)->default('09:00')->required(), TimePicker::make('closes_at')->seconds(false)->default('21:00')->required(), Select::make('disabled_modules')->multiple()->options(Modules::ALL), Toggle::make('active')->default(true)],
            'Customer' => [$text('name'), $text('phone'), TextInput::make('email')->email(), Textarea::make('notes'), Toggle::make('marketing_consent')],
            'Employee' => [self::branch(), $text('name'), $text('title')->default('Stylist'), TextInput::make('phone'), self::money('salary'), TextInput::make('commission_basis_points')->integer()->minValue(0)->maxValue(10000)->default(1000)->required()->helperText('1000 = 10% commission'), Toggle::make('active')->default(true), Select::make('working_days')->multiple()->options([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday']), TimePicker::make('shift_start')->seconds(false)->default('09:00')->required(), TimePicker::make('shift_end')->seconds(false)->default('21:00')->required()],
            'Service' => [$text('name'), TextInput::make('name_bn'), $text('slug')->helperText('Bengali Unicode slugs supported'), $text('category')->default('Hair'), self::money('price'), $num('duration', 30), TextInput::make('buffer')->integer()->minValue(0)->default(5)->required(), Textarea::make('description'), Toggle::make('active')->default(true)],
            'Product' => [$text('name'), TextInput::make('name_bn'), $text('slug'), $text('sku'), $text('category')->default('Hair care'), self::money('price'), self::money('cost'), TextInput::make('reorder_level')->integer()->minValue(0)->default(5)->required(), Textarea::make('description'), Toggle::make('active')->default(true)],
            'Booking' => [self::branch(), self::relation('customer_id', Models\Customer::class), self::relation('service_id', Models\Service::class), self::relation('employee_id', Models\Employee::class), DateTimePicker::make('starts_at')->timezone('Asia/Dhaka')->seconds(false)->required()->minDate(now()), Select::make('source')->options(['reception' => 'Reception', 'walk_in' => 'Walk-in'])->default('reception')->required(), Textarea::make('notes')],
            'Supplier' => [$text('name'), $text('phone'), TextInput::make('email')->email(), Textarea::make('address')],
            'Purchase' => [self::branch(), self::relation('supplier_id', Models\Supplier::class), self::relation('product_id', Models\Product::class), $num('quantity'), self::money('unit_cost')],
            'Transfer' => [self::branch(), self::branch('destination_id'), self::relation('product_id', Models\Product::class), $num('quantity')],
            'Expense' => [self::branch(), $text('category'), $text('description'), self::money('amount'), DatePicker::make('date')->default(today())->required(), Select::make('method')->options(['cash' => 'Cash', 'bank' => 'Bank'])->default('cash')->required()],
            'Attendance' => [self::branch(), self::relation('employee_id', Models\Employee::class), DateTimePicker::make('check_in')->timezone('Asia/Dhaka')->required(), DateTimePicker::make('check_out')->timezone('Asia/Dhaka')->after('check_in')],
            'Payroll' => [self::relation('employee_id', Models\Employee::class), TextInput::make('month')->required()->regex('/^\d{4}-(0[1-9]|1[0-2])$/')->default(now()->format('Y-m')), self::money('allowance')->default('0'), self::money('deduction')->default('0')],
            'PackagePlan' => [$text('name'), self::relation('service_id', Models\Service::class), $num('uses', 5), $num('valid_days', 90), self::money('price'), Toggle::make('active')->default(true)],
            'CustomerPackage' => [self::branch(), self::relation('customer_id', Models\Customer::class), self::relation('package_plan_id', Models\PackagePlan::class)],
            'CashSession' => [self::branch(), self::money('opening_float')->default('0')],
            'Content' => [$text('title'), $text('slug'), Select::make('type')->options(['page' => 'Page', 'blog' => 'Blog', 'faq' => 'FAQ'])->default('page')->required(), Textarea::make('body')->rows(12)->required()->columnSpanFull(), Toggle::make('published')],
            'Enquiry' => [$text('name'), $text('phone'), Textarea::make('message')->required(), Select::make('status')->options(['new' => 'New', 'in_progress' => 'In progress', 'resolved' => 'Resolved'])->required()],
            'AttendanceDevice' => [self::branch(), $text('name'), Select::make('provider')->options(['fake' => 'Demo device (simulated)', 'zkteco' => 'ZKTeco', 'hikvision' => 'Hikvision', 'dahua' => 'Dahua', 'suprema' => 'Suprema'])->default('fake')->required(), TextInput::make('host')->helperText('Live hosts require SALON_ATTENDANCE_HOSTS allowlisting.'), TextInput::make('port')->integer()->minValue(1)->maxValue(65535)->default(4370)->required(), KeyValue::make('employee_map')->keyLabel('Device user ID')->valueLabel('Employee database ID')->required(), KeyValue::make('credentials')->helperText('Optional username, password, token. Stored encrypted; never include biometric templates.')],
            'TimeOff' => [self::branch(), self::relation('employee_id', Models\Employee::class), DateTimePicker::make('starts_at')->timezone('Asia/Dhaka')->required(), DateTimePicker::make('ends_at')->timezone('Asia/Dhaka')->after('starts_at')->required(), $text('reason')],
            'Coupon' => [$text('code')->regex('/^[A-Z0-9_-]{3,30}$/'), $text('name'), TextInput::make('percent')->integer()->minValue(1)->maxValue(80)->required(), $num('usage_limit', 100), DatePicker::make('expires_at')->required(), Toggle::make('active')->default(true)],
            'GiftCard' => [self::branch(), self::relation('customer_id', Models\Customer::class)->required(false), self::money('issued_value'), DatePicker::make('expires_at')->minDate(today())->required()],
            'MembershipPlan' => [$text('name'), self::money('price'), $num('days', 30), TextInput::make('discount_percent')->integer()->minValue(0)->maxValue(50)->default(10)->required(), Toggle::make('active')->default(true)],
            'Membership' => [self::branch(), self::relation('customer_id', Models\Customer::class), self::relation('membership_plan_id', Models\MembershipPlan::class)],
            'Campaign' => [$text('title'), Textarea::make('body')->rows(8)->required()->maxLength(3000)->helperText('Sent only to customers with marketing consent. Configure mail before live use.')],
            'ServiceRecipe' => [self::relation('service_id', Models\Service::class), self::relation('product_id', Models\Product::class), $num('quantity')->helperText('Whole stock units consumed per service. Set your product stock unit appropriately.')],
            'StockAdjustment' => [self::branch(), self::relation('product_id', Models\Product::class), TextInput::make('quantity')->integer()->required()->helperText('Positive for stock added; negative for wastage or shortage.'), $text('reason')],
            default => [],
        };
    }

    public static function columns(string $model): array
    {
        $text = fn ($n) => TextColumn::make($n)->searchable()->sortable();
        $money = fn ($n) => TextColumn::make($n)->formatStateUsing(fn ($state) => Money::format($state))->sortable();
        $status = fn ($n = 'status') => TextColumn::make($n)->badge()->color(fn ($state) => match ($state) {
            'completed','paid','received','active','posted' => 'success','cancelled','refunded','suspended' => 'danger',default => 'warning'
        });
        $branch = TextColumn::make('branch.name')->label('Branch')->toggleable();

        return match ($model) {
            'Branch' => [$text('name'), $text('address'), $text('phone'), IconColumn::make('active')->boolean()],
            'Customer' => [$text('name'), $text('phone'), $text('email'), $text('loyalty_points')],
            'Employee' => [$text('name'), $branch, $text('title'), $money('salary'), IconColumn::make('active')->boolean()],
            'Service' => [$text('name'), $text('category'), $money('price'), $text('duration')->suffix(' min'), IconColumn::make('active')->boolean()],
            'Product' => [$text('name'), $text('sku'), $text('category'), $money('price'), $money('cost'), IconColumn::make('active')->boolean()],
            'Stock' => [$text('product.name'), $branch, $text('quantity')],
            'StockMovement' => [$text('product.name'), $branch, $text('quantity'), $text('reason'), $text('reference'), $text('created_at')->dateTime('d M, H:i', 'Asia/Dhaka')],
            'Booking' => [$text('customer.name'), $text('service.name'), $text('employee.name'), $branch, $text('starts_at')->dateTime('d M, H:i', 'Asia/Dhaka'), $status()],
            'Sale' => [$text('number'), $text('customer.name')->default('Walk-in'), $branch, $text('channel')->badge(), $money('total'), $money('paid'), $status(), $status('fulfillment'), $text('created_at')->dateTime('d M, H:i', 'Asia/Dhaka')],
            'Supplier' => [$text('name'), $text('phone'), $text('email')],
            'Purchase' => [$text('supplier.name'), $text('product.name'), $branch, $text('quantity'), $text('received'), $money('unit_cost'), $status()],
            'Transfer' => [$text('product.name'), $branch, $text('destination.name'), $text('quantity'), $status()],
            'Expense' => [$text('description'), $text('category'), $branch, $money('amount'), $text('date')->date(), $status()],
            'LedgerEntry' => [$text('journal.description'), $branch, $text('account'), $money('debit'), $money('credit'), $text('created_at')->dateTime('d M Y')],
            'Attendance' => [$text('employee.name'), $branch, $text('date')->date(), $text('check_in')->dateTime('H:i', 'Asia/Dhaka'), $text('check_out')->dateTime('H:i', 'Asia/Dhaka'), $text('source')->badge()],
            'Payroll' => [$text('employee.name'), $text('month'), $money('salary'), $money('commission'), $money('total'), $status()],
            'PackagePlan' => [$text('name'), $text('service.name'), $text('uses'), $text('valid_days'), $money('price')],
            'CustomerPackage' => [$text('customer.name'), $text('packagePlan.name'), $text('remaining'), $text('expires_at')->date(), $money('price')],
            'CashSession' => [$branch, $text('user.name'), $money('opening_float'), $money('expected_cash'), $money('counted_cash'), $text('opened_at')->dateTime(), $text('closed_at')->dateTime()],
            'Content' => [$text('title'), $text('type')->badge(), $text('slug'), IconColumn::make('published')->boolean()],
            'Enquiry' => [$text('name'), $text('phone'), $text('message')->limit(60), $status()],
            'CreditNote' => [$text('sale.number'), $money('amount'), $money('tax'), $text('reason'), $text('created_at')->dateTime()],
            'AuditLog' => [$text('user.name'), $text('action'), $text('subject'), $text('created_at')->dateTime('d M, H:i', 'Asia/Dhaka')],
            'AttendanceDevice' => [$text('name'), $branch, $text('provider'), $status(), $text('last_sync_at')->dateTime()],
            'AttendanceEvent' => [$text('employee.name'), $text('device_user_id'), $text('punched_at')->dateTime('d M, H:i', 'Asia/Dhaka'), $text('event_hash')->limit(20)],
            'TimeOff' => [$text('employee.name'), $branch, $text('starts_at')->dateTime(), $text('ends_at')->dateTime(), $text('reason')],
            'Coupon' => [$text('code'), $text('name'), $text('percent')->suffix('%'), $text('used'), $text('usage_limit'), $text('expires_at')->date(), IconColumn::make('active')->boolean()],
            'GiftCard' => [$text('code')->copyable(), $text('customer.name'), $branch, $money('issued_value'), $money('balance'), $text('expires_at')->date()],
            'MembershipPlan' => [$text('name'), $money('price'), $text('days'), $text('discount_percent')->suffix('%')],
            'Membership' => [$text('customer.name'), $text('membershipPlan.name'), $text('ends_at')->date(), $text('discount_percent')->suffix('%'), $status()],
            'LoyaltyEntry' => [$text('customer.name'), $text('sale.number'), $text('points'), $text('reason'), $text('created_at')->dateTime()],
            'Review' => [$text('customer.name'), $text('rating')->suffix('/5'), $text('body')->limit(80), $status()],
            'Campaign' => [$text('title'), $status(), $text('sent'), $text('created_at')->dateTime()],
            'MessageDelivery' => [$text('campaign.title'), $text('customer.name'), $status(), $text('error')->limit(50)],
            'ServiceRecipe' => [$text('service.name'), $text('product.name'), $text('quantity')],
            'StockAdjustment' => [$text('product.name'), $branch, $text('quantity'), $text('reason'), $status()],
            'CommissionAdjustment' => [$text('employee.name'), $text('sale.number'), $money('amount'), $text('month')],
            default => [$text('id')]
        };
    }
}
