/**
 * @returns {Array.<*>}
 */
Array.prototype.clone = function() {
    return this.slice(0);
};

/**
 * @param index
 * @returns {*}
 */
Array.prototype.remove = function(index) {
    if(index === 0) {
        this.shift();
        return this;
    }

    this.splice(index, 1);
    return this;
};

Array.prototype.isNull = function (){
    let result =  this.some((element) => {
        return element !== null;
    });

    return !result;
};

/**
 * @returns {string}
 */
String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
};