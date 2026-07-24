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
    <nc-modal
            class="pw-manage-tags-dialog"
            ref="window"
            size="small"
            :inlineActions="0"
            v-on:close="close"
            :container="container"
            :title="t('Manage tags')">
        <template #default>
            <ul class="pw-manage-tags-list" v-if="!loading">
                <li v-for="tag in tags" :key="tag.id" @click="toggleTag(tag)">
                    <input type="checkbox"
                           :checked="tagState(tag) === 'all'"
                           :indeterminate.prop="tagState(tag) === 'some'"
                           @click.stop="toggleTag(tag)">
                    <tag-icon :fill-color="tag.color"/>
                    <span>{{ tag.label }}</span>
                </li>
                <li class="pw-manage-tags-empty" v-if="tags.length === 0">{{ t('No tags found') }}</li>
            </ul>
            <nc-loading-icon class="pw-manage-tags-loading" :name="t('Loading')" :size="40" v-else/>
        </template>
    </nc-modal>
</template>

<script>
    import Vue from 'vue';
    import API from '@js/Helper/api';
    import TagIcon from '@icon/Tag';
    import NcModal from '@nc/NcModal.js';
    import NcLoadingIcon from '@nc/NcLoadingIcon.js';
    import ToastService from '@js/Services/ToastService';
    import PasswordManager from '@js/Manager/PasswordManager';
    import UtilityService from '@js/Services/UtilityService';

    export default {
        components: {TagIcon, NcModal, NcLoadingIcon},

        props: {
            passwords: {
                type: Array
            },
            resolve  : {
                type: Function
            }
        },

        data() {
            return {
                tags     : [],
                loading  : true,
                container: UtilityService.popupContainer(true)
            };
        },

        mounted() {
            this.loadTags();
        },

        methods: {
            async loadTags() {
                await this.ensureTagsLoaded();
                this.tags = UtilityService.sortApiObjectArray(await API.listTags(), 'label');
                this.loading = false;
            },
            async ensureTagsLoaded() {
                for(let password of this.passwords) {
                    if(!password.tags) {
                        let data = await API.showPassword(password.id, 'model+tags');
                        Vue.set(password, 'tags', data.tags);
                    }
                }
            },
            passwordHasTag(password, tagId) {
                let ids = Array.isArray(password.tags) ? password.tags:Object.keys(password.tags);
                return ids.indexOf(tagId) !== -1;
            },
            tagState(tag) {
                let count = 0;
                for(let password of this.passwords) {
                    if(this.passwordHasTag(password, tag.id)) count++;
                }

                if(count === 0) return 'none';
                if(count === this.passwords.length) return 'all';
                return 'some';
            },
            async toggleTag(tag) {
                let add = this.tagState(tag) !== 'all';

                for(let password of this.passwords) {
                    let hasTag = this.passwordHasTag(password, tag.id);
                    if(add && !hasTag) {
                        Vue.set(password.tags, tag.id, tag);
                        await PasswordManager.updatePassword(password);
                    } else if(!add && hasTag) {
                        Vue.delete(password.tags, tag.id);
                        await PasswordManager.updatePassword(password);
                    }
                }

                ToastService.success(add ? ['Added tag {tag}', {tag: tag.label}]:['Removed tag {tag}', {tag: tag.label}]);
                this.$forceUpdate();
            },
            close() {
                this.resolve();
                this.$destroy();
                if(this.$el.parentNode) this.$el.parentNode.removeChild(this.$el);
            }
        }
    };
</script>

<style lang="scss">
.pw-manage-tags-dialog {

    .pw-manage-tags-list {
        overflow-x : hidden;
        padding    : .5rem 0 1rem;

        li {
            display     : flex;
            align-items : center;
            padding     : .5rem 1.5rem;
            cursor      : pointer;

            &:hover {
                background-color : var(--color-background-hover);
            }

            input[type="checkbox"] {
                margin-right : .75rem;
            }

            .tag-icon {
                margin-right : .5rem;
                flex-shrink  : 0;
            }
        }

        .pw-manage-tags-empty {
            cursor : default;
            color  : var(--color-text-maxcontrast);

            &:hover {
                background-color : transparent;
            }
        }
    }

    .pw-manage-tags-loading {
        height : 200px;
    }
}
</style>
