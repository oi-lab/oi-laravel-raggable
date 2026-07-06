---
title: Ecosystem Integrations
description: How Raggable uses oi-laravel-settings for runtime tuning and oi-laravel-ai for cost tracking
section: advanced
order: 4
---

# Ecosystem Integrations

Raggable depends on two OI Lab packages and wires them automatically.

## oi-laravel-settings — runtime-tunable settings

The values you calibrate after a backfill are resolved through a `SettingStore` first and fall back to config, so they can change at runtime without a deploy:

- `similarity.max_distance`
- `similarity.limit`
- `auto_refresh`
- `embedding.provider`
- `embedding.model`

The `OiLaravelSettingsStore` adapter is auto-detected when `oi-laravel-settings` is installed (override with `setting_store`). Values are read/written under the scope returned by `context_binding` (null = global).

```php
use OiLab\OiLaravelRaggable\Contracts\SettingStore;

// Persist a tuned threshold — OiLaravelRaggable::maxDistance() now returns it.
app(SettingStore::class)->set('similarity.max_distance', 0.35, 'Raggable — max distance', 'float');

// Remove it to fall back to config again.
app(SettingStore::class)->forget('similarity.max_distance');
```

**Structural values stay in config.** `driver` and `dimensions` are not exposed as settings, because changing them requires re-migrating the vector columns — a runtime toggle would corrupt stored data.

## oi-laravel-ai — embedding cost tracking

Every embedding request is recorded through `oi-laravel-ai` as an `ai_requests` row: the token count, and links to the AI catalog (`ai_providers` / `ai_models`) when the provider and model codes are known. Embedding cost then appears alongside your agent usage in `AiUsageReporter`, grouped under the `embedding:<model-type>` prompt type.

Recording is handled by the `UsageRecorder` contract:

- `AiUsageRecorder` (default) — writes to `ai_requests`. Best-effort: it silently skips when the AI migrations have not run or the token count is zero, so embedding never fails on a reporting concern.
- `NullUsageRecorder` — bound when `track_usage` is `false`.

```dotenv
RAGGABLE_TRACK_USAGE=false
```

> The `oi-laravel-ai` migrations create `ai_requests` with foreign keys to your `projects` and `agent_runs` tables. Make those tables exist before migrating, or adjust the published migration to match your schema.
