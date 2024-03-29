<template>
    <div class="backup-dialog export-container">
        <div class="step-1">
            <translate tag="h1" say="Choose Format"/>
            <div class="step-content">
                <select v-model="format" id="passwords-export-target" :disabled="exporting">
                    <translate tag="option" value="json" say="Database Backup"/>
                    <translate tag="option" value="csv" say="Predefined CSV"/>
                    <translate tag="option" value="xlsx" say="Microsoft Excel"/>
                    <translate tag="option" value="ods" say="Open Office Calc"/>
                    <translate tag="option" value="customCsv" say="Custom CSV"/>
                </select>
            </div>
        </div>
        <div class="step-2" v-if="step > 1">
            <translate tag="h1" say="Select Options"/>
            <div class="step-content" v-if="format === 'json'">
                <translate tag="label"
                           for="passwords-export-encrypt"
                           say="Backup password"
                           title="(Optional) Encrypts the backup"/>
                <input ref="backupEncrypt"
                       type="password"
                       id="passwords-export-encrypt"
                       minlength="10"
                       :title="t('(Optional) Encrypts the backup')"
                       v-model="options.password"
                       :disabled="exporting"
                       autocomplete="new-password"
                       readonly/>
                <br>
                <br>
            </div>
            <div class="step-content" v-if="format !== 'customCsv'">
                <translate tag="div"
                           class="office warning"
                           say="The import only supports CSV"
                           v-if="format === 'xlsx' || format === 'ods'"/>
                <div class="step-section">
                    <input type="checkbox"
                           id="passwords-export-passwords"
                           value="passwords"
                           @change="setExportModel($event)"
                           :disabled="exporting"
                           :checked="models.indexOf('passwords') !== -1"/>
                    <translate tag="label" for="passwords-export-passwords" say="Export Passwords"/>
                </div>
                <div class="step-section">
                    <input type="checkbox"
                           id="passwords-export-folders"
                           value="folders"
                           @change="setExportModel($event)"
                           :disabled="exporting"
                           :checked="models.indexOf('folders') !== -1"/>
                    <translate tag="label" for="passwords-export-folders" say="Export Folders"/>
                </div>
                <div class="step-section">
                    <input type="checkbox"
                           id="passwords-export-tags"
                           value="tags"
                           @change="setExportModel($event)"
                           :disabled="exporting"
                           :checked="models.indexOf('tags') !== -1"/>
                    <translate tag="label" for="passwords-export-tags" say="Export Tags"/>
                </div>
                <br>
                <div class="step-section">
                    <input type="checkbox"
                           id="passwords-export-shared"
                           v-model="options.excludeShared"
                           :disabled="exporting"
                           v-if="models.indexOf('passwords') !== -1"/>
                    <translate tag="label"
                               for="passwords-export-shared"
                               say="Export passwords shared with me"
                               v-if="models.indexOf('passwords') !== -1"/>
                </div>
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
                <div class="step-section">
                    <input type="checkbox"
                           id="passwords-export-csv-header"
                           v-model="options.header"
                           :disabled="exporting"/>
                    <translate tag="label" for="passwords-export-csv-header" say="Add Header Line"/>
                </div>
                <div class="step-section">
                    <input type="checkbox"
                           id="passwords-export-csv-shared"
                           v-model="options.excludeShared"
                           :disabled="exporting"
                           v-if="options.db === 'passwords'"/>
                    <translate tag="label"
                               for="passwords-export-csv-shared"
                               say="Export passwords shared with me"
                               v-if="options.db === 'passwords'"/>
                </div>
                <br>
                <translate tag="h3" say="CSV Field Mapping"/>
                <div class="csv-mapping">
                    <div v-for="id in csvMappedFieldsSize" class="csv-mapping-field" :key="id">
                        <select @change="csvFieldMapping($event, id)"
                                :id="`passwords-mapping-${id}`"
                                :disabled="exporting">
                            <translate tag="option" say="Please choose" v-if="id === csvMappedFieldsSize"/>
                            <translate tag="option"
                                       v-for="option in csvFieldOptions"
                                       :value="option"
                                       :say="option.capitalize()"
                                       :key="option"/>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="step-3" v-if="step > 2">
            <translate tag="h1" say="Run Export"/>
            <div class="step-content">
                <translate tag="button"
                           @click="exportDb"
                           :say="buttonText"
                           :variables="{format: this.format.toUpperCase()}"
                           :disabled="exporting"
                           id="passwords-export-execute"/>
            </div>
        </div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import MessageService from "@js/Services/MessageService";
    import UtilityService from "@js/Services/UtilityService";
    import LocalisationService from "@js/Services/LocalisationService";
    import LoggingService from "@js/Services/LoggingService";

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
                    passwords: [
                        'password',
                        'username',
                        'label',
                        'notes',
                        'url',
                        'folderLabel',
                        'tagLabels',
                        'edited',
                        'created',
                        'favorite',
                        'customFields',
                        'id',
                        'revision',
                        'folderId',
                        'tagIds',
                        'empty'
                    ],
                    folders  : [
                        'label',
                        'parentLabel',
                        'edited',
                        'created',
                        'favorite',
                        'id',
                        'revision',
                        'parentId',
                        'empty'
                    ],
                    tags     : ['label', 'color', 'edited', 'created', 'favorite', 'id', 'revision', 'empty']
                }
            };
        },

        computed: {
            csvFieldOptions() {
                return this.fieldMap[this.options.db];
            },
            csvMappedFieldsSize() {
                return this.options.mapping.length + 1;
            }
        },

        created() {
            this.preventPasswordFill(1000);
        },

        methods: {
            preventPasswordFill(t = 300) {
                setTimeout(() => { if(this.$refs.backupEncrypt) this.$refs.backupEncrypt.removeAttribute('readonly');}, t);
            },
            async exportDb() {
                if(this.data) {
                    this.downloadFile();
                    return;
                }

                this.buttonText = 'Waiting...';
                this.exporting = true;

                try {
                    let module = await import(/* webpackChunkName: "ExportManager" */ '@js/Manager/ExportManager');
                    new module.ExportManager()
                        .exportDatabase(this.format, this.models, this.options)
                        .catch((e) => {
                            this.exporting = false;
                            console.error(e);
                            MessageService.alert(e.message, 'Export error');
                        })
                        .then((d) => {
                            if(d) {
                                this.data = d;
                                this.buttonText = 'Download {format}';
                            } else if(this.exporting) {
                                MessageService.alert('There is no data to export', 'Nothing to export');
                            }
                            this.exporting = false;
                        });
                } catch(e) {
                    LoggingService.error(e);
                    MessageService.alert(['Unable to load {module}', {module: 'ExportManager'}], 'Network error');
                }
            },
            setExportModel($e) {
                let model = $e.target.value,
                    index = this.models.indexOf(model);

                if($e.target.checked) {
                    if(this.format === 'csv' && navigator.userAgent.indexOf('WebKit') !== -1) {
                        this.models = [model];
                    } else if(index === -1) {
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

                if(models.length > 1 && fileExt === 'csv') fileExt = 'zip';
                for(let i = 0; i < models.length; i++) {
                    exports.push(LocalisationService.translate(models[i].capitalize()));
                }

                return `${exports.join('+')}_${date.toLocaleDateString()}.${fileExt}`;
            },
            downloadFile() {
                let mime = 'text/csv';
                if(this.format === 'csv' && this.models.length > 1) mime = 'application/zip';
                if(this.format === 'ods') mime = 'application/vnd.oasis.opendocument.spreadsheet';
                if(this.format === 'xlsx') mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

                let filename = this.generateFilename(this.models);
                UtilityService.createDownload(this.data, filename, mime);
            },
            csvFieldMapping(event, id) {
                let mapping = this.options.mapping.clone();

                mapping[id - 1] = event.target.value;
                for(let i = mapping.length - 1; i > 0; i--) {
                    if(mapping[i] === 'null' && (mapping[i - 1] === 'null' || i === mapping.length - 1)) {
                        mapping.pop();
                    } else {
                        break;
                    }
                }

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
                } else if(value === 'csv' && navigator.userAgent.indexOf('WebKit') !== -1 && this.models.length > 1) {
                    this.models = [this.models.shift()];
                } else if(value === 'json') {
                    this.preventPasswordFill();
                }

                this.validateStep();
            },
            models() {
                this.validateStep();
            },
            'options.db'(value) {
                this.models = [value];
                this.options.mapping = [];
                document.querySelectorAll('.csv-mapping-field select').forEach((e) => { e.value = null;});
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
                handler() {
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

            .office.warning {
                margin  : 5px !important;
                display : block;
            }

            .step-section {
                clear : both;
            }

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
        box-shadow : var(--color-error) 0 0 3px 1px
    }
}
</style>