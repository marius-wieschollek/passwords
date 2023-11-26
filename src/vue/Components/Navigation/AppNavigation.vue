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
        <template id="app-passwords-navigation" #list>
            <app-navigation-item :name="t('All')" :to="{ name: 'All'}" :exact="true">
                <earth-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item-folders />
            <app-navigation-item :name="t('Recent')" :to="{ name: 'Recent'}">
                <clock-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item-favorites />
            <app-navigation-item-shared />
            <app-navigation-item-tags />
            <app-navigation-item-security />
            <app-navigation-item :name="t('Search')" :to="{ name: 'Search'}" v-if="isSearchVisible">
                <magnify-icon :size=20 slot="icon" />
            </app-navigation-item>

            <session-timeout v-if="!isMobile && hasTimeout" />
            <app-navigation-item :name="t('Trash')" :pinned="true" :to="{ name: 'Trash'}" data-drop-type="trash" icon="icon-delete" />
        </template>

        <nc-app-navigation-settings slot="footer" :name="t('More')">
            <app-navigation-item :name="t('Settings')" :to="{ name: 'Settings'}">
                <cog-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item :name="t('Backup and Restore')" :to="{ name: 'Backup'}">
                <archive-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item :name="t('Handbook')" :to="{ name: 'Help'}">
                <help-circle-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item :name="t('Apps and Extensions')" :to="{ name: 'Apps and Extensions'}">
                <puzzle-icon :size=20 slot="icon" />
            </app-navigation-item>
        </nc-app-navigation-settings>
    </nc-app-navigation>
</template>
<script>
    import NcAppNavigation            from '@nc/NcAppNavigation.js';
    import AppNavigationItem          from '@vc/Navigation/AppNavigationItem';
    import AppNavigationItemFavorites from '@vc/Navigation/AppNavigationItemFavorites';
    import AppNavigationItemFolders   from '@vc/Navigation/AppNavigationItemFolders';
    import AppNavigationItemSecurity  from '@vc/Navigation/AppNavigationItemSecurity';
    import AppNavigationItemShared    from '@vc/Navigation/AppNavigationItemShared';
    import AppNavigationItemTags      from '@vc/Navigation/AppNavigationItemTags';
    import SessionTimeout             from '@vc/SessionTimeout';
    import ArchiveIcon                from '@icon/Archive';
    import ClockIcon                  from '@icon/Clock';
    import CogIcon                    from '@icon/Cog';
    import EarthIcon                  from '@icon/Earth';
    import HelpCircleIcon             from '@icon/HelpCircle';
    import MagnifyIcon                from '@icon/Magnify';
    import PuzzleIcon                 from '@icon/Puzzle';
    import NcAppNavigationSettings    from '@nc/NcAppNavigationSettings.js';

    export default {
        components: {
            SessionTimeout,
            AppNavigationItem,
            AppNavigationItemFavorites,
            AppNavigationItemFolders,
            AppNavigationItemSecurity,
            AppNavigationItemShared,
            AppNavigationItemTags,
            ArchiveIcon,
            ClockIcon,
            CogIcon,
            EarthIcon,
            HelpCircleIcon,
            MagnifyIcon,
            NcAppNavigation,
            NcAppNavigationSettings,
            PuzzleIcon
        },
        props     : {
            hasTimeout     : {},
            isMobile       : {},
            isSearchVisible: {}
        }
    };
</script>
