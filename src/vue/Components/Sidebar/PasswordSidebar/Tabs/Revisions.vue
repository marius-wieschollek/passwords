<!--
  - @copyright 2024 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <ul class="passwords-revision-list">
        <revision
                v-for="revision in revisions"
                :key="revision.id"
                :password="password"
                :revision="revision"
        />
    </ul>
</template>

<script>
    import Revision from '@vc/Sidebar/PasswordSidebar/Revisions/Revision';
    import UtilityService from "@js/Services/UtilityService";

    export default {
        components: {
            Revision
        },

        props: {
            password: {
                type: Object
            }
        },

        computed: {
            revisions() {
                return UtilityService.sortApiObjectArray(this.password.revisions, 'created', false);
            }
        },
    };
</script>