jQuery(document).ready(function($) {
    // Trigger cart update when payment method changes
    $(document.body).on('change', 'input[name="payment_method"]', function() {
        // Update the chosen payment method in session
        var chosen_method = $(this).val();
        
        // Trigger cart recalculation
        $('body').trigger('update_checkout');
        
        // Also trigger cart update if on cart page
        if ($('body').hasClass('woocommerce-cart')) {
            $('body').trigger('wc_update_cart');
        }
    });
    
    // Handle payment method selection in checkout
    $(document.body).on('updated_checkout', function() {
        // Ensure the cart is recalculated after checkout update
        setTimeout(function() {
            $('body').trigger('wc_fragment_refresh');
        }, 100);
    });
    
    // Handle AJAX cart updates
    $(document.body).on('wc_fragments_refreshed', function() {
        // Recalculate discounts after fragments are refreshed
        $('body').trigger('update_checkout');
    });
});
