<?php

namespace OiLab\OiLaravelRaggable\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use OiLab\OiLaravelRaggable\Jobs\GenerateEmbeddingJob;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;
use OiLab\OiLaravelRaggable\Services\EmbeddingService;

/**
 * Backfill or refresh the embeddings of the models registered under
 * `oi-laravel-raggable.embeddables`.
 */
class EmbedCommand extends Command
{
    protected $signature = 'raggable:embed
                            {targets?* : Registry keys to embed; all when omitted}
                            {--fresh : Regenerate even when the content hash is unchanged}
                            {--chunk=100 : Number of models fetched per database chunk}
                            {--sync : Generate inline instead of dispatching queued jobs}';

    protected $description = 'Backfill or refresh the semantic embeddings of registered models';

    public function handle(EmbeddingService $embeddings): int
    {
        $registry = OiLaravelRaggable::embeddables();

        if ($registry === []) {
            $this->warn('No embeddable models registered. Add them to oi-laravel-raggable.embeddables.');

            return self::SUCCESS;
        }

        $targets = $this->argument('targets') ?: array_keys($registry);

        foreach ($targets as $target) {
            $class = $registry[$target] ?? null;

            if ($class === null) {
                $this->error("Unknown target [{$target}]. Valid targets: ".implode(', ', array_keys($registry)));

                return self::FAILURE;
            }

            $this->embedTarget($target, $class, $embeddings);
        }

        return self::SUCCESS;
    }

    /**
     * @param  class-string  $class
     */
    private function embedTarget(string $target, string $class, EmbeddingService $embeddings): void
    {
        $count = 0;

        $class::query()
            ->with('embedding')
            ->chunkById((int) $this->option('chunk'), function ($models) use ($embeddings, &$count): void {
                if ($this->option('sync')) {
                    $count += $embeddings->embedMany($models, force: (bool) $this->option('fresh'));

                    return;
                }

                foreach ($models as $model) {
                    if ($this->option('fresh') || $model->needsEmbeddingRefresh()) {
                        Bus::dispatch(new GenerateEmbeddingJob($model));
                        $count++;
                    }
                }
            });

        $action = $this->option('sync') ? 'embedded' : 'queued';
        $this->info("[{$target}] {$count} {$action}.");
    }
}
