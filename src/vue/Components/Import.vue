<template>
    <div class="backup-dialog import-container">
        <div class="step-1">
            <translate tag="h1" say="Choose Format"/>

            <div class="step-content">
                <select v-model="source" :disabled="importing">
                    <translate tag="option" value="json" say="Database Backup"/>
                    <translate tag="option" value="legacy" say="ownCloud Passwords"/>
                    <translate tag="option" value="pwdCsv" say="Passwords CSV"/>
                    <translate tag="option" value="fldCsv" say="Folder CSV"/>
                    <translate tag="option" value="tagCsv" say="Tags CSV"/>
                    <translate tag="option" value="csv" say="Custom CSV"/>
                </select>
            </div>
        </div>

        <div class="step-2" v-if="step > 1">
            <translate tag="h1" say="Select File"/>
            <div class="step-content">
                <input type="file" :accept="mime" @change="processFile($event)" id="passwords-import-file" :disabled="importing">
            </div>
        </div>

        <div class="step-3" v-if="step > 2">
            <translate tag="h1" say="Select Import Options"/>
            <div class="step-content">
                <div v-if="!noOptions">
                    <translate tag="label" for="passwords-import-mode" say="Import Mode"/>
                    <select id="passwords-import-mode" v-model="options.mode" :disabled="importing">
                        <translate tag="option" value="0" say="Skip if same revision"/>
                        <translate tag="option" value="1" say="Skip if id exists"/>
                        <translate tag="option" value="2" say="Overwrite if id exists"/>
                        <translate tag="option" value="3" say="Clone if id exists"/>
                    </select>
                    <div v-if="type === 'csv'">
                        <br>
                        <translate tag="h3" say="Csv Options"/>
                        <translate tag="label" for="passwords-import-csv-db" say="Object Type"/>
                        <select id="passwords-import-csv-db" v-model="options.db" :disabled="importing">
                            <translate tag="option" value="passwords" say="Passwords"/>
                            <translate tag="option" value="folders" say="Folders"/>
                            <translate tag="option" value="tags" say="Tags"/>
                        </select>
                        <br>
                        <translate tag="label" for="passwords-import-csv-delimiter" say="Field delimiter"/>
                        <input type="text" id="passwords-import-csv-delimiter" v-model="options.delimiter" :disabled="importing" minlength="1" maxlength="1"/>
                        <br>
                        <br>
                        <input type="checkbox" id="passwords-import-csv-skip" v-model="options.firstLine" :disabled="importing"/>
                        <translate tag="label" for="passwords-import-csv-skip" say="Skip first line"/>
                        <br>
                        <input type="checkbox" id="passwords-import-csv-repair" v-model="options.repair" :disabled="importing"/>
                        <translate tag="label" for="passwords-import-csv-repair" say="Interpolate missing fields"/>
                        <br>
                        <br>

                        <translate tag="h3" say="Csv Field Mapping"/>
                        <table class="csv-mapping">
                            <tbody>
                            <tr>
                                <td v-for="field in csvSampleData">{{ field }}</td>
                            </tr>
                            <tr>
                                <td v-for="(id, field) in csvSampleData">
                                    <select @change="setField($event, id)" :disabled="importing">
                                        <translate tag="option" value="" say="Ignore"/>
                                        <translate tag="option" v-for="option in pwdMapFields" :value="option" :say="option.capitalize()"/>
                                    </select>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <translate tag="div" say="No options available" class="no-options" v-else/>
            </div>
        </div>

        <div class="step-4" v-if="step > 3">
            <translate tag="h1" say="Run Import"/>
            <div class="step-content">
                <translate tag="button" @click="importDb" say="Import" v-if="progress.status === null"/>
                <div class="import-progress" v-else>
                    <progress :value="progress.processed" :max="progress.total" :title="progress.status" :class="progress.style"></progress>
                    <translate :say="progress.status"/>
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
                source      : 'json',
                type        : 'json',
                mime        : 'application/json',
                pwdMapFields: ['id', 'revision', 'label', 'username', 'password', 'notes', 'url', 'folder', 'edited', 'favourite', 'tags'],
                fldMapFields: ['id', 'revision', 'label', 'parent', 'edited', 'favourite'],
                tagMapFields: ['id', 'revision', 'label', 'color', 'edited', 'favourite'],
                file        : null,
                options     : {mode: 0},
                noOptions   : false,
                step        : 2,
                importing   : false,

                progress: {
                    style    : '',
                    processed: 0,
                    total    : 0,
                    status   : null
                },
            }
        },

        computed: {
            csvSampleData() {
                let column = this.options.firstLine ? 1:0;
                return Utility.parseCsv(this.file, this.options.delimiter, 1 + column)[column];
            }
        },

        methods: {
            importDb() {
                this.progress.style = '';
                this.importing = true;
                ImportManager.importDatabase(this.file, this.type, this.options, this.registerProgress)
                    .catch((e) => {
                        this.importing = false;
                        this.progress.style = 'error';
                        this.progress.status = 'Import failed';
                        alert(e);
                    })
                    .then((d) => {
                        if(this.progress.style !== 'error') {
                            this.importing = false;
                            this.progress.style = 'success';
                            this.progress.status = 'Import successful';
                        }
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
                if(status !== null) this.progress.status = status;
            }
        },

        watch: {
            source(d) {
                this.progress.status = null;
                this.noOptions = false;
                let oldmime = this.mime;

                switch(d) {
                    case 'json':
                        this.type = 'json';
                        this.mime = 'application/json';
                        break;
                    case 'legacy':
                        this.options = {mode: 0, firstLine: 1, delimiter: ',', db: 'passwords', mapping: ['label', 'username', 'password', 'url', 'notes'], repair: true};
                        this.noOptions = true;
                        this.type = 'csv';
                        this.mime = 'text/csv';
                        break;
                    case 'pwdCsv':
                        this.options =
                            {
                                mode     : 0,
                                firstLine: 0,
                                delimiter: ';',
                                db       : 'passwords',
                                mapping  : ['id', 'revision', 'label', 'username', 'password', 'notes', 'url', 'folder', 'edited', 'favourite', 'tags']
                            };
                        this.type = 'csv';
                        this.mime = 'text/csv';
                        break;
                    case 'fldCsv':
                        this.options = {mode: 0, firstLine: 0, delimiter: ';', db: 'folders', mapping: ['id', 'revision', 'label', 'parent', 'edited', 'favourite']};
                        this.type = 'csv';
                        this.mime = 'text/csv';
                        break;
                    case 'tagCsv':
                        this.options = {mode: 0, firstLine: 0, delimiter: ';', db: 'tags', mapping: ['id', 'revision', 'label', 'color', 'edited', 'favourite']};
                        this.type = 'csv';
                        this.mime = 'text/csv';
                        break;
                    case 'csv':
                        this.options = {mode: 0, firstLine: 0, delimiter: ',', db: 'passwords', mapping: [], repair: true};
                        this.type = 'csv';
                        this.mime = 'text/csv';
                        break;
                }

                if(oldmime !== this.mime && this.file) {
                    $('#passwords-import-file').val('');
                    this.file = null;
                    this.step = 2
                }
            },
            file(d) {
                this.progress.status = null;
                if(d !== null && this.step === 2) {
                    this.step = 4;
                }
            },
            options(d) {
                this.progress.status = null;
                console.log(d);
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

                .no-options {
                    margin : 10px;
                    color  : $color-grey-darker;
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