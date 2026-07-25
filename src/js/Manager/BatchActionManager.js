import Vue from 'vue';
import LoggingService from "@js/Services/LoggingService";
import {emit} from "@nextcloud/event-bus";

/**
 * Keeps track of which folders/tags/passwords are currently checked in a list view,
 * so the header checkbox and every row can share one state without prop drilling.
 */
class BatchActionManager {

    constructor() {
        this._state = Vue.observable(
            {
                folders  : [],
                tags     : [],
                passwords: [],
                actions  : [],
                visible  : {folders: [], tags: [], passwords: []}
            }
        );
    }

    get folders() {
        return this._state.folders;
    }

    get tags() {
        return this._state.tags;
    }

    get passwords() {
        return this._state.passwords;
    }

    get totalSelectedItems() {
        return this._state.folders.length + this._state.tags.length + this._state.passwords.length;
    }

    get hasSelectedItems() {
        return this.totalSelectedItems !== 0;
    }

    get allVisibleSelected() {
        let visible = this._state.visible,
            total   = visible.folders.length + visible.tags.length + visible.passwords.length;

        return total !== 0 && this.totalSelectedItems === total;
    }

    get isProcessingItems() {
        return this._state.actions.length !== 0;
    }

    /**
     * Called by the list view whenever the currently loaded folders/tags/passwords change,
     * so "select all" knows what "all" means right now.
     *
     * @param folders
     * @param tags
     * @param passwords
     */
    setVisibleItems(folders, tags, passwords) {
        this._state.visible = {folders, tags, passwords};
        this._state.folders = this._state.folders.filter((f) => folders.indexOf(f) !== -1);
        this._state.tags = this._state.tags.filter((t) => tags.indexOf(t) !== -1);
        this._state.passwords = this._state.passwords.filter((p) => passwords.indexOf(p) !== -1);
    }

    /**
     * Selects everything currently visible, or clears the selection if everything is already selected.
     */
    selectAllVisibleItems() {
        this._state.folders = [...this._state.visible.folders];
        this._state.tags = [...this._state.visible.tags];
        this._state.passwords = [...this._state.visible.passwords];
    }

    /**
     *
     * @param folder {Object}
     * @return {Boolean}
     */
    isFolderSelected(folder) {
        return this._has(this._state.folders, folder);
    }

    /**
     *
     * @param tag {Object}
     * @return {Boolean}
     */
    isTagSelected(tag) {
        return this._has(this._state.tags, tag);
    }

    /**
     *
     * @param password {Object}
     * @return {Boolean}
     */
    isPasswordSelected(password) {
        return this._has(this._state.passwords, password);
    }

    /**
     *
     * @param item {Object}
     * @return {Boolean}
     */
    isItemBeingProcessed(item) {
        for(let action of this._state.actions) {
            if(action.hasItem(item)) {
                return true;
            }
        }

        return false;
    }

    toggleFolder(folder, state) {
        this._state.folders = this._toggle(this._state.folders, folder, state);
    }

    toggleTag(tag, state) {
        this._state.tags = this._toggle(this._state.tags, tag, state);
    }

    togglePassword(password, state = null) {
        this._state.passwords = this._toggle(this._state.passwords, password, state);
    }

    clearSelectedItems() {
        this._state.folders = [];
        this._state.tags = [];
        this._state.passwords = [];
    }

    async executeAction(actionClass, options = {}) {
        let action = new actionClass(
            {
                folders  : this._state.folders,
                passwords: this._state.passwords,
                tags     : this._state.tags
            },
            options
        );

        this._state.actions.push(action);

        if(action.clearSelection) {
            this.clearSelectedItems();
        }

        try {
            emit('passwords:batch-action:started', {action});
            await action.run();
        } catch(e) {
            LoggingService.error(e);
        }

        this._state.actions = this._remove(this._state.actions, action);

        emit('passwords:batch-action:completed', {action});
    }

    _toggle(list, item, state) {
        let isActive = this._has(list, item);

        if(typeof state !== 'boolean') {
            state = !isActive;
        }

        if(state && !isActive) {
            return this._add(list, item);
        } else if(!state && isActive) {
            return this._remove(list, item);
        }
    }

    _add(list, item) {
        return [...list, item];
    }

    _remove(list, item) {
        return list.filter((entry) => entry !== item);
    }

    _has(list, item) {
        return list.indexOf(item) !== -1;
    }
}

export default new BatchActionManager();
