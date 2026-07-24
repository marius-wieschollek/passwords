import Vue from 'vue';

/**
 * Keeps track of which folders/tags/passwords are currently checked in a list view,
 * so the header checkbox and every row can share one state without prop drilling.
 */
class SelectionManager {

    constructor() {
        this._state = Vue.observable(
            {
                folders  : [],
                tags     : [],
                passwords: [],
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
        return this._state.folders.indexOf(folder) !== -1;
    }

    isTagSelected(tag) {
        return this._state.tags.indexOf(tag) !== -1;
    }

    isPasswordSelected(password) {
        return this._state.passwords.indexOf(password) !== -1;
    }

    toggleFolder(folder) {
        this._state.folders = this._toggle(this._state.folders, folder);
    }

    toggleTag(tag) {
        this._state.tags = this._toggle(this._state.tags, tag);
    }

    togglePassword(password) {
        this._state.passwords = this._toggle(this._state.passwords, password);
    }

    /**
     * Selects everything currently visible, or clears the selection if everything is already selected.
     */
    toggleAll() {
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

    _toggle(list, item) {
        let index = list.indexOf(item);
        if(index === -1) return [...list, item];

        return list.filter((entry) => entry !== item);
    }
}

export default new SelectionManager();
