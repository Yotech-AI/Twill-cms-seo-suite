<script setup>
import { computed } from 'vue';

const props = defineProps({
    insights: { type: Object, default: null },
});

// Mirrors TwillSeo\Analysis\Report\FleschBand's case values exactly.
const FLESCH_BAND_LABELS = {
    very_easy: 'Very easy',
    easy: 'Easy',
    fairly_easy: 'Fairly easy',
    standard: 'Standard',
    fairly_difficult: 'Fairly difficult',
    difficult: 'Difficult',
    very_difficult: 'Very difficult',
};

const wordCount = computed(() => (props.insights ? props.insights.wordCount : null));
const readingTime = computed(() => (props.insights ? props.insights.readingTimeMinutes : null));
const fleschBandLabel = computed(() => {
    const band = props.insights && props.insights.fleschBand;
    return band ? FLESCH_BAND_LABELS[band] || band : null;
});
</script>

<template>
    <div v-if="insights" class="tss-insights">
        <div class="tss-insights__item">
            <span class="tss-insights__value">{{ wordCount === null ? '—' : wordCount }}</span>
            <span class="tss-insights__label">words</span>
        </div>
        <div class="tss-insights__item">
            <span class="tss-insights__value">~{{ readingTime === null ? '—' : readingTime }} min</span>
            <span class="tss-insights__label">reading time</span>
        </div>
        <div v-if="fleschBandLabel" class="tss-insights__item">
            <span class="tss-insights__value">{{ fleschBandLabel }}</span>
            <span class="tss-insights__label">readability</span>
        </div>
    </div>
</template>
