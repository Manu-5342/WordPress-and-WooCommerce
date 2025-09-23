jQuery(function($){
  $('#wccs_currency_select').on('change',function(){
      document.cookie = "wccs_currency="+$(this).val()+"; path=/";
      location.reload();
  });
});