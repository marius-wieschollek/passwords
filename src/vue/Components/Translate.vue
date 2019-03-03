<template>
    <component :is="tag" @click="fireEvent($event)" :title="getTitle" :value="getValue">
        <i v-if="icon" :class="getIcon" aria-hidden="true"></i>
        {{ getText }}
        <slot name="default" v-if="say"></slot>
    </component>
</template>

<script>
    import Localisation from '@js/Classes/Localisation';

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
                if(this.say) {
                    return Localisation.translate(this.say, this.variables);
                }
                if(this.$slots.default) {
                    return Localisation.translate(this.$slots.default[0].text.trim(), this.variables);
                }
                return '';
            },
            getTitle() {
                return this.title ? Localisation.translate(this.title, this.variables):false;
            },
            getValue() {
                return this.value ? Localisation.translate(this.value, this.variables):false;
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