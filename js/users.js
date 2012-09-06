// Copyright 2012 GREE Inc. All Rights Reserved.

/**
 * @fileoverview The users.js file contains code for the Orion Users Admin page.
 *
 *      It uses Backbone to model Users, and then provides a single View that manages the UI
 *      rendering of those Users.  Primarily handles taking user input and converting it to the data
 *      the server expects, and updating UI state during server interactions.
 *
 * @author <daniel.bowman@gree.co.jp> Danny Bowman
 *
 */


/**
 * APP
 * Top-level namespace for application code.
 */
var APP = APP || {};


(function () {

    // USERS namespace for code specific to the Admin Users page.
    APP.USERS = {};

    var _currentUser,
        _users,
        _app;

    /**
     * APP.USERS.Models
     * Module with Backbone Model for Users.
     */
    APP.USERS.Models = (function () {

        return {

            /**
             * APP.USERS.Models.User
             * Backbone Model for a User.  Generally will have an email address, ID, and 4 permission values.
             */
            User: Backbone.Model.extend({

                // Object containing the data structure the server expects for each permission level.
                permissionMap: {
                    'Read': {
                        perm_read: 1,
                        perm_restricted: 0,
                        perm_create: 0,
                        perm_update: 0,
                        perm_delete: 0
                    },
                    'Read_Restricted': {
                        perm_read: 1,
                        perm_restricted: 1,
                        perm_create: 0,
                        perm_update: 0,
                        perm_delete: 0
                    },
                    'Create/Update': {
                        perm_read: 1,
                        perm_restricted: 1,
                        perm_create: 1,
                        perm_update: 1,
                        perm_delete: 0
                    },
                    'Admin': {
                        perm_read: 1,
                        perm_restricted: 1,
                        perm_create: 1,
                        perm_update: 1,
                        perm_delete: 1
                    }
                },

                setNewPermission: function (permission) {

                    this.set(this.permissionMap[permission.replace(' ', '_')], {silent: true});

                },

                // Override URL as server is not RESTful.
                url: function () {
                    return APP.Setup.get_base_path() + 'index.php/orion/users';
                }

            }),

            /**
             * APP.USERS.Models.Users
             * Backbone Collection of User Models.
             */
            Users: Backbone.Collection.extend({

                model: this.User

            })

        };

    })();


    /**
     * APP.USERS.Views
     * Module with Backbone Views for the User Models.
     */
    APP.USERS.Views = (function () {

        return {

            /**
             * APP.USERS.Views.AppView
             * Backbone View for the User management page.  Renders a table with a row for each User model.
             */
            AppView: Backbone.View.extend({

                el: '#user_container',

                events: {
                    'change select': 'hasChanged',
                    'click .user_update': 'updateUser'
                },

                // Convenience function to extract the id integer from the row element.
                getIdFromElement: function (element) {
                    return $(element).parent().parent().attr('id').replace('user_row_', '');
                },

                // Checks if the User model permissions have changed since server sync.
                hasChanged: function (e) {
                    var id = this.getIdFromElement(e.target),
                        selected = $(e.target).find(':selected').text(),
                        user = _users.get(id);
                    user.setNewPermission(selected);
                    if (user.hasChanged()) {
                        user.statusMsg = '<span class="alert user_modified">Modified</span>';
                    } else {
                        user.statusMsg = '';
                    }
                    this.render();
                },

                // Save User to the server.
                updateUser: function (e) {

                    var id = this.getIdFromElement(e.target),
                        user = _users.get(id),
                        render = _.bind(this.render, this);

                    user.save({}, {
                        // Show confirmation alert on success.
                        success: function (model, response) {
                            model.statusMsg = '<span class="alert alert-success user_saved disable_selector">Saved!</span>';
                            render();
                            window.setTimeout(function () {
                                model.statusMsg = '';
                                render();
                            }, 1000);
                            console.log('success', model, response); // TODO - dbow - remove
                        },
                        // Show server error message on error.
                        error: function (model, errors) {
                            model.statusMsg = '<span class="alert alert-error user_error">Server Error!</span>';
                            render();
                            console.log('error', model, errors); // TODO - dbow - remove
                        }
                    });

                },

                // When model is saving to server, update statusMsg.
                handleChange: function (model) {
                    model.statusMsg = '<span class="alert alert-info user_saving disable_selector">Saving...</span>';
                    this.render();
                },

                // Initialization function to bind handlers to events on the Users collection.
                initialize: function () {

                    _users.bind('add', this.render, this);
                    _users.bind('change', this.handleChange, this);

                },

                // Sets up HTML template for App.
                template: function (obj) {

                    // jQuery .html() method seems to strip out anything within a table that is not a table element.
                    // so this is a hacky way to add the mustache template tags within the table...
                    var templateHtml = $('#app_template').html()
                                                         .replace('<tr class="user_iterator_start"></tr>', '{{#users}}')
                                                         .replace('<tr class="user_iterator_end"></tr>', '{{/users}}');

                    return Mustache.render(templateHtml, obj);

                },

                // Render the UI based on the current state of the User models.
                render: function () {

                    var templateObj = {
                            users: _users.toJSON()
                        },
                        i, len, userObj;

                    // Update templateObj values manually based on user permissions.
                    for (i = 0, len = templateObj.users.length; i < len; i++) {
                        userObj = templateObj.users[i];
                        userObj.read_selected = function () {
                            return !this.perm_create ? "selected" : null;
                        };
                        userObj.read_restricted_selected = function () {
                            return this.perm_restricted && !this.perm_create ? "selected" : null;
                        };
                        userObj.create_update_selected = function () {
                            return this.perm_create && !this.perm_delete ? "selected" : null;
                        };
                        userObj.delete_selected = function () {
                            return this.perm_delete ? "selected" : null;
                        };
                        userObj.status = _users.at(i).statusMsg;
                        if (userObj.status && userObj.status.indexOf('disable_selector') >= 0) {
                            userObj.disabled = ' disabled';
                        }
                    }

                    $(this.el).html(this.template(templateObj));

                    return this;

                }

            })

        }

    })();

    /**
     * APP.USERS.setUserData
     * Setup function to create Models and Views based on data passed by the server.
     * @param userData JSON object of users from the server.
     */
    APP.USERS.setUserData = function (userData) {
        var i,
            len,
            user;

        _users = new APP.USERS.Models.Users; // Initialize Users Collection.
        _app = new APP.USERS.Views.AppView; // Initialize AppView View.

        // For each user in the server data, create a User Model and add it to the Collection.
        for (i = 0, len = userData.length; i < len; i++) {
            if (userData[i].email !== _currentUser) {
                user = new APP.USERS.Models.User(userData[i]);
                _users.add(user);
            }
        }

    };

    APP.USERS.setCurrentUser = function (user) {
        if (user) {
            _currentUser = user;
        }
    };

}());
