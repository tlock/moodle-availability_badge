YUI.add('moodle-availability_badge-form', function (Y, NAME) {

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
    var html = '<label>' + M.util.get_string('title', 'availability_badge') + ' <span class="availability-badge">' +
            '<select name="id">' +
            '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.badges.length; i++) {
        var badge = this.badges[i];
        // String has already been escaped using format_string.
        html += '<option value="' + badge.id + '">' + badge.name + '</option>';
    }
    html += '</select></span></label>';
    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values (leave default 'choose' if creating afresh).
    if (json.creating === undefined) {
        if (json.id !== undefined &&
                node.one('select[name=id] > option[value=' + json.id + ']')) {
            node.one('select[name=id]').set('value', '' + json.id);
        }
    }

    // Add event handlers (first time only).
    if (!M.availability_badge.form.addedEvents) {
        M.availability_badge.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Just update the form fields.
            M.core_availability.form.update();
        }, '.availability_badge select');
    }

    return node;
};

M.availability_badge.form.fillValue = function(value, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        value.id = 'choose';
    } else if (selected !== 'any') {
        value.id = parseInt(selected, 10);
    }
};

M.availability_badge.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check badge item id.
    if (value.id && value.id === 'choose') {
        errors.push('availability_badge:error_selectbadge');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
