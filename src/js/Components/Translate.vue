<template>
    <span>
        <i v-if="icon" :class="getIcon"></i>
        {{ getText }}
    </span>
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
            }
        },

        computed: {
            getText() {
                if(this.$slots.default) {
                    return Utility.translate(this.$slots.default[0].text.trim(), this.variables)
                }
                return Utility.translate(this.say, this.variables)
            },
            getIcon() {
                return 'fa fa-' + this.icon;
            }
        }
    };
</script>