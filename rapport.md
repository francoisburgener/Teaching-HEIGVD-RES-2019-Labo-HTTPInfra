# RES- Labo : Infrastructure HTTP

## Etape 1 - Serveur HTTP statique (php)

Pour cela nous avons tout d'abors télécharger un template boostrape pour notre page html que nous mettant dans un dossier src. Ensuite on construit notre image docker avec l'image **php:5.6-apache** et nous copiant tout le contenu de src dans le dossier html de apache

**Dockerfile**

````dockerfile
FROM php:5.6-apache
COPY src/ /var/www/html/
````

**Command**

```
docker build -t res/apache_php
```

```
docker run -d -p 9090:80 res/apache_php
```

Ensuite il suffit d'aller dans le navigateur a l'adresse de votre vm . Sur docker l'adresse est **192.168.99.100**. Normalement vous pourrez voir votre page sur l'adresse suivante : **192.168.99.100:9090**

## Etape 2 - Serveur HTTP dynamique (javascript)

Pour cela nous avons créer un serveur http avec node. Nous avons utiliser le modules express pour faire cela.

```javascript
var Chance = require('chance');
var chance = new Chance();

var express = require('express');
var app = express();

app.get('/',function(req,res){
    res.send(generateAdress());
});

app.listen(3000,function(){
    console.log("Accepting HTTP resquest on port 3000");
});

function generateAdress(){
    var numberOfAdresses = chance.integer({
        min : 1,
        max : 10,
    });

    var adresses = [];

    for(var i = 0; i < numberOfAdresses; ++i){
        adresses.push({
            'street' : chance.street(),
            'city' : chance.city(),
            'postal' : chance.postal(),
            'country' : chance.country({full : true}),
        });
    }
    console.log(adresses);
    return adresses;
}
```

Pour pouvoir lancer notre serveur sur un container il faut d'abors préparer le Dockerfile qui va récupérer notre fichier javascript et l'exécuter

**Dockerfile**

```dockerfile
FROM node:4.4

COPY src /opt/app

CMD ["node","/opt/app/index.js"]
```

Ensuite il suffis de créer l'image :

```bash
docker build -t res/express_adresses .
```

et créer notre container

```bash
docker run -d -p 9091:3000 res/expess_adresses
```

Si l'on va sur le navigateur a l'adressse **192.168.99.199:9091** nous devrions voir les adresses générer par notre application. Il est aussi possible d'utiliser postman et telnet pour faire la même chose.

## Etape 3 - Reverse proxy (statique) 

Pour l'instant nous allons hardcodé les adresse IP de nos deux container dans le fichier de config du reverse proxy se qui n'est pas une bonne idée. Mais dans l'étape 5 nous alors faire un reverse proxy dynamic nous allons donc créer les deux fichier de config. un pour le default.

**000-default.conf**

```conf
<VirtualHost *:80>
</VirtualHost>
```

**001-reverse-proxy.conf**

```
<VirtualHost *:80>
    ServerName demo.res.ch

    ProxyPass "/api/addresses/" "http://172.17.0.3:3000/"
    ProxyPassReverse "/api/addresses/" "http://172.17.0.3:3000/"
    
    ProxyPass "/" "http://172.17.0.2:80/"
    ProxyPassReverse "/" "http://172.17.0.2:80/"
</VirtualHost>
```

Ensuite il suffis de de copier nos deux fichier de config sur notre container qui nous servira de reverse proxy

**Dockerfile**

```dockerfile
FROM php:5-6-apache

COPY conf/ /etc/apache2

RUN a2enmod proxy proxy_http
RUN a2ensite 000-* 001-*
```

Il faut maintenant créer l'image :

```
docker build -t res/apache_rp
```

Maintenant il faut relancer les container dans cette ordre pour avoir les bonne adresse IP car nous les avons hardcodé dans notre reverse proxy

```
docker run --name apache_static -d res/apache_php
docker run --name express_dynamic -d res/express_adresses
docker run -p 8080:80 --name apache_rp -d res/apache_rp
```

Comme notre reverse proxy nous demande comme hostname : **demo.res.ch** il n'est plus possible pour nous d'y aller via l'adresse IP **192.168.99.100:8080**. Il faut donc aller modifier notre fichier hosts. Sur windows le fichier hosts se trouve en **C:\Windows\System32\drivers\etc**

Il faudra rajouter la ligne suivant :

```
192.168.99.100 demo.res.ch
```

Dans le navitateur on peut maintenant utiliser http://demo.res.ch:8080 pour accéder au serveur apache et http://demo.res.ch:8080/api/adresses/ pour notre application javascript générant les adresses aléatoires.

## Etape 4 - Request AJAX (Jquery)

Pour cette etape 4 nous utilisons un script ajax qui nous permet de récuperer se que l'on a fait dans l'étape 2 pour l'intégrer dans notre site. Pour cela nous avons ajouter un script js dans le dossier js de notre site web. 

**adresses.js**

```javascript
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
```

Se script va récupérer une des solution faite par notre api et l'affiche a un endroit spécifique dans notre page web. Ici il va l'afficher a l'endroit ou l'en utilise la classe HTTPInfra.

Une fois ces modification faite il faudra biensure rebuild notre image 

```
docker build -t res/apache_php
```

Et relancer notre containeur. Le mieux est de relancer l'intégralité de nos container car nous utilisons une configuration statique pour le reverse proxy

```
docker run --name apache_static -d res/apache_php
docker run --name express_dynamic -d res/express_adresses
docker run -p 8080:80 --name apache_rp -d res/apache_rp
```

## Etape 5 - Reverse proxy (dynamique)

Pour configurer notre reverse proxy de manière dynamique il nous faudra ajouter des variable d'environnement lors de la création du container du reverse proxy. Ces variable d'environnement seront les adresses de nos deux services (api et notre page web statique). Ensuite nous pourons les récuperer avec un script php qui sera identique a notre fichier de configuration du reverse proxy

**config-template.php**

```php
<?php
	$DYNAMIC_APP = getenv('DYNAMIC_APP');
	$STATIC_APP = getenv('STATIC_APP');
?>

<VirtualHost *:80>
    ServerName demo.res.ch

    ProxyPass "/api/adresses/" "http://<?php print $DYNAMIC_APP ?>/"
    ProxyPassReverse "/api/adresses/" "http://<?php print $DYNAMIC_APP ?>/"
    
    ProxyPass "/" "http://<?php print $STATIC_APP ?>/"
    ProxyPassReverse "/" "http://<?php print $STATIC_APP ?>/"
</VirtualHost>
```

Ensuite nous devons utiliser le fichier apache2-forground afin d'executé notre script php et copier ça solution dans le fichier de configuration de notre reverse proxy.

**apache2-forground**

```
#!/bin/bash
set -e


# Custom SetUp

echo "Setup for the RES lab..."
echo "Static app URL: $STATIC_APP"
echo "Dynamic app URL: $DYNAMIC_APP"

php /var/apache2/templates/config-template.php > /etc/apache2/sites-available/001-reverse-proxy.conf

# Note: we don't just use "apache2ctl" here because it itself is just a shell-script wrapper around apache2 which provides extra functionality like "apache2ctl start" for launching apache2 in the background.
# (also, when run as "apache2ctl <apache args>", it does not use "exec", which leaves an undesirable resident shell process)

: "${APACHE_CONFDIR:=/etc/apache2}"
: "${APACHE_ENVVARS:=$APACHE_CONFDIR/envvars}"
if test -f "$APACHE_ENVVARS"; then
	. "$APACHE_ENVVARS"
fi

# Apache gets grumpy about PID files pre-existing
: "${APACHE_RUN_DIR:=/var/run/apache2}"
: "${APACHE_PID_FILE:=$APACHE_RUN_DIR/apache2.pid}"
rm -f "$APACHE_PID_FILE"

# create missing directories
# (especially APACHE_RUN_DIR, APACHE_LOCK_DIR, and APACHE_LOG_DIR)
for e in "${!APACHE_@}"; do
	if [[ "$e" == *_DIR ]] && [[ "${!e}" == /* ]]; then
		# handle "/var/lock" being a symlink to "/run/lock", but "/run/lock" not existing beforehand, so "/var/lock/something" fails to mkdir
		#   mkdir: cannot create directory '/var/lock': File exists
		dir="${!e}"
		while [ "$dir" != "$(dirname "$dir")" ]; do
			dir="$(dirname "$dir")"
			if [ -d "$dir" ]; then
				break
			fi
			absDir="$(readlink -f "$dir" 2>/dev/null || :)"
			if [ -n "$absDir" ]; then
				mkdir -p "$absDir"
			fi
		done

		mkdir -p "${!e}"
	fi
done

exec apache2 -DFOREGROUND "$@"
```

Une fois tout cela fait nous pouvons rebuild l'image de notre reverse proxy

```
docker build -t res/apache_rp .
```

Maintenant nous lançons nos deux container afin de récuperer leur adresse IP. 

```
docker run -d --name apache_static res/apache_php
docker run -d --name express_adresses res/express_adresses
```

Pour récupérer ces adresse il faut faire la commande suivante : 

```
docker inspect apache_static | grep -i ipaddr
docker inspect express_adresses | grep -i ipaddr
```

Une fois les adresse ip récuperer nous pouvons créer nos variable d'environnement lors de la création de notre container reverse proxy. Dans notre cas les deux adresse ip sont STATIC_APP=172.17.0.2 et DYNAMIC_APP=172.17.0.3

```
docker run -d -e STATIC_APP=172.17.0.2:80 -e DYNAMIC_APP=172.17.0.3:3000 -p 8080:80 --name apache_rp res/apache_rp

```

Une fois cette commande faite vous pourrez retourner sur le site via l'adresse  http://demo.res.ch:8080 et voir le site avec notre script ajax tourné. De plus a l'adresse  http://demo.res.ch:8080/api/adresses/ vous pourrez récuperer les valeur gérerer par notre api

## Bonus gestion par interface graphique (UI) 

Pour cette partie nous avons décidéd'utiliser l'outil [Portainer](https://portainer.io/index.html), qui permet de gérer simplement et rapidement nos conteneurs et images docker via une interface web.

```
docker volume create portainer_data
docker run -d -p 9000:9000 -v portainer_data:/data portainer/portainer
```

Une fois ces commandes saisies, il suffit d'accéder à l'adresse de docker, sur le port 9000, pour commencer à gérer nos conteneurs.