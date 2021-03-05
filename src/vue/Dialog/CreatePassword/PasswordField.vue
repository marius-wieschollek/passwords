<template>
    <div class="password-form-field-wrapper">
        <translate tag="label" for="password-password" say="Password" icon="key" class="area-label" />
        <div class="area-options">
            <icon :icon="visible ? 'eye':'eye-slash'" @click="visible = !visible" title="Toggle visibility" />
            <icon icon="refresh" :spin="generating" @click="generatePassword" title="Generate password" />
            <popup-menu>
                <ul slot="items">
                    <li>
                        <translate :icon="numbers ? 'check-square-o':'square-o'" @click="numbers = !numbers" say="Numbers" />
                    </li>
                    <li>
                        <translate :icon="special ? 'check-square-o':'square-o'" @click="special = !special" say="Special Characters" />
                    </li>
                </ul>
            </popup-menu>
        </div>
        <input id="password-password"
               :type="visible ? 'text':'password'"
               pattern=".{1,256}"
               autocomplete="new-password"
               v-model="model"
               :readonly="readonly"
               class="area-input"
               required>
    </div>
</template>

<script>
    import AbstractField from '@vue/Dialog/CreatePassword/AbstractField';
    import Translate  from '@vc/Translate';
    import API        from '@js/Helper/api';
    import Icon       from '@vc/Icon';
    import PopupMenu  from '@vc/PopupMenu';

    export default {
        components: {PopupMenu, Icon, Translate},
        extends   : AbstractField,
        data() {
            return {
                visible   : false,
                generating: false,
                numbers   : false,
                special   : false,
                readonly  : true,
                strength  : 1
            };
        },

        mounted() {
            this.$nextTick(() => {
                this.readonly = false;
            });
        },

        methods: {
            generatePassword() {
                if(this.generating) return;
                this.generating = true;
                let numbers  = undefined,
                    special  = undefined,
                    strength = undefined;

                if(false) {
                    numbers = this.numbers;
                    special = this.special;
                    strength = this.strength;
                }

                API.generatePassword(strength, numbers, special)
                   .then((d) => {
                       this.model = d.password;
                       this.visible = true;
                   })
                   .finally(() => {
                       this.generating = false;
                   });
            }
        }
    };
</script>

<style lang="scss">

</style>