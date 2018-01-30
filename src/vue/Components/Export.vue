<template>
    <div class="backup-dialog export-container">
        <div class="step-1">
            <translate tag="h1" say="Choose Format"/>
            <div class="step-content">
                <select v-model="format" :disabled="exporting">
                    <translate tag="option" value="null" say="Please choose"/>
                    <translate tag="option" value="json" say="Database Backup"/>
                    <translate tag="option" value="csv" say="CSV"/>
                </select>
            </div>
        </div>
        <div class="step-2" v-if="step > 1">
            <translate tag="h1" say="Select Databases"/>
            <div class="step-content">
                <input type="checkbox" id="passwords-export-passwords" value="passwords" @change="setExportModel($event)" :disabled="exporting"/>
                <translate tag="label" for="passwords-export-passwords" say="Passwords"/>
                <br>
                <input type="checkbox" id="passwords-export-folders" value="folders" @change="setExportModel($event)" :disabled="exporting"/>
                <translate tag="label" for="passwords-export-folders" say="Folders"/>
                <br>
                <input type="checkbox" id="passwords-export-tags" value="tags" @change="setExportModel($event)" :disabled="exporting"/>
                <translate tag="label" for="passwords-export-tags" say="Tags"/>
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
                format    : 'null',
                models    : [],
                step      : 1,
                data      : null,
                buttonText: 'Export',
                exporting : false
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
            }
        },

        watch: {
            format(d) {
                if(this.step === 1) this.step = 2;
                if(d === 'null') {
                    this.step = 1;
                    this.models = [];
                }

                this.buttonText = 'Export';
                this.data = null;
            },
            models(d) {
                if(this.step === 2) this.step = 3;
                if(d.length === 0 && this.step === 3) this.step = 2;
                this.buttonText = 'Export';
                this.data = null;
            }
        }
    }
</script>