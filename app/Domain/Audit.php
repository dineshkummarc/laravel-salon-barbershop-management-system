<?php

namespace App\Domain;

use App\Models\AuditLog;

class Audit
{
    public static function record(string $action, string $subject, array $changes = []): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'subject' => $subject, 'changes' => $changes]);
    }
}
