<?php

namespace OiLab\OiLaravelRaggable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AgentRun extends Model
{
    use HasUuids;

    protected $table = 'agent_runs';

    protected $guarded = [];
}
