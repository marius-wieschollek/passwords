import API from '@js/Helper/api';
import Encryption from '@js/ApiClient/Encryption';

export default class ImportJsonConversionHelper {

    /**
     *
     * @param data
     * @param options
     * @returns {Promise<void>}
     */
    static async processBackupJson(data, options) {
        let json = JSON.parse(data);

        if(json.encrypted) {
            await this._decryptJsonBackup(options, json);
        }

        return json;
    }

    /**
     *
     * @param options
     * @param json
     * @returns {Promise<void>}
     * @private
     */
    static async _decryptJsonBackup(options, json) {
        if(!options.password) throw new Error('Password required');
        let encryption = new Encryption();

        try {
            await encryption.decrypt(json.challenge, `${options.password}challenge`);
        } catch(e) {
            console.error(e);
            throw new Error('Password invalid');
        }

        for(let i in json) {
            if(!json.hasOwnProperty(i) || ['version', 'encrypted', 'challenge'].indexOf(i) !== -1) continue;

            try {
                json[i] = JSON.parse(await encryption.decrypt(json[i], options.password + i));
            } catch(e) {
                console.error(e);
                throw new Error(`Failed to decrypt ${i}`);
            }
        }
    }

    /**
     *
     * @returns {Promise<{}>}
     * @private
     */
    static async _getTagLabelMapping() {
        let tags    = await API.listTags(),
            mapping = {};

        for(let i in tags) {
            if(!tags.hasOwnProperty(i)) continue;
            mapping[tags[i].label] = tags[i].id;
        }

        return mapping;
    }
}