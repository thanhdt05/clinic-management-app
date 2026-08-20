<?php

namespace App\Listeners;

use App\Events\ActivityLogged;
use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;

class LogActivity implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * List of sensitive keys that should never be stored in activity log metadata.
     *
     * @var array<int, string>
     */
    private const array SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'remember_token',
        'access_token',
        'refresh_token',
        'secret',
        'credit_card',
        'cvv',
    ];

    /**
     * Handle the event.
     */
    public function handle(ActivityLogged $event): void
    {
        $userId = $event->causer?->id ?? Auth::id();
        $meta = $this->sanitizeMeta($event->meta);

        ActivityLog::create([
            'user_id' => $userId,
            'subject_type' => $event->subject->getMorphClass(),
            'subject_id' => $event->subject->getKey(),
            'action' => $event->action,
            'meta' => $meta,
        ]);
    }

    public function sanitizeMeta(?array $meta): ?array
    {
        if ($meta === null) {
            return null;
        }

        $sanitized = [];

        foreach ($meta as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $sanitized[$key] = '********';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeMeta($value);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
