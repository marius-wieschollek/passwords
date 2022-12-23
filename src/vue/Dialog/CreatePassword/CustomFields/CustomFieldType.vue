<!--
  - @copyright 2022 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <select v-model="model">
        <translate tag="option" value="" say="Type" selected v-if="empty"/>
        <translate tag="option" value="text" say="Text"/>
        <translate tag="option" value="secret" say="Secret"/>
        <translate tag="option" value="email" say="Email"/>
        <translate tag="option" value="url" say="Website"/>
        <translate tag="option" value="file" say="File"/>
        <translate tag="option" value="data" say="Data" v-if="showData"/>
    </select>
</template>

<script>
    import Translate from "@vc/Translate";
    import SettingsService from "@js/Services/SettingsService";

    export default {
        components: {Translate},
        props     : {
            value: String,
            empty: {
                type   : Boolean,
                default: false
            }
        },

        data() {
            return {
                model   : this.value,
                showData: SettingsService.get('client.ui.custom.fields.show.hidden', false)
            };
        },

        watch: {
            value(value) {
                this.model = value;
            },
            model(value) {
                if(this.value !== value) {
                    this.$emit('input', value);
                }
            }
        }
    };
</script>