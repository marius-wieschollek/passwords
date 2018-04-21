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
        this.handbookUrl = SettingsManager.get('server.handbook.url');
        this.pages = [];
        this.imageCounter = 0;
        this.imageCaption = null;
    }

    /**
     *
     * @param page
     * @returns {Promise<*>}
     */
    async fetchPage(page) {
        if(this.pages.hasOwnProperty(page)) return this.pages[page];

        try {
            let url      = this.handbookUrl + page,
                response = await fetch(new Request(`${url}.md`)),
                baseUrl = url.substr(url, url.lastIndexOf('/')+1);

            if(response.ok) {
                let html = this.render(await response.text(), baseUrl, url);
                this.pages[page] = html;
                return html;
            }
            return Localisation.translate('Unable to fetch page: {message}.', {message: Localisation.translate(response.statusText)});
        } catch(e) {
            if(process.env.NODE_ENV === 'development') console.error('Request failed', e);
            throw e;
        }
    }

    /**
     *
     * @param markdown
     * @param baseUrl
     * @param documentUrl
     * @returns {*}
     */
    render(markdown, baseUrl, documentUrl) {
        this.imageCounter = 0;

        let renderer = new marked.Renderer();
        renderer.link = (href, title, text, wrap) => { return this._renderLink(href, title, text, wrap, baseUrl, documentUrl);};
        renderer.image = (href, title, text, nowrap) => { return this._renderImage(href, title, text, nowrap, baseUrl);};
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
            function escape(html) {
                return html
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

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
        };
    }

    /**
     *
     * @param href
     * @param title
     * @param text
     * @param wrap
     * @param baseUrl
     * @param documentUrl
     * @returns {string}
     * @private
     */
    _renderLink(href, title, text, wrap, baseUrl, documentUrl) {
        let target = '_blank',
            url    = new URL(href, baseUrl);

        href = url.href;
        if(url.href.indexOf(documentUrl) !== -1 && url.hash.length) {
            [href, title, target] = HandbookRenderer._processAnchorLink(url.hash, title);
        } else if(url.href.indexOf(this.handbookUrl) !== -1) {
            let mime = url.href.substr(url.href.lastIndexOf('.') + 1);
            if(['png', 'jpg', 'jpeg', 'gif', 'mp4', 'm4v', 'ogg', 'webm', 'txt', 'html', 'json', 'js'].indexOf(mime) === -1) {
                [href, title, target] = this._processInternalLink(url, title);
            }
        }

        if(title === null) title = wrap ? this.imageCaption:Localisation.translate('Go to {href}', {href});
        let rel = target === '_blank' ? 'rel="noreferrer noopener"':'';

        return wrap ?
               this._wrapImage(href, title, text) :
               `<a href="${href}" title="${decodeURI(title)}" target="${target}" style="color:${ThemeManager.getColor()}" ${rel}>${text}</a>`;
    }

    /**
     *
     * @param href
     * @param title
     * @returns {*[]}
     * @private
     */
    static _processAnchorLink(href, title) {
        let hash  = HandbookRenderer._getLinkAnchor(href),
            route = VueRouter.resolve({name: 'Help', params: {page: VueRouter.currentRoute.params.page}, hash});
        if(title === null) title = Localisation.translate('Go to {href}', {href: href.substr(1).replace(/-{1}/g, ' ')});

        return [route.href, title, '_self'];
    }

    /**
     *
     * @param url
     * @param title
     * @returns {*[]}
     * @private
     */
    _processInternalLink(url, title) {
        let hash = undefined,
            href = url.href.substr(this.handbookUrl.length);
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
        let id     = text.trim().toLowerCase().replace(/[^\w]+/g, '-'),
            [link] = HandbookRenderer._processAnchorLink(`#${id}`, '');

        return `<h${level} id="help-${id}"><a href="${link}" class="fa fa-link help-anchor" aria-hidden="true"></a>${text}</h${level}>`;
    }

    /**
     *
     * @param href
     * @param title
     * @param text
     * @param nowrap
     * @param baseUrl
     * @returns {string}
     * @private
     */
    _renderImage(href, title, text, nowrap, baseUrl) {
        let url    = new URL(href, baseUrl);

        this.imageCounter++;
        if(text === null) text = href;
        if(title === null) title = text;

        let caption = Localisation.translate('Figure {count}: {title}', {count: this.imageCounter, title}),
            source  = `<img src="${url}" alt="${text.replace(/"/g, '&quot;')}" class="md-image"><span class="md-image-caption">${caption}</span>`;
        this.imageCaption = caption;

        return nowrap ? source:this._wrapImage(url, caption, source);
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
        return `<span class="md-image-container" id="help-image-${this.imageCounter}" data-image-id="${this.imageCounter}">
                <a class="md-image-link" title="${title}" href="${href}" target="_blank" rel="noreferrer noopener">
                ${source}</a></span>`;
    }
}

let HR = new HandbookRenderer();

export default HR;