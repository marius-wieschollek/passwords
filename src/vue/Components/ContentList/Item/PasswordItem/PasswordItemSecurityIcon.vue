<!--
  - @copyright 2026 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <router-link :to="securityRoute" :title="securityTitle" v-if="password.statusCode === 'DUPLICATE'" @click.prevent.stop @dblclick.prevent.stop>
        <shield-half-full-icon :size="20" fill-color="var(--color-element-warning)"/>
    </router-link>
    <shield-half-full-icon :size="20" :fill-color="securityColor" :title="securityTitle" v-else/>
</template>

<script>
    import ShieldHalfFullIcon from "@icon/ShieldHalfFull.vue";
    import LocalisationService from "@js/Services/LocalisationService";

    export default {
        components: {
            ShieldHalfFullIcon
        },

        props: {
            password: {
                type: Object
            }
        },

        computed: {
            securityColor() {
                switch(this.password.status) {
                    case 0:
                        return 'var(--color-element-success)';
                    case 1:
                        return 'var(--color-element-warning)';
                    case 2:
                        return 'var(--color-element-error)';
                    case 3:
                        return 'var(--color-main-text)';
                }
            },
            securityTitle() {
                let label = 'Unknown';
                if(this.password.status === 0) label = 'Secure';
                if(this.password.status === 1) label = `Weak (${this.password.statusCode.toLowerCase().capitalize()})`;
                if(this.password.status === 2) label = 'Breached';

                return LocalisationService.translate(label);
            },
            securityRoute() {
                return {name: 'Search', params: {query: btoa('hash:' + this.password.hash)}};
            }
        }
    };
</script>

<style lang="scss">

#app-content {
    .item-list {
        .row {
            .shield-half-full-icon {
                margin      : 1rem;
                flex-grow   : 0;
                flex-shrink : 0;
                cursor      : pointer;
            }
        }
    }
}
</style>