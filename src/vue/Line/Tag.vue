<template>
    <div class="row tag" v-if="enabled" :data-tag-id="tag.id" @click="openAction()">
        <i class="fa fa-star favourite" v-bind:class="{ active: tag.favourite }" @click="favouriteAction($event)"></i>
        <div class="favicon fa fa-tag" v-bind:style="faviconStyle"></div>
        <span class="title">{{ tag.label }}</span>
        <div class="date">{{ tag.updated.toLocaleDateString() }}</div>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="tagActionsMenu popovermenu bubble menu" :class="{ open: showMenu }">
                <slot name="menu">
                    <ul>
                        <slot name="option-top"></slot>
                        <!-- <translate tag="li" @click="detailsAction($event)" icon="info">Details</translate> -->
                        <translate tag="li" @click="editAction()" icon="edit">Edit</translate>
                        <translate tag="li" @click="deleteAction()" icon="trash">Delete</translate>
                        <slot name="option-bottom"></slot>
                    </ul>
                </slot>
            </div>
        </div>
        <slot name="buttons"></slot>
    </div>
</template>

<script>
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
                enabled : true,
                showMenu: false,
            }
        },

        computed: {
            faviconStyle() {
                return {
                    color: this.tag.color
                }
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
                $event.stopPropagation();
                this.showMenu = !this.showMenu;
            },
            openAction() {
                this.$router.push({name: 'Tags', params: {tag: this.tag.id}});
            },
            detailsAction($event, section = null) {
                this.$parent.detail = {
                    type   : 'tag',
                    element: this.tag
                }
            },
            deleteAction(skipConfirm = false) {
                TagManager.deleteTag(this.tag)
                    .then(() => {this.enabled = false;});
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
                    text-align : center;
                    font-size  : 2.25rem;
                    vertical-align: top;
                }
            }
        }
    }

</style>