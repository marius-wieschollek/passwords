<template>
    <li :style="getStyle"></li>
</template>

<script>
    export default {
        computed: {
            getStyle() {
                return {
                    'background-image': 'url(' + oc_appswebroots.firstrunwizard + '/img/intro.png)'
                }
            }
        }
    };
</script>

<style lang="scss">
    #setup-slide-start {
        background     : no-repeat center;
        background-size: cover;
    }
</style>