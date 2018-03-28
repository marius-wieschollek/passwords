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
        this.imageCounter = 0;
    }

    /**
     *
     * @param page
     * @returns {Promise<*>}
     */
    async fetchPage(page) {
        if(this.pages.hasOwnProperty(page)) return this.pages[page];

        try {
            let url = this.baseUrl + page,
                response = await fetch(new Request(`${url}.md`));

            if(response.ok) {
                let html = this.render(await response.text(), url);
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
     * @param url
     * @returns {*}
     */
    render(markdown, url) {
        this.imageCounter = 0;

        let renderer = new marked.Renderer();
        renderer.link = (href, title, text, wrap) => { return this._renderLink(href, title, text, wrap, url);};
        renderer.image = (href, title, text, nowrap) => { return this._renderImage(href, title, text, nowrap);};
        renderer.heading = HandbookRenderer._renderHeader;
        HandbookRenderer._extendMarkedLexer();

        return marked(markdown, {renderer});
    }

    /**
     *
     * @private
     */
    static _extendMarkedLexer() {
        marked.InlineLexer.prototype.outputLink = function(cap, link) {
            let href  = escape(link.href),
                title = link.title ? escape(link.title):null;

            this.isRenderingImage = false;
            if(cap[0].charAt(0) !== '!') {
                this.isRenderingLink = true;
                let value = this.renderer.link(href, title, this.output(cap[1]), this.isRenderingImage);
                this.isRenderingImage = false;
                this.isRenderingLink = false;
                return value;
            }

            this.isRenderingImage = true;
            return this.renderer.image(href, title, escape(cap[1]), this.isRenderingLink);

            function escape(html, encode) {
                return html
                    .replace(!encode ? /&(?!#?\w+;)/g:/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }
        };
    }

    /**
     *
     * @param href
     * @param title
     * @param text
     * @param wrap
     * @param pageUrl
     * @returns {string}
     * @private
     */
    _renderLink(href, title, text, wrap = false, pageUrl) {
        let target = '_blank',
            url = new URL(href, pageUrl);

        if(url.href.indexOf(pageUrl) !== -1 && url.hash.length) {
            [href, title, target] = HandbookRenderer._processAnchorLink(url.hash, title, target);
        } else if(url.href.indexOf(this.baseUrl) !== -1) {
            let mime = href.substr(url.href.lastIndexOf('.')+1);
            if(['png', 'jpg', 'jpeg', 'gif', 'mp4', 'm4v', 'ogg', 'webm', 'txt', 'html', 'json', 'js'].indexOf(mime) === -1) {
                [href, title, target] = this._processInternalLink(url, title, target);
            } else {
                href = url.href;
            }
        } else {
            href = url.href;
        }

        if(title === null) title = Localisation.translate('Go to {href}', {href});

        return wrap
            ? this._wrapImage(href, title, text)
            :`<a href="${href}" title="${title}" target="${target}" style="color:${ThemeManager.getColor()}">${text}</a>`;
    }

    /**
     *
     * @param href
     * @param title
     * @param target
     * @returns {*[]}
     * @private
     */
    static _processAnchorLink(href, title, target) {
        let hash  = HandbookRenderer._getLinkAnchor(href),
            route = VueRouter.resolve({name: 'Help', params: {page: VueRouter.currentRoute.params.page}, hash});
        if(title === null) title = Localisation.translate('Go to {href}', {href: href.substr(1).replace(/-{1}/g, ' ')});

        return [route.href, title, '_self'];
    }

    /**
     *
     * @param url
     * @param title
     * @param target
     * @returns {*[]}
     * @private
     */
    _processInternalLink(url, title, target) {
        let hash = undefined,
            href = url.href.substr(this.baseUrl.length);
        if(url.hash.length) {
            hash = HandbookRenderer._getLinkAnchor(url.hash);
            href = href.substring(0, href.indexOf(url.hash));
        }

        let route = VueRouter.resolve({name: 'Help', params: {page: href}, hash});
        if(title === null) title = Localisation.translate('Go to {href}', {href: href.replace(/-{1}/g, ' ')});

        return [route.href, title, '_self'];
    }

    /**
     *
     * @param hash
     * @returns {string}
     * @private
     */
    static _getLinkAnchor(hash) {
        let anchor = hash.trim().substr(1).toLowerCase().replace(/[^\w]+/g, '-');
        return `#help-${anchor}`;
    }

    /**
     *
     * @param text
     * @param level
     * @returns {string}
     * @private
     */
    static _renderHeader(text, level) {
        let id = text.trim().toLowerCase().replace(/[^\w]+/g, '-'),
            [link] = HandbookRenderer._processAnchorLink(`#${id}`, '', '');

        return `<h${level} id="help-${id}"><a href="${link}" class="fa fa-link help-anchor" aria-hidden="true"></a>${text}</h${level}>`;
    }

    /**
     *
     * @param href
     * @param title
     * @param text
     * @param nowrap
     * @returns {string}
     * @private
     */
    _renderImage(href, title, text, nowrap = false) {
        if(href.substr(0, 5) !== 'https') href = this.baseUrl + href;

        this.imageCounter++;
        if(text === null) text = href;
        if(title === null) title = text;

        let caption = Localisation.translate('Figure {count}: {title}', {count: this.imageCounter, title}),
            source  = `<img src="${href}" alt="${text}" class="md-image"><span class="md-image-caption">${caption}</span>`;

        return nowrap ? source:this._wrapImage(href, title, source)
    }

    /**
     *
     * @param href
     * @param title
     * @param source
     * @returns {string}
     * @private
     */
    _wrapImage(href, title, source) {
        return `<span class="md-image-container" id="help-image-${this.imageCounter}">
                <a class="md-image-link" title="${title}" href="${href}" target="_blank">
                ${source}</a></span>`;
    }
}

let HR = new HandbookRenderer();

export default HR;