const {crop} = require('easyimage');

module.exports = function() {
    return actor(
        {
            /**
             *
             * @param file     The file name
             * @param element  The element to capture
             * @param wait     Wait for x seconds before capturing
             * @param width    Width of the cropped area (Use element width by default)
             * @param height   Height of the cropped area (Use element height by default)
             * @returns {Promise<void>}
             */
            captureElement: async function(file, element, wait = 1, width = null, height = null) {
                this.captureWholePage(file, wait, true);

                let data  = await
                        this.executeScript(
                            function(el) {
                                let data = $(el).offset();
                                data.width = $(el).width();
                                data.height = $(el).height();
                                return JSON.stringify(data);
                            }, element),
                    stats = JSON.parse(data);

                await
                    crop(
                        {
                            src       : `tests/codecept/output/${file}.png`,
                            dst       : `tests/codecept/output/${file}.png`,
                            y         : stats.top,
                            x         : stats.left,
                            cropWidth : width ? width:stats.width,
                            cropHeight: height ? height:stats.height
                        }
                    );
            },

            /**
             *
             * @param name    The file name
             * @param wait    Wait for x seconds before capturing
             * @param full  Capture full page
             */
            captureWholePage: function(name, wait = 1, full = false) {
                this.moveCursorTo('#nextcloud');
                if(wait) this.wait(wait);
                this.saveScreenshot(`${name}.png`, full);
            }
        }
    );
};