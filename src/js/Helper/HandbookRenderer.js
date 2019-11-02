import marked from 'marked';
import VueRouter from '@js/Helper/router';
import ThemeManager from '@js/Manager/ThemeManager';
import Localisation from '@js/Classes/Localisation';
import SettingsService from '@js/Services/SettingsService';

/**
 *
 */
class HandbookRenderer {

    constructor() {
        this.handbookUrl = SettingsService.get('server.handbook.url');
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
            let url      = this.handbookUrl + page,
                response = await fetch(new Request(`${url}.md`), {redirect: 'error', referrerPolicy: 'no-referrer'}),
                baseUrl  = url.substr(url, url.lastIndexOf('/') + 1),
                mime     = response.headers.get('content-type');

            if(response.ok) {
                if(mime.substr(0, 10) !== 'text/plain') {
                    return HandbookRenderer._generateErrorPage(
                        Localisation.translate('Invalid content type {mime}', {mime})
                    );
                }

                let data = await response.text();
                if(!data) return HandbookRenderer._generateErrorPage(Localisation.translate('No content available'));

                let content = this.render(data, baseUrl, url);
                this.pages[page] = content;
                return content;
            } else {
                return HandbookRenderer._generateErrorPage(response.status + ' – ' + response.statusText);
            }
        } catch(e) {
            if(process.env.NODE_ENV === 'development') console.error('Request failed', e);

            return HandbookRenderer._generateErrorPage(e.message);
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
        let navigation    = [],
            media         = [],
            blankRenderer = new marked.Renderer(),
            renderer      = new marked.Renderer();

        renderer.link = (href, title, text, wrap) => { return this._renderLink(href, title, text, wrap, baseUrl, documentUrl, media);};
        renderer.image = (href, title, text, nowrap) => { return HandbookRenderer._renderImage(href, title, text, nowrap, baseUrl, media);};
        renderer.heading = (text, level) => { return HandbookRenderer._renderHeader(text, level, navigation);};
        renderer.code = (code, infostring, escaped) => {
            let content = blankRenderer.code(code, infostring, escaped);
            return content.replace(/(\r\n|\n|\r)/gm, '<br>');
        };
        HandbookRenderer._extendMarkedLexer();

        let source = marked(markdown, {renderer});

        return {source, media, navigation};
    }

    /**
     *
     * @param message
     * @returns {{source: string, media: Array, navigation: Array}}
     * @private
     */
    static _generateErrorPage(message) {
        return {
            source    : '\u{1F480} ' + Localisation.translate('Unable to fetch page: {message}.', {message}),
            media     : [],
            navigation: []
        };
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
     * @param content
     * @param wrap
     * @param baseUrl
     * @param documentUrl
     * @param media
     * @returns {string}
     * @private
     */
    _renderLink(href, title, content, wrap, baseUrl, documentUrl, media) {
        let target = '_blank',
            url    = new URL(href, href.substr(0, 1) === '#' ? documentUrl:baseUrl);

        href = url.href;
        if(url.hash.length && url.href.indexOf(`${documentUrl}#`) !== -1) {
            [href, title, target] = HandbookRenderer._processAnchorLink(url.hash, title);
        } else if(url.href.indexOf(this.handbookUrl) !== -1) {
            let mime = url.href.substr(url.href.lastIndexOf('.') + 1);
            if(['png', 'jpg', 'jpeg', 'gif', 'mp4', 'm4v', 'ogg', 'webm', 'txt', 'html', 'json', 'js'].indexOf(mime) === -1) {
                [href, title, target] = this._processInternalLink(url, title);
            }
        }

        if(wrap) {
            let element = media[content * 1];
            element.url = href;
            return HandbookRenderer._renderMediaElement(element);
        }

        if(title === null) title = Localisation.translate('Go to {href}', {href});
        let rel = target === '_blank' ? 'rel="noreferrer noopener"':'';

        return `<a href="${href}" title="${decodeURI(title)}" target="${target}" style="color:${ThemeManager.getColor()}" ${rel}>${content}</a>`;
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
        let anchor = hash.trim().substr(1).toLowerCase().replace(/[^\w]+/g, '-').replace(/^-+|-+$/g, '');
        return `#help-${anchor}`;
    }

    /**
     *
     * @param label
     * @param level
     * @param headers
     * @returns {string}
     * @private
     */
    static _renderHeader(label, level, headers) {
        let id     = label.trim().toLowerCase().replace(/[^\w]+/g, '-').replace(/^-+|-+$/g, ''),
            [href] = HandbookRenderer._processAnchorLink(`#${id}`, '');

        this._addNavigationEntry(headers, {label, href, level, id: `help-${id}`, children: []});

        return `<h${level} id="help-${id}"><a href="${href}" class="fa fa-link help-anchor" aria-hidden="true"></a>${label}</h${level}>`;
    }

    /**
     *
     * @param headers
     * @param header
     * @private
     */
    static _addNavigationEntry(headers, header) {
        if(headers.length === 0) {
            headers.push(header);
            return;
        }

        let lastHeader = headers[headers.length - 1];
        if(lastHeader.level >= header.level) {
            headers.push(header);
        } else {
            let current = lastHeader,
                parent  = null;

            while(1) {
                if(current.level >= header.level) {
                    parent.children.push(header);
                    break;
                }

                if(current.children.length === 0) {
                    current.children.push(header);
                    break;
                }

                parent = current;
                current = current.children[current.children.length - 1];
            }
        }
    }

    /**
     *
     * @param href
     * @param title
     * @param description
     * @param nowrap
     * @param baseUrl
     * @param media
     * @returns {string}
     * @private
     */
    static _renderImage(href, title, description, nowrap, baseUrl, media) {
        let url = new URL(href, baseUrl);

        if(description === null) description = href;
        if(title === null) title = description;

        let index = media.length + 1,
            id    = `help-media-${index}`,
            image = {
                url      : url.href,
                thumbnail: url.href,
                id,
                description,
                title,
                index
            };

        media.push(image);

        return nowrap ? index - 1:HandbookRenderer._renderMediaElement(image);
    }

    /**
     *
     * @param element
     * @returns {string}
     * @private
     */
    static _renderMediaElement(element) {
        let caption = Localisation.translate('Figure {count}: {title}', {count: element.index, title: element.title}),
            mime    = element.url.substr(element.url.lastIndexOf('.') + 1);

        if(['mp4', 'm4v', 'ogg', 'webm'].indexOf(mime) !== -1) {
            element.mime = `video/${mime}`;
        } else {
            element.mime = `ìmage/${mime}`;
        }

        return `<span class="md-image-container" id="${element.id}" data-image-id="${element.index}">
                <a class="md-image-link" title="${element.title}" href="${element.url}" target="_blank" rel="noreferrer noopener">
                <img src="${element.thumbnail}" alt="${element.description.replace(/"/g, '&quot;')}" class="md-image" loading="lazy">
                <span class="md-image-caption">${caption}</span>
                </a></span>`;
    }
}

let
    HR = new HandbookRenderer();

export default HR;