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
    <div class="actions">
        <nc-actions
                :inline="hasCustomAction ? 1:0"
                :boundaries-element="getBoundariesElement"
                :container="getBoundariesElement"
                :open.sync="menuOpen"
                @opened="emitOpened"
                @closed="emitClosed"
                variant="tertiary"
                @click.stop.prevent
                data-pw-role="context-menu"
        >
            <slot name="custom-action"/>
            <nc-action-button @click="actions.favorite()" data-pw-action="favorite" close-after-click>
                <template #icon>
                    <star-icon :size="20" fill-color="var(--color-element-warning)" v-if="tag.favorite"/>
                    <star-outline-icon :size="20" fill-color="var(--color-placeholder-dark)" v-else/>
                </template>
                {{ tag.favorite ? t('BatchActionRemoveFavorites'):t('BatchActionAddFavorites') }}
            </nc-action-button>
            <nc-action-button @click="actions.edit()" data-pw-action="edit" close-after-click>
                <template #icon>
                    <pencil-icon :size="20"/>
                </template>
                {{ t('Edit') }}
            </nc-action-button>
            <nc-action-separator/>
            <slot name="restore-action" />
            <nc-action-button @click="deleteTag" data-pw-action="delete" close-after-click>
                <template #icon>
                    <trash-can-outline-icon :size="20"/>
                </template>
                {{ t('Delete') }}
            </nc-action-button>
        </nc-actions>
    </div>
</template>

<script>
    import TrashCanOutlineIcon from "@icon/TrashCanOutline";
    import PencilIcon from "@icon/Pencil";
    import StarIcon from "@icon/Star";
    import StarOutlineIcon from "@icon/StarOutline";
    import NcActions from '@nc/NcActions.js';
    import NcActionButton from '@nc/NcActionButton.js';
    import NcActionSeparator from '@nc/NcActionSeparator.js';
    import TagActions from "@js/Actions/Tag/TagActions";
    import {emit, subscribe, unsubscribe} from "@js/Helper/event-bus";

    export default {
        components: {
            StarOutlineIcon,
            StarIcon,
            PencilIcon,
            TrashCanOutlineIcon,
            NcActions,
            NcActionButton,
            NcActionSeparator
        },

        props: {
            tag       : {
                type: Object
            },
            actions   : {
                type: TagActions
            },
            value: {
                type: PointerEvent
            }
        },

        data() {
            return {
                menuOpen: false,
            }
        },

        computed: {
            hasCustomAction() {
                return this.$slots.hasOwnProperty('custom-action');
            },
            getBoundariesElement() {
                return document.querySelector('.app-content .item-list');
            }
        },

        methods: {
            deleteTag() {
                this.actions.delete();
            },
            emitOpened() {
                let event = {item: this.tag};
                if(this.value) {
                    event.pos = {x: this.value.clientX, y: this.value.clientY};
                }
                emit('passwords:contextmenu:opened', event);
                subscribe('passwords:contextmenu:opened', this.processContextMenuEvent);
            },
            emitClosed() {
                emit('passwords:contextmenu:closed', {item: this.tag});
                unsubscribe('passwords:contextmenu:opened', this.processContextMenuEvent);
                this.$emit('input', null)
            },
            processContextMenuEvent(event) {
                if(this.menuOpen && event.item.id !== this.tag.id) {
                    this.menuOpen = false;
                }
            }
        },

        watch: {
            value: {
                deep: true,
                handler(value) {
                    if (value && !this.menuOpen) {
                        this.menuOpen = true;
                    }
                }
            }
        }
    };

</script>

<style lang="scss">
#app-content {
    .item-list {
        .row {
            .actions {
                display         : flex;
                align-self      : center;
                align-items     : center;
                justify-content : center;
            }
        }
    }
}
</style>