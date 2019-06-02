/**
 * Use Settings Service instead
 * @deprecated
 */
export default class ThemeManager {

    /**
     *
     * @returns {string|*}
     * @deprecated
     */
    static getColor() {
        return OCA.Theming ? OCA.Theming.color:'#0082C9';
    }
}