<template>
    <div id="app-content">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="breadcrumb"/>
            <div class="item-list">
                <generic-line v-if="$route.params.action === undefined" v-for="(data, index) in options" :key="index" :label="data[0]" :icon="data[1]" :params="{action: index}"/>
                <export v-if="$route.params.action === 'export'"/>
                <import v-if="$route.params.action === 'import'"/>
            </div>
        </div>
    </div>
</template>

<script>
    import Export from '@vc/Export';
    import Import from '@vc/Import';
    import Translate from '@vc/Translate';
    import Breadcrumb from '@vc/Breadcrumbs';
    import Utility from "@js/Classes/Utility";
    import GenericLine from "@vue/Line/Generic";

    export default {
        components: {
            Export,
            Import,
            Translate,
            Breadcrumb,
            GenericLine
        },

        data() {
            return {
                breadcrumb: [],
                options   : {
                    export: ['Backup or export', 'download'], import: ['Restore or import', 'upload']
                }
            }
        },

        created() {
            this.updateRoute();
        },

        methods: {
            updateRoute() {
                if(this.$route.params.action !== undefined) {
                    let data = this.options[this.$route.params.action];

                    this.breadcrumb = [
                        {path: {name: 'Backup'}, label: Utility.translate('Backup and Restore')},
                        {path: this.$route.path, label: Utility.translate(data[0])}
                    ]
                } else {
                    this.breadcrumb = [{path: {name: 'Backup'}, label: Utility.translate('Backup and Restore')}];
                }
            }
        },

        watch: {
            $route: function() {
                this.updateRoute();
            }
        }
    }
</script>

<style lang="scss">
    .backup-dialog {
        h1 {
            font-size        : 1.25em;
            padding          : 10px;
            line-height      : 30px;
            border-top       : 1px solid $color-grey-lighter;
            border-bottom    : 1px solid $color-grey-lighter;
            background-color : darken($color-white, 3);

            &:before {
                content       : '1';
                color         : $color-grey-darker;
                border        : 1px solid $color-grey;
                border-radius : 15px;
                width         : 30px;
                display       : inline-block;
                text-align    : center;
                font-weight   : bold;
                margin-right  : 5px;
            }
        }

        .step-2 h1::before {
            content : '2';
        }

        .step-3 h1::before {
            content : '3';
        }

        .step-4 h1::before {
            content : '4';
        }

        .step-content {
            padding : 5px;

            input[type=checkbox] {
                display : inline-block;
                margin  : 0 5px 0;
                float   : left;
                height  : 32px;
            }

            input[type=checkbox] + label {
                line-height : 32px;
                display     : inline-block;
                font-size   : 1.2em;
            }
        }

        .csv-mapping {
            display           : grid;
            margin-bottom     : 10px;
            overflow-x        : auto;
            grid-auto-columns : minmax(105px, 20%);

            .csv-mapping-data {
                padding   : 0 5px;
                word-wrap : break-word;
            }

            .csv-mapping-field {
                grid-row : 2;
                padding  : 0 5px;

                select {
                    width : 100%;
                }
            }
        }
    }
</style>