# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).

## [Non Distribué]

## [7.0.38] - 11-01-2022
- Compatibilité v14 (Trigger - Problème de class CommandeFournisseur)

## [7.0.37] - 20-12-2021
- Compatibilité v14 (Widget)

## [7.0.36] - 06-07-2021
- Corrections mineures sur les séparateurs de champs complémentaires

## [7.0.35] - 07-05-2021
- Correction de la gestion des tokens en multi entité

## [7.0.34] - 18-03-2021
- Ne supprime plus les informations du workflow EDEDOC si un workflow a ete deninit lors de la mise à jour du statut avec l'identifiant de la facture.
- Copie le champ "Transmission Facture le" dans le champ "Transmission EDEDOC le" la ou il a été éffacé.

## [7.0.33] - 07-01-2021
- Correction de la suppression des données de transmissions sur la facture clonée venant d'une facture transmisse à chorus.

## [7.0.32] - 06-01-2021
- Correction du collapse des extrafields de type séparateur.

## [7.0.31] - 13-10-2020
- Correction du widget

## [7.0.30] - 10-10-2020
- Correction instruction group by
- Correction de la methode de mise a jour du status de la facture chorus (rajout de l'id de la facture et de la date d'emission de la facture)

## [7.0.29] - 26-06-2020
- Légère correction lors de l'insertion des valeurs par défaut des dictionnaires.

## [7.0.28] - 22-06-2020
- Prise en compte de la nouvelle génération de clé par le module partner.

## [7.0.27] - 10-06-2020
- Correction pour l'envoi de facture d'acompte.

## [7.0.26] - 03-06-2020
- Suppression des données de transmissions sur la facture clonée venant d'une facture transmisse à chorus.

## [7.0.25] - 16-04-2020
- Correction du chargement de l'objet pour l'element de type invoice.

## [7.0.24] - 28-02-2020
- Bloque l'envoi vers chorus si le double du quota est dépassé.

## [7.0.23] - 20-01-2020
- Fix la possibilité d'envoyer les avoirs lorsque l'avoir est validé et non plus lorsqu'il est cloturé (Convertir en réduction future).

## [7.0.22] - 13-01-2020
- Fix "include vendor/autoload.php" avec d'autres modules.
- Correction de la methode d'accès a l'API.

## [7.0.21] - 20-12-2019
- Fix dictionnaire des modes de paiements, le champ 'Mode de paiement \[CHORUS\]' n'est plus obligatoirement unique.

## [7.0.20] - 26-11-2019
- Fix requête d'initialisation des valeurs des dictionnaires.

## [7.0.19] - 08-11-2019
- Fix message d'erreur si une exception se produit et que l'api n'a pas encore initialisé l'objet de réponse.

## [7.0.18] - 06-11-2019
- Correction du forcage de la génération du document PDF lors de la demande d'envoi vers chorus.

## [7.0.17] - 28-10-2019
- Ajout de l'option pour forcer la génération du document PDF lors de la demande d'envoi vers chorus.

## [7.0.16] - 25-10-2019
- Correction de la requête SQL lors de rafraichissement des status chorus grace au cron.
- Génération du document PDF s'il n'existe pas lors de la demande d'envoi vers chorus.
- Pas d'affichage de la selection du document a envoyé s'il n'y en a qu'un lors de la demande d'envoi vers chorus.
- Ajout dans le paramétrage du module pour activer/désactiver les deux options précédentes.

## [7.0.15] - 14-10-2019
- Correction de la visibilité des champs supplémentaires de type 'séparateur' sur les contacts.

## [7.0.14] - 08-10-2019
- Correction mineure.

## [7.0.13] - 04-10-2019
- Correction du calcul du nombre de requêtes envoyées vers EDEDOC pour le mois en cours.

## [7.0.12] - 02-10-2019
- Correction du lien de téléchargement de AdvanceDictionaries.

## [7.0.11] - 27-09-2019
- Ajout des libellés court sur les statuts.
- Afficher le bloc technique que si le mode DEBUG est activé.
- Ajout d'un droit pour afficher le widget.
- Affiche tous les statuts possibles du wokflow dans le widget.
- N'affiche que les factures impayées dans le widget.
- Ajout d'un champ complémentaire sur le tiers 'Factures envoyé sur Chorus'.
- Ajout d'une action en masse sur la liste des tiers pour activer l'envoi vers Chorus des factures.
- Ajout d'un bouton sur la facture pour activer l'envoi vers Chorus si l'option n'est pas activé sur le tiers.
- Ajout d'un champ complémentaire sur la facture 'Identifiant Chorus de la facture' dans le bloc technique.

## [7.0.10] - 23-09-2019
- Correction affichage des erreurs de l'API en mode non Debug.

## [7.0.9] - 06-09-2019
- Correction des tailles des codes des dictionnaires des correspondances statuts chorus.

## [7.0.8] - 05-09-2019
- Correction des valeurs ajouter par défaut dans le dictionnaire des statuts des factures.
- Correction du test pour la permission de l'envoi de la facture vers Chorus.
- Correction de l'enregistrement des dates récupérées depuis EdeDoc.
- Correction de la fonction cron de mise a jour des statuts.

## [7.0.7] - 01-08-2019
- Ajout du paramètre 'Processus terminé' dans le dictionnaire des statuts de la facture sur Chorus.
- Correction du test pour la permission de l'envoi de la facture vers Chorus.
- Correction pour l'envoi/MAJ du status dans les actions en masse sur la liste des factures.
- Ajout d'une option de paramétrage dans le module pour déterminer le "Mode de facturation" par défaut.

## [7.0.6] - 31-07-2019
- Correction du chargement du cache des statuts lors de la mise à jour du statut Chorus d'une facture.
- Correction du fichier de langues.

## [7.0.5] - 24-07-2019
- Ajout des attributs supplémentaires "N° d'engagement" et "N° de marché" sur les fiches Commandes, Contrats, Expédition, Modèles de facture, Interventions, Projets, Propositions commerciales et Demandes (RequestManager).
- Ajout type de contact "Contact service [CHORUS]" et "Contact valideur [CHORUS]" sur les fiches Commandes, Contrats, Expédition, Modèles de facture, Interventions, Projets, Propositions commerciales et Demandes (RequestManager).
- Ajout du nombre de factures pour chaque statut Chorus (avec lien vers liste filtrée) dans le Widget "Factures Chorus"
- Envoi en masse des factures vers Chorus depuis la liste des factures.

## [7.0.4] - 18-07-2019
- Corrections des attributs supplémentaires pour l'affichage en lecture seule.
- Ajout du statut de la facture sur chorus.
- Ajout des statuts par défauts dans les dictionnaires.

## [7.0.3] - 17-07-2019
- Modification des attributs supplémentaires des status sur la facture en 'liste issue des dictionnaires'.

## [7.0.2] - 16-07-2019
- Ajout de la position dans les dictionnaires des statuts.
- Ajout d'un widget affichant la liste de la somme des factures par statut chorus (+ option pour n'afficher que les status possédant des factures).
- Ajout d'une action de masse pour la mise à jour des statuts chorus sur la liste des factures
- Ajout d'un cron pour la mise à jour de tous les statuts chorus des factures
- Mise à jour des attributs complémentaires de la facture liée à Chorus (en lecture seule - patch core opendsi)

## [7.0.1] - 15-07-2019
- Affichage détaillé des informations du statut de l'envoi.
- Ajout des correspondances des statuts EDEDOC et CHORUS et ne renvoi pas une facture tant que le workflow n'est pas finit.

## [7.0.0] - 15-07-2019
- Version initial.

[Non Distribué]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/compare/v7.0.38...HEAD
[7.0.38]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.38
[7.0.37]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.37
[7.0.36]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.36
[7.0.35]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.35
[7.0.34]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.34
[7.0.33]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.33
[7.0.32]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.32
[7.0.31]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.31
[7.0.30]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.30
[7.0.29]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.29
[7.0.28]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.28
[7.0.27]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.27
[7.0.26]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.26
[7.0.25]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.25
[7.0.24]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.24
[7.0.23]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.23
[7.0.22]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.22
[7.0.21]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.21
[7.0.20]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.20
[7.0.19]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.19
[7.0.18]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.18
[7.0.17]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.17
[7.0.16]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.16
[7.0.15]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.15
[7.0.14]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.14
[7.0.13]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.13
[7.0.12]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.12
[7.0.11]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.11
[7.0.10]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.10
[7.0.9]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.9
[7.0.8]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.8
[7.0.7]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.7
[7.0.6]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.6
[7.0.5]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.5
[7.0.4]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.4
[7.0.3]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.3
[7.0.2]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.2
[7.0.1]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.1
[7.0.0]: http://git.open-dsi.fr/dolibarr-extension/demat4dolibarr/commits/v7.0.0
