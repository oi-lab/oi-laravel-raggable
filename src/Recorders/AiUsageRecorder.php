<?php

namespace OiLab\OiLaravelRaggable\Recorders;

use Illuminate\Support\Facades\Schema;
use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;
use OiLab\OiLaravelAi\Models\AiRequest;
use OiLab\OiLaravelRaggable\Contracts\UsageRecorder;

/**
 * Records each embedding request through oi-lab/oi-laravel-ai: an `ai_requests`
 * row carrying the token count, linked to the AI catalog when the provider and
 * model are known. Writes are best-effort — a missing table (the AI migrations
 * have not run) or an empty token count is silently skipped so embedding never
 * fails on a reporting concern.
 */
class AiUsageRecorder implements UsageRecorder
{
    public function record(string $label, string $provider, string $model, int $tokens): void
    {
        if ($tokens <= 0 || ! Schema::hasTable('ai_requests')) {
            return;
        }

        AiRequest::query()->create([
            'prompt_type' => 'embedding:'.$label,
            'tokens_input' => $tokens,
            'ai_provider_id' => $this->providerId($provider),
            'ai_model_id' => $this->modelId($model),
        ]);
    }

    protected function providerId(string $provider): ?int
    {
        if ($provider === '' || ! Schema::hasTable('ai_providers')) {
            return null;
        }

        return AiProvider::query()->where('code', $provider)->value('id');
    }

    protected function modelId(string $model): ?int
    {
        if ($model === '' || ! Schema::hasTable('ai_models')) {
            return null;
        }

        return AiModel::query()->where('code', $model)->value('id');
    }
}
