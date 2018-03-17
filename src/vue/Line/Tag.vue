<template>
    <div class="row tag" :data-tag-id="tag.id" @click="openAction($event)">
        <i class="fa fa-star favourite" :class="{ active: tag.favourite }" @click="favouriteAction($event)"></i>
        <div class="favicon fa fa-tag" :style="{color: this.tag.color}"></div>
        <div class="title" :title="tag.label"><span>{{ tag.label }}</span></div>
        <slot name="middle"/>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="tagActionsMenu popovermenu bubble menu" :class="{ open: showMenu }">
                <slot name="menu">
                    <ul>
                        <slot name="menu-top"/>
                        <!-- <translate tag="li" @click="detailsAction($event)" icon="info">Details</translate> -->
                        <translate tag="li" @click="editAction()" icon="edit">Edit</translate>
                        <translate tag="li" @click="deleteAction()" icon="trash">Delete</translate>
                        <slot name="menu-bottom"/>
                    </ul>
                </slot>
            </div>
        </div>
        <div class="date">{{ getDate }}</div>
    </div>
</template>

<script>
    import $ from "jquery";
    import Translate from '@vc/Translate';
    import TagManager from '@js/Manager/TagManager';
    import Localisation from "@js/Classes/Localisation";

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
                showMenu: false
            };
        },

        computed: {
            getDate() {
                return Localisation.formatDate(this.tag.edited);
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
                };
            },
            deleteAction(skipConfirm = false) {
                TagManager.deleteTag(this.tag);
            },
            editAction() {
                TagManager.editTag(this.tag)
                          .then((t) => {this.tag = t;});
            }
        }
    };
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