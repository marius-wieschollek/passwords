<script>
    import API from '@js/Helper/api';
    import Events from '@js/Classes/Events';
    import Utility from '@js/Classes/Utility';
    import BaseSection from '@vue/Section/BaseSection';
    import Localisation from '@js/Classes/Localisation';
    import SettingsService from '@js/Service/SettingsService';

    export default {
        extends: BaseSection,

        data() {
            let showTags    = SettingsService.get('client.ui.list.tags.show', false) && window.innerWidth > 360,
                baseModel   = showTags ? 'model+folders+passwords+password-tags':'model+folders+passwords',
                folderModel = showTags ? 'model+folders+passwords+password-tags+parent':'model+folders+passwords+parent';

            return {
                defaultFolder: '00000000-0000-0000-0000-000000000000',
                currentFolder: {id: '', parent: ''},
                trashMode    : false,
                model        : {
                    base  : baseModel,
                    folder: folderModel
                }
            };
        },

        created() {
            Events.on('data.changed', this.refreshViewIfRequired);
        },

        beforeDestroy() {
            Events.off('data.changed', this.refreshViewIfRequired);
        },

        computed: {
            isDraggable() {
                return !this.trashMode;
            },
            getBreadcrumb() {
                let route = this.trashMode ? 'Trash':'Folders',
                    items = [
                        {path: {name: route}, label: Localisation.translate(route), dropType: 'folder', folderId: this.defaultFolder}
                    ];

                if(typeof this.currentFolder.parent !== 'string' && this.currentFolder.parent.id !== this.defaultFolder && !this.currentFolder.trashed) {
                    let parent = this.currentFolder.parent;
                    items.push(
                        {
                            path    : {name: 'Folders', params: {folder: parent.id}},
                            label   : parent.label,
                            dropType: 'folder',
                            folderId: parent.id
                        }
                    );
                }

                if(this.currentFolder.id !== '' && this.currentFolder.id !== this.defaultFolder) {
                    items.push(
                        {
                            path    : this.$route.path,
                            label   : this.currentFolder.label,
                            dropType: 'folder',
                            folderId: this.currentFolder.id
                        }
                    );
                }

                return {
                    showAddNew: !this.trashMode,
                    newFolder : true,
                    folder    : this.currentFolder.id,
                    items
                };
            }
        },

        methods: {
            refreshView          : function() {
                if(this.$route.params.folder !== undefined && this.$route.params.folder !== this.currentFolder.id) {
                    this.loading = true;
                    this.folders = [];
                    this.passwords = [];
                    this.detail.type = 'none';
                    API.showFolder(this.$route.params.folder, this.model.folder).then(this.updateContentList);
                } else if(this.$route.params.folder === undefined && this.defaultFolder !== this.currentFolder.id) {
                    this.loading = true;
                    this.folders = [];
                    this.passwords = [];
                    this.detail.type = 'none';
                    API.showFolder(this.defaultFolder, this.model.base).then(this.updateContentList);
                }
            },
            refreshViewIfRequired: function(data) {
                let object = data.object;

                if(object.type === 'password' && (object.folder === this.currentFolder.id || object.folder.id === this.currentFolder.id)) {
                    if(object.trashed) {
                        this.passwords = Utility.removeApiObjectFromArray(this.passwords, object);
                    } else {
                        let passwords = Utility.replaceOrAppendApiObject(this.passwords, object);
                        this.passwords = Utility.sortApiObjectArray(passwords, this.getPasswordsSortingField(), this.sorting.ascending);
                    }
                } else if(object.type === 'folder' && object.id === this.currentFolder.id) {
                    if(object.trashed) {
                        this.loading = true;
                        API.showFolder(this.defaultFolder, this.model.base).then(this.updateContentList);
                    } else if(object.passwords && object.folders && typeof object.parent !== 'string') {
                        this.updateContentList(object);
                    } else {
                        this.loading = true;
                        API.showFolder(this.currentFolder.id, this.model.folder).then(this.updateContentList);
                    }
                } else if(object.type === 'folder' && object.parent === this.currentFolder.id) {
                    if(object.trashed) {
                        this.folders = Utility.removeApiObjectFromArray(this.folders, object);
                    } else {
                        let folders = Utility.replaceOrAppendApiObject(this.folders, object);
                        this.folders = Utility.sortApiObjectArray(folders, this.sorting.field, this.sorting.ascending);
                    }
                } else if(object.type === 'folder' && Utility.searchApiObjectInArray(this.folders, object) !== -1) {
                    this.folders = Utility.removeApiObjectFromArray(this.folders, object);
                } else if(object.type === 'password' && Utility.searchApiObjectInArray(this.passwords, object) !== -1) {
                    this.passwords = Utility.removeApiObjectFromArray(this.passwords, object);
                }
            },
            updateContentList    : function(folder) {
                this.loading = false;
                if(folder.trashed) {
                    this.trashMode = true;
                } else if(this.trashMode && this.$route.params.folder === undefined) {
                    this.trashMode = false;
                }

                this.folders = Utility.sortApiObjectArray(folder.folders, this.sorting.field, this.sorting.ascending);
                this.passwords = Utility.sortApiObjectArray(folder.passwords, this.getPasswordsSortingField(), this.sorting.ascending);
                this.currentFolder = folder;
            }
        },

        watch: {
            $route: function() {
                this.refreshView();
            }
        }
    };
</script>