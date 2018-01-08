import Vue from 'vue';
import $ from "jquery";
import App from '@vue/App.vue';

$(window).on('load', function() {
    new Vue(App);
    $(window).off('load');
});