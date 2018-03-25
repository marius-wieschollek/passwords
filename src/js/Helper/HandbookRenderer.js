import marked from 'marked';
import VueRouter from '@js/Helper/router';
import ThemeManager from '@js/Manager/ThemeManager';
import Localisation from '@/js/Classes/Localisation';
import SettingsManager from '@js/Manager/SettingsManager';

/**
 *
 */
class HandbookRenderer {

    constructor() {
        this.baseUrl = SettingsManager.get('server.manual.url');
        this.pages = [];
    }

    /**
     *
     * @param page
     * @returns {Promise<*>}
     */
    async fetchPage(page) {
        if(this.pages.hasOwnProperty(page)) return this.pages[page];

        try {
            let response = await fetch(new Request(`${this.baseUrl}${page}.md`));

            if(response.ok) {
                let html = this.render(await response.text());
                this.pages[page] = html;
                return html;
            }
            throw new Error(response.statusText);
        } catch(e) {
            if(process.env.NODE_ENV === 'development') console.error('Request failed', e);
            throw e;
        }
    }

    /**
     *
     * @param markdown
     * @returns {*}
     */
    render(markdown) {
        let renderer = new marked.Renderer();
        renderer.link = HandbookRenderer._renderLink;
        renderer.image = (href, title, text) => { return this._renderImage(href, title, text);};
        renderer.heading = HandbookRenderer._renderHeader;

        return marked(markdown, {renderer: renderer});
    }

    /**
     *
     * @param href
     * @param title
     * @param text
     * @returns {string}
     * @private
     */
    static _renderLink(href, title, text) {
        let target = '_blank';

        if(href[0] === '#') {
            let anchor = href.trim().substr(1).toLowerCase().replace(/[^\w]+/g, '-'),
                route  = VueRouter.resolve({name: 'Help', params: {page: VueRouter.currentRoute.params.page}, hash: `#help-${anchor}`});
            if(title === null) title = Localisation.translate('Go to {href}', {href: href.substr(1)});
            href = route.href;
            target = '_self';
        } else if(href.substring(0, 4) !== 'http') {
            let route = VueRouter.resolve({name: 'Help', params: {page: href}});
            if(title === null) title = Localisation.translate('Go to {href}', {href: href});
            href = route.href;
            target = '_self';
        }

        if(title === null) title = Localisation.translate('Go to {href}', {href});
        return `<a href="${href}" title="${title}" target="${target}" style="color:${ThemeManager.getColor()}">${text}</a>`;
    }

    /**
     *
     * @param text
     * @param level
     * @returns {string}
     * @private
     */
    static _renderHeader(text, level) {
        let id = text.trim().toLowerCase().replace(/[^\w]+/g, '-');

        return `<h${level} id="help-${id}">${text}</h${level}>`;
    }

    /**
     *
     * @param href
     * @param title
     * @param text
     * @returns {string}
     * @private
     */
    _renderImage(href, title, text) {
        if(href.substr(0, 5) !== 'https') href = this.baseUrl + href;

        if(text === null) text = href;
        if(title === null) title = text;

        return `<img src="${href}" title="${title}" alt="${text}">`;
    }
}

export let Renderer = new HandbookRenderer();