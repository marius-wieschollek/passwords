<template>
    <nc-app-navigation open>
        <template id="app-passwords-navigation" #list>
            <app-navigation-item :title="t('All')" :to="{ name: 'All'}" :exact="true">
                <earth-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item-folders />
            <app-navigation-item :title="t('Recent')" :to="{ name: 'Recent'}">
                <clock-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item-favorites />
            <app-navigation-item-shared />
            <app-navigation-item-tags />
            <app-navigation-item-security />
            <app-navigation-item :title="t('Search')" :to="{ name: 'Search'}" v-if="isSearchVisible">
                <magnify-icon :size=20 slot="icon" />
            </app-navigation-item>

            <session-timeout v-if="!isMobile && hasTimeout" />
            <app-navigation-item :title="t('Trash')" :pinned="true" :to="{ name: 'Trash'}" data-drop-type="trash" icon="icon-delete" />
        </template>

        <nc-app-navigation-settings slot="footer" :title="t('More')">
            <app-navigation-item :title="t('Settings')" :to="{ name: 'Settings'}">
                <cog-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item :title="t('Backup and Restore')" :to="{ name: 'Backup'}">
                <archive-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item :title="t('Handbook')" :to="{ name: 'Help'}">
                <help-circle-icon :size=20 slot="icon" />
            </app-navigation-item>
            <app-navigation-item :title="t('Apps and Extensions')" :to="{ name: 'Apps and Extensions'}">
                <puzzle-icon :size=20 slot="icon" />
            </app-navigation-item>
        </nc-app-navigation-settings>
    </nc-app-navigation>
</template>
<script>
    import NcAppNavigation            from '@nc/NcAppNavigation';
    import AppNavigationItem          from '@vc/Navigation/AppNavigationItem.vue';
    import AppNavigationItemFavorites from '@vc/Navigation/AppNavigationItemFavorites.vue';
    import AppNavigationItemFolders   from '@vc/Navigation/AppNavigationItemFolders.vue';
    import AppNavigationItemSecurity  from '@vc/Navigation/AppNavigationItemSecurity.vue';
    import AppNavigationItemShared    from '@vc/Navigation/AppNavigationItemShared.vue';
    import AppNavigationItemTags      from '@vc/Navigation/AppNavigationItemTags.vue';
    import SessionTimeout             from '@vc/SessionTimeout';
    import ArchiveIcon                from '@icon/Archive';
    import ClockIcon                  from '@icon/Clock';
    import CogIcon                    from '@icon/Cog';
    import EarthIcon                  from '@icon/Earth';
    import HelpCircleIcon             from '@icon/HelpCircle';
    import MagnifyIcon                from '@icon/Magnify';
    import NcAppNavigationSettings    from '@nc/NcAppNavigationSettings';
    import PuzzleIcon                 from '@icon/Puzzle';

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
