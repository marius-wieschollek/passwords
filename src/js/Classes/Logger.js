/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {translate} from '@nextcloud/l10n';
import Utility from "@js/Classes/Utility";

export default new class Logger {

    get exception() {
        return (...args) => {
            this.error(...args);
        };
    }

    constructor() {
        this._colors = {
            error  : '#b33939',
            warning: '#ffa502',
            success: '#20bf6b',
            info   : '#3867d6',
            log    : '#778ca3',
            debug  : '#a55eea'
        };
    }

    error(...args) {
        if(Array.isArray(args)) {
            for(let arg of args) {
                if(arg.hasOwnProperty('message')) {
                    args.unshift(arg.message);
                    this._write('error', args);
                    return;
                }
            }
        }

        this._write('error', args);

    }

    warn(...args) {
        this._write('warn', args);
    }

    log(...args) {
        this._write('log', args);
    }

    info(...args) {
        this._write('info', args);
    }

    debug(...args) {
        this._write('debug', args);
    }

    success(...args) {
        this._write('success', args);
    }

    _write(level, args) {
        let func  = level === 'success' ? 'info':level,
            label = level.charAt(0).toUpperCase() + level.slice(1);

        console[func](
            `%cPasswords ${label}`,
            `color:#fff;background-color:${this._colors[level]};padding:.5rem;border-radius:3px;display:inline-block;font-weight:bold;font-family:'Ubuntu Mono',monospace`,
            ...args
        );
    }

    printXssWarning() {
        let link = Utility.generateUrl('/apps/passwords/#/help/Browser-Console');

        console.warn(
            `%c${translate('passwords', 'BrowserConsoleWarningTitle')} %c${translate('passwords', 'BrowserConsoleWarningLine1')} %c${translate('passwords', 'BrowserConsoleWarningLine2')} %c${translate('passwords', 'BrowserConsoleWarningLine3', {link})}`,
            `color:#fff;background-color:#EA2027;padding:10rem 2rem 1rem;display:block;font-weight:bold;font-family:'Ubuntu Mono',monospace;font-size:12rem;`,
            `color:#fff;background-color:#EA2027;padding:1rem 2rem;display:block;font-weight:bold;font-family:'Ubuntu Mono',monospace;font-size:1.5rem;`,
            `color:#fff;background-color:#EA2027;padding:1rem 2rem;display:block;font-weight:bold;font-family:'Ubuntu Mono',monospace;font-size:1.5rem;`,
            `color:#fff;background-color:#EA2027;padding:1rem 2rem 2rem;display:block;font-weight:bold;font-family:'Ubuntu Mono',monospace;font-size:1.5rem;`
        );
    }
};