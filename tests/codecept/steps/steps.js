const {crop, thumbnail} = require('easyimage');

function gEP(e) {let $e=$(e)[0];$e.scrollIntoView(false);return JSON.stringify($e.getBoundingClientRect());}
function hO() {document.body.style.overflow='hidden';}
function sO() {document.body.style.overflow='initial';}
function cN() {$('.toastify').remove();}
function oM() {$('#app-settings .more a').addClass('active');}

let window = {width: 1280, height: 874};

module.exports = function() {
    return actor(
        {
            /**
             *
             * @param width
             * @param height
             */
            setWindowSize(width, height) {
                window.width = width;
                window.height = height;
                this.resizeWindow(window.width, window.height);
            },

            /**
             *
             * @param file     The file name
             * @param element  The element to capture
             * @param wait     Wait for x seconds before capturing
             * @param width    Width of the cropped area (Use element width by default)
             * @param height   Height of the cropped area (Use element height by default)
             * @param preview  Create a preview
             * @returns {Promise<void>}
             */
            async captureElement(file, element, wait = 1, width = null, height = null, preview = true) {
                await this.closeAllNotifications();
                await this.executeScript(hO);
                if(wait) this.wait(wait);
                let data  = await this.executeScript(gEP, element),
                    stats = JSON.parse(data);
                await this.captureWholePage(file, 0, false, false);
                await this.executeScript(sO);

                if(width === null || width > stats.width) width = stats.width;
                if(height === null || height > stats.height) height = stats.height;

                await crop(
                    {
                        src       : `tests/codecept/output/${file}.png`,
                        dst       : `tests/codecept/output/${file}.png`,
                        y         : stats.y,
                        x         : stats.x,
                        cropWidth : width,
                        cropHeight: height
                    }
                );

                if(preview) {
                    let thumbWidth  = 320,
                        thumbHeight = 200;
                    if(width > height) {
                        thumbHeight = Math.round(thumbWidth / (width / height));
                    } else {
                        thumbWidth = Math.round(thumbHeight / (height / width));
                    }

                    await thumbnail(
                        {
                            src    : `tests/codecept/output/${file}.png`,
                            dst    : `tests/codecept/output/_previews/${file}.jpg`,
                            quality: 85,
                            width  : thumbWidth,
                            height : thumbHeight
                        }
                    );
                }
            },

            /**
             *
             * @param file     The file name
             * @param wait     Wait for x seconds before capturing
             * @param preview  Create a preview image
             * @param hideScrollbar
             */
            async captureWholePage(file, wait = 1, preview = true, hideScrollbar = true) {
                this.moveCursorTo('#nextcloud');
                await this.closeAllNotifications();
                if(hideScrollbar) await this.executeScript(hO);
                if(wait) this.wait(wait);
                await this.saveScreenshot(`${file}.png`, false);
                if(hideScrollbar) await this.executeScript(sO);

                if(preview) {
                    await thumbnail(
                        {
                            src    : `tests/codecept/output/${file}.png`,
                            dst    : `tests/codecept/output/_previews/${file}.jpg`,
                            quality: 85,
                            width  : 320,
                            height : 200
                        }
                    );
                }
            },

            /**
             *
             * @return {Promise<void>}
             */
            async closeAllNotifications() {
                await this.executeScript(cN);
            },

            async openMoreMenu() {
                await this.executeScript(oM);
                this.waitForVisible('#app-settings .fa-puzzle-piece');
            }
        }
    );
};
