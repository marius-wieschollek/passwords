<template>
    <div class="empty-section">
        <img :src="icon" alt="">
        <translate tag="h2" say="There is nothing here"/>
        <translate :say="text"/>
        <router-link :to="searchRoute" id="global-search-link" v-if="showSearchLink">
            <translate say="Search everywhere for &quot;{query}&quot;" :variables="{query: search.query}"/>
        </router-link>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from '@vue/Components/Translate';
    import SearchManager from '@js/Manager/SearchManager';

    export default {
        components: {
            Translate
        },
        props     : {
            text    : {
                type     : String,
                'default': 'Click on "+" to add something'
            }
        },
        data() {
            return {
                search   : SearchManager.status,
                icon: oc_appswebroots.passwords + '/img/app-themed.svg'
            };
        },
        computed: {
            showSearchLink() {
                return this.search.active && this.$route.name !== 'Search';
            },
            searchRoute() {
                return { name: 'Search', params: {query: this.search.query}};
            }
        },
        created() {
            API.getSetting('server.theme.app.icon')
               .then((d) => {this.icon = d;});
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
                width   : 64px;
                height  : 64px;
                display : inline-block;
                margin  : 0 auto 15px;
                opacity : 0.4;
            }

            #global-search-link {
                display: block;
            }
        }
    }
</style>