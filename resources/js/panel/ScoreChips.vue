<script setup>
import { computed } from 'vue';
import { colorForSection, labelForScore, DEFAULT_COLORS } from './colors.js';

const props = defineProps({
    seoScore: { type: Number, default: null },
    // The engine's own OverallRating string for this section
    // ('not-available'|'bad'|'ok'|'good' — report.seo.rating), present once
    // a live response has arrived; null before that (a cached `initial`
    // score has no rating to go with it). Preferred over re-deriving a
    // color from seoScore whenever present — see colorForSection().
    seoRating: { type: String, default: null },
    readabilityScore: { type: Number, default: null },
    readabilityRating: { type: String, default: null },
    colors: { type: Object, default: () => DEFAULT_COLORS },
});

const seoColor = computed(() => colorForSection(props.seoScore, props.seoRating, props.colors));
const readabilityColor = computed(() => colorForSection(props.readabilityScore, props.readabilityRating, props.colors));
// Label is deliberately score-only, not rating-aware: the engine's
// OverallScore contract guarantees a 0 score is always paired with the
// 'not-available' rating and never any other one, so labelForScore()'s own
// 0-handling already reads correctly in both the live and cached-initial
// paths without needing to know which one it is looking at.
const seoLabel = computed(() => labelForScore(props.seoScore));
const readabilityLabel = computed(() => labelForScore(props.readabilityScore));
</script>

<template>
    <div class="tss-chips">
        <div class="tss-chip">
            <span class="tss-chip__dot" :style="{ backgroundColor: seoColor }" aria-hidden="true"></span>
            <span class="tss-chip__label">SEO {{ seoLabel }}</span>
        </div>
        <div class="tss-chip">
            <span class="tss-chip__dot" :style="{ backgroundColor: readabilityColor }" aria-hidden="true"></span>
            <span class="tss-chip__label">Readability {{ readabilityLabel }}</span>
        </div>
    </div>
</template>
