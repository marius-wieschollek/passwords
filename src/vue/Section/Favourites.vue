<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb></breadcrumb>
            <div class="item-list">
                <password-line :password="password" v-for="password in passwords" :key="password.id"></password-line>
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id" draggable="true"></folder-line>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type == 'password'" :password="detail.element"></password-details>
        </div>
    </div>
</template>

<script>
    import Events from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import FolderLine from '@vue/Line/Folder.vue';
    import PasswordLine from '@vue/Line/Password.vue';
    import PasswordDetails from '@vue/Details/Password.vue';
    import API from '@js/Helper/api';

    export default {
        data() {
            return {
                passwords: [],
                folders: [],
                detail   : {
                    type   : 'none',
                    element: null
                }
            }
        },

        components: {
            Breadcrumb,
            FolderLine,
            PasswordLine,
            PasswordDetails
        },

        created() {
            this.refreshView();
            Events.on('data.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('data.changed', this.refreshView)
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },

        methods: {
            refreshView: function () {
                API.findPasswords({favourite:true}).then(this.updatePasswordList);
                API.findFolders({favourite:true}, 'model+folders+passwords').then(this.updateFolderList);
            },

            updatePasswordList: function (passwords) {
                this.passwords = Utility.sortApiObjectArray(passwords, 'label');
            },

            updateFolderList: function (folders) {
                this.folders = Utility.sortApiObjectArray(folders, 'label');
            }
        }
    };
</script>