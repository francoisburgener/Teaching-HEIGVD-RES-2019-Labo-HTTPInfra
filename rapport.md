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

