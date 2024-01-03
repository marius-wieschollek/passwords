import Localisation from '@js/Classes/Localisation';
import ToastService from '@js/Services/ToastService';
import Logger from "@js/Classes/Logger";
import UtilityService from "@js/Services/UtilityService";

export default new class ContextMenuService {

    get ICON_MAPPING() {
        return {
            'details' : 'info',
            'share'   : 'shared',
            'edit'    : 'rename',
            'edit-new': 'rename',
            'move'    : 'external',
            'open-url': 'link',
            'delete'  : 'delete',
            'print'   : 'edit',
            'restore' : 'history',
            'qrcode'  : 'edit'
        };
    }

    /**
     * @param {Object} item
     * @param {(HTMLElement|Element)} element
     */
    register(item, element) {
        if(!window.hasOwnProperty('RightClick')) return;
        if(item.type === 'password') {
            this._registerPasswordContextMenu(item, element);
        } else {
            this._registerGenericContextMenu(item, element);
        }
    }

    /**
     * @param {Object} item
     * @param {HTMLElement} element
     */
    _registerPasswordContextMenu(item, element) {
        new RightClick.Menu(element, () => {
            this._closeContextMenus(element);
            let options = this._createOptions();

            this._fetchItemActions(options, element);
            if(item.hasOwnProperty('url') && item.url) {
                options.add(this._passwordCopyOption(item, 'url'), 1);
            }
            if(item.hasOwnProperty('username') && item.username) {
                options.add(this._passwordCopyOption(item, 'username'), 1);
            }
            options.add(this._passwordCopyOption(item, 'password'), 1);

            return options;
        });
    }

    /**
     * @param {Object} item
     * @param {HTMLElement} element
     */
    _registerGenericContextMenu(item, element) {
        new RightClick.Menu(element, () => {
            this._closeContextMenus();
            let options = this._createOptions();

            if(item.type === 'tag' || item.type === 'folder') {
                options
                    .append(
                        this._createOption(
                            'open',
                            Localisation.translate('Open {label}', {label: item.label}),
                            item.type,
                            () => {element.click();}
                        )
                    );
            }

            this._fetchItemActions(options, element);

            return options;
        });
    }

    /**
     *
     * @param {Object} item
     * @param {String} property
     * @return {RightClick.Option}
     * @private
     */
    _passwordCopyOption(item, property) {
        return this._createOption(
            'copy-' + property,
            Localisation.translate(`Copy ${property === 'username' ? 'User' : property.capitalize()}`),
            'clippy',
            () => {
                let message = 'Error copying {element} to clipboard';
                if(UtilityService.copyToClipboard(item[property])) message = '{element} was copied to clipboard';

                ToastService.info([message, {element: Localisation.translate(property.capitalize())}]);
            }
        );
    }

    /**
     *
     * @param {Object} options
     * @param {HTMLElement} element
     * @private
     */
    _fetchItemActions(options, element) {
        let actionElements = element.querySelectorAll('[data-item-action]');

        for(let actionElement of actionElements) {
            let action = actionElement.dataset.itemAction,
                label  = actionElement.innerText,
                icon   = '';

            if(action === 'favorite') {
                if(actionElement.classList.contains('star-icon')) {
                    label = Localisation.translate('Remove from favorites');
                    icon = 'star-dark';
                } else {
                    label = Localisation.translate('Mark as favorite');
                    icon = 'starred';
                }
            } else if(this.ICON_MAPPING.hasOwnProperty(action)) {
                icon = this.ICON_MAPPING[action];
            }

            options
                .append(
                    this._createOption(
                        action,
                        label,
                        icon,
                        () => {
                            actionElement.click();
                            setTimeout(() => {
                                this._closeContextMenus();
                            }, 1);
                        }
                    )
                );
        }
    }

    /**
     *
     * @param {String} name
     * @param {String} label
     * @param {String} icon
     * @param {Function} callback
     * @return {RightClick.Option}
     * @private
     */
    _createOption(name, label, icon, callback) {
        return new RightClick.Option(name, label, `icon-${icon}`, (e) => {
            e.stopPropagation();
            e.preventDefault();

            try {
                if(callback) callback();
            } catch(e) {
                Logger.error(e);
            }
        });
    }

    /**
     *
     * @return {RightClick.Options}
     * @private
     */
    _createOptions() {
        let options = new RightClick.Options();

        options.generate = function() {
            let ul = document.createElement('ul');

            for(let name in this.options) {
                if(!this.options.hasOwnProperty(name)) continue;
                let li = this.options[name].generate();

                if(li) {
                    li.addClass('action-' + name);
                    ul.append(li.get(0));
                }
            }

            return ul;
        };

        return options;
    }

    /**
     * @param {HTMLElement} element
     * @private
     */
    _closeContextMenus(element) {
        let menus = document.querySelectorAll('.item-list .menu.open');
        menus.forEach((el) => {
            el.click();
        })
    }
};