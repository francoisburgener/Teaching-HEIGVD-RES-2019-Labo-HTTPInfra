<?php
// on en met une de chaque pour montrer le load-balancing
	$DYNAMIC_APP_A = getenv('DYNAMIC_APP_A');
	$DYNAMIC_APP_B = getenv('DYNAMIC_APP_B');
	$STATIC_APP_A = getenv('STATIC_APP_A');
	$STATIC_APP_B = getenv('STATIC_APP_B');

?>

<VirtualHost *:80>
    ServerName demo.res.ch
	
	<Location "/balancer-manager">
		SetHandler balancer-manager
		//Require host example.com pas besoin ici
	</Location>

    <Proxy "balancer://dynamic-cluster">
        BalancerMember 'http://<?php print "$DYNAMIC_APP_A"?>/'
        BalancerMember 'http://<?php print "$DYNAMIC_APP_B"?>/'
    </Proxy>
	
    ProxyPass "/api/adresses/" "balancer://dynamic-cluster/"
    ProxyPassReverse "/api/adresses/" "balancer://dynamic-cluster/"
	
	<Proxy "balancer://static-cluster">
        BalancerMember 'http://<?php print "$STATIC_APP_A"?>/'
        BalancerMember 'http://<?php print "$STATIC_APP_B"?>/'
    </Proxy>
    
    ProxyPass "/" "balancer://static-cluster/"
    ProxyPassReverse "/" "balancer://static-cluster/"
</VirtualHost>