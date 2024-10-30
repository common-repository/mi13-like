/* 
mi13_like_admin script 0.3
*/

jQuery(function ($) {
  $('ul.nav-tab-wrapper').on('click', 'li:not(.nav-tab-active)', function() {
    $(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active').closest('div.tabs').find('div.tabs__content').hide().eq($(this).index()).show();
  });
  $('#mi13_like_list').on("click", "a.page-numbers", function(event){
		event.preventDefault();
		$.ajax({
			url: ajaxurl+'?action=mi13_like_pagination',
			dataType: "json",
			type: "post",
			data: {"url": $(this).attr('href'),"nonce": mi13_like_admin.nonce},
			timeout: 5000,
			success: function(resp) {
				if(resp["data"]) $('#mi13_like_list').empty().html(resp["data"]);
				else 	$('#mi13_like_list').html("<p>Request error!</p>");
			}
		});
	});
});


