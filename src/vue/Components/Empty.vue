<template>
    <div class="empty-section">
        <nc-empty-content :title="t('There is nothing here')" :description="t(text)">
            <key-variant-icon slot="icon" fill-color="var(--color-primary-text)" :size="64"/>
            <div v-if="showSearchLink" slot="action">
                <nc-button :to="searchRoute" id="global-search-link">
                    {{ t('Search everywhere for "{query}"', {query: search.query}) }}
                </nc-button>
            </div>
        </nc-empty-content>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import SearchManager from '@js/Manager/SearchManager';
    import NcButton from '@nc/NcButton';
    import KeyVariantIcon from '@icon/KeyVariant';
    import NcEmptyContent from '@nc/NcEmptyContent';

    export default {
        components: {
            NcButton,
            KeyVariantIcon,
            NcEmptyContent,
        },
        props     : {
            text: {
                type     : String,
                'default': 'Click on "⋯" to add something'
            }
        },
        data() {
            return {
                search: SearchManager.status
            };
        },
        computed: {
            showSearchLink() {
                return this.search.active && this.$route.name !== 'Search';
            },
            searchRoute() {
                return {name: 'Search', params: {query: btoa(this.search.query)}};
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
        text-align : center;
        margin-top : 30vh;
        width      : 100%;

        img {
            display : inline-block;
            margin  : 0 auto 15px;
        }
    }
}
</style>