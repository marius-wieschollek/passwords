<template>
    <component :is="tag" @click="fireEvent($event)" :title="getTitle" :value="getValue">
        <i v-if="icon" :class="getIcon"></i>
        {{ getText }}
        <slot name="default" v-if="say"></slot>
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
            iconClass: {
                type     : String,
                'default': null
            },
            title    : {
                type     : String,
                'default': ''
            },
            value    : {
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
                if (this.say) {
                    return Utility.translate(this.say, this.variables);
                }
                if (this.$slots.default) {
                    return Utility.translate(this.$slots.default[0].text.trim(), this.variables);
                }
                return '';
            },
            getTitle() {
                return this.title ? Utility.translate(this.title, this.variables):false;
            },
            getValue() {
                return this.value ? Utility.translate(this.value, this.variables):false;
            },
            getIcon() {
                return 'fa fa-' + this.icon + (this.iconClass === null ? '':' ' + this.iconClass);
            }
        },
        methods : {
            fireEvent($event) {
                this.$emit($event.type, $event)
            }
        }
    };
</script>