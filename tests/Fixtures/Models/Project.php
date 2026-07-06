<?php

namespace OiLab\OiLaravelRaggable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasUuids;

    protected $table = 'projects';

    protected $guarded = [];
}
