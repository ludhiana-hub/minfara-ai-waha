# Graph Report - .  (2026-08-02)

## Corpus Check
- 151 files · ~56,892 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 607 nodes · 1073 edges · 74 communities (71 shown, 3 thin omitted)
- Extraction: 91% EXTRACTED · 9% INFERRED · 0% AMBIGUOUS · INFERRED: 93 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Community 0
- Community 1
- Community 2
- Community 3
- Community 4
- Community 5
- Community 6
- Community 7
- Community 8
- Community 9
- Community 10
- Community 11
- Community 12
- Community 13
- Community 14
- Community 15
- Community 16
- Community 38

## God Nodes (most connected - your core abstractions)
1. `Controller` - 41 edges
2. `BotConfig` - 40 edges
3. `FaqMenu` - 32 edges
4. `WhatsAppService` - 30 edges
5. `WhatsappLog` - 19 edges
6. `ConversationAnalysis` - 16 edges
7. `NotificationTemplate` - 16 edges
8. `NotificationTarget` - 15 edges
9. `ProcessAiReply` - 14 edges
10. `PausedContact` - 11 edges

## Surprising Connections (you probably didn't know these)
- `up()` --calls--> `BotConfig`  [INFERRED]
  database/migrations/2026_07_05_000001_update_checkout_cta_content.php → app/Models/BotConfig.php
- `up()` --calls--> `BuildFaqDigestJob`  [INFERRED]
  database/migrations/2026_07_05_000001_update_checkout_cta_content.php → app/Jobs/BuildFaqDigestJob.php
- `up()` --calls--> `FaqMenu`  [INFERRED]
  database/migrations/2026_07_05_000001_update_checkout_cta_content.php → app/Models/FaqMenu.php
- `InternalNotificationController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/InternalNotificationController.php → app/Http/Controllers/Controller.php
- `WhatsAppController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/WhatsAppController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (74 total, 3 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.06
Nodes (24): AnalyticsController, BotConfigController, FaqController, LogController, NotificationLogController, WahaController, AnalyticsController, LogController (+16 more)

### Community 1 - "Community 1"
Cohesion: 0.06
Nodes (16): KnowledgeSynthesizerJob, ReConfigureWahaWebhookJob, RunAnalyticsJob, SendWhatsAppNotificationJob, GeminiService, static, GroqService, NvidiaService (+8 more)

### Community 2 - "Community 2"
Cohesion: 0.06
Nodes (15): WhatsAppController, DashboardController, JsonResponse, HumanTakeoverController, KonfigurasiController, Throwable, ProcessAiReply, BotConfig (+7 more)

### Community 3 - "Community 3"
Cohesion: 0.05
Nodes (43): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+35 more)

### Community 4 - "Community 4"
Cohesion: 0.08
Nodes (10): AnalyseConversations, FaqSuggestionController, FaqGapAggregatorJob, AnalyticsDailySummary, FaqSuggestion, ConversationAnalysisService, Illuminate\Database\Eloquent\Builder, Illuminate\Database\Eloquent\Model (+2 more)

### Community 5 - "Community 5"
Cohesion: 0.08
Nodes (10): InternalNotificationController, NotificationTemplateController, InternalNotifyRequest, StoreNotificationTemplateRequest, Throwable, NotificationLog, NotificationTemplate, NotificationTemplateService (+2 more)

### Community 6 - "Community 6"
Cohesion: 0.10
Nodes (6): EnsureWahaWebhook, SwaggerServeCommand, WahaEnsureSessionCommand, TestController, WhatsAppService, Illuminate\Console\Command

### Community 7 - "Community 7"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 8 - "Community 8"
Cohesion: 0.11
Nodes (6): Illuminate\Foundation\Testing\TestCase, ExampleTest, MockInterface, WhatsAppWebhookVerificationTest, TestCase, WhatsAppSendThrottleTest

### Community 9 - "Community 9"
Cohesion: 0.13
Nodes (9): User, TelescopeServiceProvider, static, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable (+1 more)

### Community 10 - "Community 10"
Cohesion: 0.20
Nodes (5): FaqController, FaqRequest, BuildFaqDigestJob, FaqMenu, up()

### Community 11 - "Community 11"
Cohesion: 0.17
Nodes (3): NotificationTargetController, StoreNotificationTargetRequest, NotificationTarget

### Community 12 - "Community 12"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 13 - "Community 13"
Cohesion: 0.20
Nodes (6): BotConfigSeeder, DatabaseSeeder, FaqMenuSeeder, NotificationTemplateSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 15 - "Community 15"
Cohesion: 0.83
Nodes (3): down(), getConnection(), up()

## Knowledge Gaps
- **59 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+54 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **3 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `BotConfig` connect `Community 2` to `Community 0`, `Community 1`, `Community 4`, `Community 5`, `Community 6`, `Community 8`, `Community 10`, `Community 13`, `Community 14`?**
  _High betweenness centrality (0.078) - this node is a cross-community bridge._
- **Why does `Controller` connect `Community 0` to `Community 1`, `Community 2`, `Community 4`, `Community 5`, `Community 6`, `Community 10`, `Community 11`, `Community 14`?**
  _High betweenness centrality (0.050) - this node is a cross-community bridge._
- **Why does `WhatsAppService` connect `Community 6` to `Community 0`, `Community 1`, `Community 2`, `Community 5`?**
  _High betweenness centrality (0.040) - this node is a cross-community bridge._
- **Are the 33 inferred relationships involving `BotConfig` (e.g. with `.handle()` and `.index()`) actually correct?**
  _`BotConfig` has 33 INFERRED edges - model-reasoned connections that need verification._
- **Are the 8 inferred relationships involving `FaqMenu` (e.g. with `.index()` and `.index()`) actually correct?**
  _`FaqMenu` has 8 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _59 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.06198198198198198 - nodes in this community are weakly interconnected._