<?php

namespace App\Domain;

use App\Models\AttendanceDevice;
use App\Models\AttendanceEvent;
use App\Models\Employee;
use App\Support\Modules;
use Illuminate\Validation\ValidationException;
use ImranDevBd\AttendanceHub\AttendanceHubManager;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\Enums\PunchType;

class DeviceSync
{
    public function sync(AttendanceDevice $device): int
    {
        Modules::require('attendance', $device->branch_id);
        if ($device->provider === 'fake' && ! config('salon.demo')) {
            throw ValidationException::withMessages(['device' => 'Simulated devices are disabled outside demo mode.']);
        }
        if ($device->provider !== 'fake' && ! in_array($device->host, config('salon.attendance_hosts', []), true)) {
            throw ValidationException::withMessages(['device' => 'Allowlist this device host in SALON_ATTENDANCE_HOSTS before connecting.']);
        }
        $manager = new AttendanceHubManager(app());
        if ($device->provider === 'fake') {
            $punches = [];
            foreach ($device->employee_map as $external => $employeeId) {
                $employee = Employee::where('branch_id', $device->branch_id)->findOrFail($employeeId);
                $punches[] = new AttendancePunch((string) $external, now('Asia/Dhaka')->startOfDay()->setTime(9, 0)->utc(), punchType: PunchType::CHECK_IN, deviceId: 'demo:'.$device->id);
                $punches[] = new AttendancePunch((string) $external, now('Asia/Dhaka')->startOfDay()->setTime(17, 0)->utc(), punchType: PunchType::CHECK_OUT, deviceId: 'demo:'.$device->id);
            }$driver = $manager->fake($punches);
        } else {
            $driver = $manager->provider($device->provider);
        }
        $credentials = $device->credentials ?? [];
        $driver->connect(new DeviceConnection(ip: $device->host ?? '127.0.0.1', port: (int) ($device->port ?? 4370), username: $credentials['username'] ?? null, password: $credentials['password'] ?? null, token: $credentials['token'] ?? null));
        $new = 0;
        try {
            foreach ($driver->pullAttendanceLogs() as $punch) {
                $employeeId = $device->employee_map[$punch->deviceUserId] ?? null;
                if ($employeeId) {
                    Employee::where('branch_id', $device->branch_id)->findOrFail($employeeId);
                }
                $event = AttendanceEvent::firstOrCreate(['event_hash' => $punch->getHash('business:'.$device->business_id.':device:'.$device->id)], ['branch_id' => $device->branch_id, 'attendance_device_id' => $device->id, 'employee_id' => $employeeId, 'device_user_id' => $punch->deviceUserId, 'punched_at' => $punch->punchedAt->copy()->utc(), 'payload' => $punch->toArray()]);
                if ($event->wasRecentlyCreated) {
                    $new++;
                }
            }
            $events = AttendanceEvent::where('attendance_device_id', $device->id)->whereNotNull('employee_id')->with('employee')->get();
            foreach ($events->groupBy(function ($event) {
                $local = $event->punched_at->copy()->timezone('Asia/Dhaka');
                $employee = $event->employee;
                if ($employee->shift_end < $employee->shift_start && $local->format('H:i') <= $employee->shift_end) {
                    $local->subDay();
                }

return $event->employee_id.':'.$local->toDateString();
            }) as $group) {
                $first = $group->sortBy('punched_at')->first();
                $last = $group->sortByDesc('punched_at')->first();
                app(Operations::class)->attendance(['branch_id' => $device->branch_id, 'employee_id' => $first->employee_id, 'check_in' => $first->punched_at, 'check_out' => $first->id !== $last->id ? $last->punched_at : null, 'source' => $device->provider === 'fake' ? 'device_demo' : 'device']);
            }
            $device->update(['status' => 'connected', 'last_sync_at' => now()]);
            Audit::record('attendance.synced', 'device:'.$device->id, ['new_events' => $new]);

            return $new;
        } catch (\Throwable $e) {
            $device->update(['status' => 'sync_failed']);
            throw $e;
        } finally {
            $driver->disconnect();
        }
    }
}
