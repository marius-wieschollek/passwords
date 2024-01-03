/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import DOMPurify from "dompurify";
import SettingsService from "@js/Services/SettingsService";
import FaviconService from "@js/Services/FaviconService";
import Logger from "@js/Classes/Logger";
import LocalisationService from "@js/Services/LocalisationService";

export default class PrintPasswordAction {

    /**
     *
     * @param {Password} password
     */
    constructor(password) {
        this._password = password;
    }

    async print() {
        this.printHtml(await this._getPasswordHtml());
    }

    printHtml(html) {
        let printWindow = window.open('', '', 'left=0,top=0,width=800,height=900,toolbar=0,scrollbars=0,status=0');
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }

    async _getPasswordHtml() {
        let html = `<html><head><title>${this._password.label}</title>${this._getStyle()}</head><body><h1>${await this._getIcon()}${this._password.label}</h1>`;

        if(this._password.host) {
            html += `<h2>${this._password.host}</h2>`;
        }

        html += `<br><br><table><tbody>`;
        let fields = this._getPasswordFields();
        for(let field of fields) {
            html += `<tr class="column-${field.type}"><th>${field.label}</th><td class="value">${field.value}</td></tr>`;
        }
        html += '</tbody></table>';

        html += await this._getNotesHtml();

        return html + '</body></html>';
    }

    async _getNotesHtml() {
        if(this._password.notes) {
            let marked = await import(/* webpackChunkName: "marked" */ 'marked');
            marked.setOptions({breaks: true});
            let notes = DOMPurify.sanitize(marked.marked.parse(this._password.notes));

            return `<div class="notes-label">${LocalisationService.translate('Notes')}</div><div class="notes-container">${notes}</div>`;
        }
        return '';
    }

    _getPasswordFields() {
        let fields = [];
        if(this._password.username) {
            fields.push({label: LocalisationService.translate('Username'), type: 'text', value: this._password.username});
        }
        fields.push({label: LocalisationService.translate('Password'), type: 'secret', value: this._password.password});
        if(this._password.url) {
            fields.push({label: LocalisationService.translate('Website'), type: 'url', value: this._password.url});
        }

        let showHiddenFields = SettingsService.get('client.ui.custom.fields.show.hidden');
        for(let i = 0; i < this._password.customFields.length; i++) {
            if(showHiddenFields || this._password.customFields[i].type !== 'data') fields.push(this._password.customFields[i]);
        }
        return fields;
    }

    _getIcon() {
        return new Promise(async (resolve) => {
            if(!this._password.website) {
                resolve('');
                return;
            }

            let cancelled = false;
            setTimeout(() => {
                let icon = FaviconService.get(this._password.website, 64);
                if(icon !== SettingsService.get('server.theme.app.icon')) {
                    resolve(`<img src="${icon}" alt="" />`);
                    cancelled = true;
                    return;
                }
                icon = FaviconService.get(this._password.website);
                if(icon !== SettingsService.get('server.theme.app.icon')) {
                    resolve(`<img src="${icon}" alt="" />`);
                    cancelled = true;
                    return;
                }

                resolve('');
                cancelled = true;
            }, 1000);

            try {
                let icon = await FaviconService.fetch(this._password.website, 64);
                if(!cancelled) {
                    resolve(`<img src="${icon}" alt="" />`);
                }
            } catch(e) {
                Logger.debug(e);
                resolve('');
            }
        });
    }

    _getStyle() {
        return `<style>
body, table, td, th, div {
    font-family: Lato, Ubuntu, Calibri, Verdana, Arial, sans-serif;
    font-size: 16pt;
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-all;
    word-break: break-word;
}
body {
    width: 600px;
    max-width: 600px;
    overflow: hidden;
}
h1 {
    margin: 0;
    font-size: 4rem;
}
h1 img {
    width: 3rem;
    height: 3rem;
    margin-right: 1rem;
}
h2 {
    font-style: italic;
    font-size: 1.5rem;
    margin: 0;
}
table {
    width: 600px;
}
th {
    font-weight: bold;
    text-align: left;
    vertical-align: top;
    max-width: 25%;
    min-width: 16%;
    white-space: nowrap;
}
td.value {
    font-family: 'Ubuntu Mono', Consolas, "Liberation Mono", Menlo, Courier, monospace;
    text-align: left;
    vertical-align: top;
    padding-left: 1rem;
}
div.notes-label {
    margin-top: 2rem;
    font-weight: bold;
}
div.notes-container {
    font-size: 0.85rem;
}
div.notes-container h1 {
    font-size: 2rem;
}
div.notes-container h2 {
    font-size: 1.5rem;
}
div.notes-container h3 {
    font-size: 1.4rem;
}
div.notes-container h4 {
    font-size: 1.3rem;
}
div.notes-container h5 {
    font-size: 1.2rem;
}
div.notes-container h6 {
    font-size: 1.1rem;
}
div.notes-container pre {
    white-space: pre-wrap;
}
</style>`;
    }
}