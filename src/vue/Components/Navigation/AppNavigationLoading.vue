<template>
    <div id="app-navigation-vue" class="app-navigation pw-navigation-loading">
        <nc-loading-icon :size="32" v-if="loading" />
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
            }, 50);
        },

        destroyed() {
            if(this.timeout) {
                clearTimeout(this.timeout);
            }
        }
    };
</script>

<style lang="scss">

#content-vue {
    .pw-navigation-loading {
        --color-text-maxcontrast : var(--color-text-maxcontrast-background-blur, var(--color-text-maxcontrast-default));
        transition               : transform var(--animation-quick), margin var(--animation-quick);
        width                    : 300px;
        position                 : relative;
        top                      : 0;
        left                     : 0;
        padding                  : 4rem 1rem;
        z-index                  : 1800;
        height                   : 100%;
        box-sizing               : border-box;
        user-select              : none;
        display                  : flex;
        flex-direction           : column;
        flex-grow                : 0;
        flex-shrink              : 0;
        background-color         : var(--color-main-background-blur, var(--color-main-background));
        backdrop-filter          : var(--filter-background-blur, none);
    }
}
</style>