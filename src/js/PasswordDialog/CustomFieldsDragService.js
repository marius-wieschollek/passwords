/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

export default class CustomFieldsDragService {

    /**
     * @param {Object} password
     */
    constructor(password) {
        this._field = null;
        this._password = password;
        this._mousemove = (e) => {
            if(e.buttons === 0) this.end();
        }
    }

    /**
     * Set the current password object with the custom fields
     *
     * @param {Object} password
     */
    setPassword(password) {
        this._password = password;
    }

    /**
     * Check if the given field is currently used for drag and drop
     *
     * @param {Object} field
     * @returns {Boolean}
     */
    isCurrent(field) {
        return this._field !== null && this._field === field;
    }

    /**
     * Start drag and drop for the given field
     *
     * @param {DragEvent} e
     * @param {Object} field
     * @param {Element} element
     */
    start(e, field, element) {
        e.dataTransfer.effectAllowed = 'all';
        e.dataTransfer.dropEffect = 'move';
        e.dataTransfer.setDragImage(element, 32, 32);
        e.dataTransfer.setData('text', `${field.label}: ${field.value}`);

        this._field = field;
        document.addEventListener('mousemove', this._mousemove, {passive: true});
    }

    /**
     * Register drag and drop entering a custom field
     *
     * @param {DragEvent} e
     * @param {Object} field
     */
    dragenter(e, field) {
        if(!this.isCurrent(field)) {
            this._move(this._password, this._field, field);
        }
    }

    /**
     * End drag and drop
     */
    end() {
        this._field = null;
        document.removeEventListener('mousemove', this._mousemove);
    }

    /**
     * Moves the object to the position of target
     *
     * @param {Object} password
     * @param {Object} source
     * @param {Object} target
     * @private
     */
    _move(password, source, target) {
        let sourceIndex = password.customFields.indexOf(source);
        if(sourceIndex === -1) {
            password.customFields.push(source);
            return;
        }
        password.customFields.splice(sourceIndex, 1);

        let targetIndex = password.customFields.indexOf(target);
        if(targetIndex !== -1) {
            if(targetIndex >= sourceIndex) targetIndex++;
            password.customFields.splice(targetIndex, 0, source);
        } else {
            password.customFields.push(source);
        }
    }
}