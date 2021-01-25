import API from '@js/Helper/api';
import SettingsService from "@js/Services/SettingsService";

export default new class FaviconService {

    /**
     *
     */
    constructor() {
        this._favicons = {};
        this._requests = {};
        this._queue = [];
        this._workers = [];
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<String>}
     */
    async fetch(domain, size = 32) {
        if(this._favicons.hasOwnProperty(`${domain}_${size}`)) {
            return this._favicons[`${domain}_${size}`];
        }

        if(this._requests.hasOwnProperty(`${domain}_${size}`)) {
            await this._requests[`${domain}_${size}`];
            return this._favicons[`${domain}_${size}`];
        }

        let request = this._queueApiRequest(domain, size);

        return await request;
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<*>}
     * @private
     */
    async _queueApiRequest(domain, size) {
        let promise = new Promise((resolve) => {
            this._queue.push({domain, size, resolve});
            this._triggerWorkers();
        });

        this._requests[`${domain}_${size}`] = promise;

        return promise;
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<*>}
     * @private
     */
    async _fetchFromApi(domain, size) {
        try {
            /** @type {Blob} favicon **/
            let favicon = await API.getFavicon(domain, size);
            delete this._requests[`${domain}_${size}`];

            if(favicon.type.substr(0,6) !== 'image/' || favicon.size < 1) {
                return SettingsService.get('server.theme.app.icon');
            }

            this._favicons[`${domain}_${size}`] = window.URL.createObjectURL(favicon);

            return this._favicons[`${domain}_${size}`];
        } catch(e) {
            if(this._requests.hasOwnProperty(`${domain}_${size}`)) {
                delete this._requests[`${domain}_${size}`];
            }
            return SettingsService.get('server.theme.app.icon');
        }
    }

    /**
     *
     * @private
     */
    _triggerWorkers() {
        for(let worker of this._workers) {
            if(!worker.active) {
                worker.worker(worker);
                return;
            }
        }

        let maxWorkers = this._getMaxWorkers();
        if(maxWorkers === 0 || this._workers.length < maxWorkers) {
            let worker = {
                active: true,
                worker: async (self) => {
                    while(this._queue.length > 0) {
                        let job    = this._queue.shift(),
                            result = await this._fetchFromApi(job.domain, job.size);

                        job.resolve(result);
                    }

                    self.active = false;
                }
            };

            this._workers.push(worker);
            worker.worker(worker);
        }
    }

    /**
     *
     * @return {Number}
     * @private
     */
    _getMaxWorkers() {
        let performance = SettingsService.get('server.performance');
        if(performance !== 0 && performance < 6) return performance * 3;
        if(performance === 6) return 0;
        return 1;
    }
};