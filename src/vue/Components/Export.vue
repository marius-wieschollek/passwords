<template>
    <div class="backup-dialog export-container">
        <div class="step-1">
            <translate tag="h1" say="Choose Format"/>
            <translate tag="label" for="passwords-export-format" say="Export format"/>
            <select id="passwords-export-format" v-model="format">
                <translate tag="option" value="null">Please choose</translate>
                <translate tag="option" value="json">Passwords Backup</translate>
                <translate tag="option" value="csv">CSV</translate>
            </select>
        </div>
        <div class="step-2" v-if="step > 1">
            <translate tag="h1" say="Select Databases"/>
            <select v-model="models" multiple>
                <translate tag="option" value="passwords">Passwords</translate>
                <translate tag="option" value="folders">Folders</translate>
                <translate tag="option" value="tags">Tags</translate>
            </select>
        </div>

        <div class="step-3" v-if="step > 2">
            <translate tag="h1" say="Run Export"/>
            <translate tag="button" @click="exportDb">Export</translate>
        </div>
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
                format: 'null',
                models: [],
                step  : 1
            }
        },

        methods: {
            exportDb() {
                ExportManager.exportDatabase(this.format, this.models);
            }
        },

        watch: {
            format(d) {
                if(this.step === 1) this.step = 2;
                if(d === 'null') this.step = 1;
            },
            models(d) {
                if(this.step === 2) this.step = 3;
                if(d.length === 0) this.step = 2;
            }
        }
    }
</script>