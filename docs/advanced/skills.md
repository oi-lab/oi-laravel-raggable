---
title: AI Assistant Skill
description: Install the packaged AI assistant skill into your project
section: advanced
order: 3
---

# AI Assistant Skill

This package ships an AI assistant skill (`oilab-laravel-raggable`) so AI coding assistants understand its models, contracts, commands, and configuration.

## Install it into your project

```bash
php artisan oi:skills oilab-laravel-raggable --project
```

This is provided by `oi-laravel-development`. The skill is declared in the package's `composer.json` under `extra.oi-lab.skills` and kept in sync on every `composer install`.

## Where it lives

The canonical source is `resources/stubs/ai-skill.md`. On `composer install` (via `post-autoload-dump`) it is synced to `.claude/skills/oilab-laravel-raggable/SKILL.md` and `.junie/skills/oilab-laravel-raggable/SKILL.md` inside the package.

When the package behavior changes, update the stub and re-sync:

```bash
composer sync-ai-skills
```
