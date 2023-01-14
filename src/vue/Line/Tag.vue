<template>
    <div :class="className" @click="openAction($event)" :data-tag-id="tag.id" :data-tag-title="tag.label">
        <star-icon class="favorite" data-item-action="favorite" fill-color="var(--color-warning)" @click.prevent.stop="favoriteAction" v-if="tag.favorite"/>
        <star-outline-icon class="favorite" data-item-action="favorite" fill-color="var(--color-placeholder-dark)" @click.prevent.stop="favoriteAction" v-else/>
        <div class="favicon fa fa-tag" :style="{color: this.tag.color}" :title="tag.label"></div>
        <div class="title" :title="tag.label"><span>{{ tag.label }}</span></div>
        <slot name="middle"/>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="tagActionsMenu popovermenu bubble menu" :class="{ open: showMenu }">
                <slot name="menu">
                    <ul>
                        <slot name="menu-top"/>
                        <translate tag="li" data-item-action="edit" @click="editAction()" icon="edit">Edit</translate>
                        <translate tag="li" data-item-action="delete" @click="deleteAction()" icon="trash">Delete</translate>
                        <slot name="menu-bottom"/>
                    </ul>
                </slot>
            </div>
        </div>
        <div class="date" :title="dateTitle">{{ getDate }}</div>
    </div>
</template>

<script>
    import Translate          from '@vc/Translate';
    import TagManager         from '@js/Manager/TagManager';
    import Localisation       from "@js/Classes/Localisation";
    import SearchManager      from "@js/Manager/SearchManager";
    import ContextMenuService from '@js/Services/ContextMenuService';
    import StarIcon from "@icon/Star";
    import StarOutlineIcon from "@icon/StarOutline";

    export default {
        components: {
            Translate,
            StarIcon,
            StarOutlineIcon
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
            },
            dateTitle() {
                return Localisation.translate('Last modified on {date}', {date:Localisation.formatDate(this.tag.edited, 'long')});
            },
            className() {
                let classNames = 'row tag';

                if(SearchManager.status.active) {
                    classNames += SearchManager.status.ids.indexOf(this.tag.id) !== -1 ? ' search-visible':' search-hidden';
                }

                return classNames;
            }
        },

        mounted() {
            ContextMenuService.register(this.tag, this.$el);
        },

        methods: {
            favoriteAction() {
                this.tag.favorite = !this.tag.favorite;
                TagManager.updateTag(this.tag)
                          .catch(() => { this.tag.favorite = !this.tag.favorite; });
            },
            toggleMenu() {
                this.showMenu = !this.showMenu;
                if(this.showMenu) {
                    document.addEventListener('click', this.menuEvent);
                } else {
                    document.removeEventListener('click', this.menuEvent);
                }
            },
            menuEvent($e) {
                if($e.target.closest('[data-tag-id="' + this.tag.id + '"] .more') !== null) return;
                this.showMenu = false;
                document.removeEventListener('click', this.menuEvent);
            },
            openAction($event) {
                if($event.target.closest('.more') !== null) return;
                this.$router.push({name: 'Tags', params: {tag: this.tag.id}});
            },
            deleteAction() {
                TagManager.deleteTag(this.tag);
            },
            editAction() {
                TagManager.editTag(this.tag)
                          .then((t) => {this.tag = t;});
            }
        },

        watch: {
            tag(value) {
                ContextMenuService.register(value, this.$el);
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