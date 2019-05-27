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

