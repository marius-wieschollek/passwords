import App from '@js/Init/Application';
import API from '@js/Helper/api';
import router from '@js/Helper/router';
import Messages from '@js/Classes/Messages';
import SettingsService from '@js/Services/SettingsService';
import Logger from '@js/Classes/Logger';

class EventManager {

    constructor() {
        this._pointer = 0;
        this.ignoreApiErrors = false;
    }

    /**
     *
     */
    init() {
        this._registerStarEvent();
        this._registerApiErrorEvent();
        this._registerSettingsObserver();
    }

    /**
     *
     * @private
     */
    _registerStarEvent() {
        document.addEventListener('keyup', (e) => {this._starEvent(e); }, {passive: true});
    }

    /**
     *
     * @private
     */
    _registerApiErrorEvent() {
        App.events.on('api.request.error', (e) => { this._apiErrorEvent(e); });
    }

    /**
     *
     * @private
     */
    _registerSettingsObserver() {
        SettingsService.observe('user.encryption.cse', (setting) => {
            API.config.cseMode = setting.value === 1 ? 'CSEv1r1':'none';
        });
    }

    /**
     *
     * @param e
     * @private
     */
    _starEvent(e) {
        let code    = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65],
            current = code[this._pointer];

        if(current !== e.keyCode || e.ctrlKey || e.shiftKey || e.metaKey) {
            this._pointer = 0;
            return;
        }

        this._pointer++;
        if(this._pointer === code.length) {
            App.app.starChaser = true;
            this._pointer = 0;
        }
    }

    /**
     *
     * @param e
     * @returns {Promise<void>}
     * @private
     */
    async _apiErrorEvent(e) {
        if(this.ignoreApiErrors) return;

        if(e.id === '4ad27488') {
            let current = router.currentRoute,
                target  = {name: current.name, path: current.path, hash: current.hash, params: current.params};

            if(current.name === 'Authorize') return;

            target = btoa(JSON.stringify(target));
            router.push({name: 'Authorize', params: {target}});

            Messages.notification('The session has expired');
        } else if(e.response && e.response.status === 401 && e.message === "CORS requires basic auth") {
            await Messages.alert('The session token is no longer valid. The app will now reload.', 'API Session Token expired');
            location.reload();
        } else if(e.message) {
            Messages.notification(e.message);
            Logger.error(e);
        } else if(e.response && (!e.response.url || e.response.url.indexOf('service/favicon') === -1)) {
            Messages.notification(`${e.response.status} - ${e.response.statusText}`);
            Logger.error(e);
        } else {
            Logger.error(e);
        }
    }
}

export default new EventManager();