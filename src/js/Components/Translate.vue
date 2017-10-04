<template>
    <component :is="tag" @click="fireEvent($event)">
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
                'default': () => {}
            },
            icon     : {
                type     : String,
                'default': null
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
            getIcon() {
                return 'fa fa-' + this.icon;
            }
        },
        methods: {
            fireEvent($event) {
                this.$emit($event.type, $event)
            }
        }
    };
</script>