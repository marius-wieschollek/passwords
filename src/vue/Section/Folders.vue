<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb :newFolder="true" :folder="currentFolder" :items="breadcrumb"></breadcrumb>
            <div class="item-list">
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id" draggable="true"></folder-line>
                <password-line :password="password"
                               v-for="password in passwords"
                               :key="password.id"
                               draggable="true"></password-line>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type === 'password'" :password="detail.element"></password-details>
        </div>
    </div>
</template>

<script>
    import PwEvents from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import FolderLine from '@vue/Line/Folder.vue';
    import PasswordLine from '@vue/Line/Password.vue';
    import PasswordDetails from '@vue/Details/Password.vue';
    import API from '@js/Helper/api';

    export default {
        components: {
            Breadcrumb,
            PasswordDetails,
            PasswordLine,
            FolderLine
        },
        data() {
            return {
                defaultFolder: '00000000-0000-0000-0000-000000000000',
                currentFolder: '00000000-0000-0000-0000-000000000000',
                folders      : [],
                passwords    : [],
                detail       : {
                    type   : 'none',
                    element: null
                },
                breadcrumb   : []
            }
        },
        computed  : {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },

        created() {
            this.refreshView();
            PwEvents.on('data.changed', this.refreshView);
        },

        beforeDestroy() {
            PwEvents.off('data.changed', this.refreshView)
        },
        watch: {
            $route: function () {
                this.refreshView()
            }
        },

        methods: {
            refreshView: function () {
                if (this.$route.params.folder !== undefined) {
                    API.showFolder(this.$route.params.folder, 'model+folders+passwords+parent').then(this.updateContentList);
                } else {
                    API.showFolder(this.defaultFolder, 'model+folders+passwords').then(this.updateContentList);
                }
            },

            updateContentList: function (data) {
                this.breadcrumb = [
                    {path: '/show/folders', label: Utility.translate('Folders')}
                ];

                if (typeof data.parent !== 'string' && data.parent.id !== this.defaultFolder) {
                    this.breadcrumb = [{path: '/show/folders', label: '…'}];
                    this.breadcrumb.push({path: '/show/folders/' + data.parent.id, label: data.parent.label})
                }

                if (data.id !== this.defaultFolder) {
                    this.breadcrumb.push({path: this.$route.path, label: data.label});
                }

                this.folders = Utility.sortApiObjectArray(data.folders, 'label', true);
                this.passwords = Utility.sortApiObjectArray(data.passwords, 'label', true);
                this.currentFolder = data.id;
            }
        }
    };
</script>