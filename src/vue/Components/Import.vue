<template>
    <div class="import-container">
        <select v-model="importSettings.type">
            <translate tag="option" value="json">JSON Backup</translate>
            <!--
            <translate tag="option" value="legacy">Legacy Passwords App</translate>
            <translate tag="option" value="csvFolder">CSV Tags Backup</translate>
            <translate tag="option" value="csvTags">CSV Folders Backup</translate>
            <translate tag="option" value="csvPassword">CSV Password Backup</translate>
            -->
        </select>

        <select v-model="importSettings.mode">
            <translate tag="option" value="0">Skip if same revision</translate>
            <translate tag="option" value="1">Skip if id exists</translate>
            <translate tag="option" value="2">Always overwrite</translate>
            <translate tag="option" value="3">Clone if id exists</translate>
        </select>

        <input type="file" :accept="importSettings.mime" @change="processFile($event)" value="Select File">
        <translate tag="button" @click="importDb">Import</translate>
        <progress :value="importStatus.processed" :max="importStatus.total" :title="importStatus.status"/>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import Utility from "@js/Classes/Utility";
    import ImportManager from '@js/Manager/ImportManager';


    export default {
        components: {
            Translate
        },

        data() {
            return {
                importSettings: {
                    type: 'json',
                    mime: 'application/json',
                    mode: 0,
                    data: null
                },
                importStatus  : {
                    processed: 0,
                    total    : 0,
                    status   : ''
                }
            }
        },

        methods: {
            importDb() {
                ImportManager.importDatabase(
                    this.importSettings.data,
                    this.importSettings.type,
                    this.importSettings.mode,
                    this.registerProgress
                );
            },
            processFile(event) {
                let file   = event.target.files[0],
                    reader = new FileReader();
                reader.onload = (e) => { this.importSettings.data = e.target.result; };
                reader.readAsText(file)
            },
            registerProgress(processed, total, status) {
                this.importStatus.processed = processed;
                this.importStatus.total = total;
                if(status !== null) {
                    this.importStatus.status = Utility.translate(status);
                }
            }
        }
    }
</script>

<style>

</style>