(function($) {
    $(document).ready(function() {
        // Debug: Confirm script loaded
        console.log('WC Addons Admin JS Loaded');

        // Initialize group index
        var groupIndex = $('.addon_group').length > 0 ? parseInt($('.addon_group').last().data('group-index')) + 1 : 0;

        // Add new add-on type
        $(document).on('click', '.add_new_addon_group', function() {
            console.log('Add More Addon Type clicked, new index: ' + groupIndex);
            var group = `
                <div class="addon_group" data-group-index="${groupIndex}">
                    <h4>Add-On Type ${groupIndex + 1} <button type="button" class="button remove_addon_group">Remove Type</button></h4>
                    <p class="form-field">
                        <label>Display Type</label>
                        <select name="_addon_groups[${groupIndex}][display_type]">
                            <option value="checkbox">Checkbox</option>
                            <option value="radio">Radio Button</option>
                            <option value="select">Dropdown</option>
                        </select>
                    </p>
                    <div class="addons_list">
                        <div class="addon_row">
                            <p class="form-field">
                                <label>Add-On Name</label>
                                <input type="text" name="_addon_groups[${groupIndex}][addons][0][name]" />
                            </p>
                            <p class="form-field">
                                <label>Add-On Price</label>
                                <input type="number" step="0.01" name="_addon_groups[${groupIndex}][addons][0][price]" />
                            </p>
                            <p class="form-field">
                                <button type="button" class="button remove_addon">Remove</button>
                            </p>
                        </div>
                    </div>
                    <button type="button" class="button add_new_addon">Add Another Add-On</button>
                </div>`;
            $('.addon_groups').append(group);
            groupIndex++;
        });

        // Remove add-on type
        $(document).on('click', '.remove_addon_group', function() {
            console.log('Remove Addon Type clicked');
            var group = $(this).closest('.addon_group');
            var groups = $('.addon_group');
            if (groups.length > 1) {
                group.remove();
            } else {
                alert('At least one add-on type is required.');
            }
        });

        // Add new add-on
        $(document).on('click', '.add_new_addon', function() {
            console.log('Add Another Add-On clicked');
            var group = $(this).closest('.addon_group');
            var groupIndex = parseInt(group.data('group-index'));
            var addonIndex = group.find('.addon_row').length;
            var row = `
                <div class="addon_row">
                    <p class="form-field">
                        <label>Add-On Name</label>
                        <input type="text" name="_addon_groups[${groupIndex}][addons][${addonIndex}][name]" />
                    </p>
                    <p class="form-field">
                        <label>Add-On Price</label>
                        <input type="number" step="0.01" name="_addon_groups[${groupIndex}][addons][${addonIndex}][price]" />
                    </p>
                    <p class="form-field">
                        <button type="button" class="button remove_addon">Remove</button>
                    </p>
                </div>`;
            group.find('.addons_list').append(row);
        });

        // Remove add-on
        $(document).on('click', '.remove_addon', function() {
            console.log('Remove Add-On clicked');
            var row = $(this).closest('.addon_row');
            var addons = row.closest('.addons_list').find('.addon_row');
            if (addons.length > 1) {
                row.remove();
            } else {
                alert('At least one add-on is required per type.');
            }
        });
    });
})(jQuery);