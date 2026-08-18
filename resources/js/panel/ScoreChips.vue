<script setup>
import { computed } from 'vue';
import { colorForScore, labelForScore, DEFAULT_COLORS } from './colors.js';

const props = defineProps({
    seoScore: { type: Number, default: null },
    readabilityScore: { type: Number, default: null },
    colors: { type: Object, default: () => DEFAULT_COLORS },
});

const seoColor = computed(() => colorForScore(props.seoScore, props.colors));
const readabilityColor = computed(() => colorForScore(props.readabilityScore, props.colors));
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
