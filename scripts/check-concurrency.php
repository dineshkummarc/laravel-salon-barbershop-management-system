<?php

/** Destructive test against a disposable database only. */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
use App\Domain\Appointments;
use App\Domain\Checkout;
use App\Models\Stock;
use App\Models\User;
use App\Support\BusinessContext;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'haircut_concurrency_test' || ! function_exists('pcntl_fork')) {
    fwrite(STDERR, "Requires MySQL database named haircut_concurrency_test and ext-pcntl. This script rebuilds that disposable database.\n");
    exit(1);
}
Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
app(BusinessContext::class)->set(1);
auth()->setUser(User::where('email', 'owner@example.test')->first());
Stock::where('branch_id', 1)->where('product_id', 1)->update(['quantity' => 1]);
function race(callable $work): array
{
    $barrier = tempnam(sys_get_temp_dir(), 'groomos-race-');
    unlink($barrier);
    $children = [];
    DB::disconnect();
    foreach ([1, 2] as $worker) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('fork failed');
        }if ($pid === 0) {
            DB::purge();
            while (! is_file($barrier)) {
                usleep(1000);
            }try {
                $work($worker);
                exit(0);
            } catch (ValidationException $e) {
                exit(20);
            } catch (Throwable $e) {
                fwrite(STDERR, $e->getMessage()."\n");
                exit(99);
            }
        }$children[] = $pid;
    }
    touch($barrier);
    $statuses = [];
    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
        $statuses[] = pcntl_wexitstatus($status);
    }unlink($barrier);
    DB::purge();
    sort($statuses);

    return $statuses;
}
$stock = race(fn ($worker) => app(Checkout::class)->create(['branch_id' => 1, 'items' => [['type' => 'product', 'id' => 1, 'quantity' => 1]], 'idempotency_key' => 'race-stock:'.$worker]));
$time = now('Asia/Dhaka')->addDays(20)->setTime(11, 0)->toIso8601String();
$booking = race(fn () => app(Appointments::class)->book(['branch_id' => 1, 'customer_id' => 1, 'service_id' => 1, 'employee_id' => 1, 'starts_at' => $time]));
$remaining = Stock::where('branch_id', 1)->where('product_id', 1)->value('quantity');
if ($stock !== [0, 20] || $booking !== [0, 20] || $remaining !== 0) {
    echo json_encode(compact('stock', 'booking', 'remaining'))."\n";
    exit(1);
}
echo "PASS: simultaneous checkout allows exactly one sale; stock remains zero.\nPASS: simultaneous booking allows exactly one appointment.\n";
