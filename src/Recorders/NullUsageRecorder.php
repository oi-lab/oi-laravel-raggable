<?php

namespace OiLab\OiLaravelRaggable\Recorders;

use OiLab\OiLaravelRaggable\Contracts\UsageRecorder;

/**
 * No-op usage recorder, used when tracking is disabled.
 */
class NullUsageRecorder implements UsageRecorder
{
    public function record(string $label, string $provider, string $model, int $tokens): void
    {
        //
    }
}
