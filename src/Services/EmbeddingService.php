<?php

namespace OiLab\OiLaravelRaggable\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use OiLab\OiLaravelRaggable\Contracts\Embeddable;
use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\Models\Embedding;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;

/**
 * Generates and persists embeddings for embeddable models through the
 * configured Embedder. Source text is split into overlapping chunks so a single
 * record never exceeds the provider token limit: the embedding row is a
 * document header (content hash, source text and centroid vector) and each
 * chunk holds its own vector for precise similarity search.
 */
class EmbeddingService
{
    public function __construct(
        private readonly TextChunker $chunker,
        private readonly Embedder $embedder,
    ) {}

    /**
     * Generate (or refresh) the embedding of a single model. Returns null when
     * the model has no embeddable text; skips the embedder call when the content
     * hash is unchanged unless forced.
     */
    public function embed(Model&Embeddable $model, bool $force = false): ?Embedding
    {
        $text = trim($model->toEmbeddingText());

        if ($text === '') {
            $model->embedding()->delete();

            return null;
        }

        $hash = hash('sha256', $text);
        $existing = $model->embedding()->first();

        if (! $force && $existing !== null && $existing->content_hash === $hash) {
            return $existing;
        }

        $chunks = $this->chunker->chunk($text);
        $embedded = $this->embedTexts(array_column($chunks, 'content'));

        return $this->store($model, $text, $hash, $chunks, $embedded);
    }

    /**
     * Generate embeddings for many models. Returns the number of embeddings
     * written. Each model is embedded independently because its chunks must map
     * back to a single document header.
     *
     * @param  iterable<int, Model&Embeddable>  $models
     */
    public function embedMany(iterable $models, bool $force = false): int
    {
        $written = 0;

        foreach ($models as $model) {
            $text = trim($model->toEmbeddingText());

            if ($text === '') {
                $model->embedding()->delete();

                continue;
            }

            $hash = hash('sha256', $text);

            if (! $force && $model->embedding !== null && $model->embedding->content_hash === $hash) {
                continue;
            }

            $chunks = $this->chunker->chunk($text);
            $embedded = $this->embedTexts(array_column($chunks, 'content'));
            $this->store($model, $text, $hash, $chunks, $embedded);
            $written++;
        }

        return $written;
    }

    /**
     * Generate a raw vector for an ad hoc text (search queries, themes...).
     * Long inputs are reduced to their first chunk, which is sufficient for the
     * short queries this method serves.
     *
     * @return list<float>
     */
    public function vectorFor(string $text): array
    {
        $chunks = $this->chunker->chunk($text);

        if ($chunks === []) {
            return [];
        }

        return $this->embedder->embed([$chunks[0]['content']])->first();
    }

    /**
     * Embed a list of chunk texts, batching embedder calls to respect the
     * per-request input count and token budget.
     *
     * @param  list<string>  $texts
     * @return array{vectors: list<list<float>>, provider: string, model: string}
     */
    protected function embedTexts(array $texts): array
    {
        $vectors = [];
        $provider = '';
        $model = '';

        foreach ($this->batchInputs($texts) as $batch) {
            $result = $this->embedder->embed($batch);

            foreach ($result->vectors as $vector) {
                $vectors[] = $vector;
            }

            $provider = $result->provider !== '' ? $result->provider : $provider;
            $model = $result->model !== '' ? $result->model : $model;
        }

        return ['vectors' => $vectors, 'provider' => $provider, 'model' => $model];
    }

    /**
     * Group chunk texts into embedder requests bounded by both the input count
     * and the estimated token budget.
     *
     * @param  list<string>  $texts
     * @return list<list<string>>
     */
    protected function batchInputs(array $texts): array
    {
        $maxInputs = max(1, (int) config('oi-laravel-raggable.chunk.max_inputs_per_request', 128));
        $tokenBudget = max(1, (int) config('oi-laravel-raggable.chunk.request_token_budget', 16000));

        $batches = [];
        $batch = [];
        $batchTokens = 0;

        foreach ($texts as $text) {
            $tokens = $this->chunker->estimateTokens($text);

            if ($batch !== [] && (count($batch) >= $maxInputs || $batchTokens + $tokens > $tokenBudget)) {
                $batches[] = $batch;
                $batch = [];
                $batchTokens = 0;
            }

            $batch[] = $text;
            $batchTokens += $tokens;
        }

        if ($batch !== []) {
            $batches[] = $batch;
        }

        return $batches;
    }

    /**
     * Persist the document header and replace its chunks in a single
     * transaction.
     *
     * @param  list<array{content: string, token_count: int, index: int}>  $chunks
     * @param  array{vectors: list<list<float>>, provider: string, model: string}  $embedded
     */
    protected function store(Model&Embeddable $model, string $text, string $hash, array $chunks, array $embedded): Embedding
    {
        $embeddingModel = OiLaravelRaggable::embeddingModel();

        return DB::transaction(function () use ($embeddingModel, $model, $text, $hash, $chunks, $embedded): Embedding {
            /** @var Embedding $embedding */
            $embedding = $embeddingModel::query()->updateOrCreate(
                [
                    'embeddable_type' => $model->getMorphClass(),
                    'embeddable_id' => $model->getKey(),
                ],
                [
                    'content_hash' => $hash,
                    'content' => $text,
                    'vector' => $this->centroid($embedded['vectors']),
                    'provider' => $embedded['provider'],
                    'model' => $embedded['model'],
                    'generated_at' => now(),
                ],
            );

            $embedding->chunks()->delete();

            foreach ($chunks as $index => $chunk) {
                $embedding->chunks()->create([
                    'content' => $chunk['content'],
                    'vector' => $embedded['vectors'][$index] ?? null,
                    'chunk_index' => $chunk['index'],
                    'token_count' => $chunk['token_count'],
                ]);
            }

            return $embedding->load('chunks');
        });
    }

    /**
     * Element-wise mean of the chunk vectors — the document-level centroid
     * stored on the embedding header so whole-document similarity can run over a
     * single vector. Returns null when there are no vectors.
     *
     * @param  list<list<float>>  $vectors
     * @return list<float>|null
     */
    protected function centroid(array $vectors): ?array
    {
        if ($vectors === []) {
            return null;
        }

        $dimensions = count($vectors[0]);
        $sums = array_fill(0, $dimensions, 0.0);

        foreach ($vectors as $vector) {
            foreach ($vector as $index => $value) {
                $sums[$index] += (float) $value;
            }
        }

        $count = count($vectors);

        return array_map(static fn (float $sum): float => $sum / $count, $sums);
    }
}
