<template>
    <component :is="tag" @click="fireEvent($event)" :title="getTitle">
        <i v-if="icon" :class="getIcon"></i>
        {{ getText }}
    </component>
</template>

<script>
    import Utility from "@js/Classes/Utility";

    export default {
        props: {
            say      : {
                type     : String,
                'default': null
            },
            variables: {
                type     : Object,
                'default': () => { return {}; }
            },
            icon     : {
                type     : String,
                'default': null
            },
            iconClass     : {
                type     : String,
                'default': null
            },
            title      : {
                type     : String,
                'default': ''
            },
            tag      : {
                type     : String,
                'default': 'span'
            }
        },

        computed: {
            getText() {
                if (this.$slots.default) {
                    return Utility.translate(this.$slots.default[0].text.trim(), this.variables)
                }
                return Utility.translate(this.say, this.variables)
            },
            getTitle() {
                return this.title ? Utility.translate(this.title, this.variables):'';
            },
            getIcon() {
                return 'fa fa-' + this.icon + ' ' + this.iconClass;
            }
        },
        methods: {
            fireEvent($event) {
                this.$emit($event.type, $event)
            }
        }
    };
</script>