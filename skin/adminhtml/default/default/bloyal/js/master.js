
function runAllbLoyalActions(url){

	new Ajax.Request(url, {
		  method: 'get',
		  onSuccess: function(transport) {
		    location.reload();
		  }
	 
	});	
}
