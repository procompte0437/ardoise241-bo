<?php

namespace App\Models;

use App\Services\AccountService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'phone', 'name', 'code_hash', 'local_salt', 'local_hash', 'trial_started_at',
        'trial_days', 'paid_until', 'blocked', 'rev', 'last_sync_at', 'last_device', 'note',
    ];

    protected $hidden = ['code_hash', 'local_hash', 'local_salt'];

    protected function casts(): array
    {
        return [
            'trial_started_at' => 'datetime',
            'paid_until' => 'datetime',
            'last_sync_at' => 'datetime',
            'trial_days' => 'integer',
            'blocked' => 'boolean',
            'rev' => 'integer',
        ];
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(AccountToken::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(AccountPerson::class);
    }

    public function foods(): HasMany
    {
        return $this->hasMany(AccountFood::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AccountEntry::class);
    }

    /** Durée d'essai qui s'applique à ce compte : la sienne, sinon la durée globale. */
    public function effectiveTrialDays(): int
    {
        return $this->trial_days ?? AccountService::globalTrialDays();
    }

    /** Date à laquelle l'accès s'arrête : fin d'abonnement si elle est plus loin que la fin d'essai. */
    public function accessEndsAt(): CarbonInterface
    {
        $trialEnd = $this->trial_started_at->copy()->addDays($this->effectiveTrialDays());

        if ($this->paid_until && $this->paid_until->greaterThan($trialEnd)) {
            return $this->paid_until;
        }

        return $trialEnd;
    }

    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->accessEndsAt());
    }

    public function daysLeft(): int
    {
        return max(0, (int) ceil(now()->diffInSeconds($this->accessEndsAt(), false) / 86400));
    }

    /** etat : actif, essai, expire, bloque. */
    public function status(): string
    {
        if ($this->blocked) {
            return 'bloque';
        }
        if ($this->isExpired()) {
            return 'expire';
        }

        return $this->paid_until && $this->paid_until->isFuture() ? 'abonne' : 'essai';
    }
}
