<template>
    <div class="custom-fields" id="custom-fields">
        <custom-field-form :field="field"
                           :taken-names="getTakenNames"
                           @deleted="deleteField"
                           @updated="updateField"
                           v-for="(field, index) in getFields"
                           :key="index"
                           :is-blank="field === undefined">
            <hr v-if="index !== getFields.length-1">
        </custom-field-form>
    </div>
</template>

<script>
    import Utility from '@js/Classes/Utility';
    import SettingsManager from '@js/Manager/SettingsManager';
    import CustomFieldForm from '@vue/Dialog/CreatePassword/CustomFieldForm';

    export default {
        components: {CustomFieldForm},
        props     : {
            fields: {
                type: Object
            }
        },
        data() {
            return {
                showHiddenFields: SettingsManager.get('client.ui.custom.fields.show.hidden'),
                customFields    : this.fields,
                fieldPositions  : {}
            };
        },
        computed  : {
            getFields() {
                let fields     = [],
                    fieldCount = 0;

                for(let name in this.customFields) {
                    if(!this.customFields.hasOwnProperty(name)) continue;
                    fieldCount++;
                    if(!this.showHiddenFields && name.substr(0, 1) === '_') continue;

                    let position = fields.length;
                    if(this.fieldPositions.hasOwnProperty(name)) {
                        position = this.fieldPositions[name];
                    } else {
                        this.fieldPositions[name] = position;
                    }
                    fields.push(
                        {
                            name,
                            position,
                            type : this.customFields[name].type,
                            value: this.customFields[name].value
                        }
                    );
                }

                if(fieldCount < 20) fields.push(undefined);
                return Utility.sortApiObjectArray(fields, 'position');
            },
            getTakenNames() {
                return Object.keys(this.customFields);
            }
        },
        methods   : {
            updateField($event) {
                let fields       = Utility.cloneObject(this.customFields),
                    originalName = $event.originalName,
                    name         = $event.name;

                if(originalName !== name) {
                    if(this.fieldPositions.hasOwnProperty(originalName)) {
                        this.fieldPositions[name] = this.fieldPositions[originalName];
                        delete this.fieldPositions[originalName];
                    }

                    if(originalName !== '') delete fields[originalName];
                }

                fields[name] = {
                    type : $event.type,
                    value: $event.value
                };
                this.customFields = fields;
            },
            deleteField($event) {
                let fields = Utility.cloneObject(this.customFields);
                delete fields[$event];
                this.customFields = fields;
            }
        },
        watch     : {
            fields() {
                if(JSON.stringify(this.fields) !== JSON.stringify(this.customFields)) {
                    this.customFields = this.fields;
                }
            },
            customFields() {
                let fields = {};
                for(let name in this.customFields) {
                    if(!this.customFields.hasOwnProperty(name) || !name.length) continue;
                    let field = this.customFields[name];

                    fields[name] = {type: field.type, value: field.value};
                }

                this.$emit('updated', fields);
            }
        }
    };
</script>

<style lang="scss">
    #app-popup #passwords-create-new #custom-fields {
        hr {
            color      : transparent;
            border     : none;
            border-top : 1px solid var(--color-border-dark);
        }
    }
</style>