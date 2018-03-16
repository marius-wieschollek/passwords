<template>
    <div class="empty-section">
        <img :src="icon" alt="">
        <translate tag="h2" say="There is nothing here"/>
        <translate :say="text"/>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from "@vue/Components/Translate";

    export default {
        components: {
            Translate
        },
        props     : {
            text: {
                type     : String,
                'default': 'Click on "+" to add something'
            }
        },
        data() {
            return {
                icon: oc_appswebroots.passwords + '/img/app-themed.svg'
            };
        },
        created() {
            API.getSetting('server.theme.app.icon')
                .then((d) => {this.icon = d});
        }
    };
</script>

<style lang="scss">
    #app.passwords {
        .empty-section {
            color      : #878787;
            text-align : center;
            margin-top : 30vh;
            width      : 100%;

            img {
                width: 64px;
                height: 64px;
                display : inline-block;
                margin : 0 auto 15px;
                opacity   : 0.4;
            }
        }
    }
</style>