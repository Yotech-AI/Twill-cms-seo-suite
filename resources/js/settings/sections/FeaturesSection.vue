<script setup>
/**
 * The six feature toggles SeoSettings::feature() reads — the same keys
 * config('twill-seo.features.*') seeds by default, now editable at runtime.
 */
import SectionCard from '../SectionCard.vue';

const model = defineModel({ required: true });

defineProps({
    status: { type: Object, default: () => ({}) },
});

defineEmits(['save']);

const FEATURES = [
    { key: 'analysis', label: 'Content analysis', hint: 'The traffic-light SEO and readability panel on each item\'s edit form.' },
    { key: 'sitemap', label: 'XML sitemap', hint: 'Serves /sitemap.xml and the per-type sitemap pages.' },
    { key: 'schema', label: 'Schema.org (JSON-LD)', hint: 'The structured-data graph rendered by <x-twill-seo::head />.' },
    { key: 'og', label: 'Open Graph tags', hint: 'og:title, og:description, og:image and related meta tags.' },
    { key: 'twitter', label: 'Twitter Card tags', hint: 'twitter:card, twitter:title and twitter:description.' },
    { key: 'hreflang', label: 'Hreflang alternates', hint: 'Cross-locale <link rel="alternate"> tags — needs at least two locales.' },
];
</script>

<template>
    <SectionCard title="Features" description="Switch entire package features on or off." :status="status" @save="$emit('save')">
        <label v-for="feature in FEATURES" :key="feature.key" class="tss-checkbox tss-checkbox--block">
            <input v-model="model[feature.key]" type="checkbox" />
            <span>
                <span class="tss-checkbox__label">{{ feature.label }}</span>
                <span class="tss-checkbox__hint">{{ feature.hint }}</span>
            </span>
        </label>
    </SectionCard>
</template>
