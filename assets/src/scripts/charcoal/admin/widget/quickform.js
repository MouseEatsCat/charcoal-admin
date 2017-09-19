/* globals commonL10n */
/**
 * Quick form is called by JS and must be
 * added in the component manager manually.
 *
 * @param {Object} opts Widget options
 * @return {thisArg}
 */
Charcoal.Admin.Widget_Quick_Form = function (opts) {
    this.widget_type = 'charcoal/admin/widget/quick-form';
    this.save_callback = opts.save_callback || '';
    this.cancel_callback = opts.cancel_callback || '';

    this.save_action   = opts.save_action || 'object/save';
    this.update_action = opts.update_action || 'object/update';
    this.extra_form_data = opts.extra_form_data || {};

    this.form_working = false;
    this.is_new_object = false;
    this.xhr = null;
    this.obj_id = Charcoal.Admin.parseNumber(opts.obj_id) || 0;

    return this;
};
Charcoal.Admin.Widget_Quick_Form.prototype = Object.create(Charcoal.Admin.Widget.prototype);
Charcoal.Admin.Widget_Quick_Form.prototype.constructor = Charcoal.Admin.Widget_Quick_Form;
Charcoal.Admin.Widget_Quick_Form.prototype.parent = Charcoal.Admin.Widget.prototype;

Charcoal.Admin.Widget_Quick_Form.prototype.init = function () {
    this.bind_events();
};

Charcoal.Admin.Widget_Quick_Form.prototype.bind_events = function () {
    var that = this;
    $(document).on('submit', '#' + this.id(), function (e) {
        e.preventDefault();
        that.submit_form(this);
    });
    $('#' + this.id()).on(
        'click.charcoal.bs.dialog',
        '[data-dismiss="dialog"]',
        function (event) {
            if ($.isFunction(that.cancel_callback)) {
                that.cancel_callback(event);
            }
        }
    );
};

Charcoal.Admin.Widget_Quick_Form.prototype.submit_form = function (form) {
    if (this.form_working) {
        return;
    }

    this.form_working = true;

    this.is_new_object = !this.obj_id;

    var $trigger, $form, form_data;

    $form = $(form);
    $trigger = $form.find('[type="submit"]');

    if ($trigger.prop('disabled')) {
        return false;
    }

    // Let the component manager prepare the submit first
    // Calls the save function on each properties
    Charcoal.Admin.manager().prepare_submit();

    form_data = new FormData(form);

    this.disable_form($form, $trigger);

    var extraFormData = this.extra_form_data;

    for (var data in extraFormData) {
        if (extraFormData.hasOwnProperty(data)){
            form_data.append(data, extraFormData[data]);
        }
    }

    this.xhr = $.ajax({
        type: 'POST',
        url: this.request_url(),
        data: form_data,
        dataType: 'json',
        processData: false,
        contentType: false,
    });

    this.xhr
        .then(this.request_parse.bind(this, commonL10n.errorOccurred))
        .fail(this.request_fail.bind(this))
        .done(this.request_done.bind(this, $form, $trigger))
        .always(this.request_always.bind(this, $form, $trigger));
};

Charcoal.Admin.Widget_Quick_Form.prototype.disable_form = Charcoal.Admin.Widget_Form.prototype.disable_form;

Charcoal.Admin.Widget_Quick_Form.prototype.enable_form = Charcoal.Admin.Widget_Form.prototype.enable_form;

Charcoal.Admin.Widget_Quick_Form.prototype.request_url = Charcoal.Admin.Widget_Form.prototype.request_url;

Charcoal.Admin.Widget_Quick_Form.prototype.request_parse = Charcoal.Admin.Widget_Form.prototype.request_parse;

Charcoal.Admin.Widget_Quick_Form.prototype.request_fail = Charcoal.Admin.Widget_Form.prototype.request_fail;

Charcoal.Admin.Widget_Quick_Form.prototype.request_always = Charcoal.Admin.Widget_Form.prototype.request_always;

Charcoal.Admin.Widget_Quick_Form.prototype.request_done = function ($form, $trigger, response/* ... */) {
    if (response.feedbacks && !this.suppress_feedback()) {
        Charcoal.Admin.feedback(response.feedbacks);
    }

    if (response.next_url) {
        // @todo "dynamise" the label
        Charcoal.Admin.feedback().add_action({
            label: commonL10n.continue,
            callback: function () {
                window.location.href = Charcoal.Admin.admin_url() + response.next_url;
            }
        });
    }

    this.enable_form($form, $trigger);
    this.form_working = false;

    if (typeof this.save_callback === 'function') {
        this.save_callback(response);
    }
};
