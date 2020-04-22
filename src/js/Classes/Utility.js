export default class Utility {

    /**
     *
     * @param text
     * @returns {boolean}
     */
    static copyToClipboard(text) {
        let element   = document.createElement('textarea'),
            range     = document.createRange(),
            selection = window.getSelection();

        element.value    = text;
        element.readOnly = true;

        document.body.appendChild(element);
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
        element.select();
        element.setSelectionRange(0, 999999);

        let result = document.execCommand('copy');
        document.body.removeChild(element);

        return result;
    }

    /**
     *
     * @param content
     * @param name
     * @param mime
     */
    static createDownload(content, name = null, mime = 'text/plain') {
        if(name === null) name = `${new Date().toISOString()}.txt`;

        let blob    = content instanceof Blob ? content:new Blob([content], {type: mime}),
            element = document.createElement('a'),
            url     = window.URL.createObjectURL(blob);

        element.setAttribute('href', url);
        element.setAttribute('download', name);
        element.style.display = 'none';

        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }

    /**
     *
     * @param url
     * @param target
     */
    static openLink(url, target = '_blank') {
        let element = document.createElement('a');
        element.setAttribute('href', url);
        element.setAttribute('target', target);
        if(target === '_blank' || element.host !== location.host) element.setAttribute('rel', 'noreferrer noopener');
        element.style.display = 'none';

        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }

    /**
     *
     * @param top
     * @param left
     * @param behavior
     */
    static scrollTo(top = 0, left = 0, behavior = 'smooth') {
        if(navigator.userAgent.indexOf('MSIE') === -1 && navigator.userAgent.indexOf('Edge') === -1 && navigator.userAgent.indexOf('Safari') === -1) {
            window.scrollTo({left, top, behavior});
        } else {
            window.scrollTo(left, top);
        }
    }

    /**
     *
     * @returns {number}
     */
    static getTimestamp() {
        return Math.floor(new Date().getTime() / 1000);
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

        if(sortFunction === undefined) {
            sortFunction = (a, b) => {
                let aP = a[property], bP = b[property];
                if(aP === bP) return 0;
                if(typeof aP === 'string') {
                    if(ascending) return aP.localeCompare(bP, 'kn', {sensitivity: 'base'});
                    return bP.localeCompare(aP, 'kn', {sensitivity: 'base'});
                }
                if(ascending) return aP < bP ? -1:1;
                return aP > bP ? -1:1;
            };
        }

        for(let key in object) {
            if(!object.hasOwnProperty(key)) continue;
            rArr.push(object[key]);
        }

        rArr.sort(sortFunction);

        if(returnArray) return rArr;

        for(let i = 0; i < rArr.length; i++) {
            let element = rArr[i];
            rObj[element.uuid] = element;
        }

        return rObj;
    }

    /**
     *
     * @param object
     * @returns {Array}
     */
    static objectToArray(object) {
        let array = [];
        for(let key in object) {
            if(!object.hasOwnProperty(key)) continue;
            array.push(object[key]);
        }
        return array;
    }

    /**
     *
     * @param array
     * @param object
     * @returns {*}
     */
    static replaceOrAppendApiObject(array, object) {
        for(let i = 0; i < array.length; i++) {
            let current = array[i];
            if(current.id === object.id) {
                array[i] = object;
                return array;
            }
        }
        array.push(object);

        return array;
    }

    /**
     *
     * @param array
     * @param object
     * @returns {*}
     */
    static removeApiObjectFromArray(array, object) {
        for(let i = 0; i < array.length; i++) {
            let current = array[i];
            if(current.id === object.id) {
                return array.remove(i);
            }
        }

        return array;
    }

    /**
     *
     * @param array
     * @param object
     * @returns {*}
     */
    static searchApiObjectInArray(array, object) {
        for(let i = 0; i < array.length; i++) {
            let current = array[i];
            if(current.id === object.id) return i;
        }

        return -1;
    }

    /**
     *
     * @param a
     * @param b
     * @returns {*}
     */
    static mergeObject(a, b) {
        let object = Utility.cloneObject(a);

        for(let key in b) {
            if(!b.hasOwnProperty(key)) continue;
            object[key] = b[key];
        }

        return object;
    }

    /**
     *
     * @param object
     */
    static cloneObject(object) {
        let clone = new object.constructor();

        for(let key in object) {
            if(!object.hasOwnProperty(key)) continue;
            let element = object[key];

            if(Array.isArray(element)) {
                clone[key] = element.slice(0);
            } else if(element instanceof Date) {
                clone[key] = new Date(element.getTime());
            } else if(element === null) {
                clone[key] = null;
            } else if(typeof element === 'object') {
                clone[key] = Utility.cloneObject(element);
            } else {
                clone[key] = element;
            }
        }

        return clone;
    }

    /**
     * Creates a new array with just the values and no empty keys
     *
     * @param array
     * @returns {Array}
     */
    static arrayValues(array) {
        let output = [],
            iterator = array.values();

        for (let value of iterator) {
            if(value !== undefined) output.push(value);
        }

        return output;
    }

    /**
     * Generates a full url to the Nextcloud page
     *
     * @param path
     * @param params
     * @param options
     * @returns {*}
     */
    static generateUrl(path = '', params = [], options = {}) {
        let baseUrl = OC.generateUrl(path, params, options);
        if(baseUrl.indexOf(location.origin) === -1) {
            return new URL(location.origin + baseUrl).href;
        }

        return baseUrl;
    }
}