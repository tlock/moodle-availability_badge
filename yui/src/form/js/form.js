/**
 * JavaScript for form editing badge conditions.
 *
 * @module moodle-availability_badge-form
 */
M.availability_badge = M.availability_badge || {};

/**
 * @class M.availability_badge.form
 * @extends M.core_availability.plugin
 */
M.availability_badge.form = Y.Object(M.core_availability.plugin);

/**
 * Groups available for selection (alphabetical order).
 *
 * @property badges
 * @type Array
 */
M.availability_badge.form.badges = null;

/**
 * Is the <datalist> natively available in the browser? (we'll fallback to <select> if not)
 */
M.availability_badge.form.nativedatalist = ('list' in document.createElement('input')) &&
    !!( document.createElement('datalist') && window.HTMLDataListElement );

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} badges Array of objects containing badgeid => name
 */
M.availability_badge.form.initInner = function(badges) {
    this.badges = badges;
};

M.availability_badge.form.getNode = function(json) {
    // Create HTML structure.
    if (this.nativedatalist) {
        var html = '<label>' + M.util.get_string('title', 'availability_badge') +
            '<input  type="text" name="id" list="badges" placeholder="' +
            M.util.get_string('choosedots', 'moodle') + '"></input><span class="form-autocomplete-downarrow">▼</span>'
        html += '<datalist id="badges">';
    }
    else {
        var html = '<label>' + M.util.get_string('title', 'availability_badge') +
            '<span class="availability-badge"><select name="id">' +
            '<option id="0" value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    }

    for (var i = 0; i < this.badges.length; i++) {
        var badge = this.badges[i];
        // String has already been escaped using format_string.
        html += '<option id="' + badge.id + '" value="' + badge.name + ' [' + badge.id + ']">'
              + badge.name + ' [' + badge.id + ']</option>';
    }

    if (this.nativedatalist) {
        html += '</datalist></label>';
    }
    else {
        html += '</select></span></label>';
    }
   

    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values (leave default 'choose' if creating afresh).
    if (json.creating === undefined) {
        if (this.nativedatalist) {
            var dataitem = node.one('#badges > option[id="' + json.id + '"]');
            if (json.id !== undefined && dataitem) {
                node.one('input[name=id]').set('value', '' + dataitem.get('value'));
            }
        } else {
            var dataitem = node.one('select[name=id] > option[id="' + json.id + '"]');
            if (json.id !== undefined && dataitem) {
                dataitem.set('selected', 'selected');
            }
        }
    }

    // Add event handlers (first time only).
    if (!M.availability_badge.form.addedEvents) {
        M.availability_badge.form.addedEvents = true;
        var root = Y.one('#fitem_id_availabilityconditionsjson');
        // Just update the form fields.
        if (this.nativedatalist) {
            root.delegate('input', function () {
                M.core_availability.form.update();
            }, '.availability_badge input');
        } else {
            root.delegate('change', function () {
                M.core_availability.form.update();
            }, '.availability_badge select');
        }
    }

    return node;
};

M.availability_badge.form.fillValue = function(value, node) {
    if (this.nativedatalist) {
        var selected = node.one('input[name=id]').get('value');
        if (node.one('#badges option[value="' + selected + '"]')) {
            var item = node.one('#badges option[value="' + selected + '"]').get('id');
            value.id = parseInt(item, 10);
        }
    } else {
        var selected = node.one('select[name=id]').get('value');
        if (node.one('select[name=id] > option[value="' + selected + '"]')) {
            var item = node.one('select[name=id] > option[value="' + selected + '"]').get('id');
            value.id = parseInt(item, 10);
        }
    }
};

M.availability_badge.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check badge item id.
    if (!value.id || (value.id && value.id === 'choose')) {
        errors.push('availability_badge:error_selectbadge');
    }
};