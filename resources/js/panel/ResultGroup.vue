<script setup>
import { computed, ref } from 'vue';
import ResultItem from './ResultItem.vue';
import { DEFAULT_COLORS } from './colors.js';

const props = defineProps({
    group: { type: Object, required: true },
    colors: { type: Object, default: () => DEFAULT_COLORS },
});

// Seeded once from the group's own defaultOpen (problems/improvements open,
// good/feedback/errors collapsed — see resultGroups.js) and then left to the
// user's own toggling for the lifetime of this report.
const open = ref(!!props.group.defaultOpen);
const dotColor = computed(() => props.colors[props.group.colorKey] || DEFAULT_COLORS.grey);

function toggle() {
    open.value = !open.value;
}
</script>

<template>
    <div class="tss-group" :class="{ 'tss-group--open': open }">
        <button type="button" class="tss-group__header" :aria-expanded="open" @click="toggle">
            <span class="tss-group__dot" :style="{ backgroundColor: dotColor }" aria-hidden="true"></span>
            <span class="tss-group__label">{{ group.label }}</span>
            <span class="tss-group__count">{{ group.results.length }}</span>
            <span class="tss-group__chevron" aria-hidden="true">{{ open ? '−' : '+' }}</span>
        </button>
        <ul v-show="open" class="tss-group__list">
            <ResultItem v-for="item in group.results" :key="item.id" :result="item" :colors="colors" />
        </ul>
    </div>
</template>
