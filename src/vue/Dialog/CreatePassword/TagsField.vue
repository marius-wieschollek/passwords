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
    <div class="password-form-field-wrapper password-form-tags-wrapper">
        <translate tag="label" for="password-tags" say="Tags" icon="tags" class="area-label" />
        <input id="password-tags" type="text" class="area-input" >
    </div>
</template>

<script>
    import AbstractField from "@vue/Dialog/CreatePassword/AbstractField";
    import Translate from "@vc/Translate";

    export default {
        components: {Translate},
        extends: AbstractField
    };
</script>