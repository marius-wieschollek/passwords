<!--
  - @copyright 2021 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <ul class="folder-picker-breadcrumbs">
        <li @click="$emit('navigate', folder)" v-for="folder in hierarchy" :key="folder.id">{{ folder.label }}</li>
        <li v-if="current.id !== '00000000-0000-0000-0000-000000000000'">{{ current.label }}</li>
    </ul>
</template>

<script>
    export default {
        props   : {
            current: Object,
            folders: Array
        },
        computed: {
            hierarchy() {
                let parentId = this.current.parent,
                    folders  = [];

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
    display          : flex;
    position         : sticky;
    top              : -.5rem;
    margin-top       : -.5rem;
    padding-top      : .5rem;
    background-color : var(--color-main-background);

    li {
        opacity     : 0.5;
        line-height : 44px;
        display     : flex;
        cursor      : pointer;

        &:first-child {
            background-image    : var(--icon-home-000);
            background-repeat   : no-repeat;
            background-position : center;
            font-size           : 0;
            width               : 1.5rem;
            margin-right        : -.5rem;

            &:before {
                display : none;
            }
        }

        &:before {
            background-image    : url("../../../img/icons/breadcrumb.svg?v=1");
            background-repeat   : no-repeat;
            background-position : center;
            background-size     : auto 24px;
            width               : 1.75rem;
            content             : "";
        }

        &:hover {
            opacity : 0.7;
        }

        &:last-child {
            opacity : 0.7;
            cursor  : default;
        }
    }
}
</style>