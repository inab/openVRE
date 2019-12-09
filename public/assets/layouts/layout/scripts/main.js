
function openTermsOfUse() {
//Decomment for tearms of use to work

	// $('#modalTerms').modal({ show: 'true' });

	// $.ajax({
	// 	type: "POST",
	// 	url: baseURL + "/applib/getTermsOfUse.php",
	// 	data:"id=1",
	// 	success: function(data) {

	// 		$('#modalTerms .modal-body .container-terms').html(data);
	// 	}
	// });
}


function checkSessionState() {
	$.ajax({
		type: "POST",
		url: baseURL+"/applib/checkSession.php",
		data:"id=1",
		success: function(data) {
			var obj = JSON.parse(data);

			if(parseInt(obj.remaining) < 60) {
				$('#session-expire-top').show();
				$('#session-expire-top span').html(obj.remaining);
			}

			if(!obj.hasSession) {
				$('#session-expire-top').hide();
				$('#modalSessionExpired').modal({ show: 'true', backdrop: 'static', keyboard: false});
				$('#modalSessionExpired .modal-body #session-text').html('Your session has expired after ' + obj.duration + ' of inactivity, please log in again or keep using the MuG VRE as a non-registered user.');

			}
			
		}
	});

}

jQuery(document).ready(function() {
	// Optimalisation: Store the references outside the event handler:
    var $window = $(window);

	setInterval(checkSessionState, 5000);
	
	var menu_toggler = false;

    function checkWidth() {
        if(!menu_toggler) {
			var windowsize = $window.width();
			if (windowsize < 1400) {
				$('body').addClass('page-sidebar-closed');
				$('ul.page-sidebar-menu').addClass('page-sidebar-menu-closed');
				$('.beta-short').show();
				$('.beta-long').hide();
				if ($.cookie) {
					$.cookie('sidebar_closed', '1');
				}	
			}else {
				$('body').removeClass('page-sidebar-closed');
				$('ul.page-sidebar-menu').removeClass('page-sidebar-menu-closed');
				$('.beta-short').hide();
				$('.beta-long').show();
				if ($.cookie) {
					$.cookie('sidebar_closed', '0');
				}	
			}
		}else{
			menu_toggler = false;
		}
    }
    // Execute on load
    checkWidth();
    // Bind event listener
    $(window).resize(checkWidth);

	$('.menu-toggler.sidebar-toggler').on('click', function() {
		
		if($('ul.page-sidebar-menu').hasClass('page-sidebar-menu-closed')) {
			$('.beta-short').hide();
			$('.beta-long').show();
		} else {
			$('.beta-short').show();
			$('.beta-long').hide();
		}

		menu_toggler = true;
	});

	// LOGOUT
	$('#logout-button').on('click', function() {

		if($("#type-of-user").val() == 3) {
			$('#modalLogoutGuest').modal({ show: 'true' });
		} else {
			App.blockUI({
				boxed: true,
				message: 'Logging out...'
			});
			// setTimeout(() => {
				
			
			$.ajax({
				type: "POST",
				url: baseURL + "/applib/logoutToken.php",
				data:"id=1",
				success: function(data) {
					d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
					if(d == '1'){
						setTimeout(function(){ location.href = baseURL ; }, 1000);	
					}else{
						App.unblockUI();
					}
				}
			});

		// }, 500000);
		}
	
	});


	$('#modalLogoutGuest').on('click', '.btn-ok', function(e) {

		$('#modalLogoutGuest').modal('hide');

		App.blockUI({
						boxed: true,
				message: 'Logging out...'
					});

			$.ajax({
				type: "POST",
				url: baseURL + "/applib/logoutToken.php",
				data:"id=1",
				success: function(data) {
					d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
					if(d == '1'){
						setTimeout(function(){ location.href = '/'; }, 1000);	
					}else{
						App.unblockUI();
					}
				}
			});
	});

	
});


