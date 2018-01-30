<template>
    <div class="backup-dialog import-container">
        <div class="step-1">
            <translate tag="h1" say="Choose Format"/>

            <div class="step-content">
                <select v-model="type" :disabled="importing">
                    <translate tag="option" value="null" say="Please choose"/>
                    <translate tag="option" value="json" say="Database Backup"/>
                    <!--
                    <translate tag="option" value="legacy">Legacy Passwords App</translate>
                    <translate tag="option" value="csvFolder">CSV Tags Backup</translate>
                    <translate tag="option" value="csvTags">CSV Folders Backup</translate>
                    <translate tag="option" value="csvPassword">CSV Password Backup</translate>
                    -->
                </select>
            </div>
        </div>

        <div class="step-2" v-if="step > 1">
            <translate tag="h1" say="Select File"/>
            <div class="step-content">
                <input type="file" :accept="mime" @change="processFile($event)" :disabled="importing">
            </div>
        </div>

        <div class="step-3" v-if="step > 2">
            <translate tag="h1" say="Select Import Options"/>
            <div class="step-content">
                <translate tag="label" for="passwords-import-mode" say="Import Mode"/>
                <select id="passwords-import-mode" v-model="mode" :disabled="importing">
                    <translate tag="option" value="null" say="Please choose"/>
                    <translate tag="option" value="0" say="Skip if same revision"/>
                    <translate tag="option" value="1" say="Skip if id exists"/>
                    <translate tag="option" value="2" say="Overwrite if id exists"/>
                    <translate tag="option" value="3" say="Clone if id exists"/>
                </select>
            </div>
        </div>

        <div class="step-4" v-if="step > 3">
            <translate tag="h1" say="Run Import"/>
            <div class="step-content">
                <translate tag="button" @click="importDb" say="Import" v-if="progress.status === null"/>
                <div class="import-progress" v-else>
                    <progress :value="progress.processed" :max="progress.total" :title="progress.status" :class="progress.style"></progress>
                    <span>{{progress.status}}</span>
                </div>
            </div>
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
                type     : 'null',
                mime     : 'application/json',
                mode     : 'null',
                file     : null,
                step     : 1,
                importing: false,

                progress: {
                    style    : '',
                    processed: 0,
                    total    : 0,
                    status   : null
                },
            }
        },

        methods: {
            importDb() {
                this.progress.style = '';
                this.importing = true;
                ImportManager.importDatabase(this.file, this.type, this.mode, this.registerProgress)
                    .catch((e) => {
                        this.importing = false;
                        this.progress.style = 'error';
                        this.progress.status = 'Import failed';
                        alert(e);
                    })
                    .then((d) => {
                        this.importing = false;
                        this.progress.style = 'success';
                        this.progress.status = 'Import successful';
                    });
            },
            processFile(event) {
                let file   = event.target.files[0],
                    reader = new FileReader();
                reader.onload = (e) => { this.file = e.target.result; };
                reader.readAsText(file)
            },
            registerProgress(processed, total, status) {
                this.progress.processed = processed;
                this.progress.total = total;
                if(status !== null) {
                    this.progress.status = Utility.translate(status);
                }
            }
        },

        watch: {
            type(d) {
                this.progress.status = null;
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
                this.progress.status = null;
                if(d !== null && this.step === 2) {
                    this.step = 3;
                }
            },
            mode(d) {
                this.progress.status = null;
                if(d === 'null') {
                    this.step = 3;
                    return;
                }

                if(this.step === 3) this.step = 4;
            }
        }
    }
</script>

<style lang="scss">
    .import-container {
        .step-3 {
            .step-content {
                label {
                    margin-right : 5px;
                }
            }
        }

        .import-progress {
            position : relative;

            progress {
                width         : 100%;
                height        : 34px;
                border-radius : 3px;
                border        : none;

                &::-moz-progress-bar {
                    background-color : $color-theme;
                    border-radius    : 3px;
                    transition       : background-color 0.25s ease-in-out;
                }

                &::-webkit-progress-bar {
                    background-color : $color-theme;
                    border-radius    : 3px;
                    transition       : background-color 0.25s ease-in-out;
                }

                &.error {
                    &::-moz-progress-bar {
                        background-color : $color-red-dark;
                    }
                    &::-webkit-progress-bar {
                        background-color : $color-red-dark;
                    }
                }
                &.success {
                    &::-moz-progress-bar {
                        background-color : $color-green;
                    }
                    &::-webkit-progress-bar {
                        background-color : $color-green;
                    }
                }
            }

            span {
                position    : absolute;
                left        : 5px;
                line-height : 32px;
                font-size   : 1.2em;
                color       : $color-black-light;
            }
        }
    }
</style>