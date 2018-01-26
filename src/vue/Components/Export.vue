<template>
    <div class="export-container">
        <select v-model="exportSettings.models" multiple>
            <translate tag="option" value="passwords">Passwords</translate>
            <translate tag="option" value="folders">Folders</translate>
            <translate tag="option" value="tags">Tags</translate>
        </select>
        <select v-model="exportSettings.format">
            <translate tag="option" value="json">JSON</translate>
            <translate tag="option" value="csv">CSV</translate>
        </select>
        <translate tag="button" @click="exportDb">Export</translate>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import ExportManager from '@js/Manager/ExportManager';

    export default {
        components: {
            Translate
        },

        data() {
            return {
                exportSettings: {
                    models: ['passwords', 'folders', 'tags'],
                    format: 'json'
                }
            }
        },

        methods: {
            exportDb() {
                ExportManager.exportDatabase(this.exportSettings.format, this.exportSettings.models);
            }
        }
    }
</script>