<template>
    <div class="row header">
        <batch-action-toolbar :is-trash-section="isTrashSection"/>
        <template v-if="!isBatchActionActive">
            <translate class="title" :class="titleClass" say="Name" @click="updateSorting('label')" title="Sort by name"/>
            <translate class="date" :class="dateClass" say="Modified" @click="updateSorting('edited')" title="Sort by modified date"/>
        </template>
    </div>
</template>

<script>
    import Translate from "@vue/Components/Translate";
    import BatchActionManager from "@js/Manager/BatchActionManager";
    import BatchActionToolbar from "@vue/Components/Header/BatchActionToolbar";

    export default {
        components: {
            Translate,
            BatchActionToolbar
        },

        props: {
            field    : {
                type: String
            },
            ascending: {
                type: Boolean
            },
            isTrashSection    : {
                type: Boolean
            }
        },


        computed: {
            titleClass() {
                return this.getClass('label');
            },
            dateClass() {
                return this.getClass('edited');
            },
            isBatchActionActive() {
                return BatchActionManager.hasSelectedItems;
            }
        },

        methods: {
            getClass(field) {
                if(this.field === field) {
                    return this.ascending ? 'asc':'desc';
                }
                return '';
            },
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
            color               : $color-grey-dark;
            -webkit-user-select : none;
            -moz-user-select    : none;
            -ms-user-select     : none;
            user-select         : none;
            display             : flex;
            align-items         : center;

            .title {
                padding-left : 50px;
                flex-grow    : 1;
            }

            .date {
                color     : $color-grey-dark;
                width     : auto;
                min-width : 85px;
            }

            .asc::after,
            .desc::after {
                content      : "\f0d7";
                font-family  : var(--pw-icon-font-face);
                padding-left : 5px;
            }

            .asc::after {
                content : "\f0d8";
            }

            &:active,
            &:hover {
                background-color : initial;
            }
        }
    }
}
</style>
