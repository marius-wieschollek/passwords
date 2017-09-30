/**
 *
 * @returns {Array.<*>}
 */
Array.prototype.clone = function () {
    return this.slice(0);
};

/**
 *
 * @param index
 * @returns {*}
 */
Array.prototype.remove = function (index) {
    if (index === 0) {
        this.shift();
        return this;
    }
    return this.splice(index, 1);
};

/**
 *
 * @returns {string}
 */
String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
};

/**
 *
 * @param text
 */
function copyToClipboard(text) {
    let id = 'ctc-' + Math.random(),
        $element = $('<textarea id="' + id + '">' + text + '</textarea>');

    $('body').append($element);
    $element.select();

    document.execCommand('copy');

    $element.remove();
}