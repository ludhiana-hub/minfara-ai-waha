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
            if (!$provider || !$provider->hasKey()) {
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

                    continue;
                }

                // Only a connection timeout is worth retrying — quota/model/key errors
                // won't change on an immediate retry.
                if (in_array($error, $retryOn, true) && $totalAttempts < $maxTotalAttempts) {
                    $this->sleeper->sleep($retryDelayMs);
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
                } else {
                    $attemptLog[$providerName][$model] = $error;
                    $errors[] = $error;
                }
            }

            if (!$result && $providerFullyTried && $markUnhealthy) {
                $breaker->markOpen($providerName, $cooldownSeconds);
                Log::info("AI provider {$providerName} marked unhealthy for {$cooldownSeconds}s — all models exhausted");
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
