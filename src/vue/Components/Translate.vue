<template>
    <component :is="tag" @click="fireEvent($event)" :title="getTitle" :value="getValue">
        <icon v-if="icon" :icon="icon" :class="iconClass" />
        {{ getText }}
        <slot name="default" v-if="say"></slot>
    </component>
</template>

<script>
    import Localisation from '@js/Classes/Localisation';
    import Icon from "@vc/Icon";

    export default {
        components: {Icon},
        props: {
            say           : {
                type     : String,
                'default': null
            },
            variables     : {
                type     : Object,
                'default': () => { return {}; }
            },
            icon          : {
                type     : String,
                'default': null
            },
            iconClass     : {
                type     : String,
                'default': null
            },
            title         : {
                type     : String,
                'default': ''
            },
            value         : {
                'default': false
            },
            localizedValue: {
                type     : String,
                'default': null
            },
            tag           : {
                type     : String,
                'default': 'span'
            }
        },

        computed: {
            getText() {
                if (this.say) {
                    return Localisation.translate(this.say, this.variables);
                }
                if (this.$slots.default) {
                    return Localisation.translate(this.$slots.default[0].text.trim(), this.variables);
                }
                return '';
            },
            getTitle() {
                return this.title ? Localisation.translate(this.title, this.variables):false;
            },
            getValue() {
                if(this.localizedValue !== null) return Localisation.translate(this.localizedValue, this.variables);
                return this.value;
            }
        },
        methods : {
            fireEvent($event) {
                this.$emit($event.type, $event)
            }
        }
    };
</script>