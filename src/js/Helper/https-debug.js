(function() {
    if(location.protocol !== 'https:' && location.href.indexOf('?https=false') === -1) {
        location.href = `${location.origin}${location.pathname}?https=false`;
    } else if(location.protocol === 'https:' && location.href.indexOf('?https=false') !== -1) {
        location.href = `${location.origin}${location.pathname}`;
    }
}());