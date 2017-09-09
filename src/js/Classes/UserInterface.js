class UserInterface {

    /**
     *
     */
    constructor() {
        this._api = new EnhancedApi(location.origin, null, null, null, true);
        this.sections = ['main'];
        this.components = [];
        this.events = [];
        this.routes = [];
        this.app = null;
        this.router = null;
        this.constants = {};
    }

    loadConstants() {
        let $elements = $('[data-constant]');

        for (let i = 0; i < $elements.length; i++) {
            let $data = $elements.eq(i).data();
            this.constants[$data.constant] = decodeURI($data.value);
        }
    }

    /**
     *
     * @param name
     * @param defaultValue
     */
    getConstant(name, defaultValue = null) {
        if (!this.constants.hasOwnProperty(name)) return defaultValue;

        return this.constants[name];
    }

    /**
     *
     * @returns {{}|*}
     */
    getConstants() {
        return this.constants;
    }

    /**
     *
     * @private
     */
    _initializeApp() {
        this.loadConstants();
        UserInterface._initializeRoutes();
        let router = new VueRouter({routes: this._processRoutes()});
        this.app = new Vue({router}).$mount('#app');
        this.router = router;
    }

    /**
     *
     * @private
     */
    static _initializeRoutes() {
        PasswordsUi.registerRoute({path: '*', components: ['section.all']});
        PasswordsUi.registerRoute({path: '/show/all', components: ['section.all']});
        PasswordsUi.registerRoute({path: '/show/folders', components: ['section.folders'], children: [{path: ':id'}]});
        PasswordsUi.registerRoute({path: '/show/tags', components: ['section.tags'], children: [{path: ':id'}]});
        PasswordsUi.registerRoute({path: '/show/recent', components: ['section.recent']});
        PasswordsUi.registerRoute({path: '/show/favourites', components: ['section.favourites']});
        PasswordsUi.registerRoute({path: '/show/shared', components: ['section.shared']});
        PasswordsUi.registerRoute({path: '/show/security', components: ['section.security']});
        PasswordsUi.registerRoute({path: '/show/trash', components: ['section.trash']});
    }

    /**
     *
     * @returns {Array}
     * @private
     */
    _processRoutes() {
        let routes = this.getRoutes();

        for (let i = 0; i < routes.length; i++) {
            let components = {};
            let sections = this.sections.clone();

            for (let j = 0; j < routes[i].components.length; j++) {
                let component = routes[i].components[j];
                let name;

                if (component.indexOf(' as ') !== -1) {
                    let cmp;
                    [cmp, name] = component.split(' as ');
                    let index = sections.indexOf(name);
                    if (index !== -1) {
                        sections.remove(index);
                    }
                    component = cmp;
                } else {
                    name = sections.shift();
                }
                components[name] = this.getComponent(component);
            }

            routes[i].components = components;
        }

        return routes;
    }

    /**
     *
     * @param name
     * @param configuration
     */
    registerComponent(name, configuration) {

        if (typeof name === 'object') {
            configuration = name;
            name = configuration.name
        }

        this.components[name] = configuration;

    }

    /**
     *
     * @param event
     * @param callback
     */
    addEventListener(event, callback) {
        if (!this.events.hasOwnProperty(event)) {
            this.events[event] = [];
        }

        this.events[event].push(callback);
    }

    /**
     *
     * @param event
     * @param callback
     */
    removeEventListener(event, callback) {
        if (!this.events.hasOwnProperty(event)) return;
        let callbacks = this.events[event];

        while (callbacks.indexOf(callback) !== -1) {
            let index = callbacks.indexOf(callback);
            callbacks = callbacks.remove(index);
        }
    }

    /**
     *
     * @param event
     * @param data
     */
    fireEvent(event, data = {}) {
        if (!this.events.hasOwnProperty(event)) return;
        let callbacks = this.events[event];
        data = {event: event, data: data};

        for (let i = 0; i < callbacks.length; i++) {
            try {
                callbacks[i](data);
            } catch (e) {
                console.error(e);
            }
        }
    }

    /**
     *
     * @param notification
     */
    notification(notification) {
        let $element = OC.Notification.show(notification);

        setTimeout(function () {
            OC.Notification.hide($element);
        }, 10000)
    }

    /**
     *
     * @param message
     * @param title
     * @returns {Promise}
     */
    alert(message, title = 'Message') {
        return new Promise((resolve, reject) => {
            OC.dialogs.alert(message, title, function (success) {
                if (success) {resolve();}
                else {reject();}
            });
        });
    }

    /**
     *
     * @param message
     * @param title
     * @returns {Promise}
     */
    info(message, title = 'Message') {
        return new Promise((resolve, reject) => {
            OC.dialogs.info(message, title, function (success) {
                if (success) {resolve();}
                else {reject();}
            });
        });
    }

    /**
     *
     * @param message
     * @param title
     */
    confirm(message, title = 'Message') {
        return new Promise((resolve, reject) => {
            OC.dialogs.confirm(message, title, function (success) {
                if (success) {resolve();}
                else {reject();}
            });
        });

    }

    /**
     *
     * @param name
     * @returns {*}
     */
    getComponent(name) {
        return this.components[name];
    }

    /**
     *
     * @param configuration
     */
    registerRoute(configuration) {
        this.routes.push(configuration);
    }

    /**
     *
     * @returns {Array}
     */
    getRoutes() {
        return this.routes.clone();
    }

    /**
     *
     * @returns {null|*}
     */
    getRouter() {
        return this.router;
    }

    /**
     *
     * @returns {null|*}
     */
    getApp() {
        return this.app;
    }

    /**
     *
     * @returns {EnhancedApi}
     */
    getApi() {
        return this._api;
    }
}