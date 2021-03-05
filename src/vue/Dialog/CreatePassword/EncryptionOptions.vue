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
    <div class="password-form-encryption-wrapper" v-if="hasEncryption">
        <translate tag="label" for="password-cse" say="Encryption" />
        <select id="password-cse"
                name="cseType"
                :title="title"
                v-model.number="password.cseType">
            <translate tag="option" value="none" say="On the server" />
            <translate tag="option" value="CSEv1r1" say="Libsodium" />
        </select>
    </div>
</template>

<script>
    import API       from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import Localisation from '@js/Classes/Localisation';

    export default {
        components: {Translate},
        props     : {
            password: Object
        },

        data() {
            return {
                hasEncryption: API.hasEncryption
            };
        },

        computed: {
            title() {
                return Localisation.translate('Choose the encryption type for this password');
            }
        }
    };
</script>