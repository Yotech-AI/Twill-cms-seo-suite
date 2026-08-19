<script setup>
/**
 * Site identity: name, tagline, title separator, the schema.org entity
 * (organization or person) with its logo, the default social share image,
 * and social profile URLs (schema.org `sameAs`).
 */
import { reactive, ref, watch } from 'vue';
import MediaPickerModal from '../MediaPickerModal.vue';
import SectionCard from '../SectionCard.vue';

const model = defineModel({ required: true });

const props = defineProps({
    media: { type: Object, required: true }, // { logo, default_share } — last-saved summaries
    api: { type: Object, required: true },
    status: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['save']);

// Freshly picked (or explicitly removed) images, shown in place of the
// last-saved `media` prop until the next successful save resets them — see
// the status.success watcher below. undefined = "no override, use the
// last-saved summary"; null = "explicitly removed"; an object = "just picked".
const preview = reactive({ logo: undefined, default_share: undefined });

// null = closed; 'logo' | 'default_share' = which field the open picker is
// choosing for.
const openField = ref(null);

function openPicker(field) {
    openField.value = field;
}

function closePicker() {
    openField.value = null;
}

function onPicked(item) {
    const field = openField.value;
    if (!field) return;

    preview[field] = item;
    model.value[`${field}_media_id`] = item.id;
    closePicker();
}

function removeImage(field) {
    preview[field] = null;
    model.value[`${field}_media_id`] = null;
}

function previewFor(field) {
    return preview[field] !== undefined ? preview[field] : props.media[field];
}

watch(
    () => props.status && props.status.success,
    (success) => {
        if (success) {
            preview.logo = undefined;
            preview.default_share = undefined;
        }
    }
);

function addSocialProfile() {
    model.value.social_profiles = [...(model.value.social_profiles || []), ''];
}

function removeSocialProfile(index) {
    model.value.social_profiles = model.value.social_profiles.filter((_, i) => i !== index);
}

function save() {
    // Blank rows are a normal in-progress editing state, not a real value —
    // strip them before they reach a `url` validation rule that would 422 on
    // an empty string. modelValue itself is trimmed too, so the rows the
    // user sees afterward are exactly what got saved.
    model.value.social_profiles = (model.value.social_profiles || [])
        .map((url) => url.trim())
        .filter((url) => url !== '');

    emit('save');
}
</script>

<template>
    <SectionCard
        title="General"
        description="Site identity used across templates, Open Graph tags and the schema.org graph."
        :status="status"
        @save="save"
    >
        <div class="tss-field">
            <label class="tss-field__label" for="tss-site-name">Site name</label>
            <input id="tss-site-name" v-model="model.site_name" type="text" class="tss-input" placeholder="Your site's name" />
        </div>

        <div class="tss-field">
            <label class="tss-field__label" for="tss-tagline">Tagline</label>
            <input id="tss-tagline" v-model="model.tagline" type="text" class="tss-input" placeholder="A short tagline" />
        </div>

        <div class="tss-field tss-field--narrow">
            <label class="tss-field__label" for="tss-separator">Title separator</label>
            <input id="tss-separator" v-model="model.separator" type="text" maxlength="10" class="tss-input" />
            <p class="tss-field__hint">Used between {title} and {site_name} in the title template, e.g. "Page - Site".</p>
        </div>

        <div class="tss-field">
            <span class="tss-field__label">Schema.org entity</span>
            <div class="tss-radio-group">
                <label class="tss-radio">
                    <input v-model="model.entity_type" type="radio" value="organization" name="tss-entity-type" />
                    Organization
                </label>
                <label class="tss-radio">
                    <input v-model="model.entity_type" type="radio" value="person" name="tss-entity-type" />
                    Person
                </label>
            </div>
        </div>

        <div class="tss-field">
            <label class="tss-field__label" for="tss-entity-name">Entity name</label>
            <input
                id="tss-entity-name"
                v-model="model.entity_name"
                type="text"
                class="tss-input"
                placeholder="Falls back to the site name"
            />
        </div>

        <div class="tss-field-row">
            <div class="tss-field">
                <span class="tss-field__label">Logo</span>
                <div class="tss-media-picker">
                    <img v-if="previewFor('logo')" class="tss-media-picker__thumb" :src="previewFor('logo').thumbnail" :alt="previewFor('logo').name" />
                    <span v-else class="tss-media-picker__thumb tss-media-picker__thumb--empty" aria-hidden="true"></span>
                    <div class="tss-media-picker__actions">
                        <button type="button" class="tss-btn tss-btn--secondary" @click="openPicker('logo')">Choose…</button>
                        <button v-if="previewFor('logo')" type="button" class="tss-btn tss-btn--text" @click="removeImage('logo')">
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <div class="tss-field">
                <span class="tss-field__label">Default share image</span>
                <div class="tss-media-picker">
                    <img
                        v-if="previewFor('default_share')"
                        class="tss-media-picker__thumb"
                        :src="previewFor('default_share').thumbnail"
                        :alt="previewFor('default_share').name"
                    />
                    <span v-else class="tss-media-picker__thumb tss-media-picker__thumb--empty" aria-hidden="true"></span>
                    <div class="tss-media-picker__actions">
                        <button type="button" class="tss-btn tss-btn--secondary" @click="openPicker('default_share')">
                            Choose…
                        </button>
                        <button
                            v-if="previewFor('default_share')"
                            type="button"
                            class="tss-btn tss-btn--text"
                            @click="removeImage('default_share')"
                        >
                            Remove
                        </button>
                    </div>
                </div>
                <p class="tss-field__hint">Used for og:image/twitter:image when a page has no image of its own.</p>
            </div>
        </div>

        <div class="tss-field">
            <span class="tss-field__label">Social profiles</span>
            <p class="tss-field__hint">Linked from the schema.org entity as sameAs — X, Facebook, LinkedIn, etc.</p>
            <div v-for="(url, index) in model.social_profiles" :key="index" class="tss-chip-row">
                <input
                    :value="url"
                    type="url"
                    class="tss-input"
                    placeholder="https://…"
                    @input="model.social_profiles[index] = $event.target.value"
                />
                <button type="button" class="tss-btn tss-btn--text" aria-label="Remove" @click="removeSocialProfile(index)">
                    &times;
                </button>
            </div>
            <button type="button" class="tss-btn tss-btn--secondary" @click="addSocialProfile">Add profile</button>
        </div>
    </SectionCard>

    <MediaPickerModal :open="openField !== null" :api="api" @close="closePicker" @select="onPicked" />
</template>
