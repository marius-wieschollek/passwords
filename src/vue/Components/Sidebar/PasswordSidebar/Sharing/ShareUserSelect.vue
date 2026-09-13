<!--
  - @copyright 2026 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <nc-select-users :input-label="t('BatchActionShareSelectUsersLabel')"
                     :multiple="true"
                     :options="availableUsers"
                     :placeholder="t('BatchActionShareSelectUsersPlaceholder')"
                     v-model="selectedUsers"
                     @search="fetchShareSuggestions"
                     class="wide"
    />
</template>

<script>
    import NcSelectUsers from '@nc/NcSelectUsers.js';
    import API from "@js/Helper/api";
    import LocalisationService from "@js/Services/LocalisationService";
    import UtilityService from "@js/Services/UtilityService";

    export default {
        components: {
            NcSelectUsers
        },
        props     : {
            value: {
                type   : Array,
                default: () => {
                    return [];
                }
            }
        },
        data() {
            return {
                selectedUsers : UtilityService.arrayValues(this.value),
                availableUsers: []
            };
        },
        mounted() {
            this.fetchShareSuggestions('');
        },
        methods: {
            async fetchShareSuggestions(query) {
                let matches      = await API.findShareRecipients(query, 25),
                    groupSubname = LocalisationService.translate('BatchShareSubnameGroup'),
                    users        = [];

                for(let match of matches) {
                    if(this.selectedUsers !== null && this.selectedUsers.indexOf(match) !== -1) {
                        continue;
                    }

                    users.push(
                        {
                            id         : match.id,
                            displayName: match.name,
                            isNoUser   : match.type === 'group',
                            subname    : match.type === 'group' ? groupSubname:match.context
                        });
                }

                this.availableUsers = users;
            },
            modelMatchesSelection() {
                if(this.value.length !== this.selectedUsers.length) {
                    return false;
                }


                for(let i = 0; i < this.value.length; i++) {
                    if(!this.selectedUsers[i] || this.selectedUsers[i].id !== this.value[i].id) {
                        return false;
                    }
                }

                return true;
            }
        },

        watch: {
            value(value) {
                if(!this.modelMatchesSelection()) {
                    this.selectedUsers = UtilityService.arrayValues(value);
                    this.fetchShareSuggestions('');
                }
            },
            selectedUsers(selectedUsers) {
                if(!this.modelMatchesSelection()) {
                    this.$emit('input', selectedUsers);
                }
            }
        }
    };
</script>

<style scoped lang="scss">
.nc-select-users.wide {
    width : 100%;
}
</style>