<?php

namespace App\Support;

use App\Models\Business;

class BusinessContext
{
    public ?int $businessId = null;

    public bool $public = false;

    public function id(): ?int
    {
        return $this->businessId ?? auth()->user()?->business_id ?? (auth()->user()?->role === 'platform' ? session('business_id') : null);
    }

    public function branchId(): ?int
    {
        if ($this->public) {
            return null;
        }
        $user = auth()->user();
        if ($user?->branch_id) {
            return $user->branch_id;
        }

        return session()->has('branch_id') ? (int) session('branch_id') : null;
    }

    public function business(): ?Business
    {
        return $this->id() ? Business::find($this->id()) : null;
    }

    public function set(int $id, bool $public = false): void
    {
        $this->businessId = $id;
        $this->public = $public;
    }
}
