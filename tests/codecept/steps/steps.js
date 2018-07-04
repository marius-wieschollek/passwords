const {crop, thumbnail} = require('easyimage');

function gEP(e) {let $e=$(e);$e[0].scrollIntoView(false);let d=$e.offset();d.width=$e.width();d.height=$e.height();return JSON.stringify(d);}
let window = {width:1280, height:874};

module.exports = function() {
    return actor(
        {
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
             * @returns {Promise<void>}
             */
            async captureElement(file, element, wait = 1, width = null, height = null) {

                if(wait) this.wait(wait);
                let data  = await this.executeScript(gEP, element),
                    stats = JSON.parse(data);
                await this.captureWholePage(file, 0, false);

                if(width === null || width > stats.width) width = stats.width;
                if(height === null || height > stats.height) height = stats.height;

                await crop(
                    {
                        src       : `tests/codecept/output/${file}.png`,
                        dst       : `tests/codecept/output/${file}.png`,
                        y         : stats.top,
                        x         : stats.left,
                        cropWidth : width,
                        cropHeight: height
                    }
                );
            },

            /**
             *
             * @param file     The file name
             * @param wait     Wait for x seconds before capturing
             * @param full     Capture full page
             * @param preview  Create a preview image
             */
            async captureWholePage(file, wait = 1, preview = true) {
                this.moveCursorTo('#nextcloud');
                if(wait) this.wait(wait);
                await this.saveScreenshot(`${file}.png`, false);

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
            }
        }
    );
};
