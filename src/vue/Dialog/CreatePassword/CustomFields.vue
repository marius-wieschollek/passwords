<template>
    <div class="custom-fields" id="custom-fields">
        <custom-field-form :field="field" :taken-names="getTakenNames" @deleted="deleteField" @updated="updateField" v-for="(field, index) in getFields">
            <hr v-if="index !== getFields.length-1">
        </custom-field-form>
    </div>
</template>

<script>
    import Utility from '@js/Classes/Utility';
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
                currentFields: this.fields
            };
        },
        computed  : {
            getFields() {
                let fields = [];

                for(let name in this.currentFields) {
                    if(!this.currentFields.hasOwnProperty(name)) continue;
                    fields.push(
                        {
                            name,
                            type : this.currentFields[name].type,
                            value: this.currentFields[name].value
                        }
                    );
                }

                if(fields.length < 20) fields.push(undefined);
                return fields;
            },
            getTakenNames() {
                return Object.keys(this.currentFields);
            }
        },
        methods   : {
            updateField($event) {
                let fields = Utility.cloneObject(this.currentFields);
                if($event.originalName !== '' && $event.originalName !== $event.name) {
                    delete fields[$event.originalName];
                }
                fields[$event.name] = {
                    type : $event.type,
                    value: $event.value
                };
                this.currentFields = fields;
            },
            deleteField($event) {
                delete this.currentFields[$event];
            }
        }
    };
</script>

<style lang="scss">
    #app-popup #passwords-create-new #custom-fields {
        hr {
            color      : transparent;
            border     : none;
            border-top : 1px solid $color-grey-light;
        }
    }
</style>