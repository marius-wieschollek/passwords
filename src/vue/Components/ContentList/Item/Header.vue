<template>
    <div class="row header">
        <batch-action-toolbar :is-trash-section="isTrashSection"/>
        <template v-if="!isBatchActionActive">
            <div class="header-title">
                <nc-button alignment="start-reverse" variant="tertiary" :title="t('Sort by name')" wide @click="updateSorting('label')">
                    <template #icon>
                        <sort-alphabetical-ascending-icon :size="20" v-if="currentSorting === 'label-ascending'"/>
                        <sort-alphabetical-descending-icon :size="20" v-if="currentSorting === 'label-descending'"/>
                    </template>
                    {{ t('Name') }}
                </nc-button>
            </div>
            <div class="header-date">
                <nc-button :alignment="modifiedAlignment" variant="tertiary" :title="t('Sort by modified date')" wide @click="updateSorting('edited')">
                    <template #icon>
                        <sort-calendar-ascending-icon :size="20" v-if="currentSorting === 'edited-ascending'"/>
                        <sort-calendar-descending-icon :size="20" v-if="currentSorting === 'edited-descending'"/>
                    </template>
                    {{ t('Modified') }}
                </nc-button>
            </div>
        </template>
    </div>
</template>

<script>
    import Translate from "@vue/Components/Translate";
    import NcButton from '@nextcloud/vue/components/NcButton';
    import BatchActionManager from "@js/Manager/BatchActionManager";
    import BatchActionToolbar from "@vue/Components/ContentList/Item/HeaderItem/BatchActionToolbar";
    import SortAlphabeticalAscendingIcon from "@icon/SortAlphabeticalAscending.vue";
    import SortAlphabeticalDescendingIcon from "@icon/SortAlphabeticalDescending.vue";
    import SortCalendarAscendingIcon from "@icon/SortCalendarAscending.vue";
    import SortCalendarDescendingIcon from "@icon/SortCalendarDescending.vue";

    export default {
        components: {
            SortCalendarDescendingIcon,
            SortCalendarAscendingIcon,
            SortAlphabeticalDescendingIcon,
            SortAlphabeticalAscendingIcon,
            Translate,
            NcButton,
            BatchActionToolbar
        },

        props: {
            field         : {
                type: String
            },
            ascending     : {
                type: Boolean
            },
            isTrashSection: {
                type: Boolean
            }
        },


        computed: {
            isBatchActionActive() {
                return BatchActionManager.hasSelectedItems;
            },
            currentSorting() {
                return `${this.field}-${this.ascending ? 'ascending':'descending'}`;
            },
            modifiedAlignment() {
                return this.field === 'edited' ? 'end-reverse':'end';
            }
        },

        methods: {
            updateSorting(field) {
                if(this.field === field) {
                    this.$emit('updateSorting', {field: field, ascending: !this.ascending});
                } else {
                    this.$emit('updateSorting', {field: field, ascending: true});
                }
            }
        }
    };
</script>

<style lang="scss">
#app-content {
    .item-list {
        .row.header {
            display     : flex;
            align-items : center;
            padding     : 0 1rem 0 .25rem;

            .header-title {
                padding-left : 40px;
                flex-grow    : 1;
            }

            .header-date {
                width     : auto;
                min-width : 10rem;
            }

            &:active,
            &:hover {
                background-color : initial;
            }
        }
    }
}
</style>
