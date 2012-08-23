/**
 * Bootstrap Window
 * ================
 * NOTICE: This module requires twitter bootstrap v2 and their modal plugin.
 *
 * The window module is able to create sophisticated windows out of thin air.
 *
 * Explanation of the parameters object you pass to the window constructor:
 *
 * {
 *  backdrop: true              //Should the window display a dark shade behind it?
 *  keyboard: true              //Should the window get closed when the user hits ESC?
 *  show: false                 //Automatically open the window after it got created.
 *  close_on_backdrop: true     //Should the window be closed if the user clicks on the backdrop?
 *  close_title: true           //Display an X button in the upper right corner of the window which closes.
 *  template: null              //Path to a HTML template to be loaded and rendered inside the window.
 *  window_class: ''            //A CSS classname to be attached to the modal window.
 *  show_header: true           //Option to display or hide the complete window header.
 *  show_footer: true           //Option to display or hide the complete window footer.
 *  width: 580                  //Width of the window in pixel.
 *  buttons: []                 //Action buttons to be displayed in the footer. Default: One close button. See button reference.
 *  before_close: function()    //
 * }
 *
 *
 * Action buttons are ordered from left to right in the window footer.
 * Pass an array of action buttons you want to use inside your window to the constructor within params.buttons
 *
 * A button object can consist of this options:
 * {
 *  id: null,                   //Optional ID for this button (unique for the window) if you want to access the button after creation.
 *  type: null,                 //Button style. Null = default grey, 'primary' = blue action button
 *  label: '',                  //Button caption
 *  do_close: false,            //Should a click on the button close the window? The attached callback will
 *  css_class: null,            //One or more CSS classes to be appended to the button.
 *  disabled: false,            //Should the button be disabled? (not responding to clicks)
 *  hidden: false,              //Should the button be hidden?
 *  callback: function(){}      //Callback function that gets triggered when the button has been clicked by the user. Return false from it to stop automatic do_close.
 * }
 *
 * --------------------------
 *
 * @TODO: Make the set_button() method functional.
 *
 * @version 1
 * @author Christian Engel <hello@wearekiss.com>
 */
define(['text!./bs_window/window.html', 'text!./bs_window/window-button.html'], function (base_template, button_template) {
    var base_template = _.template(base_template),
        button_template = _.template(button_template);

    /**
     * Default settings for every window.
     * @type {Object}
     */
    var settings = {
        backdrop:true,
        keyboard:true,
        show:false,
        title:'New window',
        window_class: '',
        close_title:true,
        close_on_backdrop:true,
        template:null,
        template_data:null,
        window_class: '',
        show_header:true,
        show_footer:true,
        width:580,
        height_limit: 400,
        buttons:[
            {
                id:null,
                type:null,
                label:'Close',
                do_close:true,
                css_class:'',
                disabled:false,
                hidden:false,
                callback:function () {
                }
            }
        ],
        before_close:function () {
        }
    }

    /**
     * Default settings for every button.
     * @type {Object}
     */
    var button_settings = {
        id:null,
        type:null,
        label:'',
        do_close:false,
        css_class:'',
        disabled:false,
        hidden:false,
        callback:function () {
        }
    }

    return function (params) {

        params = _.extend(_.clone(settings), params);

        //First, render the HTML code for the buttons:
        var button_html = '',
                callbacks = {}, //Object to store the callback functions.
                close_stopper = {}, //Object thats used to prevent closing of the window.
                i,
                rid,
                delay_show = false, //Will be set to true if a content template should be shown.
                do_show = false;    //Will be set to true if the window is called (show()) before the template is loaded.

        if (params.buttons.length) {
            params.buttons.reverse();
            for (i = 0; i < params.buttons.length; i++) {
                params.buttons[i] = _.extend(_.clone(button_settings), params.buttons[i]);
                if (params.buttons[i].type == 'primary') params.buttons[i].type = 'btn-primary';
                if (!params.buttons[i].id) {
                    params.buttons[i].id = 'btn_' + Math.random().toString().substr(2);
                }
                callbacks[params.buttons[i].id] = params.buttons[i].callback;
                button_html += button_template(params.buttons[i]);
            }
            params.buttons = button_html;
        }

        var DOM_el = $(base_template(params));

        DOM_el.modal(_.extend(_.clone(params), {backdrop:false}));

        document.body.appendChild(DOM_el[0]);

        if (params.template) {
            delay_show = true;
            require(['text!' + params.template], function (tmp) {
                var html = _.template(tmp)(params.template_data);
                $('.modal-body', DOM_el).html(html);
                delay_show = false;
                if (do_show) obj.show();
            });
        }

        //===============Listeners====================

        //Clicking on any button in the footer
        $('.modal-footer button', DOM_el).click(function (e) {
            if ($(this).hasClass('disabled')) return;
            var button_id = $(this).attr('data-id');
            close_stopper[button_id] = false;
            if (callbacks[button_id]() === false) {
                close_stopper[button_id] = true;
            }
        });

        //Clicking on any close button on the window.
        $('[data-close=1]', DOM_el).click(function () {
            var button_id = $(this).attr('data-id');
            if (close_stopper[button_id]) {
                close_stopper[button_id] = false;
                return;
            }
            if (params.before_close() === false) return;
            obj.hide();
        });

        //===============End of Listeners=============


        var obj = {
            /**
             * Reference to the jQuery enhanced window DOM object to mess around with.
             */
            el:DOM_el,
            /**
             * Reference to the jQuery enhanced content DOM element to have direct access to the content area.
             */
            content_el:$('.modal-body', DOM_el),
            /**
             * Calling this will make the window appear.
             */
            show:function () {
                if (delay_show) {
                    do_show = true;
                    return;
                }
                DOM_el.modal('show');
                if (params.backdrop) {
                    $('body').append($('<div class="modal-backdrop fade in"></div>').fadeIn());
                    if (params.close_on_backdrop) {
                        $('.modal-backdrop').click(function () {
                            if (params.before_close() === false) return;
                            obj.hide();
                        });
                    }
                }
                obj.trigger('show');
            },
            /**
             * Calling this will hide the window.
             */
            hide:function () {
                DOM_el.modal('hide');
                if (params.backdrop) {
                    $('.modal-backdrop').fadeOut(function () {
                        $(this).remove();
                    });
                }
                obj.trigger('hide');
            },
            /**
             * Will overwrite the window title. HTML enabled.
             * @param title
             */
            set_title:function (title) {
                $('.modal-header h3', DOM_el).html(title);
            },
            /**
             * Set an error message to be displayed in the footer of the window.
             * Will remove the displayed spinner, if there is any.
             * @param message
             */
            set_error:function (message) {
                $('.modal-footer .spinner', DOM_el).hide();
                if (!message) {
                    $('.modal-footer .error', DOM_el).hide();
                    return;
                }
                $('.modal-footer .alert-error', DOM_el).show().children('.error-content').html(message);
            },
            /**
             * Show the spinner in the footer of the window and put a label beside it.
             * Will remove the displayed error, if there is any.
             * @param message
             */
            set_spinner:function (message) {
                $('.modal-footer .error', DOM_el).hide();
                if (!message) {
                    $('.modal-footer .spinner', DOM_el).hide();
                    return;
                }
                $('.modal-footer .spinner', DOM_el).show().children('.spinner-content').html(message);
            },
            /**
             * Set different parameters for the button with the given id.
             *
             * {
             *  disabled: true|false,
             *  hidden: true|false,
             *  css_class: '',
             *  label: '',
             *  type: null | 'primary'
             * }
             *
             * All fields are optional, you can set multiple values at once.
             * @param id
             * @param params
             */
            set_button:function (id, params) {

            },
            /**
             * This will reset all form elements in the window content to their default values.
             */
            reset_forms:function () {
                $.each($('.modal-body form', DOM_el), function () {
                    $(this)[0].reset();
                });
            },
            /**
             * Select an element from the content area.
             * @param selector
             * @return {*}
             */
            $: function(selector){
                return $(selector, obj.content_el);
            },
            set_width: function(width){
                DOM_el.animate({
                    width: width,
                    marginLeft: '-'+(width/2)
                }, 'fast');
            },
            show_footer: function(hide){
                if(hide === false){
                    $('.modal-footer', DOM_el).slideUp(function(){
                        //$(this).removeClass('hide');
                    });
                    return;
                }
                $('.modal-footer', DOM_el).slideDown();
            },
            /**
             * Overwrites some default settings.
             * @param new_params
             */
            set_settings: function(new_params){
                params = _.extend(_.clone(params), new_params);
            }
        }

        obj.content_el.css('max-height', params.height_limit);

        _.extend(obj, Backbone.Events);

        return obj;
    }
});