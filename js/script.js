// Copyright 2012 GREE Inc. All Rights Reserved.

/**
 * @fileoverview The script.js file contains general code for all Orion pages.
 *
 * @author <daniel.bowman@gree.co.jp> Danny Bowman
 * @author <karan.kurani@gree.co.jp> Karan Kurani
 *
 */


/**
 * APP
 * Top-level namespace for application code.
 */
var APP = APP || {},
    Backbone = Backbone || {};

(function () {

    /**
     * APP.Setup
     * Module handling initial application setup.
     */
    APP.Setup = (function () {

        var base_path;

        return {

            get_base_path: function () {
                return base_path;
            },

            set_base_path: function (path) {
                if (base_path === undefined) {
                    base_path = path;
                }
            },

            init: function () {

                // TODO - dbow - is this obsolete?
                $.extend({

                    getUrlVars: function () {

                        var i,
                            hashLen,
                            hash,
                            hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&'),
                            vars = [];

                        for (i = 0, hashLen = hashes.length; i < hashLen; i++) {
                            hash = hashes[i].split('=');
                            if (hash[0] === "target") {
                                if (vars[hash[0]] === undefined) {
                                    vars[hash[0]] = [];
                                }
                                vars[hash[0]].push(hash[1]);
                                continue;
                            }
                            vars[hash[0]] = hash[1];
                        }
                        return vars;
                    },

                    getUrlVar: function (name) {
                        return $.getUrlVars()[name];
                    }

                });

            }

        };

    })();

    /**
     * Overriding Backbone.sync because server is not RESTful
     */
    Backbone.sync = function (method, model, options) {

        // Mapping types based on orion.php
        var typeMap = {
            create: 'POST',
            read: 'GET',
            update: 'POST',
            'delete': 'POST'
        };

        options || (options = {});

        var params = {
            type: typeMap[method],
            dataType: 'json'
        };

        // Get URL from the model itself, based on type.
        if (!options.url) {
            params.url = model.url(method);
        }

        if (!options.data && model) {

            if (model.has('dashboard_name')) {
                // Normal dashboard handling.
                params.data = {};
                if (method == 'create' || method == 'update') {
                    params.data.dashboard_json = JSON.stringify(model.toJSON());
                }
                if (!model.isNew()) {
                    params.data.dashboard_id = model.get('id');
                }

            } else if (model.has('perm_read')) {
                // User admin handling.
                params.data = {
                    user: JSON.stringify(model.toJSON())
                };
            } else if (model.has('display')) {
                // Link admin handling.
                params.data = model.toJSON();
            }
        }

        if (params.type !== 'GET' && !Backbone.emulateJSON) {
            params.processData = true;
        }

        $.ajax(_.extend(params, options));

    };


}());
