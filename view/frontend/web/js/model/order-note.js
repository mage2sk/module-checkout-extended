define([
    'ko'
], function (ko) {
    'use strict';

    return {
        value: ko.observable(''),
        enabled: ko.observable(false),
        maxLength: ko.observable(500)
    };
});
