<template>
    <form class="passwords-form">
        <div v-for="field in getFields">
            <translate :say="field.label"></translate>
            <input :type="field.type" :value="field.value" :name="field.name" :id="field.id" v-model="fields[field.name]">
        </div>
    </form>
</template>

<script>
    import Translate from '@vue/Components/Translate';

    export default {
        components: {Translate},
        props: {
            message: {
                type: String|Array
            },
            form: {
                type: Object
            }
        },
        data() {
            return {
                fields: {}
            }
        },
        computed: {
            getFields() {
                let fields = [];

                for(let name in this.form) {
                    if(!this.form.hasOwnProperty(name)) continue;
                    let field = this.form[name],
                        value = field.value ? field.value:'',
                        type  = field.type ? field.type:'text',
                        id = `password-field-${name}`,
                        label = field.label ? field.label:name.capitalize();

                    this.fields[name] = value;

                    fields.push({name,value,type,id,label});
                }

                return fields;
            }
        }
    };
</script>

<style lang="scss">

</style>