<template>
    <div class="backup-dialog export-container">
        <div class="step-1">
            <translate tag="h1" say="Choose Format"/>
            <div class="step-content">
                <select v-model="format" :disabled="exporting">
                    <translate tag="option" value="json" say="Database Backup"/>
                    <translate tag="option" value="csv" say="Predefined CSV"/>
                    <translate tag="option" value="customCsv" say="Custom CSV"/>
                </select>
            </div>
        </div>
        <div class="step-2" v-if="step > 1">
            <translate tag="h1" say="Select Options"/>
            <div class="step-content" v-if="format === 'json'">
                <translate tag="label" for="passwords-export-encrypt" say="Backup password" title="(Optional) Encrypts the backup"/>
                <input type="password" id="passwords-export-encrypt" minlength="10" :title="backupPasswordTitle" v-model="options.password" :disabled="exporting" readonly/>
                <br>
                <br>
            </div>
            <div class="step-content" v-if="format !== 'customCsv'">
                <input type="checkbox" id="passwords-export-passwords" value="passwords" @change="setExportModel($event)" :disabled="exporting" :checked="models.indexOf('passwords') !== -1"/>
                <translate tag="label" for="passwords-export-passwords" say="Export Passwords"/>
                <br>
                <input type="checkbox" id="passwords-export-folders" value="folders" @change="setExportModel($event)" :disabled="exporting" :checked="models.indexOf('folders') !== -1"/>
                <translate tag="label" for="passwords-export-folders" say="Export Folders"/>
                <br>
                <input type="checkbox" id="passwords-export-tags" value="tags" @change="setExportModel($event)" :disabled="exporting" :checked="models.indexOf('tags') !== -1"/>
                <translate tag="label" for="passwords-export-tags" say="Export Tags"/>
                <br>
                <br>
                <input type="checkbox" id="passwords-export-shared" v-model="options.excludeShared" :disabled="exporting" v-if="models.indexOf('passwords') !== -1"/>
                <translate tag="label" for="passwords-export-shared" say="Export passwords shared with me" v-if="models.indexOf('passwords') !== -1"/>
            </div>
            <div class="step-content" v-else>
                <translate tag="label" for="passwords-export-csv-db" say="Database"/>
                <select id="passwords-export-csv-db" v-model="options.db" :disabled="exporting">
                    <translate tag="option" value="passwords" say="Passwords"/>
                    <translate tag="option" value="folders" say="Folders"/>
                    <translate tag="option" value="tags" say="Tags"/>
                </select>
                <br>
                <translate tag="label" for="passwords-export-csv-delimiter" say="Field delimiter"/>
                <select id="passwords-export-csv-delimiter" v-model="options.delimiter" :disabled="exporting">
                    <translate tag="option" value="," say="Comma"/>
                    <translate tag="option" value=";" say="Semicolon"/>
                    <translate tag="option" value=" " say="Space"/>
                    <translate tag="option" value="	" say="Tab"/>
                </select>
                <br>
                <br>
                <input type="checkbox" id="passwords-export-csv-header" v-model="options.header" :disabled="exporting"/>
                <translate tag="label" for="passwords-export-csv-header" say="Add Header Line"/>
                <br>
                <input type="checkbox" id="passwords-export-csv-shared" v-model="options.excludeShared" :disabled="exporting" v-if="options.db === 'passwords'"/>
                <translate tag="label" for="passwords-export-csv-shared" say="Export passwords shared with me" v-if="options.db === 'passwords'"/>
                <br>
                <br>
                <translate tag="h3" say="CSV Field Mapping"/>
                <div class="csv-mapping">
                    <div v-for="id in options.mapping.length+1" class="csv-mapping-field" :key="id">
                        <select @change="csvFieldMapping($event, id)" :disabled="exporting">
                            <translate tag="option" value="null" say="Empty"/>
                            <translate tag="option" v-for="option in csvFieldOptions" :value="option" :say="option.capitalize()" :key="option"/>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="step-3" v-if="step > 2">
            <translate tag="h1" say="Run Export"/>
            <div class="step-content">
                <translate tag="button" @click="exportDb" :say="buttonText" :variables="{format: this.format.toUpperCase()}" :disabled="exporting"/>
            </div>
        </div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import Utility from "@js/Classes/Utility";
    import Messages from "@js/Classes/Messages";
    import ExportManager from '@js/Manager/ExportManager';

    export default {
        components: {
            Translate
        },

        data() {
            return {
                format    : 'json',
                options   : {includeShared: false},
                models    : ['passwords', 'folders', 'tags'],
                step      : 3,
                data      : null,
                buttonText: 'Export',
                exporting : false,
                fieldMap  : {
                    passwords: ['password', 'username', 'label', 'notes', 'url', 'folderLabel', 'tagLabels', 'edited', 'created', 'favourite', 'id', 'revision', 'folderId', 'tagIds'],
                    folders  : ['label', 'parentLabel', 'edited', 'created', 'favourite', 'id', 'revision', 'parentId'],
                    tags     : ['label', 'color', 'edited', 'created', 'favourite', 'id', 'revision']
                }
            };
        },

        computed: {
            csvFieldOptions() {
                return this.fieldMap[this.options.db];
            },
            backupPasswordTitle() {
                return Utility.translate('(Optional) Encrypts the backup');
            }
        },

        created() {
            this.preventPasswordFill(500);
        },

        methods: {
            preventPasswordFill(t = 300) {
                setTimeout(() => {document.getElementById('passwords-export-encrypt').removeAttribute('readonly');}, t);
            },
            exportDb() {
                if(this.data) {
                    this.downloadFile();
                    return;
                }

                ExportManager.exportDatabase(this.format, this.models, this.options)
                             .catch((e) => {
                                 this.exporting = false;
                                 if(typeof e !== 'string') e = e.message;
                                 Messages.alert(e, 'Export error');
                             })
                             .then((d) => {
                                 if(d) {
                                     this.data = d;
                                     this.buttonText = 'Download {format}';
                                 } else {
                                     Messages.alert('There is no data to export', 'Nothing to export');
                                 }
                                 this.exporting = false;
                             });

                this.buttonText = 'Waiting...';
                this.exporting = true;
            },
            setExportModel($e) {
                let model = $e.target.value,
                    index = this.models.indexOf(model);

                if($($e.target).prop("checked")) {
                    if(index === -1) {
                        this.models.push(model);
                    }
                } else if(index !== -1) {
                    this.models.remove(index);
                }
            },
            generateFilename(models) {
                let date    = new Date(),
                    exports = [],
                    fileExt = this.format === 'customCsv' ? 'csv':this.format;

                for(let i = 0; i < models.length; i++) {
                    exports.push(Utility.translate(models[i].capitalize()));
                }

                return exports.join('+') + '_' + date.toLocaleDateString() + '.' + fileExt;
            },
            downloadFile() {
                let mime = this.format === 'json' ? 'application/json':'text/csv';
                if(typeof this.data === 'string') {
                    let filename = this.generateFilename(this.models);
                    Utility.createDownload(this.data, filename, mime);
                } else if(this.data !== null) {
                    for(let i in this.data) {
                        if(!this.data.hasOwnProperty(i)) continue;

                        let filename = this.generateFilename([i]);
                        Utility.createDownload(this.data[i], filename, mime);
                    }
                }
            },
            csvFieldMapping(event, id) {
                let mapping = this.options.mapping.clone(),
                    value   = $(event.target).val();

                id--;
                if(value === 'null') value = null;
                mapping[id] = value;
                if(!value && id + 1 === mapping.length) mapping = mapping.remove(id);

                this.options.mapping = mapping;
            },
            validateStep() {
                if(this.models.length === 0) {
                    this.step = 2;
                } else if(this.format === 'json' && this.options.password && this.options.password.length < 10) {
                    this.step = 2;
                } else if(this.format === 'customCsv' && this.options.mapping.length === 0) {
                    this.step = 2;
                } else {
                    this.step = 3;
                }
                this.buttonText = 'Export';
                this.data = null;
            }
        },

        watch: {
            format(value) {
                if(value === 'customCsv') {
                    this.options = {db: 'passwords', delimiter: ',', header: true, mapping: []};
                } else if(value === 'json') {
                    this.preventPasswordFill();
                }

                this.validateStep();
            },
            models(value) {
                this.validateStep();
            },
            'options.db'(value) {
                this.models = [value];
                this.options.mapping = [];
                $('.csv-mapping-field select').val('');
                this.validateStep();
            },
            'options.password'(value) {
                if(value.length !== 0 && value.length < 10) {
                    document.getElementById('passwords-export-encrypt').className = 'invalid';
                } else {
                    document.getElementById('passwords-export-encrypt').className = '';
                }
            },
            options: {
                handler(value) {
                    this.validateStep();
                },
                deep: true
            }
        }
    };
</script>

<style lang="scss">
    .export-container {
        .step-2 {
            .step-content {
                label {
                    margin-right : 5px;
                }

                label {
                    min-width : 90px;
                    display   : inline-block;
                }
            }
        }

        #passwords-export-encrypt.invalid,
        #passwords-export-encrypt:invalid {
            box-shadow : $color-red 0 0 3px 1px
        }
    }
</style>