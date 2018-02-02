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
            <div class="step-content" v-if="format !== 'customCsv'">
                <input type="checkbox" id="passwords-export-passwords" value="passwords" @change="setExportModel($event)" :disabled="exporting" :checked="models.indexOf('passwords') !== -1"/>
                <translate tag="label" for="passwords-export-passwords" say="Export Passwords"/>
                <br>
                <input type="checkbox" id="passwords-export-folders" value="folders" @change="setExportModel($event)" :disabled="exporting" :checked="models.indexOf('folders') !== -1"/>
                <translate tag="label" for="passwords-export-folders" say="Export Folders"/>
                <br>
                <input type="checkbox" id="passwords-export-tags" value="tags" @change="setExportModel($event)" :disabled="exporting" :checked="models.indexOf('tags') !== -1"/>
                <translate tag="label" for="passwords-export-tags" say="Export Tags"/>
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
                <translate tag="h3" say="CSV Field Mapping"/>
                <div class="csv-mapping">
                    <div v-for="id in options.mapping.length+1" class="csv-mapping-field" :key="id">
                        <select @change="csvFieldMapping($event, id)" :disabled="exporting">
                            <translate tag="option" value="null" say="Empty"/>
                            <translate tag="option" v-for="option in csvFieldOptions" :value="option" :say="option.capitalize()" :key="option"/>
                        </select>
                    </div>
                </div>
                <input type="checkbox" id="passwords-export-csv-header" v-model="options.header" :disabled="exporting"/>
                <translate tag="label" for="passwords-export-csv-header" say="Add Header Line"/>
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
    import ExportManager from '@js/Manager/ExportManager';
    import Utility from "@js/Classes/Utility";

    export default {
        components: {
            Translate
        },

        data() {
            return {
                format    : 'json',
                options   : {},
                models    : ['passwords', 'folders', 'tags'],
                step      : 3,
                data      : null,
                buttonText: 'Export',
                exporting : false,
                fieldMap  : {
                    passwords: ['password', 'username', 'label', 'notes', 'url', 'edited', 'favourite', 'folderlabel', 'taglabels', 'folder', 'tags', 'id', 'revision'],
                    folders  : ['label', 'edited', 'favourite', 'parentlabel', 'parent', 'id', 'revision'],
                    tags     : ['label', 'color', 'edited', 'favourite', 'id', 'revision']
                }
            }
        },

        computed: {
            csvFieldOptions() {
                return this.fieldMap[this.options.db];
            }
        },

        methods: {
            exportDb() {
                if(this.data) {
                    this.downloadFile();
                    return;
                }

                ExportManager.exportDatabase(this.format, this.models)
                    .catch((e) => {
                        this.exporting = false;
                        alert(e);
                    })
                    .then((d) => {
                        this.data = d;
                        this.buttonText = 'Download {format}';
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
                    exports = [];

                for(let i = 0; i < models.length; i++) {
                    exports.push(Utility.translate(models[i].capitalize()))
                }

                return exports.join('+') + '_' + date.toLocaleDateString() + '.' + this.format;
            },
            downloadFile() {
                if(typeof this.data === 'string') {
                    let filename = this.generateFilename(this.models);
                    Utility.createDownload(this.data, filename);
                } else if(this.data !== null) {
                    for(let i in this.data) {
                        if(!this.data.hasOwnProperty(i)) continue;

                        let filename = this.generateFilename([i]);
                        Utility.createDownload(this.data[i], filename);
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
            }
        },

        watch: {
            format(value) {
                if(value === 'customCsv') {
                    this.options = {db: 'passwords', delimiter: ',', header: true, mapping: []}
                } else {
                    this.step = 3;
                }

                this.buttonText = 'Export';
                this.data = null;
            },
            models(value) {
                if(this.step === 2) this.step = 3;
                if(value.length === 0 && this.step === 3) this.step = 2;
                this.buttonText = 'Export';
                this.data = null;
            },
            'options.mapping'(value) {
                if(value.length !== 0 && this.step === 2) {
                    this.step = 3;
                } else if(value.length === 0 && this.step === 3) this.step = 2;
            }
        }
    }
</script>