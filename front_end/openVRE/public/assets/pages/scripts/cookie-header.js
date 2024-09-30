var baseURL = $('#base-url').val();
if(Cookies.get('currentPage')) {
		location.href = Cookies.get('currentPage');	
} else {
		location.href = baseURL + 'home/';	
}

