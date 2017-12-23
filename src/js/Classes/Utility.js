export default class Utility {

    /**
     *
     * @param text
     * @param variables
     * @returns {string}
     */
    static translate(text, variables = {}) {
        if (OC !== undefined) {
            return t('passwords', text, variables);
        }

        return '';
    }

    /**
     *
     * @param text
     */
    static copyToClipboard(text) {
        let id       = 'ctc-' + Math.random(),
            $element = $('<textarea id="' + id + '">' + text + '</textarea>');

        $('body').append($element);
        $element.select();

        document.execCommand('copy');

        $element.remove();
    }

    /**
     *
     * @param object        The object to be sorted
     * @param property      The property to sort by
     * @param ascending     Sort ascending if true, descending if false
     * @param returnArray   Return the results as array
     * @param sortFunction  Custom sort function
     * @returns {Array|Object}
     */
    static sortApiObjectArray(object, property = 'uuid', ascending = true, returnArray = true, sortFunction) {
        let rArr = [], rObj = {};

        if (sortFunction === undefined) {
            sortFunction = (a, b) => {
                let aP = a[property], bP = b[property];
                if (aP === bP) return 0;
                if (typeof aP === 'string') {
                    if (ascending) return aP.localeCompare(bP, 'kn', {sensitivity: 'base'});
                    return bP.localeCompare(aP, 'kn', {sensitivity: 'base'}) === 1 ? -1:1;
                }
                if (ascending) return aP < bP ? -1:1;
                return aP > bP ? -1:1;
            }
        }

        for (let key in object) {
            if (!object.hasOwnProperty(key)) continue;
            rArr.push(object[key]);
        }

        rArr.sort(sortFunction);

        if (returnArray) return rArr;

        for (let i = 0; i < rArr.length; i++) {
            let element = rArr[i];
            rObj[element.uuid] = element;
        }

        return rObj;
    }

    /**
     *
     * @param array
     * @param object
     * @returns {*}
     */
    static replaceOrAppendApiObject(array, object) {
        for (let i = 0; i < array.length; i++) {
            let current = array[i];
            if (current.id === object.id) {
                array[i] = object;
                return array;
            }
        }
        array.push(object);

        return array;
    }
}