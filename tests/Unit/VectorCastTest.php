<?php

use OiLab\OiLaravelRaggable\Casts\VectorCast;
use OiLab\OiLaravelRaggable\Models\Chunk;

beforeEach(function () {
    $this->cast = new VectorCast;
    $this->model = new Chunk;
});

it('serializes a vector to a bracketed list', function () {
    expect($this->cast->set($this->model, 'vector', [0.1, 0.2, 0.3], []))
        ->toBe('[0.1,0.2,0.3]');
});

it('serializes null to null', function () {
    expect($this->cast->set($this->model, 'vector', null, []))->toBeNull();
});

it('parses a stored vector back to a list of floats', function () {
    expect($this->cast->get($this->model, 'vector', '[0.1,0.2,0.3]', []))
        ->toBe([0.1, 0.2, 0.3]);
});

it('reads null and empty as null', function () {
    expect($this->cast->get($this->model, 'vector', null, []))->toBeNull()
        ->and($this->cast->get($this->model, 'vector', '', []))->toBeNull();
});

it('round-trips a vector', function () {
    $stored = $this->cast->set($this->model, 'vector', [1.5, -2.25, 0.0], []);

    expect($this->cast->get($this->model, 'vector', $stored, []))
        ->toBe([1.5, -2.25, 0.0]);
});
