<script setup>
/**
 * One row per registry key (see SettingsPayload::registryRows() /
 * ::contentTypes()) — the matrix always lists every managed content type,
 * whether or not it has ever been saved, so there is nothing to add or
 * remove here, only fields to edit per row.
 */
import SectionCard from '../SectionCard.vue';

const model = defineModel({ required: true });

defineProps({
    registry: { type: Array, required: true }, // list<{key, label}>
    status: { type: Object, default: () => ({}) },
});

defineEmits(['save']);

/** A row is created lazily so a registry key added after the settings row
 * already exists still has something to bind inputs to. */
function rowFor(model_, key) {
    if (!model_[key]) {
        model_[key] = {
            title_template: '',
            description_template: '',
            schema_type: 'WebPage',
            sitemap: true,
        };
    }

    return model_[key];
}

const SCHEMA_TYPE_SUGGESTIONS = ['WebPage', 'Article', 'BlogPosting', 'NewsArticle', 'Product', 'FAQPage'];
</script>

<template>
    <SectionCard
        title="Content types"
        description="Per-type title and description templates, schema.org type, and sitemap inclusion."
        :status="status"
        @save="$emit('save')"
    >
        <datalist id="tss-schema-type-suggestions">
            <option v-for="type in SCHEMA_TYPE_SUGGESTIONS" :key="type" :value="type" />
        </datalist>

        <div v-for="row in registry" :key="row.key" class="tss-content-type-row">
            <h4 class="tss-content-type-row__label">{{ row.label }}</h4>

            <div class="tss-field">
                <label class="tss-field__label" :for="`tss-title-tpl-${row.key}`">Title template</label>
                <input
                    :id="`tss-title-tpl-${row.key}`"
                    v-model="rowFor(model, row.key).title_template"
                    type="text"
                    class="tss-input"
                    placeholder="{title} {sep} {site_name}"
                />
            </div>

            <div class="tss-field">
                <label class="tss-field__label" :for="`tss-desc-tpl-${row.key}`">Description template</label>
                <input
                    :id="`tss-desc-tpl-${row.key}`"
                    v-model="rowFor(model, row.key).description_template"
                    type="text"
                    class="tss-input"
                    placeholder="No default — leave empty for no meta description unless set per item"
                />
            </div>

            <div class="tss-field-row">
                <div class="tss-field tss-field--narrow">
                    <label class="tss-field__label" :for="`tss-schema-${row.key}`">Schema.org type</label>
                    <input
                        :id="`tss-schema-${row.key}`"
                        v-model="rowFor(model, row.key).schema_type"
                        type="text"
                        class="tss-input"
                        list="tss-schema-type-suggestions"
                    />
                </div>

                <label class="tss-checkbox tss-checkbox--inline">
                    <input v-model="rowFor(model, row.key).sitemap" type="checkbox" />
                    Include in XML sitemap
                </label>
            </div>
        </div>
    </SectionCard>
</template>
