<template>
    <div class="custom-fields" id="custom-fields">
        <custom-field-form
                :field="field"
                @deleted="deleteField"
                @updated="updateField"
                v-for="(field, index) in getFields"
                :key="index">
            <hr v-if="index !== fields.length">
        </custom-field-form>
    </div>
</template>

<script>
    import Utility from '@js/Classes/Utility';
    import SettingsService from '@js/Services/SettingsService';
    import CustomFieldForm from '@vue/Dialog/CreatePassword/CustomFieldForm';

    export default {
        components: {CustomFieldForm},
        props     : {
            fields: {
                type: Array
            }
        },
        data() {
            return {
                showHidden  : SettingsService.get('client.ui.custom.fields.show.hidden')
            };
        },
        computed  : {
            getFields() {
                let fields = [];

                for(let i = 0; i < this.fields.length; i++) {
                    let field = Utility.cloneObject(this.fields[i]);
                    if(field.type === 'data' && !this.showHidden) continue;

                    field.id = i;
                    field.blank = false;
                    fields.push(field);
                }

                if(fields.length < 20) {
                    fields.push({label: '', type: 'text', value: '', id: fields.length, blank: true});
                }

                return fields;
            }
        },
        methods   : {
            updateField($event) {
                let fields = Utility.cloneObject(this.fields);
                fields[$event.id] = {label: $event.label, type: $event.type, value: $event.value};
                this.$emit('updated', fields);
            },
            deleteField($event) {
                let customFields = Utility.cloneObject(this.fields);
                customFields.splice($event.id, 1);
                this.$emit('updated', customFields);
            },
            cloneFields() {

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