<template>
    <div class="backup-dialog import-container">
        <div class="step-1">
            <translate tag="h1" say="Choose Format"/>
            <translate tag="label" for="passwords-import-format" say="Import format"/>
            <select id="passwords-import-format" v-model="type">
                <translate tag="option" value="null">Please choose</translate>
                <translate tag="option" value="json">Passwords Backup</translate>
                <!--
                <translate tag="option" value="legacy">Legacy Passwords App</translate>
                <translate tag="option" value="csvFolder">CSV Tags Backup</translate>
                <translate tag="option" value="csvTags">CSV Folders Backup</translate>
                <translate tag="option" value="csvPassword">CSV Password Backup</translate>
                -->
            </select>
        </div>

        <div class="step-2" v-if="step > 1">
            <translate tag="h1" say="Select File"/>
            <translate tag="label" for="passwords-import-file" say="Import File"/>
            <input type="file" for="passwords-import-file" :accept="mime" @change="processFile($event)" value="Select File">
        </div>

        <div class="step-3" v-if="step > 2">
            <translate tag="h1" say="Select Import Options"/>
            <translate tag="label" for="passwords-import-mode" say="Import Mode"/>
            <select id="passwords-import-mode" v-model="mode">
                <translate tag="option" value="null">Please choose</translate>
                <translate tag="option" value="0">Skip if same revision</translate>
                <translate tag="option" value="1">Skip if id exists</translate>
                <translate tag="option" value="2">Always overwrite</translate>
                <translate tag="option" value="3">Clone if id exists</translate>
            </select>
        </div>

        <div class="step-4" v-if="step > 3">
            <translate tag="h1" say="Run Import"/>
            <translate tag="button" @click="importDb">Import</translate>
            <progress :value="importStatus.processed" :max="importStatus.total" :title="importStatus.status"/>
        </div>
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
                type: 'null',
                mime: 'application/json',
                mode: 'null',
                file: null,

                importStatus: {
                    processed: 0,
                    total    : 0,
                    status   : ''
                },
                step        : 1
            }
        },

        methods: {
            importDb() {
                ImportManager.importDatabase(
                    this.file,
                    this.type,
                    this.mode,
                    this.registerProgress
                );
            },
            processFile(event) {
                let file   = event.target.files[0],
                    reader = new FileReader();
                reader.onload = (e) => { this.file = e.target.result; };
                reader.readAsText(file)
            },
            registerProgress(processed, total, status) {
                this.importStatus.processed = processed;
                this.importStatus.total = total;
                if(status !== null) {
                    this.importStatus.status = Utility.translate(status);
                }
            }
        },

        watch: {
            type(d) {
                if(d === 'null') {
                    this.step = 1;
                    this.file = null;
                    return;
                }

                if(this.step === 1) this.step = 2;
                switch(d) {
                    case 'json':
                        this.mime = 'application/json';
                        break;
                    case 'csv':
                        this.mime = 'text/csv';
                        break;
                }
            },
            file(d) {
                if(d !== null && this.step === 2) {
                    this.step = 3;
                }
            },
            mode(d) {
                if(d === 'null') {
                    this.step = 3;
                    return;
                }

                if(this.step === 3) this.step = 4;
            }
        }
    }
</script>

<style>

</style>