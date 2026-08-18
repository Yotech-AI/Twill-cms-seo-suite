<!--
    Shown when the bridge's blocks[ hash has drifted from the panel's mount
    snapshot (see PanelApp's onBridgeChange) — body content changed but was
    never saved, so content-based checks (word count, keyphrase density, and
    every other assessment that reads the actual body text) are stale until
    the item is saved and the panel re-runs against fresh saved content.
-->
<template>
    <p class="tss-banner tss-banner--stale" role="status">
        Content changed — save this item to refresh content-based checks.
    </p>
</template>
