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
    <div class="buttons">
        <translate say="Connect with PassLink"
                   title="Link this client to your Nextcloud account without annoying passwords and urls"
                   tag="button"
                   @click="initPassLink()"
                   v-if="item.passlink.enabled"/>
        <web target="_blank"
             className="button"
             :class="{primary:download.primary, secondary:!download.primary}"
             :href="download.url"
             :text="download.label"
             v-for="download in item.downloads"
             :key="download.url"/>
    </div>
</template>

<script>
    import Translate from "@vc/Translate";
    import Web from "@vc/Web";
    import Connect from "@js/PassLink/Connect";

    export default {
        components: {Translate, Web},
        props     : {
            item: Object
        },
        methods   : {
            initPassLink() {
                Connect.initialize(this.item.passlink.link, this.item.passlink.qrcode, this.item.passlink.protocol);
            }
        }
    };
</script>

<style scoped>

</style>