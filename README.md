# Synchronize Netflix with Trakt.tv

## Description

Permet de synchroniser le profil d'un utilisateur Netflix avec son compte Track.tv (https://trakt.tv/).

La synchronisation utilise le site BetaSeries (https://www.betaseries.com/) qui permet de lier son compte avec Netflix. L'application utilise l'API de BetaSeries pour récupérer les films et séries regardés sur Netflix et les marquent comme lus sur le compte Track.tv grâce à l'API proposé par Track.tv. (https://trakt.docs.apiary.io/#).

La synchronisation se fait périodiquement grâce à une tâche CRON à ajouter sur le serveur.

## Technos

- PHP 7.4.

- Materialize CSS (https://materializecss.com/).
- PHP Mailer 6.0 pour l'envoi du rapport de synchronisation.
- PHP Cron Scheduler 2.4 pour gérer la synchronisation périodique.