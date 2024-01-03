import API from '@js/Helper/api';
import router from '@js/Helper/router';
import {emit} from '@nextcloud/event-bus';
import LoggingService from "@js/Services/LoggingService";
import SettingsService from '@js/Services/SettingsService';


class KeepAliveManager {

    get hasTimeout() {
        return this._hasTimeout;
    }

    get lastRequest() {
        return this._lastRequest;
    }

    constructor() {
        this._mode = 0;
        this._timer = null;
        this._event = null;
        this._hasTimeout = false;
        this._lastRequest = 0;
        this._lockTimer = null;
    }

    /**
     * Initialize keep alive management
     */
    init() {
        SettingsService.observe('client.session.keepalive', (s) => { this._updateKeepAlive(s.value); });
        SettingsService.observe('user.session.lifetime', () => {
            let type = SettingsService.get('client.session.keepalive');
            this._updateKeepAlive(type);
        });

        let type = SettingsService.get('client.session.keepalive');
        this._updateKeepAlive(type);
    }

    /**
     * Initialize keep alive timers and events
     *
     * @param type
     * @private
     */
    _updateKeepAlive(type) {
        this._cleanUp();

        if(type === 0) {
            this._initPermanentKeepAlive();
        } else if(type === 1) {
            this._initActionKeepAlive();
        } else if(type === 2) {
            this._initPassiveKeepAlive();
        }

        this._mode = type;
        emit('passwords:keepalive:updated', {hasTimeout: this._hasTimeout});
    }

    /**
     * Clean up timers and events when mode changes
     *
     * @private
     */
    _cleanUp() {
        if(this._mode === 0) {
            clearInterval(this._timer);
        } else if(this._mode === 1) {
            clearInterval(this._timer);
            clearInterval(this._lockTimer);
            document.body.removeEventListener('mouseover', this._event, {passive: true});
            document.body.removeEventListener('keypress', this._event, {passive: true});
            document.body.removeEventListener('click', this._event, {passive: true});
            API.events.off('api.request.before', this._event);
        } else if(this._mode === 2) {
            API.events.off('api.request.before', this._event);
            clearInterval(this._lockTimer);
        }
    }

    /**
     * Always trigger keep alive requests before session runs out
     *
     * @private
     */
    _initPermanentKeepAlive() {
        this._hasTimeout = false;
        let timeout = SettingsService.get('user.session.lifetime') - 10;
        this._timer = setInterval(() => { API.keepaliveSession(); }, timeout * 1000);
    }

    /**
     * Do not trigger keep alive requests but keep track of timeouts
     *
     * @private
     */
    _initPassiveKeepAlive() {
        this._hasTimeout = true;

        this._event = () => {
            this._lastRequest = Date.now();
            emit('passwords:keepalive:activity', {time: this._lastRequest});
        };
        API.events.on('api.request.before', this._event);
        this._event();

        this._lifeTime = SettingsService.get('user.session.lifetime') * 1000;
        this._lockTimer = setInterval(() => { this._checkLockdown(); }, 1000);
    }

    /**
     * Trigger keep alive requests when user activity detected
     *
     * @private
     */
    _initActionKeepAlive() {
        this._hasTimeout = true;

        this._event = (e) => {
            if(e && e.url && e.url.indexOf('session/keepalive') !== -1) return;

            if((!e || !e.url) && Date.now() > this._lastRequest + 10000) API.keepaliveSession();

            this._lastRequest = Date.now();
            emit('passwords:keepalive:activity', {time: this._lastRequest});
        };
        document.body.addEventListener('click', this._event, {passive: true});
        document.body.addEventListener('keypress', this._event, {passive: true});
        document.body.addEventListener('mouseover', this._event, {passive: true});
        API.events.on('api.request.before', this._event);
        this._event();

        this._timer = setInterval(() => { this._sendKeepAlive(); }, 10000);
        this._lockTimer = setInterval(() => { this._checkLockdown(); }, 1000);
        this._lifeTime = SettingsService.get('user.session.lifetime') * 1000;
    }

    /**
     * Send keep alive request
     *
     * @private
     */
    _sendKeepAlive() {
        let maxTime = this._lastRequest + 10000;

        if(Date.now() <= maxTime) API.keepaliveSession();
    }

    /**
     * Check if session expired and lock app
     *
     * @private
     */
    async _checkLockdown() {
        if(this._lastRequest + this._lifeTime < Date.now()) {
            let current = router.currentRoute,
                target  = {name: current.name, path: current.path, hash: current.hash, params: current.params};
            this._removePopupWindows();

            if(current.name === 'Authorize') return;

            try {
                await API.closeSession();
            } catch(e) {
                LoggingService.error(e);
            }

            target = btoa(JSON.stringify(target));
            if(router.currentRoute.name === 'Authorize') return;
            router.push({name: 'Authorize', params: {target}});
        }
    }

    /**
     *
     * @private
     */
    _removePopupWindows() {
        let popup = document.getElementById('app-popup');
        if(popup !== null && popup.hasChildNodes()) {
            for(let child of popup.childNodes) {
                child.remove()
            }
        }
        popup.append(document.createElement('div'));

        let dialogs = document.querySelectorAll('.oc-dialog-dim, .oc-dialog');
        for(let dialog of dialogs) {
            dialog.remove()
        }
    }
}

export default new KeepAliveManager();