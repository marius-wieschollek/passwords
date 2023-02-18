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
    <nc-modal :container="container" size="small" v-on:close="close">
        <div class="password-change-dialog">
            <nc-loading-icon :size="64" v-if="loading"/>
            <div v-else-if="passwordPageUrl">
                <translate say="ChangePasswordPageText" :variables="password"/>
                <pre>{{ passwordPageUrl }}</pre>
                <nc-button :href="passwordPageUrl" target="_blank" type="primary" wide>
                    {{ t('ChangePasswordPageLink') }}
                </nc-button>
            </div>
            <div v-else>
                <translate say="ChangePasswordPageFallback" :variables="password"/>
                <br><br>
                <nc-button :href="password.url" target="_blank" type="primary" wide>
                    {{ t('Open {label}', {label: password.host}) }}
                </nc-button>
            </div>
        </div>
    </nc-modal>
</template>

<script>
    import API from "@js/Helper/api";
    import Utility from "@js/Classes/Utility";
    import Translate from '@vc/Translate';
    import NcModal from "@nc/NcModal";
    import NcButton from '@nc/NcButton';
    import NcLoadingIcon from '@nc/NcLoadingIcon';

    export default {
        components: {
            NcModal,
            NcButton,
            Translate,
            NcLoadingIcon
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                loading        : true,
                passwordPageUrl: null,
                container      : Utility.popupContainer(true)
            };
        },

        mounted() {
            this.loadPasswordPageUrl();
        },

        methods: {
            loadPasswordPageUrl() {
                this.loading = true;
                API.getPasswordChangeUrl(this.password.host)
                   .then((response) => {
                       this.passwordPageUrl = response.url;
                   })
                   .catch(() => {
                       this.passwordPageUrl = null;
                   })
                   .finally(() => {
                       this.loading = false;
                   });
            },
            close() {
                this.$destroy();
                if(this.$el.parentNode) this.$el.parentNode.removeChild(this.$el);
            }
        },

        watch: {
            password() {
                this.loadPasswordPageUrl();
            }
        }
    };
</script>

<style lang="scss">
.password-change-dialog {
    padding    : 1rem;
    min-height : 4rem;

    pre {
        max-width        : 100%;
        white-space      : normal;
        padding          : .5rem;
        background-color : var(--color-background-dark);
        border-radius    : var(--border-radius-large);
        margin           : 1rem 0;
        font-family      : var(--pw-mono-font-face);
        font-size        : .8rem;
        text-align       : center;
        word-break       : break-word;
    }
}
</style>