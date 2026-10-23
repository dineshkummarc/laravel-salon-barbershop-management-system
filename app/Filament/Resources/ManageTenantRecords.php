<?php

namespace App\Filament\Resources;

use App\Domain\Appointments;
use App\Domain\Operations;
use App\Domain\Retention;
use App\Models\Branch;
use App\Models\CashSession;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageTenantRecords extends ManageRecords
{
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->using(fn (array $data): Model => $this->handleRecordCreation($data))];
    }

    protected function handleRecordCreation(array $data): Model
    {
        abort_unless(static::getResource()::canCreate(), 403);
        $model = static::getModel();
        if (isset($data['branch_id'])) {
            Branch::findOrFail($data['branch_id']);
        }

        return match (class_basename($model)) {
            'Booking' => app(Appointments::class)->book(array_merge($data, ['starts_at' => Carbon::parse($data['starts_at'], 'UTC')->toIso8601String()])),
            'Attendance' => app(Operations::class)->attendance($data),
            'Payroll' => app(Operations::class)->payroll((int) $data['employee_id'], $data['month'], (int) $data['allowance'], (int) $data['deduction']),
            'GiftCard' => app(Retention::class)->gift($data),
            'Membership' => app(Retention::class)->membership($data),
            'CustomerPackage' => app(Operations::class)->issuePackage($data),
            'CashSession' => $this->openRegister($data),
            default => $model::create($data),
        };
    }

    private function openRegister(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            Branch::whereKey($data['branch_id'])->lockForUpdate()->firstOrFail();
            if (CashSession::where('user_id', auth()->id())->whereNull('closed_at')->exists()) {
                throw ValidationException::withMessages(['opening_float' => 'Close your existing register first.']);
            }

            return CashSession::create($data + ['user_id' => auth()->id(), 'opened_at' => now()]);
        });
    }
}
