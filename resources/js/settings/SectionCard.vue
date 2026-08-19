<script setup>
/**
 * The chrome every settings section shares: a header with its own Save
 * button, and inline success/error feedback right below it — factored out
 * once rather than copied into each of the four sections, which otherwise
 * differ only in the fields between this header and the next section's.
 */
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    status: { type: Object, default: () => ({}) },
});

defineEmits(['save']);
</script>

<template>
    <section class="tss-card">
        <header class="tss-card__header">
            <div class="tss-card__heading">
                <h3 class="tss-card__title">{{ title }}</h3>
                <p v-if="description" class="tss-card__description">{{ description }}</p>
            </div>
            <button
                type="button"
                class="tss-btn tss-btn--primary"
                :disabled="status && status.saving"
                @click="$emit('save')"
            >
                {{ status && status.saving ? 'Saving…' : 'Save' }}
            </button>
        </header>

        <p v-if="status && status.success" class="tss-banner tss-banner--success">Saved.</p>
        <div v-if="status && status.error" class="tss-error">
            <span class="tss-error__text">{{ status.error }}</span>
        </div>

        <div class="tss-card__body">
            <slot />
        </div>
    </section>
</template>
