<template>
    <li>
        <div>
            <video width="100%" :src="imagePath"></video>
        </div>
    </li>
</template>

<script>
    import Translate from "@/vue/Components/Translate";
    export default {
        components: {Translate},
        computed: {
            imagePath() {
                return oc_appswebroots.passwords + '/img/demo-folders.webm';
            }
        }
    };
</script>

<style lang="scss">
    #setup-slide-keep-order {
        p:nth-of-type(1):before {
            content : "\F07b";
        }
        p:nth-of-type(2):before {
            content : "\F02c";
        }
    }
</style>