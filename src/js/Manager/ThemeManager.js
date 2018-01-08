/**
 *
 */
class ThemeManager {

    constructor() {
        this._color = '#0082C9';
        this._contrastColor = '#fff';

        if(OCA.Theming) {
            this._color = OCA.Theming.color;
            this._contrastColor = OCA.Theming.inverted ? '#000':'#fff';

        }
    }

    /**
     *
     * @param selector
     * @param contrast
     */
    setBorderColor(selector, contrast = false) {
        this.setCssProperty(selector, 'border-color', contrast);
    }

    /**
     *
     * @param selector
     * @param property
     * @param contrast
     */
    setCssProperty(selector, property, contrast = false) {
        $(selector).css(property, contrast ? this._contrastColor:this._color);
    }

    /**
     *
     * @returns {string|*}
     */
    getColor() {
        return this._color;
    }

    /**
     *
     * @returns {string|*}
     */
    getContrastColor() {
        return this._contrastColor;
    }
}

let TM = new ThemeManager();

export default TM;