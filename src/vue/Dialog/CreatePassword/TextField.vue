<template>
    <div class="password-form-field-wrapper">
        <translate tag="label" :for="getId" :say="label" :icon="icon" class="area-label" />
        <input :id="getId" type="text" class="area-input" :maxlength="maxlength" v-model="model"  :autocomplete="autocomplete">
    </div>
</template>

<script>
    import AbstractField from '@vue/Dialog/CreatePassword/AbstractField';
    import Translate     from '@vc/Translate';
    import Icon          from '@vc/Icon';

    export default {
        components: {Icon, Translate},
        extends   : AbstractField,
        props     : {
            id   : String,
            label: String,
            icon : String,
            maxlength : [String, Number],
            autocomplete: {
                type: String,
                default: null
            }
        },
        computed: {
            getId() {
                return `password-${this.id}`;
            }
        }
    };
</script>

<style lang="scss">

</style>