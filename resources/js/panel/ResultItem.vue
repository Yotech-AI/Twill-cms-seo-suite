<script setup>
import { computed } from 'vue';
import { colorForRating, DEFAULT_COLORS } from './colors.js';

const props = defineProps({
    result: { type: Object, required: true },
    colors: { type: Object, default: () => DEFAULT_COLORS },
});

const dotColor = computed(() => colorForRating(props.result && props.result.rating, props.colors));
</script>

<template>
    <li class="tss-result">
        <span class="tss-result__dot" :style="{ backgroundColor: dotColor }" aria-hidden="true"></span>
        <!-- result.text is plain text from the engine (AssessmentResult::$text) — never v-html. -->
        <span class="tss-result__text">{{ result.text }}</span>
    </li>
</template>
