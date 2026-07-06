<?php

namespace OiLab\OiLaravelRaggable\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OiLab\OiLaravelRaggable\Contracts\Embeddable;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;
use OiLab\OiLaravelRaggable\Services\EmbeddingService;

/**
 * Refresh the embedding of a single model on the configured queue. Unique per
 * model so rapid successive saves collapse to one regeneration.
 */
class GenerateEmbeddingJob implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Deleted models cannot be re-resolved when the job runs.
     */
    public bool $deleteWhenMissingModels = true;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public Model&Embeddable $model)
    {
        $this->onQueue(OiLaravelRaggable::queue());
    }

    public function uniqueId(): string
    {
        return $this->model->getMorphClass().':'.$this->model->getKey();
    }

    public function handle(EmbeddingService $embeddings): void
    {
        $embeddings->embed($this->model);
    }
}
