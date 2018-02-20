/**
 *
 */
class SettingsManager {
    set(setting, value) {
        let name = 'passwords.'+setting;
        window.localStorage[name] = JSON.stringify(value);
    }

    get(setting, standard = null) {
        let name = 'passwords.'+setting;
        if(window.localStorage.hasOwnProperty(name)) {
            return JSON.parse(window.localStorage[name]);
        }
        return standard;
    }
}

let SM = new SettingsManager();

export default SM;