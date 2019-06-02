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