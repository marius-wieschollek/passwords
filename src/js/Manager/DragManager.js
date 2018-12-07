import $ from 'jquery';
import Utility from '@js/Classes/Utility';

/**
 *
 */
export default new class DragManager {

    constructor() {
        $(document).on('dragover', DragManager.scrollContent);
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
        let $el = $(`<div id="dragicon" style="background-image:url(${image})">${label}</div>`);
        $('body').append($el);

        event.dataTransfer.effectAllowed = 'all';
        event.dataTransfer.setData('text', label);
        event.dataTransfer.setDragImage($el[0], 0, 0);

        return new Promise((resolve, reject) => {
            document.addEventListener('drop', (e) => {
                e.preventDefault();
                $el.remove();
                let $target = $(e.target);
                if(!$target.data().dropType) {
                    $target = $(e.target).parents('[data-drop-type]');
                }

                if($target.length !== 0 && types.indexOf($target.data().dropType) !== -1) {
                    resolve($target.data());
                } else {
                    reject({});
                }
            }, false);
        });
    }

    static scrollContent(e) {
        let height    = window.innerHeight,
            offsetTop = window.scrollY;

        if(e.originalEvent.clientY < height * 0.25) {
            Utility.scrollTo(offsetTop - 50);
        } else if(e.originalEvent.clientY > height * 0.85) {
            Utility.scrollTo(offsetTop + 50);
        }
        return false;
    }
};