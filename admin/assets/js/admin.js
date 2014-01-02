(function ( $ ) {
	"use strict";

	$(function () {

		$('#piwp_populate_users').on('click', function(){
			var data = {
				action 		: 'piwp_populate_users'
			};
			$.post(ajaxurl, data, function(response) {
				console.log(response);
			});
		});

		$('#piwp_populate_posts').on('click', function(){
			var data = {
				action 		: 'piwp_populate_posts'
			};
			$.post(ajaxurl, data, function(response) {
				console.log(response);
			});
		});	
	});

}(jQuery));

