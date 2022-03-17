# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).

## [7.0.37] - 14-12-2021
- Retrait du jeton de limitation des requêtes. Trop problématique

## [7.0.36] - 10-12-2021
- Correction de la reprise des données sur les Salariés et la Forme juridique

## [7.0.35] - 08-12-2021
- Correction de l'affichage de l'erreur lors de la recherche des informations du tiers

## [7.0.34] - 03-12-2021
- Correction de la récupération du nom de l'entreprise morale / physique

## [7.0.33] - 05-10-2021
- Correction de la récupération du code d'activité de l'entreprise et non de l'unité légale

## [7.0.32] - 20-09-2021
- Suppression du patch introduit dans la version 7.0.28 de Sirene maintenant que ce patch a été introduis dans le core de Dolibarr (v12 et +)
- Ajout de la récupération des données concernant les effectifs et le type d'entité légale

## [7.0.31] - 23-07-2021
- Données Dénomination sociale récupérées au niveau de l'établissement secondaire plutôt qu'au niveau de l'établissement principal

## [7.0.30] - 20-07-2021
- Compatibilité v14.0.x
- Correction du lien vers le site de vérification RNCS suite à une évolution du site du gouvernement (Ajout d'un "entreprise/" dans l'URL)
- Intégration d'un patch core pour les versions 13.0.0 à 13.0.3.
  Problème de sélecteur lors d'une recherche Sirene avec plusieurs résultats, uniquement le 1er résultat est repris même si on sélectionne un autre résultat.
  Patch : https://github.com/Dolibarr/dolibarr/pull/17701
  
  Problème réglé si vous êtes en version 13.0.4. Les autres branches de Dolibarr ne sont pas concernées par ce problème

## [7.0.29] - 09-07-2021
- Correction récupération code pays lors de la mise à jour d'une fiche
- Désactivation par défaut de la tâche planifiée lors de l'activation du module

## [7.0.28] - 05-07-2021
- Correction affichage z-index pour la fenêtre de vérification des tiers 

## [7.0.27] - 30-06-2021
- Données APE récupérées au niveau de l'établissement secondaire plutôt qu'au niveau de l'établissement principal

## [7.0.26] - 11-06-2021
- Correction des droits d'accès pour la mise à jour des données d'un tiers provenant de l'API Sirene

## [7.0.25] - 08-06-2021
- Correction erreur SQL pour l'accès au dictionnaire des codes Naf 

## [7.0.24] - 01-06-2021
- Affichage et correction du dictionnaire dans la configuration du module

## [7.0.23] - 21-05-2021
- Problème d'activation du jeton unique en multientité
- Ne pas afficher par défaut les champs supplémentaires Date Sirene & Etat MAJ Sirene lors de la création d'un tiers
- Intégration d'un patch core pour les versions 13.0.0 à 13.0.2.
  Problème de sélecteur lors d'une recherche Sirene avec plusieurs résultats, uniquement le 1er résultat est repris même si on sélectionne un autre résultat.
  Patch : https://github.com/Dolibarr/dolibarr/pull/17701

## [7.0.22] - 22-04-2021
- Correction balises de traduction

## [7.0.21] - 10-04-2021
- Supprimer les espaces lors de lors de la recherche d'un numéro Siret ou Siren
- Activation par défaut du jeton unique lors de l'activation du module
- Ajout d'une constante concernant la version du module Sirene
- Correctif sur la tâche planifiée
- Correctif sur la gestion du token
- Traduction

## [7.0.20] - 04-03-2021
- Ajout d'un message d'alerte sur un tiers fermé pour préciser de retirer le siret pour relancer une mise à jour des informations du tiers
- Correction du problème de calcul du numéro de TVA intracommunautaire lors d'une mise à jour d'un tiers

## [7.0.19] - 24-02-2021
- Ajout d'un contrôle planifié de vérification des données en lien avec l'API Sirene
- Correction problème sur les établissements fermés

## [7.0.18] - 23-07-2020
- Ajout d'une option dans le panneau de configuration pour choisir le lien de vérification du numéro siret (introduit dans Sirene 7.0.15)

## [7.0.17] - 06-07-2020
- Correction problème calcul numéro TVA intracommunautaire dans de rare cas (FRXX < 10)

## [7.0.16] - 01-07-2020
- Remplissage automatique du code du département

## [7.0.15] - 29-06-2020
- Modification de l'url de verification du numéro de SIRET

## [7.0.14] - 22-06-2020
- Ne pas tenir compte du Code NAF pour les pays hors France

## [7.0.13] - 16-04-2020
- Modification affichage bloc recherche répertoire SIRENE lors de la création du tiers pour les petits écrans.
- Compatibilité v12

## [7.0.12] - 12-02-2020
- Clarifie la recherche du code naf dans le service Sirene.
- Ajout d'une option pour n'afficher que les tiers qui n'ont jamais fermés

## [7.0.11] - 13-01-2020
- Fix "include vendor/autoload.php" avec d'autres modules.

## [7.0.10] - 26-11-2019
- Correction récupération des identifiants des dictionnaires (compatibilité PHP v7.2)

## [7.0.9] - 06-11-2019
- Correction du non chargement de la fiche tiers lors de la présence d'un code NAF non présent dans le dictionnaire des codes NAF
- Correction de l'accès au dictionnaire des codes NAF

## [7.0.8] - 21-10-2019
- Ajout du calcul du numéro de TVA intracommunautaire (Basé sur le numéro Siren)

## [7.0.7] - 07-10-2019
- Correction du chemin d'un include pour une fonction nécessitant la librairie codenaf. (Compatibilité v10)

## [7.0.6] - 30-09-2019
- Correction de la pérénité des valeures du formulaire de création du tiers à l'affichage du choix du tiers retourné par Sirene.

## [7.0.5] - 05-09-2019
- Fusion avec le module Code Naf.
- Corrections mineurs.

## [7.0.4] - 02-09-2019
- Correction de la gestion des erreurs lors de la requete a Sirene.

## [7.0.3] - 01-07-2019
- Correction de l'ajout des paramètres du formulaires lors de l'affichage de la boite de confirmation(affichage des resultats)
- Simplification du message d'erreur (avec option pour afficher l'erreur complète)

## [7.0.2] - 27-06-2019
- N'écrase les paramètres déjà renseignés

## [7.0.1] - 26-06-2019
- Sélectionne automatiquement la première société active trouvée

## [7.0.0] - 12-06-2019
- Version initiale.

[Non Distribué]: http://git.open-dsi.fr/dolibarr-extension/sirene/compare/v7.0.36...HEAD
[7.0.36]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.36
[7.0.35]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.35
[7.0.34]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.34
[7.0.33]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.33
[7.0.32]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.32
[7.0.31]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.31
[7.0.30]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.30
[7.0.28]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.28
[7.0.27]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.27
[7.0.26]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.26
[7.0.25]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.25
[7.0.24]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.24
[7.0.23]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.23
[7.0.22]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.22
[7.0.21]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.21
[7.0.20]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.20
[7.0.19]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.19
[7.0.18]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.18
[7.0.17]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.17
[7.0.16]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.16
[7.0.15]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.15
[7.0.14]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.14
[7.0.13]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.13
[7.0.12]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.12
[7.0.11]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.11
[7.0.10]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.10
[7.0.9]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.9
[7.0.8]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.8
[7.0.7]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.7
[7.0.6]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.6
[7.0.5]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.5
[7.0.4]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.4
[7.0.3]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.3
[7.0.2]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.2
[7.0.1]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.1
[7.0.0]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.0
