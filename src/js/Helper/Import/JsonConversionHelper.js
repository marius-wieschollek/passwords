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

        if(json.encrypted) await this._decryptJsonBackup(options, json);

        if(json.version < 2) this._convertCustomFields(json);
        if(json.version > 2) throw new Error('Unsupported database version');

        return json;
    }

    /**
     *
     * @param json
     * @private
     */
    static _convertCustomFields(json) {
        if(json.hasOwnProperty('passwords')) {
            for(let i = 0; i < json.passwords.length; i++) {
                let oldFields = json.passwords[i].customFields,
                    newFields = [];

                for(let label in oldFields) {
                    if(!oldFields.hasOwnProperty(label)) continue;
                    let type = label.substr(0, 1) === '_' ? 'data':oldFields[label].type;

                    newFields.push(
                        {
                            label,
                            type,
                            value: oldFields[label].value
                        }
                    );
                }

                json.passwords[i].customFields = newFields;
            }
        }
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
}