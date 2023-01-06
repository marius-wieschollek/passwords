<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <nc-breadcrumbs class="folder-picker-breadcrumbs">
        <NcBreadcrumb
                v-for="(folder, index) in hierarchy"
                :key="folder.id"
                :title="folder.label"
                v-on:click.native="$emit('navigate', folder)"
                :disableDrop="true"
        >
            <folder-icon v-if="index === 0" slot="icon"/>
        </NcBreadcrumb>
    </nc-breadcrumbs>
</template>

<script>
    import NcBreadcrumbs from '@nc/NcBreadcrumbs';
    import NcBreadcrumb from '@nc/NcBreadcrumb';
    import FolderIcon from "@icon/Folder";

    export default {
        components: {FolderIcon, NcBreadcrumb, NcBreadcrumbs},
        props   : {
            current: Object,
            folders: Array
        },
        computed: {
            hierarchy() {
                let parentId = this.current.parent,
                    folders  = [this.current];

                whileLoop: while(true) {
                    for(let folder of this.folders) {
                        if(folder.id === parentId) {
                            folders.push(folder);
                            if(folder.id === '00000000-0000-0000-0000-000000000000') break whileLoop;
                            parentId = folder.parent;
                            continue whileLoop;
                        }
                    }

                    break;
                }

                return folders.reverse();
            }
        }
    };
</script>

<style lang="scss">
.folder-picker-breadcrumbs {
    padding: .25rem;
    position: sticky;
    top:0;
    z-index: 1;
    background-color: var(--color-main-background);
}
</style>