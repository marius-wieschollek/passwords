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
    <ul class="folder-picker-list" v-if="folders.length" :style="style">
        <li v-for="item in items" :key="item.id" @click="navigate(item)" :class="{disabled: !item.enabled}" :title="item.title">{{ item.label }}</li>
    </ul>
    <translate class="folder-picker-empty" say="&quot;{folder}&quot; contains no folders" :variables="{folder: current.label}" v-else />
</template>

<script>
    import Translate       from '@vc/Translate';
    import SettingsService from '@js/Services/SettingsService';
    import Localisation    from '@js/Classes/Localisation';

    export default {
        components: {Translate},
        props     : {
            current: Object,
            folders       : Array,
            ignoredFolders: Array
        },
        computed  : {
            style() {
                return {
                    '--pw-folder-image': `url(${SettingsService.get('server.theme.folder.icon')})`
                };
            },
            items() {
                let items = [];

                for(let folder of this.folders) {
                    let enabled = this.ignoredFolders.indexOf(folder.id) === -1,
                        title   = Localisation.translate('Open {label}', {label: folder.label});

                    if(!enabled) {
                        title = Localisation.translate('{label} can\'t be used', {label: folder.label});
                    }

                    items.push(
                        {
                            id   : folder.id,
                            label: folder.label,
                            title,
                            model: folder,
                            enabled
                        }
                    );
                }

                return items;
            }
        },
        methods   : {
            navigate(item) {
                if(item.enabled) {
                    this.$emit('navigate', item.model);
                }
            }
        }
    };
</script>

<style lang="scss">
.folder-picker-list {
    li {
        background-image    : var(--pw-folder-image);
        background-repeat   : no-repeat;
        line-height         : 3rem;
        padding-left        : 3rem;
        background-position : .5rem center;
        background-size     : 2rem;
        cursor              : pointer;
        border-bottom       : 1px solid var(--color-border);

        &:hover {
            background-color : var(--color-background-hover);
        }

        &:last-child {
            border-bottom : none;
        }

        &.disabled {
            opacity          : 0.5;
            cursor           : not-allowed;
            background-color : var(--color-background-dark);

            &:hover {
                background-color : var(--color-background-dark);
            }
        }
    }
}

.folder-picker-empty {
    text-align  : center;
    display     : block;
    line-height : 3rem;
    color       : var(--color-text-lighter);
}
</style>