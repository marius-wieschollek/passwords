<template>
    <component :is="tag" @click="fireEvent($event)" :title="getTitle" :aria-label="getAriaLabel" :value="getValue">
        <slot name="icon" v-if="!icon"></slot>
        <icon v-if="icon" :icon="icon" :class="iconClass" />
        {{ getText }}
        <slot name="default" v-if="say"></slot>
    </component>
</template>

<script>
    import Icon from "@vc/Icon";
    import LocalisationService from "@js/Services/LocalisationService";

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
                    return LocalisationService.translate(this.say, this.variables);
                }
                if (this.$slots.default) {
                    return LocalisationService.translate(this.$slots.default[0].text.trim(), this.variables);
                }
                return '';
            },
            getTitle() {
                return this.title ? LocalisationService.translate(this.title, this.variables):false;
            },
            getValue() {
                if(this.localizedValue !== null) return LocalisationService.translate(this.localizedValue, this.variables);
                return this.value;
            },
            getAriaLabel() {
                let text = this.getText,
                    title = this.getTitle;
                return `${text ? text:this.getValue}${title ? ` (${title})`:''}`
            }
        },
        methods : {
            fireEvent($event) {
                this.$emit($event.type, $event)
            }
        }
    };
</script>