<?php

namespace OiLab\OiLaravelRaggable\Contracts;

/**
 * Records the token usage of an embedding request. The default implementation
 * writes to oi-lab/oi-laravel-ai so embedding cost is reported alongside agent
 * usage; a null implementation is used when tracking is disabled.
 */
interface UsageRecorder
{
    /**
     * @param  string  $label  Groups the request in reports (e.g. the embeddable morph type).
     */
    public function record(string $label, string $provider, string $model, int $tokens): void;
}
