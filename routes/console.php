<?php

use App\Domain\Audit;
use App\Jobs\BookingReminder;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Support\BusinessContext;
use App\Support\Modules;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Console\Input\InputOption;

Artisan::command('salon:demo-reset', function () {
    if (app()->environment('production')) {
        $this->error('Forbidden in production.');

        return 1;
    }
    if (! $this->option('force')) {
        $this->error('This deletes the configured database. Pass --force explicitly.');

        return 1;
    }
    $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
})->purpose('Rebuild the local demo database (destructive)')->addOption('force', null, InputOption::VALUE_NONE, 'Explicitly confirm deletion');
Artisan::command('salon:check', function () {
    $unbalanced = DB::table('ledger_entries')->selectRaw('journal_id,SUM(debit)-SUM(credit) as difference')->groupBy('journal_id')->havingRaw('SUM(debit) <> SUM(credit)')->count();
    $this->info($unbalanced ? 'Unbalanced journals: '.$unbalanced : 'All journals balance.');

    return $unbalanced ? 1 : 0;
})->purpose('Verify financial journal balance');
Schedule::command('queue:prune-failed --hours=168')->daily();

Artisan::command('salon:reminders', function () {
    foreach (Business::where('status', 'active')->get() as $business) {
        app(BusinessContext::class)->set($business->id, true);
        if (! Modules::enabled('appointments')) {
            continue;
        }
        foreach (Booking::where('status', 'confirmed')->whereBetween('starts_at', [now(), now()->addHours(3)])->get() as $booking) {
            BookingReminder::dispatch($business->id, $booking->id);
        }
    }
    $this->info('Eligible reminders queued. Existing delivery records prevent repeat sends.');
})->purpose('Queue upcoming appointment reminders');
Schedule::command('salon:reminders')->everyFifteenMinutes()->withoutOverlapping();

Artisan::command('salon:subscriptions', function () {
    foreach (Business::where('status', 'active')->whereNotNull('subscription_ends_at')->get() as $business) {
        if ($business->subscription_ends_at->copy()->addDays($business->grace_days)->endOfDay()->isPast()) {
            $business->update(['status' => 'suspended']);
            app(BusinessContext::class)->set($business->id, true);
            Audit::record('subscription.suspended', 'business:'.$business->id);
        }
    }
    $this->info('Subscription periods checked.');
})->purpose('Suspend expired subscriptions after their grace period');
Schedule::command('salon:subscriptions')->daily()->withoutOverlapping();

Artisan::command('salon:install', function () {
    if (Business::exists()) {
        $this->error('A business already exists. Use the platform workspace to add more businesses.');

        return 1;
    }
    $name = $this->ask('Business name');
    $email = $this->ask('Owner email');
    $phone = $this->ask('Business phone');
    $password = $this->secret('Owner password (10+ characters, letters and numbers)');
    $validator = validator(compact('name', 'email', 'phone', 'password'), ['name' => 'required|string|max:120', 'email' => 'required|email|unique:users,email', 'phone' => 'required|string|max:30', 'password' => ['required', Password::min(10)->letters()->numbers()]]);
    if ($validator->fails()) {
        $this->error($validator->errors()->first());

        return 1;
    }
    DB::transaction(function () use ($name, $email, $phone, $password) {
        $business = Business::create(['name' => $name, 'slug' => universal_slug($name), 'phone' => $phone, 'email' => $email, 'modules' => ['services', 'pos', 'crm', 'hr', 'content', 'reports'], 'plan' => 'basic']);
        app(BusinessContext::class)->set($business->id, true);
        Branch::create(['name' => 'Main branch', 'address' => 'Update your branch address', 'phone' => $phone]);
        User::create(['name' => 'Business Owner', 'email' => $email, 'password' => $password, 'role' => 'owner', 'business_id' => $business->id]);
    });
    $this->info('Business created. Sign in at /admin/login, configure your branch, services and content, then enable the public website.');
})->purpose('Create the first real business and owner without demo data');
