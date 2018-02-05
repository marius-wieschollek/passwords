import API from "@/js/Helper/api";
import * as randomMC from "random-material-color";
import Utility from "@/js/Classes/Utility";

/**
 *
 */
class ImportJsonConversionHelper {

    /**
     *
     * @param json
     * @returns {Promise<void>}
     */
    async processBackupJson(json) {
        return JSON.parse(json);
    }

    /**
     *
     * @param json
     * @returns {Promise<*>}
     */
    async processPassmanJson(json) {
        let data = JSON.parse(json);

        return {
            tags     : await ImportJsonConversionHelper._processPassmanTags(data),
            passwords: ImportJsonConversionHelper._processPassmanPasswords(data)
        };
    }

    /**
     *
     * @param db
     * @returns {Promise<Array>}
     * @private
     */
    static async _processPassmanTags(db) {
        let tags    = [],
            mapping = await ImportJsonConversionHelper._getTagLabelMapping();

        for(let i = 0; i < db.length; i++) {
            let element = db[i];

            for(let j = 0; j < element.tags.length; j++) {
                let label = element.tags[j].text,
                    id    = label;

                if(mapping.hasOwnProperty(label)) {
                    id = mapping[label];
                } else {
                    mapping[label] = label;
                    tags.push({
                                  id   : label,
                                  label: label,
                                  color: randomMC.getColor()
                              });
                }
                element.tags[j] = id;
            }
        }

        return tags;
    }

    /**
     *
     * @param db
     * @returns {Array}
     * @private
     */
    static _processPassmanPasswords(db) {
        let passwords = [];

        for(let i = 0; i < db.length; i++) {
            let element = db[i], object = {
                id      : element.guid,
                label   : element.label,
                username: element.username,
                password: element.password,
                url     : element.url,
                notes   : element.description,
                edited  : element.changed,
                tags    : element.tags
            };

            if(element.hidden) continue;
            if(element.email) {
                if(object.username.length === 0) {
                    object.username = element.email;
                } else {
                    if(object.notes.length !== 0) object.notes += "\n\n";
                    object.notes += Utility.translate('Email') + ': ' + element.email;
                }
            }

            passwords.push(object);
        }

        return passwords;
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

let IJCH = new ImportJsonConversionHelper();

export default IJCH;