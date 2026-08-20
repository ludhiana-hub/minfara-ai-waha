<?php

namespace App\Services\Ai;

use App\Models\AiRequestTrace;
use App\Models\BotConfig;
use App\Services\Ai\Support\CircuitBreaker;
use App\Services\Ai\Support\ErrorNormalizer;
use App\Services\Ai\Support\JsonExtractor;
use App\Services\Ai\Support\SleeperContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Single entry point for every AI call in the app. This is a LIFT of ProcessAiReply's
 * provider/model fallback loop (circuit breaker, fail-open guard, attempt cap, timeout
 * retry) — not a rewrite. See config/ai_profiles.php for the per-profile knobs that used
 * to be hardcoded constants in ProcessAiReply.
 */
final class AiRouter
{
    public function __construct(
        private readonly ProviderRegistry $providers,
        private readonly SleeperContract $sleeper,
    ) {}

    public function hasAnyUsableProvider(string $profile): bool
    {
        foreach ($this->resolveOrder($profile) as $name) {
            $provider = $this->providers->get($name);
            if ($provider && $provider->hasKey()) {
                return true;
            }
        }

        return false;
    }

    public function run(AiRequest $request): AiResult
    {
        $startedAt = microtime(true);
        $cfg       = $this->profileConfig($request->profile());

        $order = array_values(array_diff($this->resolveOrder($request->profile(), $cfg), $request->excludedProviders()));

        $breaker           = new CircuitBreaker($cfg['circuit_scope'] ?? '');
        $maxTotalAttempts  = $cfg['max_total_attempts'];
        $cooldownSeconds   = $cfg['cooldown_seconds'];
        $timeout           = $request->timeoutOverride() ?? $cfg['timeout'];
        $markUnhealthy     = $cfg['mark_unhealthy'] ?? true;
        $retryOn           = $cfg['retry_on'] ?? [];
        $retryDelayMs      = $cfg['retry_delay_ms'] ?? 1000;
        $fallbackModels    = $cfg['fallback_models'] ?? [];
        $primaryModelSpecs = $cfg['primary_model'] ?? [];

        // Providers that recently failed on every model get skipped — UNLESS every
        // configured provider is currently marked unhealthy, in which case ignore the
        // marks entirely and try the normal order anyway (never skip straight to failure).
        $unhealthy    = array_values(array_filter($order, fn (string $p) => $breaker->isOpen($p)));
        $allUnhealthy = count($order) > 0 && count($unhealthy) === count($order);

        $totalAttempts = 0;
        $attemptLog    = [];
        $errors        = [];
        $result        = null;
        $usedProvider  = null;
        $usedModel     = null;
        $json          = null;

        foreach ($order as $providerName) {
            if ($totalAttempts >= $maxTotalAttempts) {
                break;
            }

            $provider = $this->providers->get($providerName);
            if (!$provider) {
                // Admin typo'd/misconfigured ai_provider_order (e.g. "grok" instead of "groq")
                // — without this, the provider is silently skipped and the only symptom is
                // "AI sometimes doesn't reply", with nothing in the logs pointing at why.
                Log::warning("AiRouter: unknown provider '{$providerName}' in provider order — check ai_provider_order config");
                continue;
            }
            if (!$provider->hasKey()) {
                continue;
            }

            if (!$allUnhealthy && $breaker->isOpen($providerName)) {
                Log::info("AI provider {$providerName} skipped — cooldown active");
                continue;
            }

            $primaryModel = $this->resolvePrimaryModel($providerName, $primaryModelSpecs);
            $models       = array_values(array_unique(array_filter(array_merge(
                [$primaryModel],
                $fallbackModels[$providerName] ?? []
            ))));

            if (empty($models)) {
                continue;
            }

            // Stays true only if every model in this provider's list actually got a fair
            // try — false if the attempt cap cut it short, so we don't unfairly mark it
            // unhealthy for something that wasn't really its fault.
            $providerFullyTried = true;

            // Tracks the most recent 429 classification for this provider, so the cooldown
            // below can reflect an actual rate-limit/quota signal instead of a flat guess.
            $lastRateLimitType = null;
            $lastCooldownHint  = null;

            foreach ($models as $model) {
                if ($totalAttempts >= $maxTotalAttempts) {
                    $providerFullyTried = false;
                    break 2;
                }

                $jsonMode = $request->isExpectingJson() && $provider->supports(Capability::JsonMode, $model);
                $call     = new AiCall(
                    $model,
                    $this->buildMessages($request),
                    $request->maxTokens(),
                    $request->temperature(),
                    $timeout,
                    $jsonMode,
                );

                [$raw, $parsedJson] = $this->attempt($provider, $call, $request->isExpectingJson());
                $totalAttempts++;

                if ($raw->success) {
                    $result       = $raw;
                    $usedProvider = $providerName;
                    $usedModel    = $model;
                    $json         = $parsedJson;
                    $attemptLog[$providerName][$model] = 'success';
                    $breaker->clear($providerName);
                    break 2;
                }

                $error = $raw->error ?? 'unknown';
                $this->trackRateLimit($error, $raw->cooldownSeconds, $lastRateLimitType, $lastCooldownHint);

                // Self-heal: a provider that rejected json_mode outright is worth one retry
                // without the flag, same model, same attempt budget.
                if ($jsonMode && ErrorNormalizer::looksLikeUnsupportedJsonMode($error) && $totalAttempts < $maxTotalAttempts) {
                    [$retryRaw, $retryJson] = $this->attempt($provider, $call->withoutJsonMode(), false);
                    $totalAttempts++;

                    if ($retryRaw->success) {
                        $result       = $retryRaw;
                        $usedProvider = $providerName;
                        $usedModel    = $model;
                        $json         = $retryJson;
                        $attemptLog[$providerName][$model] = 'success (json mode stripped)';
                        $breaker->clear($providerName);
                        break 2;
                    }

                    $attemptLog[$providerName][$model] = "{$error} (json mode stripped, still failed: " . ($retryRaw->error ?? 'unknown') . ')';
                    $errors[] = $retryRaw->error ?? $error;
                    $this->trackRateLimit($retryRaw->error ?? $error, $retryRaw->cooldownSeconds, $lastRateLimitType, $lastCooldownHint);

                    continue;
                }

                // Only a connection timeout is worth retrying — quota/model/key errors
                // won't change on an immediate retry.
                if (in_array($error, $retryOn, true) && $totalAttempts < $maxTotalAttempts) {
                    // Jitter avoids a thundering herd: a burst of timeouts across many
                    // concurrent user messages would otherwise all retry at exactly the same
                    // instant one retry-delay later.
                    $this->sleeper->sleep($retryDelayMs + random_int(0, 300));
                    [$retryRaw, $retryJson] = $this->attempt($provider, $call, $request->isExpectingJson());
                    $totalAttempts++;

                    if ($retryRaw->success) {
                        $result       = $retryRaw;
                        $usedProvider = $providerName;
                        $usedModel    = $model;
                        $json         = $retryJson;
                        $attemptLog[$providerName][$model] = 'success (after retry)';
                        $breaker->clear($providerName);
                        break 2;
                    }

                    $attemptLog[$providerName][$model] = "{$error} (retried, still failed: " . ($retryRaw->error ?? 'unknown') . ')';
                    $errors[] = $retryRaw->error ?? $error;
                    $this->trackRateLimit($retryRaw->error ?? $error, $retryRaw->cooldownSeconds, $lastRateLimitType, $lastCooldownHint);
                } else {
                    $attemptLog[$providerName][$model] = $error;
                    $errors[] = $error;
                }
            }

            if (!$result && $providerFullyTried && $markUnhealthy) {
                $effectiveCooldown = $this->resolveCooldown($lastRateLimitType, $lastCooldownHint, $cfg, $cooldownSeconds);
                $breaker->markOpen($providerName, $effectiveCooldown);
                $reason = $lastRateLimitType ?? 'exhausted';
                Log::info("AI provider {$providerName} marked unhealthy for {$effectiveCooldown}s ({$reason}) — all models exhausted");
            }

            if ($result) {
                break;
            }
        }

        $success       = $result !== null && $result->success;
        $correlationId = (string) Str::uuid();

        $aiResult = new AiResult(
            success: $success,
            reply: $success ? $result->text : '',
            tokens: $success ? $result->tokens : null,
            provider: $usedProvider,
            model: $usedModel,
            attempts: $totalAttempts,
            attemptLog: $attemptLog,
            errors: $errors,
            json: $json,
            correlationId: $correlationId,
        );

        if (!$success) {
            Log::error('AiRouter: all providers/models exhausted', [
                'profile'           => $request->profile(),
                'total_attempts'    => $totalAttempts,
                'attempt_log'       => $attemptLog,
                'skipped_unhealthy' => $unhealthy,
                'fail_open'         => $allUnhealthy,
            ]);
        }

        $this->maybeTrace($request, $aiResult, $cfg, (int) round((microtime(true) - $startedAt) * 1000));

        return $aiResult;
    }

    /** Records the most recent 429 classification/hint seen for the provider currently being tried. */
    private function trackRateLimit(string $error, ?int $cooldownHint, ?string &$type, ?int &$hint): void
    {
        if (in_array($error, [ErrorNormalizer::RATE_LIMITED, ErrorNormalizer::QUOTA_EXCEEDED], true)) {
            $type = $error;
            $hint = $cooldownHint;
        }
    }

    /**
     * Picks the circuit-breaker cooldown for a provider that just exhausted all its models.
     * A concrete Retry-After hint (clamped to a sane range) wins; otherwise falls back to a
     * per-error-type default; otherwise the generic flat default used for non-429 failures.
     */
    private function resolveCooldown(?string $type, ?int $hint, array $cfg, int $default): int
    {
        if ($hint !== null) {
            return max($cfg['cooldown_floor_seconds'] ?? 15, min($hint, $cfg['cooldown_ceiling_seconds'] ?? 21600));
        }

        return match ($type) {
            ErrorNormalizer::RATE_LIMITED   => $cfg['rate_limited_cooldown_seconds'] ?? 90,
            ErrorNormalizer::QUOTA_EXCEEDED => $cfg['quota_exceeded_cooldown_seconds'] ?? 3600,
            default                         => $default,
        };
    }

    /** @return array{0: AiRawResult, 1: ?array} */
    private function attempt($provider, AiCall $call, bool $expectJson): array
    {
        $raw = $provider->send($call);

        if ($raw->success && $expectJson) {
            $parsed = JsonExtractor::extract($raw->text);
            if ($parsed === null) {
                return [AiRawResult::fail(ErrorNormalizer::JSON_PARSE_FAILED), null];
            }

            return [$raw, $parsed];
        }

        return [$raw, null];
    }

    /** @return array<int, array{role:string, content:string}> */
    private function buildMessages(AiRequest $request): array
    {
        $messages = [];
        $system   = $request->systemPrompt();

        // OpenAI-compatible json_object mode requires the word "json" to appear somewhere
        // in the messages or the API 400s — this also doubles as a prompt-only fallback for
        // providers/models that ignore the native flag entirely.
        if ($request->isExpectingJson() && $system !== '' && !str_contains(mb_strtolower($system), 'json')) {
            $system .= "\n\nBalas HANYA dengan JSON valid, tanpa markdown code block.";
        }

        if ($system !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }

        foreach ($request->history() as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $request->userMessage()];

        return $messages;
    }

    private function profileConfig(string $profile): array
    {
        $profileCfg = config("ai_profiles.profiles.{$profile}");

        if ($profileCfg === null) {
            throw new \InvalidArgumentException("Unknown AiRouter profile: {$profile}");
        }

        return array_merge(config('ai_profiles.defaults', []), $profileCfg);
    }

    /** @return string[] */
    private function resolveOrder(string $profile, ?array $cfg = null): array
    {
        $cfg ??= $this->profileConfig($profile);

        if (!empty($cfg['order_config_key'])) {
            $orderStr = BotConfig::get($cfg['order_config_key'], implode(',', $cfg['default_order'] ?? []));

            return array_values(array_filter(array_map('trim', explode(',', (string) $orderStr))));
        }

        return $cfg['default_order'] ?? [];
    }

    private function resolvePrimaryModel(string $providerName, array $specs): ?string
    {
        $spec = $specs[$providerName] ?? null;
        if ($spec === null) {
            return null;
        }

        $fromBotConfig = $spec['bot_config'] ? BotConfig::get($spec['bot_config']) : null;

        return $fromBotConfig ?: (config($spec['config']) ?: $spec['default']);
    }

    private function maybeTrace(AiRequest $request, AiResult $result, array $cfg, int $latencyMs): void
    {
        $mode = $cfg['trace'] ?? 'on_retry';

        if ($mode === 'never') {
            return;
        }

        if ($mode === 'on_retry' && $result->success && $result->attempts <= 1) {
            return;
        }

        try {
            AiRequestTrace::create([
                'correlation_id' => $result->correlationId,
                'profile'        => $request->profile(),
                'provider'       => $result->provider,
                'model'          => $result->model,
                'attempts'       => $result->attempts,
                'json_mode'      => $request->isExpectingJson(),
                'success'        => $result->success,
                'error'          => $result->lastError(),
                'tokens'         => $result->tokens,
                'latency_ms'     => $latencyMs,
                'attempt_log'    => $result->attemptLog,
                'created_at'     => now(),
            ]);
        } catch (\Exception $e) {
            // Tracing must never take down the actual AI call it's observing.
            Log::warning('AiRouter: failed to write trace row', ['message' => $e->getMessage()]);
        }
    }
}
