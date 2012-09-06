// Copyright 2012 GREE Inc. All Rights Reserved.

/**
 * @fileoverview The links.js file contains code for the Orion Links Admin page.
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

    var _categories,  // Array of category objects.
        _links,  // Backbone Collection of Links
        _appView;  // Backbone top level view.

    // LINKS namespace for code specific to the Admin Links page.
    APP.LINKS = {};


    /**
     * APP.LINKS.Models
     * Module with Backbone Models for the Admin Links page.
     */
    APP.LINKS.Models = (function () {

        return {

            // A Link has 4 attributes: id (after creation), category_id, display, and url.
            Link: Backbone.Model.extend({

                defaults: {
                    category_id: 1,
                    display: '',
                    url: ''
                },

                parse: function (response) {
                    response.category_id = parseInt(response.category_id, 10);
                    return response;
                },

                validate: function (attrs) {
                    var dups;
                    this.errors = '';
                    if (attrs.display === '' || attrs.url === '') {
                        this.errors = 'Must provide a display name and a URL.';
                    }
                    dups = _links.where({
                        category_id: attrs.category_id,
                        display: attrs.display
                    });
                    if (dups && dups.length > 1) {
                        this.errors = 'A link with that name already exists for that category'
                    }

                    // Trigger validationFailed event if validation fails.
                    if (this.errors) {
                        this.trigger('validationFailed');
                        return this.errors;
                    }
                },

                // Override URL as server is not RESTful.
                url: function (type) {
                    var base_path = APP.Setup.get_base_path(),
                        app_path = 'index.php/links/',
                        URL_MAP = {
                            create: 'save_link',
                            read: '',
                            update: 'save_link',
                            'delete': 'delete_link'
                        };
                    return base_path + app_path + URL_MAP[type];
                }

            }),

            Links: Backbone.Collection.extend({

                model: this.Link

            })

        };

    })();


    /**
     * APP.LINKS.Views
     * Module with Backbone Views for the Admin Links page.
     */
    APP.LINKS.Views = (function () {

        var _linkForm;

        return {

            // Top level view for admin Links page.
            AppView: Backbone.View.extend({

                el: $('#links_container'),

                events: {
                    'click #add_link': 'createLink',
                    'click .edit_link': 'editLink',
                    'click .delete_link': 'deleteLink'
                },

                createLink: function () {
                    _links.add(new APP.LINKS.Models.Link);
                },

                editLink: function (e) {
                    var id = $(e.target).attr('id').replace('edit_link_', ''),
                        link = _links.get(id);
                    if (_linkForm) {
                        _linkForm.undelegateEvents();
                    }
                    _linkForm = new APP.LINKS.Views.LinkView({model: link});
                    _linkForm.render();
                    if (link.has('category_id')) {
                        $('#category_id option[value="' + link.get('category_id') + '"]').attr('selected', 'selected');
                    }
                },

                deleteLink: function (e) {
                    var id = $(e.target).attr('id').replace('delete_link_', ''),
                        link = _links.get(id);
                    if (window.confirm("Are you sure you want to delete Link " + id)) {
                        link.destroy();
                    }
                },

                addLink: function (link) {
                    if (_linkForm) {
                        _linkForm.undelegateEvents();
                    }
                    _linkForm = new APP.LINKS.Views.LinkView({model: link});
                    _linkForm.render();
                },

                // Sets up HTML template for App.
                template: function (obj) {
                    // jQuery .html() method seems to strip out anything within a table that is not a table element.
                    // so this is a hacky way to add the mustache template tags within the table...
                    var templateHtml = $('#app_template').html()
                                                         .replace('<tr class="link_iterator_start"></tr>', '{{#links}}')
                                                         .replace('<tr class="link_iterator_end"></tr>', '{{/links}}');
                    return Mustache.render(templateHtml, obj);
                },

                render: function () {
                    var templateObj = _links.toJSON();
                    _.each(templateObj, function (link) {
                        var category = _.find(_categories, function (category) {
                            return category.id === parseInt(link.category_id, 10);
                        });
                        if (category) {
                            link.category_name = category.category_name;
                        } else {
                            link.category_name = 'UNUSED_' + link.category_id;
                        }
                    });
                    this.$el.html(this.template({
                        links: templateObj
                    }));
                },

                initialize: function () {

                    this.render();

                    _links.bind('add', this.addLink, this);
                    _links.bind('remove', this.render, this);
                    _links.bind('saved', this.render, this);

                }

            }),

            // Link edit/create form view
            LinkView: Backbone.View.extend({

                el: '#links_form',

                events: {
                    'submit form': 'submitHandler'
                },

                submitHandler: function (e) {

                    var attributeObj = {},
                        isNew = this.model.isNew();

                    this.$('input, select').each(function () {
                        if ($(this).attr('id') === 'category_id') {
                            attributeObj[$(this).attr('id')] = parseInt($(this).val(), 10);
                        } else {
                            attributeObj[$(this).attr('id')] = _.escape($(this).val());
                        }
                    });
                    this.model.set(attributeObj, {silent: true});

                    this.$('.alert').hide();
                    $('#saving-box').show();
                    $('.btn').addClass('disabled').attr('disabled', 'disabled');

                    this.model.save({}, {

                        // Show confirmation alert on success.
                        success: function (model, response) {
                            var responseText = isNew ? 'Link Saved!' : 'Link Updated!',
                                alertBox = $('#success-box');
                            $('.btn').removeClass('disabled').removeAttr('disabled');
                            alertBox.text(responseText).show();
                            window.setTimeout(function () {
                                alertBox.fadeOut(3000);
                            }, 1000);
                            model.trigger('saved');
                            log('success', model, response); // TODO - dbow - remove
                        },

                        // Show server error message on error.
                        error: function (model, errors) {
                            var responseText = '<div>THERE WAS AN ERROR SAVING TO THE SERVER</div>';
                            $('.btn').removeClass('disabled').removeAttr('disabled');
                            if (errors.responseText) {
                                $('#error-box').html(_.escape(responseText + errors.responseText)).show();
                            }
                            $('#saving-box').hide();
                            log('error', model, errors); // TODO - dbow - remove
                        }

                    });

                    return false;

                },

                template: function (obj) {
                    return Mustache.render($('#link_template').html(), obj);
                },

                render: function () {
                    var templateObj = this.model.toJSON();
                    templateObj.buttonText = this.model.isNew() ? 'Add' : 'Update';
                    templateObj.errors = this.model.errors;
                    this.$el.html(this.template(templateObj));
                },

                empty: function () {
                    $('#saving-box').hide();
                    this.$el.html('');
                },

                initialize: function () {
                    this.model.on('validationFailed', this.render, this);
                    this.model.on('saved', this.empty, this);
                }

            })

        }

    })();

    // Function to set up Link admin page.  Passed an array of links.
    APP.LINKS.setLinks = function (links) {
        var parsedLinks = $.parseJSON(links),
            link;
        _links = new APP.LINKS.Models.Links;
        for (var i = 0, len = parsedLinks.length; i < len; i++) {
            link = new APP.LINKS.Models.Link(parsedLinks[i]);
            _links.add(link, {silent: true});
        }
        _appView = new APP.LINKS.Views.AppView;
    };

    // Stores array of category objects in local variable.
    APP.LINKS.setCategories = function (categories) {
        _categories = $.parseJSON(categories);
    };

}());
