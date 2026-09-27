<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    /**
     * Keys that should never be stored in activity log properties.
     *
     * @var list<string>
     */
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
        'secret',
        'api_key',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Activity $activity): void {
            $properties = $activity->properties ? $activity->properties->toArray() : [];

            // Sanitize sensitive attributes recursively
            $properties = static::sanitizeProperties($properties);

            // Enrich with request context if running in HTTP request
            if (request()) {
                $request = request();

                if (! isset($properties['ip'])) {
                    $properties['ip'] = $request->ip();
                }

                if (! isset($properties['user_agent'])) {
                    $properties['user_agent'] = $request->userAgent();
                }

                if (! isset($properties['correlation_id'])) {
                    $properties['correlation_id'] = $request->header('X-Correlation-ID')
                        ?? $request->attributes->get('correlation_id');
                }
            }

            $activity->properties = collect($properties);
        });
    }

    /**
     * Sanitize array to strip sensitive values.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function sanitizeProperties(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = static::sanitizeProperties($value);
            } elseif (in_array(strtolower((string) $key), static::$sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
}
