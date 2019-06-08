<template>
    <component :is="tag"
               :type="type"
               :value="value"
               ref="field"
               :placeholder="getPlaceholder"
               :title="getTitle"
               @input="inputEvent()"/>
</template>

<script>
    import Localisation from '@js/Classes/Localisation';

    export default {
        props   : {
            variables  : {
                type     : Object,
                'default': () => { return {}; }
            },
            title      : {
                type     : String,
                'default': null
            },
            value      : {
                type     : String,
                'default': ''
            },
            placeholder: {
                type     : String,
                'default': null
            },
            type       : {
                type     : String,
                'default': 'text'
            },
            tag        : {
                type     : String,
                'default': 'input'
            }
        },
        computed: {
            getPlaceholder() {
                return this.placeholder ? Localisation.translate(this.placeholder, this.variables):false;
            },
            getTitle() {
                return this.title ? Localisation.translate(this.title, this.variables):false;
            }
        },
        methods : {
            inputEvent() {
                this.$emit('input', this.$refs.field.value)
            }
        }
    }
</script>

<style scoped>

</style>