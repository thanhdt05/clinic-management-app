<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityLogged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $action  Action identifier (from ActivityAction constants)
     * @param  Model  $subject  Target Eloquent Model
     * @param  User|null  $causer  Actor who performed the action
     * @param  array<string, mixed>|null  $meta  Contextual metadata
     */
    public function __construct(
        public string $action,
        public Model $subject,
        public ?User $causer = null,
        public ?array $meta = null
    ) {}
}
