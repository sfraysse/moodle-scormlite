> **ScormLite** est un plugin pour Moodle permettant le déploiement, la consultation et le suivi de contenus SCORM simplifiés, conformes aux principes de SCORM Lite (cf http://scormlite.com).


# Versions

Vous êtes sur la page de la **version 3.4.6** du plugin ScormLite, dernière version compatible avec **Moodle 3.4**.

Ce plugin existe aussi pour les versions suivantes de Moodle :
- **Moodle 3.5** : [ScormLite 3.5](https://github.com/sfraysse/moodle-scormlite/tree/3.5)
- **Moodle 3.6** : [ScormLite 3.6](https://github.com/sfraysse/moodle-scormlite/tree/3.6)


# Installation


## Pré-requis

- Moodle version 3.4.x


## Procédure d'installation

- Télécharger la dernière version du plugin : https://github.com/sfraysse/moodle-scormlite/archive/v3.4.6.zip.
- Dans `Moodle > Administration > Plugins > Install plugins`, importer le fichier ZIP du plugin.
- Suivre la procédure d'installation.


## Paramétrage général du plugin

En dehors des réglages par défaut pour les activités nouvellement créées, on trouve :

- **Display close button** - Afficher un bouton de sortie de l'activité au dessus du Player SCORM lorsque l'affichage est non fenêtré.

- **Display rank** - Afficher une colonne de classement des apprenants dans la page de suivi.

- **Protect from session timeout** - Maintenir la session active durant la consulation d'un contenu SCORM Lite.

- **Get a debug file** - Autoriser la récupération d'un fichier de débogage.

- **Set a debug file** - Autoriser l'import d'un fichier de débogage.

- **Record error logs** - Autoriser l'écriture d'un journal d'erreurs.


## Permissions associées au plugin

- **mod/scormlite:reviewmycontent** - Autoriser l’utilisateur à accéder à sa propre copie corrigée. Par défaut, cette fonction est autorisée pour tous les rôles.

- **mod/scormlite:reviewothercontent** - Autoriser l’utilisateur à accéder à la copie corrigée des autres apprenants. Par défaut, cette fonction est interdite pour les apprenants.

- **mod/scormlite:viewmyreport** - Autoriser l’utilisateur à accéder à son rapport d’avancement. Par défaut, cette fonction est autorisée pour tous les rôles.

- **mod/scormlite:viewotherreport** - Autoriser l’utilisateur à accéder aux rapports d’avancement des autres apprenants. Par défaut, cette fonction est interdite pour les apprenants.

- **mod/scormlite:debugget** - Autoriser l’utilisateur à récupérer un fichier de débogage. Par défaut, cette fonction n’est autorisée que pour le rôle `Course Manager`.

- **mod/scormlite:debugset** - Autoriser l’utilisateur à importer un fichier de débogage. Par défaut, cette fonction n’est autorisée que pour le rôle `Course Manager`.


# Utilisation 


## Paramétrage d'une activité

En dehors des réglages communs à toutes les activités Moodle, on trouve :

- **Code** - Code de l'activité repris dans la page de suivi.

- **Package file** - Fichier ZIP contenant le contenu SCORM Lite.

- **Availability / From / Until** - Disponibilité de l'activité.

- **Max time** - Temps maximum autorisé pour terminer l'activité.

- **Passing score** - Seuil de réussite.

- **Display in** - Affichage de l'activité.

- **Display chronometer** - Affichage du chronomètre au sein du contenu SCORM Lite.

- **Number of attempts** - Nombre d'essais autorisés.

- **Scoring method** - Score retenu lorsque plusieurs essais sont autorisés.

- **Prevent new attempts after success** - Interdire un nouvel essai dès lors que l'activité est réussie.

- **Review access** - Accès à la copie corrigée.

- **Quetzal statistics access** - Accès aux statistiques Quetzal.

- **Reporting colors** - Couleurs à appliquer dans la page de suivi.


## Consultation de l'activité

Lorsque l’utilisateur lance l'activité, plusieurs cas sont possibles :

- **Si l’activité est indisponible**, un message approprié est affiché et le contenu ne peut être lancé.

- **Si l’activité est accessible et n’a jamais été lancée**, la page affiche le nombre de tentatives autorisées, le nombre de tentatives terminées, ainsi qu’un bouton `Start`.

- **Si le contenu a déjà été consulté**, la page affiche le nombre de tentatives autorisées, le nombre de tentatives terminées, l’état de complétion, le temps passé, éventuellement un score, les dates et heures de début et de fin.

- **Si la consultation du contenu n’est pas terminée (complétion)**, alors un bouton `Resume` est affiché.

- **Si la consultation du contenu est terminée et que de nouvelles tentatives sont autorisées**, alors un bouton `New Attempt` est affiché.

- **Si la consultation du contenu est terminée et que l'accès à la copie corrigée est autorisé**, alors un bouton `Review` est affiché.


## Rapport de suivi

Lorsque l’utilisateur est autorisé à accéder au rapport de suivi, un onglet `Report` est affiché. 

Il conduit à la page de suivi de l'activité contenant pour chaque apprenant :

- Son image et son nom ;
- La tentative retenue par rapport au nombre de tentatives autorisées ;
- Les date et heure de début de consultation ;
- La durée de consultation ;
- L’état de consultation (complétion ou réussite) ;
- Eventuellement un score, avec accès à la copie corrigée si cet accès est autorisé ; 
- Eventuellement le classement de l'apprenant ;
- Eventuellement l’accès aux fonctions de débogage ;

Ce rapport permet aussi :
- De voir la moyenne moyenne des scores obtenus ;
- De supprimer les données de certains utilisateurs.

