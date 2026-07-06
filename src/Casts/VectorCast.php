<?php

namespace OiLab\OiLaravelRaggable\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a vector column to a `list<float>` and back. The stored form is a
 * bracketed comma list (`[0.1,0.2,...]`) — valid JSON for the portable
 * "database" driver and a valid pgvector text input for the "pgvector" driver,
 * so the same model works unchanged across both.
 *
 * @implements CastsAttributes<list<float>|null, list<float>|null>
 */
class VectorCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return list<float>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return array_map(static fn ($v): float => (float) $v, array_values($value));
        }

        $decoded = json_decode((string) $value, true);

        if (! is_array($decoded)) {
            return null;
        }

        return array_map(static fn ($v): float => (float) $v, array_values($decoded));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $floats = array_map(static fn ($v): float => (float) $v, array_values((array) $value));

        return '['.implode(',', $floats).']';
    }
}
