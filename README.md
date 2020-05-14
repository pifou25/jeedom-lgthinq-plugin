# jeedom-lgthinq-plugin

## Jeedom

Jeedom est un projet de **box domotique** évolutif qui permet de gérer de multiples protocoles
 et objets connectés de la maison.

Il s'appuie principalement sur un *market store* proposant des plugins interface avec toute sorte de protocoles et objets.

Le "core" jeedom est également un projet opensource disponible sur github: 
https://github.com/jeedom/core

La société jeedom commercialise des box prêtes à l'emploi, ainsi qu'un SAV, et quelques plugins "officiels" 
(qui peuvent être payant). Mais il est tout à fait possible de monter sa propre box jeedom à partir 
d'un Raspberry Pi par exemple, puis acheter les plugins payants, et / ou installer ses propres plugins.

## LG Smart-Thinq

LG propose une application propriétaire pour ses appareils connectés **Smart-Thinq**, mais également une **API** permettant 
de communiquer avec ces appareils. Il est donc possible pour les développeurs d'implémenter cette API pour monitorer 
et commander mon frigo, ma télé ou mon lave-linge.

Il existe déjà une API mais hélas, celle-ci est en **python** :

* https://pypi.org/project/wideq/ ( [lien github](https://github.com/sampsyo/wideq) )

Tandis que jeedom est un projet **PHP** 

Le but de ce plugin est donc simplement d'exposer les possibilités de la lib wideq pour communiquer avec l'API LG, 
pour que jeedom puisse l'utiliser. Ce plugin se compose donc en 2 parties:

1. un **serveur flask python**, qui va exposer les fonctionnalités de wideq/LG, il s'agit donc de 
faire une API REST en python
2. un **plugin jeedom**, full PHP, permettant d'interroger cette API REST

### Prérequis

1. jeedom fonctionnel sur un serveur web + base de données
2. python > v3.6 pour la librairie wideq, avec les dépendances: flask, request
3. Docker
4. un compte LG et un appareil connecté

## Développement

1. Le code python : un simple script python / flask 

2. Le code PHP : doit respecter l'arborescence d'un plugin jeedom :
```
core
- php
- class
- js
- config
3rparty
desktop
- php
- class
- js
mobile
- php
- class
- js
resources
```

## Test et Déploiement

Pour pouvoir tester ce projet, une image docker sera faite, intégrant jeedom (serveur web + php + 
base mysql + le code jeedom). Plusieurs images pourront être utiliées pour simuler différents environnemnts:

1.Debian 9
* [ ] jeedom v3
* [ ] jeedom v4
2. Debian 10
* [ ] jeedom v3
* [ ] jeedom v4

D'autres mélanges et saveurs pourront être ajouté pour refléter l'ensemble du parc des box existantes.

Ensuite, il suffira de copier ce plugin dans le répertoire /var/www/html/plugins pour pouvoir le tester.

***

[Plus d'informations sur le wiki](https://github.com/pifou25/jeedom-lgthinq-plugin/wiki)
