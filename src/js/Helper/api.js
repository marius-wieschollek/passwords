import EnhancedApi from 'passwords-client';
import SettingsService from "@js/Services/SettingsService";
import Logger from "@js/Classes/Logger";

class PasswordsApi extends EnhancedApi {

    constructor(props) {
        super(props);

        this._requests = [];
        this._activeRequests = 0;
        this._queueCheckActive = false;
        this._maxRequests = 1;
    }

    initialize(config = {}) {
        super.initialize(config);

        let performance = SettingsService.get('server.performance');
        if(performance !== 0 && performance < 6) this._maxRequests = performance * 3;
        if(performance === 6) this._maxRequests = 32;
    }

    _sendRequest(path, data = null, method = null, dataType = 'application/json', requestOptions = {}) {
        return new Promise((resolve, reject) => {
            let priority = 2,
                name     = (Array.isArray(path) ? path[0]:path).split('.');
            if(name[0] === 'service') {
                priority = 3;
            } else if(['create', 'update', 'delete', 'set'].indexOf(name[name.length - 1]) !== -1) {
                priority = 1;
            } else if(name[0] === 'session') {
                priority = 0;
            }

            this._requests.push(
                {
                    resolve,
                    reject,
                    data: {path, data, method, dataType, requestOptions},
                    priority
                }
            );

            this._checkRequestQueue();
        });
    }

    /**
     *
     * @private
     */
    _checkRequestQueue() {
        if(this._queueCheckActive || this._requests.length === 0) {
            return;
        }
        this._queueCheckActive = true;
        this._requests.sort(function(a, b) {
            if(a.priority === b.priority) return 0;
            return a.priority < b.priority ? -1:1;
        });

        while((this._activeRequests < this._maxRequests || this._maxRequests === 0) && this._requests.length > 0) {
            let request = this._requests.shift();

            this._activeRequests++;
            super._sendRequest(request.data.path, request.data.data, request.data.method, request.data.dataType, request.data.requestOptions)
                 .then(request.resolve)
                 .catch(request.reject)
                 .finally(() => {
                     if(this._activeRequests > 0) {
                         this._activeRequests--;
                     }
                     this._checkRequestQueue();
                 });
        }

        this._queueCheckActive = false;
    }
}

export default new PasswordsApi();