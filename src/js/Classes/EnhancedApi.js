class EnhancedApi extends SimpleApi {

    /**
     * EnhancedApi Constructor
     *
     * @param endpoint
     * @param user
     * @param password
     * @param token
     * @param debug
     */
    constructor(endpoint, user = null, password = null, token = null, debug = false) {
        super(endpoint + '/index.php/apps/passwords/', user, password, token, debug);
    }

    /**
     *
     * @param numeric
     * @returns {*}
     */
    static getClientVersion(numeric = false) {
        return numeric ? 100:'0.1.0';
    }

    /**
     *
     * @param attributes
     * @param strict
     * @returns object
     */
    static validatePassword(attributes, strict = false) {
        let password    = {},
            definitions = EnhancedApi.getPasswordDefinition();

        for (let property in definitions) {
            if (!definitions.hasOwnProperty(property)) continue;
            let definition = definitions[property];

            if (!attributes.hasOwnProperty(property)) {
                if (definition.required) throw "Property " + property + " is required but missing";
                password[property] = definition.hasOwnProperty('default') ? definition.default:null;
                continue;
            }

            let attribute = attributes[property],
                type      = typeof attribute;

            if (definition.required && (!attribute || 0 === attribute.length)) {
                throw "Property " + property + " is required but missing";
            }

            if (definition.type && definition.type !== type && (definition.type !== 'array' || !Array.isArray(attribute))) {
                if (!strict && definition.hasOwnProperty('default')) {
                    attribute = definition.default;
                } else if (strict || definition.required) {
                    throw "Property " + property + " has invalid type " + type;
                } else {
                    attribute = null;
                }
            }

            if (definition.length) {
                if (Array.isArray(attribute) && attribute.length > definition.length) {
                    if (strict) throw "Property " + property + " exceeds the maximum length of " + definition.length;
                    attribute = attribute.slice(0, definition.length)
                } else if (type === 'string' && attribute.length > definition.length) {
                    if (strict) throw "Property " + property + " exceeds the maximum length of " + definition.length;
                    attribute = attribute.substr(0, definition.length)
                }
            }

            password[property] = attribute;
        }


        return password;
    }

    /**
     * Creates a new password with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    async createPassword(data = {}) {
        try {
            data = EnhancedApi.validatePassword(data);
        } catch (e) {
            return this.createRejectedPromise(e);
        }

        if (!data.title) EnhancedApi._generatePasswordTitle(data);

        return super.createPassword(data);
    }

    /**
     * Update an existing password with the given attributes.
     * If data does not contain an id, a new password will be created.
     *
     * @param data
     * @returns {Promise}
     */
    async updatePassword(data = {}) {
        if(!data.id) return this.createPassword(data);

        try {
            data = EnhancedApi.validatePassword(data);
        } catch (e) {
            return this.createRejectedPromise(e);
        }

        if (!data.title) EnhancedApi._generatePasswordTitle(data);

        return super.updatePassword(data);
    }

    /**
     * Generates an automatic title from the given data
     *
     * @param data
     * @returns string
     * @private
     */
    static _generatePasswordTitle(data) {
        data.title = String(data.login);
        if (data.url !== null) {
            data.title += '@' + SimpleApi.parseUrl(data.url, 'host').replace('www.', '');
        }
    }

    /**
     * Gets all the passwords, including those in the trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listPasswords(detailLevel = 'default') {
        return new Promise((resolve, reject) => {
            super.listPasswords(detailLevel)
                .then((data) => {
                    data = this._processPasswordList(data);
                    resolve(data);
                })
                .catch(reject);
        });
    }

    /**
     * Generates a password with the given strength and the given options
     *
     * @param strength
     * @param useNumbers
     * @param useSpecialCharacters
     * @param useSmileys
     * @returns {Promise}
     */
    generatePassword(strength = 1, useNumbers = false, useSpecialCharacters = false, useSmileys = false) {
        return super.generatePassword(strength, useNumbers, useSpecialCharacters, useSmileys);
    }

    /**
     *
     * @param message
     * @returns {Promise}
     */
    createRejectedPromise(message) {
        return new Promise((resolve, reject) => {
            let error = {status: 'error', message: message};
            if (this._debug) console.error(error);
            reject(error);
        });
    }

    /**
     *
     * @param data
     * @returns {{}}
     * @private
     */
    _processPasswordList(data) {
        let passwords = {};

        for(let i=0; i<data.length; i++) {
            let password = data[i];

            if(password.url) {
                let host = SimpleApi.parseUrl(password.url, 'host');
                password.icon = this.getFaviconUrl(host);
                password.image = this.getPreviewUrl(host);
            } else {
                password.icon = this.getFaviconUrl(null);
                password.image = this.getPreviewUrl(null);
            }

            switch(password.secure) {
                // @TODO add warning if custom requirements fail
                case true: password.secure = 0; break;
                case false: password.secure = 2; break;
            }

            passwords[password.id] = password;
        }

        return passwords;
    }

    /**
     *
     * @returns object
     */
    static getPasswordDefinition() {
        return {
            id    : {
                type    : 'string',
                length  : 36
            },
            login    : {
                type    : 'string',
                length  : 48,
                required: true
            },
            password : {
                type    : 'string',
                length  : 48,
                required: true
            },
            title    : {
                type   : 'string',
                length : 48,
                default: null
            },
            url      : {
                type   : 'string',
                length : 2048,
                default: null
            },
            notes    : {
                type   : 'string',
                length : 4096,
                default: null
            },
            cseType  : {
                type   : 'string',
                length : 10,
                default: null
            },
            sseType  : {
                type   : 'string',
                length : 10,
                default: null
            },
            hidden   : {
                type   : 'boolean',
                default: false
            },
            favourite: {
                type   : 'boolean',
                default: false
            },
            tags     : {
                type   : 'array',
                default: []
            },
            folders  : {
                type   : 'array',
                default: []
            }
        }
    }
}