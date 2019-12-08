<template>
    <component :is="tag"
               :type="type"
               :value="userInput"
               ref="field"
               :placeholder="getPlaceholder"
               :title="getTitle"
               @input="inputEvent()"
               @keyup="fireEvent($event)"
               @keydown="fireEvent($event)"
               @keypress="fireEvent($event)"/>
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
        data() {
            return {
                userInput: this.value
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
            },
            fireEvent($event) {
                this.$emit($event.type, $event)
            }
        },
        watch: {
            value(value) {
                this.$refs.field.value = value;
                this.userInput = value;
            }
        }
    }
</script>

<style scoped>

</style>