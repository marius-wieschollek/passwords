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
    <div class="password-form-field-wrapper">
        <label :for="id" class="area-label">
            <icon :icon="icon"/>
            {{ model.label }}
        </label>
        <div class="area-options">
            <icon icon="trash-o"/>
        </div>
        <text-custom-field :id="id" class="area-input" v-model="model"/>
    </div>
</template>

<script>
    import Icon from "@vc/Icon";
    import Translate from "@vc/Translate";
    import AbstractField from "@vue/Dialog/CreatePassword/AbstractField";
    import TextCustomField from "@vue/Dialog/CreatePassword/CustomFields/TextCustomField";

    export default {
        components: {TextCustomField, Icon, Translate},
        extends   : AbstractField,
        props     : {
            value: Object
        },
        data() {
            return {
                id: `ps-custom-field-${this.value.type}-${Math.round(Math.random() * 10000)}`
            };
        },
        computed: {
            icon() {
                if(this.model.type === 'text') return 'font';
                if(this.model.type === 'secret') return 'lock';
                if(this.model.type === 'email') return 'envelope';
                if(this.model.type === 'url') return 'globe';
                if(this.model.type === 'file') return 'cloud';
                if(this.model.type === 'data') return 'file-archive-o';

                return 'question';
            }
        }
    };
</script>

<style lang="scss">

</style>