https://pifou25.github.io/jeedom-lgthinq-plugin/ \[[French doc](https://pifou25.github.io/jeedom-lgthinq-plugin/fr_FR)\]

# Plugin LGThinq

Ce plugin permet de relier vos équipements LG Smart-Thinq avec Jeedom. 
Il n'y a pas de possibilité directe d'inclure les appareils directement
 dans Jeedom, vous devez vous inscrire sur le portail LG et inclure 
 chaque appareil via l'application propriétaire LG.
 
Jeedom via ce plugin interroge le "cloud" LG via leur API propriétaire,
 pour recevoir l'état et renvoyer des ordres aux appareils.

# Installation

Installer le plugin via le Market puis l'activer.

## Configuration

Il est tout d'abords nécessaire de lancer l'installation des dépendances.

Ensuite vous pourrez démarrer le daemon, le script qui tourne en permanence
 en arrière plan pour maintenir la liaison avec le cloud LG.
 
### Daemon LGThinq

Le daemon n'est pas accessible de l'extérieur, il permet juste à Jeedom 
d'interroger le cloud LG et recevoir les notifications.

# Paramétrage Jeedom

## Configuration en 3 étapes

### Choisir le pays et la langue

Le Pays est sur 2 lettres majuscule (FR) et la langue est une combinaison 
langue + pays (fr_FR ou fr_CA ...)

Puis cliquer sur le bouton "Gateway" : ceci doit ouvrir une popup sur 
le portail LG Account correspondant au choix.

### S'identifier sur le site LG

Si le Gateway ne vous a pas redirigé vers le portail LG account ( popup bloquée 
par votre navigateur?) cliquez sur le bouton "LG Account Login" :)

Une fois authentifié sur le portail LG, ceci doit vous rediriger vers 
une page toute blanche, ce n'est pas une erreur! récupérer l'URL de 
cette page, et la copier / coller dans la configuration jeedom / step 2

Cette URL est de la forme: 
`https://fr.m.lgaccount.com/login/iabClose?access_token=<token1>&refresh_token=<token2>&oauth2_backend_url=https://gb.lgeapi.com/`

### Refresh Token

Ceci afin de pousser le paramètre (l'URL de redirection contient le token LG) 
et finaliser la configuration. Le bouton "Sauvegarder" a le même effet.

Voilà, vous pouvez tester avec le ping server que tout a bien fonctionné, 
le bouton "ping" vous donne les infos suivantes:

```
LgThinq plugin server ok, running since Sun Jun 14 2020 16:16:27 GMT+0200 (heure d’été d’Europe centrale), token config is true
```

Vous pouvez aussi modifier le port utilisé par le serveur (5025 par défaut). 
Et l'URL pour les utilisateurs avertis (si vous lancez manuellement le serveur ailleurs...)

# Utilisation

## Synchronisation

Vous pouvez utiliser ce bouton pour découvrir les appareils LG connectés sur 
votre compte LG et les importer dans Jeedom.

Dans la popup, cocher les appareils à importer, et leur affecter le bon modèle 
de configuration dans la liste.

enregistrer puis faire F5 pour mettre à jour la page avec les nouveaux objets générés.

## Actualisation

# Liens Utiles

\[[ChangeLog](https://pifou25.github.io/jeedom-lgthinq-plugin/fr_FR/changelog)\]