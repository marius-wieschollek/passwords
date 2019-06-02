import API from '@js/Helper/api';
import SettingsService from '@js/Service/SettingsService';


class KeepAliveManager {

    get hasTimeout() {
        return this._hasTimeout;
    }

    constructor() {
        this._mode = 0;
        this._timer = null;
        this._hasTimeout = false;
        this._event = null;
        this._lifeTime = 0;
        this._lastRequest = 0;
    }

    init() {
        SettingsService.observe('client.session.keepalive', (s) => { this._updateKeepAlive(s.value); });
        let type = SettingsService.get('client.session.keepalive');

        this._updateKeepAlive(type);
    }

    _updateKeepAlive(type) {

        if(this._mode === 1) {
            clearInterval(this._timer);
            this._timer = null;
        } else if(this._mode === 2) {
            document.body.removeEventListener('mouseover', this._event, {passive: true});
            document.body.removeEventListener('keypress', this._event, {passive: true});
            document.body.removeEventListener('click', this._event, {passive: true});
        }


        if(type === 0) {
            this._hasTimeout = true;
        } else if(type === 1) {
            this._initPermanentKeepAlive();
        } else if(type === 2) {
            this._initActionKeepAlive();
        }
        this._mode = type;
    }

    _initPermanentKeepAlive() {
        this._hasTimeout = false;
        let timeout = SettingsService.get('user.session.lifetime') - 10;

        this._timer = setInterval(() => { API.keepaliveSession(); }, timeout * 1000);
    }

    _initActionKeepAlive() {
        this._hasTimeout = true;
        this._lifeTime = SettingsService.get('user.session.lifetime') * 1000;
        this._event = () => {this._keepAliveEvent();};

        document.body.addEventListener('click', this._event, {passive: true});
        document.body.addEventListener('keypress', this._event, {passive: true});
        document.body.addEventListener('mouseover', this._event, {passive: true});
        API.keepaliveSession();
        this._lastRequest = Date.now();
    }

    _keepAliveEvent(e) {
        let minTime = this._lastRequest + 10,
            maxTime = this._lastRequest + this._lifeTime;

        if(Date.now() > minTime && Date.now() < maxTime) {
            API.keepaliveSession();
            this._lastRequest = Date.now();
        }
    }
}

export default new KeepAliveManager();