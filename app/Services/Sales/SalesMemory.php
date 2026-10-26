<?php

namespace App\Services\Sales;

use App\Models\LeadProfile;

/**
 * Keeps a per-customer LeadProfile up to date from what they say, and derives the sales stage
 * ProcessAiReply feeds into the prompt. The stage is recomputed every turn from remembered facts
 * plus this turn's signals; only `checkout_sent` is sticky (so the checkout push isn't repeated).
 */
class SalesMemory
{
    public function observe(string $from, ?string $contactName, string $message): LeadProfile
    {
        $profile = LeadProfile::firstOrNew(['from_number' => $from]);
        $signals = LeadSignalExtractor::extract($message);

        if ($contactName && !$profile->contact_name) {
            $profile->contact_name = $contactName;
        }

        foreach (['language', 'level', 'goal'] as $fact) {
            if (isset($signals[$fact])) {
                $profile->{$fact} = $signals[$fact];
            }
        }
        if (isset($signals['package'])) {
            $profile->package_interest = $signals['package'];
        }

        if (isset($signals['objection'])) {
            $objections = $profile->objections ?? [];
            if (!in_array($signals['objection'], $objections, true)) {
                $objections[] = $signals['objection'];
            }
            $profile->objections = $objections;
        }

        $profile->stage = $this->resolveStage($profile, $signals);
        $profile->save();

        // Stage is a per-turn view; carry the current objection so the playbook can address it.
        $profile->currentObjection = $signals['objection'] ?? null;

        return $profile;
    }

    /** Call after a successful AI reply: marks the checkout link as delivered. */
    public function afterReply(LeadProfile $profile, string $reply): void
    {
        if (!str_contains($reply, 'mitfara.com')) {
            return;
        }

        $profile->checkout_pushed_at = now();

        if ($profile->stage === LeadProfile::STAGE_READY_TO_CHECKOUT) {
            $profile->stage = LeadProfile::STAGE_CHECKOUT_SENT;
        }

        $profile->save();
    }

    private function resolveStage(LeadProfile $profile, array $signals): string
    {
        if (!empty($signals['ready'])) {
            return $profile->stage === LeadProfile::STAGE_CHECKOUT_SENT
                ? LeadProfile::STAGE_CHECKOUT_SENT
                : LeadProfile::STAGE_READY_TO_CHECKOUT;
        }

        if ($profile->stage === LeadProfile::STAGE_CHECKOUT_SENT) {
            return LeadProfile::STAGE_CHECKOUT_SENT;
        }

        if (isset($signals['objection'])) {
            return LeadProfile::STAGE_OBJECTION;
        }

        if ($profile->language && ($profile->goal || $profile->level) && $profile->package_interest) {
            return LeadProfile::STAGE_QUALIFIED;
        }

        if ($profile->language) {
            return LeadProfile::STAGE_EXPLORING;
        }

        return LeadProfile::STAGE_NEW;
    }
}
