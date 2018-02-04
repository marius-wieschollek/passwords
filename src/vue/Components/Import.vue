<template>
    <div class="backup-dialog import-container">
        <div class="step-1">
            <translate tag="h1" say="Choose Format"/>

            <div class="step-content">
                <select v-model="source" :disabled="importing">
                    <translate tag="option" value="json" say="Database Backup"/>
                    <translate tag="option" value="pwdCsv" say="Passwords CSV"/>
                    <translate tag="option" value="fldCsv" say="Folder CSV"/>
                    <translate tag="option" value="tagCsv" say="Tags CSV"/>
                    <translate tag="option" value="legacy" say="ownCloud Passwords"/>
                    <translate tag="option" value="pmanCsv" say="Passman CSV"/>
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
            <translate tag="h1" say="Select Options"/>
            <div class="step-content">
                <div v-if="!noOptions">
                    <translate tag="label" for="passwords-import-mode" say="Import Mode"/>
                    <select id="passwords-import-mode" v-model="options.mode" :disabled="importing">
                        <translate tag="option" value="0" say="Skip if same revision"/>
                        <translate tag="option" value="1" say="Skip if id exists"/>
                        <translate tag="option" value="2" say="Overwrite if id exists"/>
                        <translate tag="option" value="3" say="Clone if id exists"/>
                    </select>
                    <br>
                    <div v-if="source === 'csv'">
                        <translate tag="h3" say="CSV Options"/>
                        <translate tag="label" for="passwords-import-csv-db" say="Database"/>
                        <select id="passwords-import-csv-db" v-model="options.db" :disabled="importing">
                            <translate tag="option" value="passwords" say="Passwords"/>
                            <translate tag="option" value="folders" say="Folders"/>
                            <translate tag="option" value="tags" say="Tags"/>
                        </select>
                        <br>
                        <translate tag="label" for="passwords-import-csv-delimiter" say="Field delimiter"/>
                        <select id="passwords-import-csv-delimiter" v-model="options.delimiter" :disabled="importing">
                            <translate tag="option" value="," say="Comma"/>
                            <translate tag="option" value=";" say="Semicolon"/>
                            <translate tag="option" value=" " say="Space"/>
                            <translate tag="option" value="	" say="Tab"/>
                        </select>

                        <br>
                        <br>
                        <input type="checkbox" id="passwords-import-csv-skip" v-model="options.firstLine" :disabled="importing"/>
                        <translate tag="label" for="passwords-import-csv-skip" say="Skip first line"/>
                        <br>
                        <input type="checkbox" id="passwords-import-csv-repair" v-model="options.repair" :disabled="importing"/>
                        <translate tag="label" for="passwords-import-csv-repair" say="Interpolate missing fields"/>
                        <br>
                        <input type="checkbox" id="passwords-export-csv-shared" v-model="options.skipShared" :disabled="importing" v-if="options.mode !== '3' && options.db === 'passwords'"/>
                        <translate tag="label" for="passwords-export-csv-shared" say="Don't edit passwords shared with me" v-if="options.mode !== '3' && options.db === 'passwords'"/>
                        <br>
                        <br>

                        <translate tag="h3" say="CSV Field Mapping"/>
                        <translate tag="label" for="passwords-import-csv-preview-line" say="Preview Line"/>
                        <select id="passwords-import-csv-preview-line" v-model="previewLine" :disabled="importing">
                            <translate tag="option" v-for="index in 10" :value="index.toString()" say="Line {line}" :variables="{line:index}" :key="index"/>
                        </select>
                        <div class="csv-mapping">
                            <div v-for="value in csvSampleData" class="csv-mapping-data" :key="value">{{ value }}</div>
                            <div v-for="id in csvSampleData.length" class="csv-mapping-field" :key="id">
                                <select @change="csvFieldMapping($event, id)" :disabled="importing">
                                    <translate tag="option" value="null" say="Ignore"/>
                                    <translate tag="option" v-for="(label, option) in csvFieldOptions(id)" :value="option" :say="label" :key="option"/>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <br>
                        <input type="checkbox" id="passwords-export-shared" v-model="options.skipShared" :disabled="importing" v-if="options.mode !== '3'"/>
                        <translate tag="label" for="passwords-export-shared" say="Dont't edit passwords shared with me" v-if="options.mode !== '3'"/>
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
    import Messages from "@js/Classes/Messages";
    import ImportManager from '@js/Manager/ImportManager';


    export default {
        components: {
            Translate
        },

        data() {
            return {
                source     : 'json',
                type       : 'json',
                mime       : 'application/json',
                fieldMap   : {
                    passwords: ['password', 'username', 'label', 'notes', 'url', 'edited', 'favourite', 'folderLabel', 'tagLabels', 'folderId', 'tagIds', 'id', 'revision'],
                    folders  : ['label', 'edited', 'favourite', 'parentLabel', 'parentId', 'id', 'revision'],
                    tags     : ['label', 'color', 'edited', 'favourite', 'id', 'revision']
                },
                file       : null,
                options    : {mode: 0, skipShared: true},
                noOptions  : false,
                step       : 2,
                previewLine: 1,
                importing  : false,

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
                let data = Utility.parseCsv(this.file, this.options.delimiter, this.previewLine);

                return data.length >= this.previewLine ? data[this.previewLine - 1]:data[data.length];
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
                        if(typeof e !== 'string') e = e.message;
                        Messages.alert(e, 'Import error');
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
            },
            csvFieldOptions(current) {
                let fields  = this.fieldMap[this.options.db],
                    options = {};

                for(let i = 0; i < fields.length; i++) {
                    let field = fields[i],
                        index = this.options.mapping.indexOf(field);

                    if(index === -1 || index === current - 1) {
                        options[field] = field.capitalize();
                    }
                }

                return options;
            },
            csvFieldMapping(event, id) {
                let mapping = this.options.mapping.clone(),
                    value   = $(event.target).val();

                if(value === 'null') value = null;
                mapping[id - 1] = value;
                this.options.mapping = mapping;
            }
        },

        watch: {
            source(value) {
                this.progress.status = null;
                this.noOptions = false;
                let oldMime = this.mime;

                switch(value) {
                    case 'json':
                        this.mime = 'application/json';
                        this.type = 'json';
                        break;
                    case 'legacy':
                        this.options = {mode: 0, skipShared: true, profile: 'legacy'};
                        this.noOptions = true;
                        this.mime = 'text/csv';
                        this.type = 'csv';
                        break;
                    case 'pwdCsv':
                        this.options = {mode: 0, skipShared: true, profile: 'passwords'};
                        this.mime = 'text/csv';
                        this.type = 'csv';
                        break;
                    case 'fldCsv':
                        this.options = {mode: 0, skipShared: true, profile: 'folders'};
                        this.mime = 'text/csv';
                        this.type = 'csv';
                        break;
                    case 'tagCsv':
                        this.options = {mode: 0, skipShared: true, profile: 'tags'};
                        this.mime = 'text/csv';
                        this.type = 'csv';
                        break;
                    case 'pmanCsv':
                        this.options = {mode: 0, skipShared: true};
                        this.mime = 'text/csv';
                        this.type = 'pmanCsv';
                        break;
                    case 'csv':
                        this.options = {mode: 0, skipShared: true, firstLine: 0, delimiter: ',', db: 'passwords', mapping: [], repair: true, profile: 'custom'};
                        this.mime = 'text/csv';
                        this.type = 'csv';
                        break;
                }

                if(oldMime !== this.mime && this.file) {
                    $('#passwords-import-file').val('');
                    this.file = null;
                    this.step = 2
                } else if(this.step === 4 && this.source === 'csv') {
                    this.step = 3;
                }
            },
            file(data) {
                this.progress.status = null;
                if(data !== null && this.step === 2) {
                    this.step = this.source === 'csv' ? 3:4;
                }
            },
            options() {
                this.progress.status = null;
            },
            'options.db'() {
                $('.csv-mapping-field select').val('');
                this.progress.status = null;
                if(this.source === 'csv') this.options.mapping = [];
            },
            'options.skipShared'() {
                this.progress.status = null;
            },
            'options.mapping'(mapping) {
                if(!this.file) return;
                if(
                    (this.options.db === 'passwords' && mapping.indexOf('password') !== -1) ||
                    (this.options.db === 'folders' && mapping.indexOf('label') !== -1) ||
                    (this.options.db === 'tags' && mapping.indexOf('label') !== -1 && mapping.indexOf('color') !== -1)
                ) {
                    this.step = 4;
                } else if(this.step === 4) {
                    this.step = 3;
                }
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

                label {
                    min-width : 90px;
                    display   : inline-block;
                }

                label[for=passwords-import-csv-preview-line] {
                    padding-left : 5px;
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