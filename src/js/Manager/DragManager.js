import $ from "jquery";

export default new class DragManager {

    constructor() {
        this._data = {};

        $(document).on('dragover', this.over);
    }

    /**
     *
     * @param event
     * @param label
     * @param image
     * @param types
     * @returns {Promise<any>}
     */
    start(event, label, image, types = []) {
        let $el = $('<div id="dragicon" style="background-image:url(' + image + ')">' + label + '</div>');
        $('body').append($el);

        event.dataTransfer.effectAllowed = 'all';
        event.dataTransfer.setData('text', label);
        event.dataTransfer.setDragImage($el[0], 0, 0);

        return new Promise((resolve, reject) => {
            document.addEventListener('drop', (e) => {
                e.preventDefault();
                let $target = $(e.target).parents('[data-drop-type]');
                $el.remove();

                if ($target.length !== 0 && types.indexOf($target.data().dropType) !== -1) {
                    resolve($target.data())
                } else {
                    reject();
                }
            }, false);
        })
    }

    drop(e, types, resolve, reject) {

    }

    over() {
        return false;
    }
}