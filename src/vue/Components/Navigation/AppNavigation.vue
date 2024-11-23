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
    <nc-app-navigation>
        <app-navigation-search/>
        <template id="app-passwords-navigation" #list>
            <app-navigation-item :name="t('All')" :to="{ name: 'All'}" :exact="true">
                <template #icon>
                    <earth-icon :size="20"/>
                </template>
            </app-navigation-item>
            <app-navigation-item-folders/>
            <app-navigation-item :name="t('Recent')" :to="{ name: 'Recent'}">
                <template #icon>
                    <clock-icon :size="20"/>
                </template>
            </app-navigation-item>
            <app-navigation-item-favorites/>
            <app-navigation-item-shared/>
            <app-navigation-item-tags/>
            <app-navigation-item-security/>
            <app-navigation-item :name="t('Search')" :to="{ name: 'Search'}" v-if="isSearchVisible">
                <template #icon>
                    <magnify-icon :size="20"/>
                </template>
            </app-navigation-item>

            <session-timeout v-if="!isMobile && hasTimeout"/>
        </template>
        <template #footer>
            <app-navigation-more/>
        </template>
    </nc-app-navigation>
</template>
<script>
    import NcAppNavigation from '@nc/NcAppNavigation.js';
    import AppNavigationItem from '@vc/Navigation/AppNavigationItem';
    import AppNavigationItemFavorites from '@vc/Navigation/AppNavigationItemFavorites';
    import AppNavigationItemFolders from '@vc/Navigation/AppNavigationItemFolders';
    import AppNavigationItemSecurity from '@vc/Navigation/AppNavigationItemSecurity';
    import AppNavigationItemShared from '@vc/Navigation/AppNavigationItemShared';
    import AppNavigationItemTags from '@vc/Navigation/AppNavigationItemTags';
    import AppNavigationMore from '@vc/Navigation/AppNavigationMore';
    import AppNavigationSearch from '@vc/Navigation/AppNavigationSearch.vue';
    import SessionTimeout from '@vc/SessionTimeout';
    import ClockIcon from '@icon/Clock';
    import EarthIcon from '@icon/Earth';
    import MagnifyIcon from '@icon/Magnify';

    export default {
        components: {
            AppNavigationSearch,
            SessionTimeout,
            AppNavigationItem,
            AppNavigationItemFavorites,
            AppNavigationItemFolders,
            AppNavigationItemSecurity,
            AppNavigationItemShared,
            AppNavigationItemTags,
            AppNavigationMore,
            ClockIcon,
            EarthIcon,
            MagnifyIcon,
            NcAppNavigation
        },
        props     : {
            hasTimeout     : {},
            isMobile       : {},
            isSearchVisible: {}
        }
    };
</script>

<style lang="scss">
.app-navigation-list {
    flex-grow : 1;
    height    : auto !important;
}
</style>