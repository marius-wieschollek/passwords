import FaviconService from "@js/Services/FaviconService";
import UtilityService from "@js/Services/UtilityService";

/**
 *
 */
export default new class DragManager {

    constructor() {
        document.addEventListener('dragover', (e) => { return this._autoScroll(e); });
        document.addEventListener('drop', (e) => {return this._end(e);}, false);
        this._state = {model: null, resolve: null, reject: null, active: false};
    }

    /**
     *
     * @param event
     * @param model
     * @returns {Promise<any>}
     */
    start(event, model) {
        let dragDummy = this._createDragElement(model);

        event.dataTransfer.effectAllowed = 'all';
        event.dataTransfer.dropEffect = 'move';
        event.dataTransfer.setData('text', model.label);
        event.dataTransfer.setDragImage(dragDummy, 0, 0);

        return new Promise((resolve, reject) => {
            this._state = {
                resolve,
                reject,
                model,
                active: true
            };
        });
    }

    /**
     *
     * @param {DragEvent} e
     * @private
     */
    _end(e) {
        e.preventDefault();
        this._removeDragElement();

        if(!this._state.active) return;
        this._state.active = false;

        let data = e.target.dataset;
        if(!data.dropType) {
            let target = e.target.closest('[data-drop-type]');
            if(!target) return;
            data = target.dataset;
        }

        let types = ['folder'];
        if(this._state.model.type === 'password') types.push('tag');
        if(!this._state.model.trashed) types.push('trash');

        if(types.indexOf(data.dropType) !== -1) {
            if(this._state.resolve) this._state.resolve(data);
        } else {
            if(this._state.reject) this._state.reject({});
        }
    }

    /**
     *
     * @param {Object} model
     * @returns {HTMLDivElement}
     * @private
     */
    _createDragElement(model) {
        this._removeDragElement();

        let image = model.icon;
        if(model.type === 'password') {
            image = FaviconService.get(model.website);
        }

        let div = document.createElement('div');
        div.id = 'dragicon';
        div.style.backgroundImage = `url(${image})`;
        div.innerText = model.label;
        document.body.append(div);

        return div;
    }

    /**
     *
     * @private
     */
    _removeDragElement() {
        let element = document.getElementById('dragicon');
        if(element) element.remove();
    }

    /**
     *
     * @param {DragEvent} e
     * @returns {boolean}
     * @private
     */
    _autoScroll(e) {
        e.preventDefault();
        let height    = window.innerHeight,
            offsetTop = window.scrollY;

        if(e.clientY < height * 0.25) {
            UtilityService.scrollTo(offsetTop - 50);
        } else if(e.clientY > height * 0.85) {
            UtilityService.scrollTo(offsetTop + 50);
        }
        return false;
    }
};