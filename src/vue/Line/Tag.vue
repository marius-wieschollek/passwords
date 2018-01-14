<template>
    <div class="row tag" :data-tag-id="tag.id" @click="openAction($event)">
        <i class="fa fa-star favourite" :class="{ active: tag.favourite }" @click="favouriteAction($event)"></i>
        <div class="favicon fa fa-tag" :style="{color: this.tag.color}"></div>
        <span class="title">{{ tag.label }}</span>
        <div class="date">{{ tag.edited.toLocaleDateString() }}</div>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="tagActionsMenu popovermenu bubble menu" :class="{ open: showMenu }">
                <slot name="menu">
                    <ul>
                        <slot name="option-top"/>
                        <!-- <translate tag="li" @click="detailsAction($event)" icon="info">Details</translate> -->
                        <translate tag="li" @click="editAction()" icon="edit">Edit</translate>
                        <translate tag="li" @click="deleteAction()" icon="trash">Delete</translate>
                        <slot name="option-bottom"/>
                    </ul>
                </slot>
            </div>
        </div>
        <slot name="buttons"/>
    </div>
</template>

<script>
    import $ from "jquery";
    import Translate from '@vc/Translate.vue';
    import TagManager from '@js/Manager/TagManager';

    export default {
        components: {
            Translate
        },

        props: {
            tag: {
                type: Object
            }
        },

        data() {
            return {
                showMenu: false,
            }
        },

        methods: {
            favouriteAction($event) {
                $event.stopPropagation();
                this.tag.favourite = !this.tag.favourite;
                TagManager.updateTag(this.tag)
                    .catch(() => { this.tag.favourite = !this.tag.favourite; });
            },
            toggleMenu($event) {
                this.showMenu = !this.showMenu;
                this.showMenu ? $(document).click(this.menuEvent):$(document).off('click', this.menuEvent);
            },
            menuEvent($e) {
                if($($e.target).closest('[data-tag-id=' + this.tag.id + '] .more').length !== 0) return;
                this.showMenu = false;
                $(document).off('click', this.menuEvent);
            },
            openAction($event) {
                if($($event.target).closest('.more').length !== 0) return;
                this.$router.push({name: 'Tags', params: {tag: this.tag.id}});
            },
            detailsAction($event, section = null) {
                this.$parent.detail = {
                    type   : 'tag',
                    element: this.tag
                }
            },
            deleteAction(skipConfirm = false) {
                TagManager.deleteTag(this.tag);
            },
            editAction() {
                TagManager.editTag(this.tag)
                    .then((t) => {this.tag = t;});
            }
        }
    }
</script>

<style lang="scss">

    #app-content {
        .item-list {
            .row.tag {
                .favicon {
                    text-align     : center;
                    font-size      : 2.25rem;
                    vertical-align : top;
                }
            }
        }
    }

</style>