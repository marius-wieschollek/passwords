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
                        {path: '/backup', label: Utility.translate('Backup')},
                        {path: this.$route.path, label: Utility.translate(data[0])}
                    ]
                } else {
                    this.breadcrumb = [];
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