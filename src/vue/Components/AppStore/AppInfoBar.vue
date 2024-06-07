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
    <div class="appstore-item-infos" :class="{official:official}">
        <web target="_blank"
             icon="certificate"
             className="sources"
             :href="item.links.homepage"
             text="official"
             title="This client is maintained by the authors of the Passwords app"
             v-if="official"/>
        <span class="features" v-if="hasFeatures">
            <span class="dot" v-if="official">⦁</span>
            <translate class="feature" icon="lock" say="Strong Encryption" title="This client officially supports our strong end-to-end encryption" v-if="hasEncryption"/>
            <span class="dot" v-if="!official">⦁</span>
        </span>
        <span class="author" v-if="!official">
            <web target="_blank" :href="item.author.homepage" :text="author"/>
            <span class="dot">⦁</span>
            <web target="_blank" :href="item.links.sources" text="source code"/>
        </span>
    </div>
</template>

<script>
    import Web from "@vc/Web";
    import Translate from "@vc/Translate";
    import LocalisationService from "@js/Services/LocalisationService";

    export default {
        components: {Translate, Web},
        props     : {
            item: Object
        },
        computed  : {
            author() {
                return LocalisationService.translate('by {author}', {author: this.item.author.name});
            },
            official() {
                return this.item.official;
            },
            hasFeatures() {
                return this.hasEncryption;
            },
            hasEncryption() {
                return this.item.features.encryption;
            }
        }
    };
</script>

<style lang="scss">
.appstore-item-infos {
    color         : var(--color-main-text);
    margin-bottom : 1rem;
    font-style    : italic;

    .dot {
        margin : 0 0.5rem;
    }

    .features .feature {
        cursor      : help;
        font-weight : 500;
    }

    a:hover {
        text-decoration : underline;
    }

    &.official {
        color : var(--color-success);

        .features .feature {
            font-weight : normal;
        }

        a.sources {
            color : var(--color-success);
        }
    }
}
</style>