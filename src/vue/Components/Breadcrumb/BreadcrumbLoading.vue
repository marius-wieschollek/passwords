<template>
    <div class="passwords-breadcrumbs-loading passwords-breadcrumbs breadcrumb breadcrumb--collapsed">
        <nc-loading-icon :size="24" v-if="loading" />
    </div>
</template>

<script>
    import NcLoadingIcon from '@nc/NcLoadingIcon';

    export default {
        components: {NcLoadingIcon},
        data() {
            return {
                loading: false,
                timeout: null
            };
        },

        mounted() {
            this.timeout = setTimeout(() => {
                this.loading = true;
                this.timeout = null;
            }, 100);
        },

        destroyed() {
            if(this.timeout) {
                clearTimeout(this.timeout);
            }
        }
    };
</script>

<style lang="scss">
.passwords-breadcrumbs-loading {
    height      : var(--header-menu-item-height);
    display     : flex;
    align-items : center;
    margin      : .5rem;
}
</style>