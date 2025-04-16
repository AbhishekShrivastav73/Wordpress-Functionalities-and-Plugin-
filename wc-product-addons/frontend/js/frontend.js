jQuery(document).ready(function($) {
    // Debug: Confirm script loaded
    console.log('WC Addons Frontend JS Loaded');

    // Function to calculate and update total price
    function updateTotalPrice() {
        var basePrice = parseFloat(wc_addons_params.base_price) || 0;
        var addonTotal = 0;
        var quantity = parseInt($('.quantity input.qty').val()) || 1;

        // Checkbox add-ons
        $('.addon-checkbox:checked').each(function() {
            var price = parseFloat($(this).data('price')) || 0;
            addonTotal += price;
        });

        // Radio add-ons
        $('.addon-radio:checked').each(function() {
            var price = parseFloat($(this).data('price')) || 0;
            addonTotal += price;
        });

        // Select add-ons
        $('.addon-select').each(function() {
            var selected = $(this).find('option:selected');
            var price = parseFloat(selected.data('price')) || 0;
            addonTotal += price;
        });

        var totalPrice = (basePrice + addonTotal) * quantity;
        // Format price with currency symbol
        $('#addon-total-price').text(wc_addons_params.currency_symbol + totalPrice.toFixed(2));
        console.log('Total calculated: Base=' + basePrice + ', Addons=' + addonTotal + ', Quantity=' + quantity + ', Total=' + totalPrice);
    }

    // Initial calculation
    updateTotalPrice();

    // Event listeners
    $(document).on('change', '.addon-checkbox', function() {
        console.log('Checkbox changed');
        updateTotalPrice();
    });

    $(document).on('change', '.addon-radio', function() {
        console.log('Radio changed');
        updateTotalPrice();
    });

    $(document).on('change', '.addon-select', function() {
        console.log('Select changed');
        updateTotalPrice();
    });

    // Quantity change
    $(document).on('change input', '.quantity input.qty', function() {
        console.log('Quantity changed to: ' + $(this).val());
        updateTotalPrice();
    });
});