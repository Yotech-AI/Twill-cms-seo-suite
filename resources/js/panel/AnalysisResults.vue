<script setup>
import { computed } from 'vue';
import ResultGroup from './ResultGroup.vue';
import { groupResults } from './resultGroups.js';
import { DEFAULT_COLORS } from './colors.js';

const props = defineProps({
    title: { type: String, required: true },
    results: { type: Array, default: () => [] },
    colors: { type: Object, default: () => DEFAULT_COLORS },
});

const groups = computed(() => groupResults(props.results));
</script>

<template>
    <div v-if="groups.length" class="tss-analysis">
        <h3 class="tss-analysis__title">{{ title }}</h3>
        <ResultGroup v-for="group in groups" :key="group.category" :group="group" :colors="colors" />
    </div>
</template>
