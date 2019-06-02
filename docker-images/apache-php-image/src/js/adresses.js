$(function(){
	function loadAddresses(){
		console.log("Loading addresses");
		$.getJSON("/api/adresses/",function(addresses){
			var message = "No adresses !"
			
			if(addresses.length > 0){
				message = addresses[0].street + " " + addresses[0].city + " " + addresses[0].postal + " " + addresses[0].country;
			}
			
			$(".HTTPInfra").text(message);
		});
	};
	console.log("init loading addresses")
	setInterval(loadAddresses,1000);
});