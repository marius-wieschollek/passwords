/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {mdiAccountPlus, mdiArchive, mdiClipboardOutline, mdiContentSave, mdiDelete, mdiPuzzle, mdiSync} from "@mdi/js";

export default class HandbookIcons {

    get ICONS() {
        return {
            "🗃": mdiArchive,
            "🔃": mdiSync,
            "🧩": mdiPuzzle,
            "📋": mdiClipboardOutline,
            "💾": mdiContentSave,
            "🗑": mdiDelete,
            "👤": mdiAccountPlus
        };
    }

    /**
     *
     * @param {String} html
     *
     * @return {String}
     */
    replace(html) {
        const icons = this.ICONS;
        for(let icon in icons) {
            const regexp = new RegExp(icon, 'g'),
                  svg    = `<svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="${icons[icon]}" /></svg>`;
            html = html.replace(regexp, svg);
        }

        return html;
    }
}