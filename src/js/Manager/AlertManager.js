import Messages from '@js/Classes/Messages';
import Utility from '@js/Classes/Utility';

class AlertManager {

    init() {
        let element = document.querySelector('meta[name=pw-alert]');
        if(element) {
            let alerts = element.getAttribute('content');
            if(alerts.length === 0) return;

            alerts = JSON.parse(alerts);
            if(alerts.length !== 0) this.showAlerts(alerts).catch(console.error);
        }
    }

    /**
     *
     * @param {Object[]} alerts
     * @return {Promise<void>}
     */
    async showAlerts(alerts) {
        for(let alert of alerts) {
            try {
                await Messages.alert(alert.message, alert.title);
                if(alert.link) Utility.openLink(alert.link);
            } catch(e) {

            }
        }
    }
}

export default new AlertManager();