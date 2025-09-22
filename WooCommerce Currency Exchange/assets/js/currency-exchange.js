(function($){
  function refreshWoo(){
    // Refresh cart fragments
    if (typeof $.fn.block === 'function' && $(document.body).trigger) {
      $(document.body).trigger('wc_fragment_refresh');
      $(document.body).trigger('update_checkout');
      $(document.body).trigger('updated_wc_div');
    }
    // As a fallback, reload the page after a short delay
    setTimeout(function(){
      window.location.reload();
    }, 400);
  }

  $(document).on('change', '#wcce-select', function(){
    var code = $(this).val();
    $.post(WCCE.ajax_url, {
      action: 'wc_ce_set_currency',
      nonce: WCCE.nonce,
      code: code
    }).done(function(resp){
      if (resp && resp.success) {
        refreshWoo();
      } else {
        console.warn('Failed to set currency', resp);
        refreshWoo();
      }
    }).fail(function(err){
      console.error('Currency AJAX error', err);
      refreshWoo();
    });
  });
})(jQuery);
