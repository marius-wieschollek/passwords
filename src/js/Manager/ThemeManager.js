/**
 *
 */
export default class ThemeManager {

    /**
     *
     * @returns {string|*}
     */
    static getColor() {
        return OCA.Theming ? OCA.Theming.color:'#0082C9';
    }

    /**
     *
     * @returns {string|*}
     */
    static getContrastColor() {
        if(! OCA.Theming) return '#fff';

        return OCA.Theming.inverted ? '#545454':'#fff';
    }
}