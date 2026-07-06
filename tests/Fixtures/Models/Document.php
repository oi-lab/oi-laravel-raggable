<?php

namespace OiLab\OiLaravelRaggable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use OiLab\OiLaravelRaggable\Concerns\HasEmbedding;
use OiLab\OiLaravelRaggable\Contracts\Embeddable;

/**
 * @property string $title
 * @property string $body
 * @property string|null $status
 */
class Document extends Model implements Embeddable
{
    use HasEmbedding;

    protected $guarded = [];

    public function toEmbeddingText(): string
    {
        return $this->embeddingTextFrom([$this->title, $this->body]);
    }

    public function embeddableAttributes(): array
    {
        return ['title', 'body'];
    }
}
