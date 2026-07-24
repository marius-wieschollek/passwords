import Vue from 'vue';
import LoggingService from "@js/Services/LoggingService";

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

    get count() {
        return this._state.folders.length + this._state.tags.length + this._state.passwords.length;
    }

    get active() {
        return this.count !== 0;
    }

    get allSelected() {
        let visible = this._state.visible,
            total   = visible.folders.length + visible.tags.length + visible.passwords.length;

        return total !== 0 && this.count === total;
    }

    /**
     * Called by the list view whenever the currently loaded folders/tags/passwords change,
     * so "select all" knows what "all" means right now.
     *
     * @param folders
     * @param tags
     * @param passwords
     */
    setVisible(folders, tags, passwords) {
        this._state.visible = {folders, tags, passwords};
        this._state.folders = this._state.folders.filter((f) => folders.indexOf(f) !== -1);
        this._state.tags = this._state.tags.filter((t) => tags.indexOf(t) !== -1);
        this._state.passwords = this._state.passwords.filter((p) => passwords.indexOf(p) !== -1);
    }

    isFolderSelected(folder) {
        return this._has(this._state.folders, folder);
    }

    isTagSelected(tag) {
        return this._has(this._state.tags, tag);
    }

    isPasswordSelected(password) {
        return this._has(this._state.passwords, password);
    }

    isItemProcessed(item) {
        for(let action of this._state.actions) {
            console.log(action.hasItem(item));
            if(action.hasItem(item)) {
                return true;
            }
        }

        return false;
    }

    toggleFolder(folder, state) {
        if(typeof state !== 'boolean') {
            this._state.folders = this._toggle(this._state.folders, folder);

            return;
        }

        if(state && !this._has(this._state.folders, folder)) {
            this._state.folders = this._add(this._state.folders, folder);
        } else if(!state && this._has(this._state.folders, folder)) {
            this._state.folders = this._remove(this._state.folders, folder);
        }
    }

    toggleTag(tag, state) {
        if(typeof state !== 'boolean') {
            this._state.tags = this._toggle(this._state.tags, tag);

            return;
        }

        if(state && !this._has(this._state.tags, tag)) {
            this._state.tags = this._add(this._state.tags, tag);
        } else if(!state && this._has(this._state.tags, tag)) {
            this._state.tags = this._remove(this._state.tags, tag);
        }
    }

    togglePassword(password, state) {
        if(typeof state !== 'boolean') {
            this._state.passwords = this._toggle(this._state.passwords, password);

            return;
        }

        if(state && !this._has(this._state.passwords, password)) {
            this._state.passwords = this._add(this._state.passwords, password);
        } else if(!state && this._has(this._state.passwords, password)) {
            this._state.passwords = this._remove(this._state.passwords, password);
        }
    }

    /**
     * Selects everything currently visible, or clears the selection if everything is already selected.
     */
    selectAll() {
        if(this.allSelected) {
            this.clear();
            return;
        }

        this._state.folders = [...this._state.visible.folders];
        this._state.tags = [...this._state.visible.tags];
        this._state.passwords = [...this._state.visible.passwords];
    }

    clear() {
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

        this.clear();

        try {
            await action.run();
        } catch(e) {
            LoggingService.error(e);
        }

        this._state.actions = this._remove(this._state.actions, action);
    }

    _toggle(list, item) {
        if(this._has(list, item)) return this._remove(list, item);

        return this._add(list, item);
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
