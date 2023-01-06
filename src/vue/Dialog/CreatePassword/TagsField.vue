<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="password-form-field-wrapper password-form-tags-wrapper">
        <translate tag="label" for="password-tags" say="Tags" icon="tags" class="area-label"/>
        <tags id="password-tags" class="area-input" :value="value" v-on:input="$emit('input', $event)" :no-wrap="true" />
    </div>
</template>

<script>
    import Translate from "@vc/Translate";
    import Tags from "@vc/Tags";

    export default {
        components: {Translate, Tags},
        props     : {
            value: {
                type   : [Array, Object],
                default: () => {
                    return [];
                }
            }
        }
    };
</script>