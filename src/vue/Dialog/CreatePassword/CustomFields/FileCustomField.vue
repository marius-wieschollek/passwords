<!--
  - @copyright 2021 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="password-form-custom-field password-form-file-field" v-if="isVisible">
        <div class="file-field" @click="chooseFile">
            <input :type="inputType" :id="id" v-model="data" v-bind="inputAttributes" required/>
            <span class="button" :style="style"/>
        </div>
        <custom-field-type v-model="type"/>
    </div>
</template>

<script>
    import AbstractCustomField from "@vue/Dialog/CreatePassword/CustomFields/AbstractCustomField";
    import Icon from "@vc/Icon";
    import SettingsService from "@js/Services/SettingsService";
    import Messages from "@js/Classes/Messages";

    export default {
        components: {Icon},
        extends   : AbstractCustomField,

        computed: {
            inputType() {
                return 'url';
            },
            inputAttributes() {
                return {
                    readonly: true
                };
            },
            style() {
                return {
                    backgroundImage: `url(${SettingsService.get('server.theme.folder.icon')})`
                };
            }
        },
        methods : {
            async chooseFile() {
                this.data = await Messages.filePicker();
            }
        }
    };
</script>

<style lang="scss">
.password-form-file-field {

    .file-field {
        display : flex;
    }

    input {
        cursor       : pointer;
        margin-right : 0;
        color        : var(--color-text-lighter);
    }

    .button {
        border-radius       : 0;
        border-left         : 0;
        background-repeat   : no-repeat;
        background-position : center;
        width               : 1rem;
        cursor              : pointer;
        margin-right        : 0;
    }
}
</style>