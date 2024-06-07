import ClientService from "@js/Services/ClientService";
import LoggingService from "@js/Services/LoggingService";

export default class ImportJsonConversionHelper {

    /**
     *
     * @param data
     * @param options
     * @returns {Promise<{}>}
     */
    static async processBackupJson(data, options) {
        let json = JSON.parse(data);

        if(json.encrypted) await this._decryptJsonBackup(options, json);

        if((!json.passwords && !json.tags && !json.folders) || json.items) throw new Error('Invalid backup file.');
        if(json.version < 2) this._convertCustomFields(json);
        if(json.version < 4) this._cleanCustomFields(json);
        if(json.version > 3) {
            if(json.version > 99) throw new Error('This seems to be a server backup. It can only be restored using the command line.');
            throw new Error('Unsupported database version');
        }

        return {data: json, errors: []};
    }

    /**
     * Migrate old custom fields data schema
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
     * Remove messy data from custom fields
     *
     * @param json
     * @private
     */
    static _cleanCustomFields(json) {
        if(json.hasOwnProperty('passwords')) {
            for(let i = 0; i < json.passwords.length; i++) {
                let oldFields = json.passwords[i].customFields,
                    newFields = [];

                for(let j = 0; j < oldFields.length; j++) {
                    if(oldFields[j] === null) {
                        continue;
                    }

                    if(!oldFields[j].hasOwnProperty('type') || oldFields[j].type === null) {
                        if(oldFields[j].value === null) continue;
                        oldFields[j].type = 'text';
                    }

                    newFields.push(
                        {
                            label: oldFields[j].label,
                            type : oldFields[j].type,
                            value: oldFields[j].value
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

        try {
            await ClientService
                .getClient()
                .getInstance('encryption.expv1')
                .decrypt(json.challenge, `${options.password}challenge`);
        } catch(e) {
            LoggingService.error(e);
            throw new Error('Password invalid');
        }

        for(let i in json) {
            if(!json.hasOwnProperty(i) || ['version', 'encrypted', 'challenge'].indexOf(i) !== -1) continue;

            try {
                let decryptedData = await ClientService
                    .getClient()
                    .getInstance('encryption.expv1')
                    .decrypt(json[i], options.password + i);

                json[i] = JSON.parse(decryptedData);
            } catch(e) {
                LoggingService.error(e);
                throw new Error(`Failed to decrypt ${i}`);
            }
        }
    }
}