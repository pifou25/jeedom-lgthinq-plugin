# Plugin LGThinq

# Installation

Vous pouvez l'installer via le Market, ou bien [l'install jeedom / github](https://github.com/NextDom/NextDom/wiki/Comment-faut-il-configurer-la-source-github-pour-Jeedom-%3F) avec ces infos:

ID logique du plugin = **lgthinq**

utilisateur = **pifou25**

nom du dépot = **jeedom-lgthinq-plugin**

branche = **master**

Une fois installé, vous pouvez **activer** le plugin. Puis faire une **installation des dépendances**, c'est indispensable la première fois! Cette installation est très longue sur une box smart ou sur un jeedom v3.x non migré sous la version Buster de Linux, laissez donc tourner l'installation des dépendances jusqu'à 1H si besoin ! (beaucoup plus rapide sur buster)

## Configuration en 3 étapes

### Choisir le pays et la langue

Le Pays est sur 2 lettres majuscule (FR) et la langue est une combinaison langue + pays (fr_FR ou fr_CA ...)

Puis cliquer sur le bouton vert "Gateway" : ceci doit ouvrir une popup sur le portail LG Account correspondant au choix.

### S'identifier sur le site LG

_Si le Gateway ne vous a pas redirigé vers le portail LG account ( popup bloquée par votre navigateur?) cliquez sur le bouton "LG Account Login"_ :) 

Une fois authentifié sur le portail LG, ceci doit vous rediriger vers une page toute blanche, ce n'est pas une erreur! récupérer l'URL de cette page, et la copier / coller dans la configuration jeedom / step 2

_Cette URL est de la forme:_ `https://fr.m.lgaccount.com/login/iabClose?access_token=<token1>&refresh_token=<token2>&oauth2_backend_url=https://gb.lgeapi.com/`

### Refresh Token

Ceci afin de pousser le paramètre (l'URL de redirection contient le token LG) et finaliser la configuration. Le bouton "Sauvegarder" a le même effet.

Voilà, vous pouvez tester avec le ping server que tout a bien fonctionné, le bouton "ping" vous donne les infos suivantes:

LgThinq plugin **server ok**, running since **Sun Jun 14 2020 16:16:27** GMT+0200 (heure d’été d’Europe centrale), token config is **true**

Vous pouvez aussi modifier le port utilisé par le serveur (5025 par défaut). _Et l'URL pour les utilisateurs avertis (si vous lancez manuellement le serveur ailleurs...)_

### Daemon LGThinq

Un _Démon_ est utilisé, c'est un processus qui surveille les communications entre Jeedom et l'API (interface) LG. Vous pouvez modifier le port par défaut en cas de conflit avec un autre processus.

### Paramétrage Jeedom

Aucun paramétrage pour l'instant.

# Utilisation

## Synchronisation

Vous pouvez utiliser ce bouton pour **découvrir** les appareils LG connectés sur votre compte LG et les importer dans Jeedom.

Dans la popup, cocher les appareils à importer, et leur affecter le bon modèle de configuration dans la liste.

**enregistrer** puis faire **F5 pour mettre à jour la page** avec les nouveaux objets générés. Pour le moment, seules les données d'information sont disponibles, les commandes ne sont pas encore possibles.

## Actualisation

Pour l'instant, l'actualisation se fait uniquement via le _cron_ de Jeedom, vous pouvez choisir la fréquence du cron (de 1 min à ... quotidien)

# Liens Utiles

[Aide](https://pifou25.github.io/jeedom-lgthinq-plugin/fr_FR/)
[Dépôt Github](https://github.com/pifou25/jeedom-lgthinq-plugin)
