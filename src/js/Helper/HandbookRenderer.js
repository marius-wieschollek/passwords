import { marked, Renderer as MarkedRenderer} from 'marked';
import VueRouter from '@js/Helper/router';
import SettingsService from '@js/Services/SettingsService';
import mermaid from "mermaid";
import DOMPurify from 'dompurify';
import LocalisationService from "@js/Services/LocalisationService";
import LoggingService from "@js/Services/LoggingService";

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
                if(mime.substr(0, 10) !== 'text/plain' && mime.substr(0, 13) !== 'text/markdown') {
                    return HandbookRenderer._generateErrorPage(
                        LocalisationService.translate('Invalid content type {mime}', {mime})
                    );
                }

                let data = await response.text();
                if(!data) return HandbookRenderer._generateErrorPage(LocalisationService.translate('No content available'));

                let content = this.render(data, baseUrl, url);
                this.pages[page] = content;
                return content;
            } else {
                return HandbookRenderer._generateErrorPage(response.status + ' – ' + response.statusText);
            }
        } catch(e) {
            if(APP_ENVIRONMENT === 'development') LoggingService.error('Request failed', e);

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
            blankRenderer = new MarkedRenderer(),
            renderer      = new MarkedRenderer();

        renderer.link = (href, title, text, wrap) => { return this._renderLink(href, title, text, wrap, baseUrl, documentUrl, media);};
        renderer.image = (href, title, text, nowrap) => { return HandbookRenderer._renderImage(href, title, text, nowrap, baseUrl, media);};
        renderer.heading = (text, level) => { return HandbookRenderer._renderHeader(text, level, navigation);};
        renderer.code = (code, infostring, escaped) => { return HandbookRenderer._renderCode(code, infostring, escaped, blankRenderer); };
        renderer.blockquote = (quote) => { return HandbookRenderer._renderBlockquote(quote, blankRenderer); };

        marked.use({renderer});
        let source = DOMPurify.sanitize(marked.parse(markdown));

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
            source    : '\u{1F480} ' + LocalisationService.translate('Unable to fetch page: {message}.', {message}),
            media     : [],
            navigation: []
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
        let matches = content.match(/data-image-id="(\d+)"/)
        if(matches && matches.length > 1) {
            let element = media[matches[1] * 1 - 1];
            element.url = href;
            return HandbookRenderer._renderMediaElement(element);
        }

        if(title === null) title = LocalisationService.translate('Go to {href}', {href});
        let rel = target === '_blank' ? 'rel="noreferrer noopener"':'';

        return `<a href="${href}" title="${decodeURI(title)}" target="${target}" ${rel}>${content}</a>`;
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
        if(title === null) title = LocalisationService.translate('Go to {href}', {href: href.substr(1).replace(/-{1}/g, ' ')});

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
        if(title === null) title = LocalisationService.translate('Go to {href}', {href: href.replace(/-{1}/g, ' ')});

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
        let id     = label.trim().toLowerCase()
                          .replace(/&#{0,1}[a-z0-9]+;/g, '')
                          .replace(/[^\w]+/g, '-')
                          .replace(/^-+|-+$/g, ''),
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
        let caption = LocalisationService.translate('Figure {count}: {title}', {count: element.index, title: element.title}),
            mime    = element.url.substr(element.url.lastIndexOf('.') + 1);

        if(['mp4', 'm4v', 'ogg', 'webm'].indexOf(mime) !== -1) {
            element.mime = `video/${mime}`;
        } else {
            element.mime = `ìmage/${mime}`;
        }

        return `<span class="md-image-container" id="${element.id}" data-image-id="${element.index}">
                <a class="md-image-link" title="${element.title}" href="${element.url}" target="_blank" rel="noreferrer noopener">
                <img src="${element.thumbnail}" alt="${element.description.replace(/"/g, '&quot;')}" class="md-image">
                <span class="md-image-caption">${caption}</span>
                </a></span>`;
    }

    /**
     *
     * @param {String} quote
     * @param {marked.Renderer} blankRenderer
     * @return {String}
     * @private
     */
    static _renderBlockquote(quote, blankRenderer) {
        let css   = null,
            types = [':exclamation:', ':warning:', ':thumbsup:', ':star:'],
            map   = {':exclamation:': 'important', ':warning:': 'warning', ':thumbsup:': 'recommended', ':star:': 'info'};

        for(let type of types) {
            if(quote.indexOf(type) !== -1) {
                quote = quote.replace(type, '');
                css = map[type];
            }
        }

        let content = blankRenderer.blockquote(quote);
        if(css !== null) content = content.replace('<blockquote>', `<blockquote class="${css}">`);
        return content;
    }

    /**
     *
     * @param code
     * @param infostring
     * @param escaped
     * @param blankRenderer
     * @return {*}
     * @private
     */
    static _renderCode(code, infostring, escaped, blankRenderer) {
        if(infostring === 'mermaid') {
            let id             = 'help-graph-' + Math.round(Math.random() * 100000),
                themeVariables = {
                    primaryColor    : SettingsService.get('server.theme.color.primary'),
                    background      : SettingsService.get('server.theme.color.background')
                };

            mermaid.mermaidAPI.initialize({startOnLoad: false, theme: 'base', themeVariables});
            let graph = mermaid.mermaidAPI.render(id, code);

            return `<div class="handbook-graph">${graph}</div>`;
        }

        let content = blankRenderer.code(code, infostring, escaped);
        return content.replace(/(\r\n|\n|\r)/gm, '<br>');
    }
}

let HR = new HandbookRenderer();

export default HR;