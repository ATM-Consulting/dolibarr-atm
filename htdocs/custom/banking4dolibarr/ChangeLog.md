# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).

## [Non Distribué]

## [7.0.58] - 20-10-2021
- Compatibilité avec Dolibarr v14 pour la TVA et les salaires
- Modification pour la compatibilité avec l'API de budget Insight (rajout de description de nouvelles erreurs et suppression du champ Contrepartie)
- La date de fin des factures clients et fournisseurs des elements impayés est maintenant la date limite de règlement
- Suppression de l'unicité

## [7.0.57] - 06-10-2021
- Ajout de l'affichage du texte des erreurs se produisant lors du rapprochement d'un bon de prelevement en fonction du code retour des fonction de la classe BonPrelevement.

## [7.0.56] - 05-07-2021
- Distinction du paramètrages de l'écart des dates maximales pour les éléments impayés.

## [7.0.55] - 24-06-2021
- Correction des doublons present sur l'ecran de validation des écritures impayées rapprochées automatiquement.
- Ajout d'une option 'Relevé automatique' sur les comptes bancaires liés dans le paramétrages du module.

## [7.0.54] - 02-06-2021
- Correction de la création des écritures bancaires par société lors d'un rapprochement manuel de multiple factures clients / fournisseurs.
- Lors du rapprochement manuel avec un bon de prélèvement, se baser sur le montant du bon en prenant en compte les rejets.
  (pour les version de Dolibarr inférieur a la v15, les lignes d'écritures bancaires générées par un rejet doivent être liées manuellement).

## [7.0.53] - 07-05-2021
- Correction compatibilité des dictionnaires en multi entité

## [7.0.52] - 05-05-2021
- Correction de la gestion des tokens en multi entité

## [7.0.51] - 28-04-2021
- Correction compatibilité avec la maj de l'API pour la récupération des transactions (quand il y a plus de lignes que la limite de téléchargement par requête définie)
- Permet le rapprochement des lignes différées car elles ne sont jamais mise à jour pour indiquer que la transaction est actée.
- Correction du filtre sur la colonne "Différé" de la liste des écritures téléchargées.
- Correction du rapprochement manuel pour le cas de la TVA en fonction des versions de Dolibarr.
- Correction affichage icône Doublon d'écriture avant Dolibarr v11 (Fontawesome v4)

## [7.0.50] - 27-04-2021
- Corrections mineures

## [7.0.49] - 07-04-2021
- Correction de la désactivation du rechargement auto de la page à la sélection d'un tiers lors du rapprochement manuel d'une nouvelle facture fournisseur

## [7.0.48] - 01-04-2021
- Correction de la création d'une nouvelle facture fournisseur à partir d'une écriture téléchargée (montant rapproché)
- Et propose de créer une facture suivante si le montant (celle de la nouvelle facture et des factures déjà rapprochés) est inférieure à celle de l'écriture téléchargée
- Correction de la suggestion des tiers lors du rapprochement manuel d'une nouvelle facture fournisseur (ne prend que les fournisseurs)

## [7.0.47] - 19-03-2021
- Ajout d'une option pour calculer la période par défaut sur un rapprochement manuel selon la date de règlement (Mois précédent / mois courant)
- Lors de la recherche d'un élément impayé dans un rapprochement manuel, les filtres "compte bancaire" & "Mode de règlement" sont vides par défaut pour avoir un maximum de résultat
- Corrections fichiers langue

## [7.0.46] - 15-03-2021
- Changement lien boutique Open-DSI pour les acheteurs du module sur le Dolistore

## [7.0.45] - 03-03-2021
- Correction de la validation des impayés rapprochés automatiquement pour les comptes bancaires manuels.
- Standardisation des filtres de recherches sur les dates.
- Correction du rapprochement manuel d'un nouveau règlement TVA.
- Correction de la prise en compte du type de gestion des paiements divers.
- Correction de l'import standard des écritures bancaires pour les comptes bancaires manuels.

## [7.0.44] - 23-02-2021
- Correction du rafraichissement des comptes bancaires dans le paramétrage du module quand il y a des comptes bancaires manuels et reliés à Budget Insight.
- Correction de la prise en compte de la date de début lors du premier téléchargement des écritures bancaires. 
- Amélioration du rapprochement automatique des impayés (avec test sur les dates).
- Affichage des dates des objets rapprochés dans l'écran de validation des rapprochements automatiques.
- Changement des icônes dans la liste des écritures téléchargés

## [7.0.43] - 21-01-2021
- Permet l'ajout de compte bancaire manuellement même si la clé du module est renseigné.

## [7.0.42] - 20-01-2021
- Correction affichage icône rapprochement manuel avant Dolibarr v11 (Fontawesome v4)

## [7.0.41] - 18-01-2021
- Ajout d'un menu déporté d'accès à la gestion des comptes bancaires
- Si clé abonnement présent, l'administration renvoie vers l'onglet Compte bancaires plutôt que vers les paramètres
- Dans les actions en masse, la suppression des écritures (protégée par un droit) a été mise en dernière position
- Ajout compatibilité avec le module "Fil ariane" (Breadcrumb)

## [7.0.40] - 04-01-2021
- Correction de la détection du type "remboursement" avec un montant au crédit lors d'un rapprochement manuel de TVA.
- Correction de la prise en compte du type sur la creation en masse des paiements divers.
- Ajout de l'enregistrement d'une nouvelle charge sociale/fiscale depuis le rapprochement manuel.
- Possibilité de modifier le montant de la ligne bancaire téléchargé si la devise diffère de la banque et que la ligne n'est pas rapproché.

## [7.0.39] - 07-12-2020
- Correction du rapprochement manuel d'un paiement multiple.

## [7.0.38] - 26-11-2020
- Correction de la compatibilité avec postgresql.
- Regroupe tous les fichiers de mise à jour SQL en un seul fichier (ne sont pas systématiquement exécuté dans l'ordre ...).

## [7.0.37] - 18-11-2020
- Ajout compatibilité avec le module "Révolution Pro Thème Dolibarr"
- Tronque le nom de la banque nouvellement créée depuis la synchro des comptes bancaires depuis Budget Insight.
- Ajoute la référence client ou fournisseur lors de la validation des rapprochements automatiques trouvés.
- Ajoute la gestion du fonctionnement sans avoir de clé d'activation.
- Ajoute la compatibilité avec postgresql.
- Creation automatique d'un bordereau lors d'un depot de cheque.
- Creation d'un règlement lié a plusieurs factures lors d'un rapprochement manuel (si créé en une fois).

## [7.0.36] - 28-10-2020
- Ajout d'icône sur les boutons
- Changement icône rapprochement manuel
- Sécurité

## [7.0.35] - 20-09-2020
- Rapprochement manuel - Écritures bancaires - Le type de paiement peut être vide
- Rapprochement manuel - Nouveau règlement salaire - Calcul des dates de période d'après la date d'opération
- Rapprochement manuel - Nouveau règlement TVA - Calcul de la date de fin de période d'après la date d'opération

## [7.0.34] - 10-09-2020
- Correction instruction group by
- Correction du changement du status lors du rapprochement des lignes de banques liés à celle téléchargée venant d'être rapprochée
- Correction du montant du paiement de la charge fiscale/sociale rapprochée
- Ajout de la ref externe dans la liste des impayés lors du rapprochement manuel
- Ajout d'un import standard de lignes de banque téléchargées

## [7.0.33] - 15-07-2020
- Correction d'un warning dans le dictionnaire des catégories

## [7.0.32] - 30-06-2020
- Correction de la récupération de l'ID de l'écriture bancaire du paiement nouvellement créée lors de la validation d'un rapprochement automatique ou manuelle d'éléments impayées (emprunts, notes de frais, charges sociales/fiscales et dons) qui sera lié à l'écriture téléchargée
- Mise à jour de l'API vers Budget Insight (nouvelle syntaxe, code d'erreur, ...)

## [7.0.31] - 15-06-2020
- Correction de nom de variables globales
- Refonte de l'interface de paramétrage du module
- Support de la passerelle de redirection et de la langue de la webview affichée
- Prise en compte de la nouvelle génération de clé par le module partner.
- Ne sélectionne plus l'utilisateur courant si aucun employé suggéré n'est trouvé lors du rapprochement manuel d'un salaire.
- Correction du rapprochement manuel d'un règlement de TVA.

## [7.0.30] - 09-06-2020
- Gestion des lignes en doubles renvoyées par l'API avec les lignes supprimées fournit par l'API.
- Message de reconnexion sur la fenêtre de rafraîchissement lors d'une demande de renouvellement d'une authentification forte.
- Modifications traductions
- Corrections d'affichages

## [7.0.29] - 01-06-2020
- Les paramètres d'écart de dates ne sert plus au rapprochement automatique des impayés.
- Ajout de la suppressions en masses des écritures téléchargées non rapprochées avec un droit associé.
- Ajout du "Compte comptable" dans le dictionnaire "Liste des catégories des écritures bancaires".
- Ajout de la création et rapprochement de paiements divers en masse depuis la liste des écritures téléchargées non rapprochées.
- Ajout de la catégorie dans le récapitulatif de l'écritures téléchargée lors d'un rapprochement manuel. 
- Correction du fonctionnement du rapprochement manuel des paiements divers.
- Correction du montant des charges sociales dans la vue nécessaire à la detection des impayées.

## [7.0.28] - 18-05-2020
- Mise à jour des types de champs des issues d'une table au format integer dans les dictionnaires et suppression de l'instruction CONVERT( AS INTEGER) dans les requêtes
- Les vues ne comportent que les parties des modules activées dans dolibarr pour la recherche des impayées.
- Prise en charge des avoirs et remises sur les factures fournisseurs.

## [7.0.27] - 12-05-2020
- Correction du test des modes de paiement nécessaire à l'activation du module
- Correction de la création des vues en fonction des modules activés à l'activation du module

## [7.0.26] - 07-05-2020
- Correction du rapprochement automatique des notes de frais impayées
- Correction de la détection de l'écart de dates lors du rapprochement automatique des montants identiques
- Correction de la compatibilité multi-entité lors du rapprochement automatique des montants identiques
- Correction du rapprochement manuel des "nouveaux règlements de salaires"
- Correction des alignements des cellules des tableaux

## [7.0.25] - 05-05-2020
- Ajout de require_once, par sécurité, pour les règlements des factures fournisseurs (pour assurer la génération du PDF)
- Correction css pour l'affichage du rapprochement manuel
- Le premier rafraîchissement des écritures bancaires téléchargées se base sur la date d'opération
- Ajout d'une limitation au niveau du nombre de banques liées
- Correction de la détection d'un rapprochement complet avec plusieurs lignes lors d'éléments impayés
- N'affiche que les utilisateurs actifs pour la notification par email dans la configuration

## [7.0.24] - 03-05-2020
- Correction comptabilité Dolibarr < v9 champ date formulaire Nouveau paiement salaire
- Ajout logo Open DSI HD pour Dolibarr > v12
- Widget - Montant non à la ligne
- Ajout de la création d'un paiement de tva directement depuis le rapprochement manuel
- Clé de langue manquante
- Utilisation dol_fiche_head dans l'affichage des rapprochements manuels

## [7.0.23] - 20-04-2020
- Ajout d'un paramètre 'Type d'écriture supportée' dans le module pour ne télécharger que les écritures en débit, crédit ou les deux
- Ajout d'une tache planifiée pour mettre à jour les écritures téléchargées avec une option de notification par email.
- Correction du chargement de l'objet pour l'élément de type invoice.
- Ajout de la suggestion de l'employé lors d'un rapprochement manuel d'un nouveau salaire (sélectionné automatiquement si il n'en trouve qu'un).
- Ajout de l'écart toléré dans le passé/future séparément pour les débits et les crédits.
- Le rapprochement auto grâce à la référence se fait maintenant aussi sur la réf. client et la réf. fournisseur.
- Ajout du total des montants sélectionnés lors d'un rapprochement manuel.
- Sélection automatique du tiers suggéré s'il n'en trouve qu'un lors d'un rapprochement manuel d'une nouvelle facture fournisseur.
- Correction de l'affichage de l'écran de rapprochement manuel.
- Correction de la détection du rapprochement total du montant de l'écriture téléchargée lors d'un rapprochement manuel.
- Rajout d'une info-bulle pour le filtre des listes sur les dates.

## [7.0.22] - 17-04-2020
- Prise en charge des badges pour les statuts des lignes des écritures téléchargées (Dolibarr v10+) 
- Comptabilité Dolibarr v11
- Ajout de la création d'un paiement de salaire dans le rapprochement manuel

## [7.0.21] - 14-04-2020
- Correction de la recherche du nom du tiers (alternatif et spécifique) dans les lignes d'écritures bancaires téléchargées ayant un montant identique, dans la plage de date paramétrée, lors du rapprochement automatiques.
- Correction des lignes d'écritures bancaires téléchargées ayant un montant identique, dans la plage de dates paramétrée, lors du rapprochement automatiques.
- Correction affichage onglet "écritures téléchargées" | Colonne actions

## [7.0.20] - 02-04-2020
- Corrections (mode étendue) sur la note d'une écriture téléchargée non rapprochée.
- Recherche du nom du tiers (alternatif et spécifique) en plus de la réf des pièces dans les lignes d'écritures bancaires téléchargées lors du rapprochement automatique.
- Ajout des rapprochements automatiques pour les prélèvements et les remises de chèques.
- Ajout d'un quota pour le nombre maximum de comptes bancaires liés.

## [7.0.19] - 25-03-2020
- Correction colonne widget
- Correction coloration des lignes téléchargées
- Correction lien boutique Open-DSI

## [7.0.18] - 23-03-2020
- Ajout d'une note sur les écritures téléchargées
- Changement de l'icône de rapprochement manuel

## [7.0.17] - 20-03-2020
- Correction langue française
- Suppression d'un droit obsolète
- Correction bug

## [7.0.16] - 11-03-2020
- Widget : Affiche la colonne 'Solde bancaire au dernier relevé'
- Correction ouverture de lien externe dans la création d'un élément dans le rapprochement manuel

## [7.0.15] - 09-03-2020
- Ajout de la création d'une facture fournisseur dans le rapprochement manuel d'une écriture téléchargée

## [7.0.14] - 06-03-2020
- Widget : N'affiche les colonnes 'Solde bancaire au dernier relevé' et 'Solde bancaire Dolibarr' que si la variable globale BANKING4DOLIBARR_WIDGET_SHOW_BALANCE est mise à 1
- Corrections de textes
- Ajout des options de paramétrage de la couleur de fond spécifique pour les mouvements débiteurs ou créditeurs dans le module.
- La colonne 'Écritures bancaires (D)' dans la liste des écritures téléchargées est cachée par défaut
- Les écritures rapprochées ventilées dans le grand livre ne peuvent pas être corrigées

## [7.0.13] - 28-02-2020
- Ajout de la possibilité de changer le numéro du relevé depuis la liste des écritures téléchargées.
- Ne peux plus modifier un rapprochement sur une écriture bancaire Dolibarr si sa banque est lié a Budget Insight

## [7.0.12] - 26-02-2020
- Ajout du rapprochement automatique et manuelle des éléments impayés débits/crédits (charges sociales, notes de frais, emprunts)
- Amélioration du retour des erreurs sur le compte bancaire lié ou la connection lors du rafraîchissement des écritures bancaires ou des comptes bancaires

## [7.0.11] - 26-02-2020
- Ajout du retour des erreurs sur le compte bancaire lié ou la connection lors du rafraîchissement des écritures bancaires

## [7.0.10] - 24-02-2020
- Correction du support des "Catégories" renvoyées par Budget Insight
- Correction affichage des montants crédit/débit dans les tableaux

## [7.0.9] - 17-02-2020
- Correction du blocage du rapprochement (Dolibarr) si la banque est lié a Budget Insight
- Correction de droit lors du rapprochement manuel d'opérations diverses ou de virement bancaire
- Ajout de la compatibilité avec la nouvelle façon de gérer les paiements divers depuis la V10+ de Dolibarr
- Correction du montant de payement créé pour les factures fournisseurs lors du rapprochement manuel 
- Correction du rapprochement de multiples lignes bancaires (Dolibarr) pour une ligne téléchargée
- Correction de montant débit/crédit lors du rapprochement manuel des éléments impayés
- Ajout des colonnes "Compte bancaire" et "Mode de paiement" sur la liste des éléments impayés
- Corrections mineures

## [7.0.8] - 10-02-2020
- Remplacement champ facnumber par ref pour compatibilité avec Dolibarr v10

## [7.0.7] - 10-02-2020
- Amélioration du fonctionnement de rafraîchissement de la page lors de la fermeture d'une boite de traitement 'rapprochement manuel/automatique'
- Correction des totaux sur le widget

## [7.0.6] - 07-02-2020
- Affichage du solde du compte lié et de la date de la dernière synchro sur la fiche de la banque et l'onglet des écritures téléchargés.
- Clarification du rapprochement manuel "Virement bancaire"
- Correction du rapprochement manuel des factures fournisseurs impayées
- Ajout du support de la colorisation des lignes en fonction du débit/crédit
- Ajout d'un droit pour pouvoir modifier le rapprochement (Dolibarr) sur la fiche de l'écriture bancaire (Dolibarr)
- Ajout d'un widget d'informations sur les comptes bancaires liés
- Ajout de la date du rapprochement sur les lignes d'écritures bancaires téléchargés
- Renommage du bouton "Annuler" en "Terminer" sur l'écran de confirmation des lignes rapprochées automatiquement
- Correction de la détection du montant totalement rapprochement lors d'un rapprochement manuel de lignes comportant des montants avec une décimale
- Les filtres débit/crédit n'affiche que les lignes en débit ou en crédit
- Correction Description des paramètres du module
- Amélioration du fonctionnement de rafraîchissement de la page lors de la fermeture d'une boite de traitement 'rapprochement manuel/automatique'

## [7.0.5] - 04-02-2020
- Correction du calcul du numéro de relevé lors du rapprochement auto.

## [7.0.4] - 23-01-2020
- Correction du filtre "Type" sur la liste des écritures bancaires téléchargées.
- Ré-affichage de la case à cocher pour les lignes non rapprochées (caché seulement si ligne partiellement rapprochée avec une même écriture bancaires(Dolibarr)).
- Correction bug lors de la sélection du nombre de lignes affichées dans les listes de l'écran de rapprochement manuel).
- Correction du montant des factures fournisseurs impayées (Débit / Crédit inversé).
- Correction de l'enregistrement d'un virement ayant la même devise.
- Option dans le module d'une limite de recherche sur les dates pour les rapprochements automatiques des écritures bancaires et filtre par défaut dans le rapprochement manuel des écritures bancaires 
- Ajout de la possibilité de lier plusieurs écritures bancaires téléchargées à une même écriture bancaire Dolibarr.
- Correction des index / clés étrangères dans les tables (reliquat de version précédente)
- Ne rapproche plus automatiquement le "mode de paiement et montant identique" et "montant identique uniquement"

## [7.0.3] - 22-01-2020
- Correction du rapprochement automatique (n'inclue plus le solde des banques).
- Ajout du rapprochement manuel.

## [7.0.2] - 14-01-2020
- Fix la comparaison sans espaces des IBAN lors de la mise à jour de la liste des comptes bancaires.
- Fix la recherche sans espaces des ref des pièces dans les lignes d'écritures bancaires téléchargées lors du rapprochement automatique.
- Fix la validation manuelle des lignes rapprochées automatiquement demandant confirmation.
- Amélioration de l'écran de confirmation des lignes rapprochées automatiquement.
- Fix la date de dernière mise a jour des lignes d'écritures bancaires téléchargées.

## [7.0.1] - 13-01-2020
- Fix "include vendor/autoload.php" avec d'autres modules.

## [7.0.0] - 15-11-2019
- Version initial.

[Non Distribué]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/compare/v7.0.58...HEAD
[7.0.58]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.58
[7.0.57]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.57
[7.0.56]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.56
[7.0.55]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.55
[7.0.54]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.54
[7.0.53]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.53
[7.0.52]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.52
[7.0.51]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.51
[7.0.50]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.50
[7.0.49]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.49
[7.0.48]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.48
[7.0.47]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.47
[7.0.46]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.46
[7.0.45]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.45
[7.0.44]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.44
[7.0.43]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.43
[7.0.42]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.42
[7.0.41]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.41
[7.0.40]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.40
[7.0.39]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.39
[7.0.38]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.38
[7.0.37]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.37
[7.0.36]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.36
[7.0.35]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.35
[7.0.34]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.34
[7.0.33]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.33
[7.0.32]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.32
[7.0.31]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.31
[7.0.30]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.30
[7.0.29]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.29
[7.0.28]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.28
[7.0.27]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.27
[7.0.26]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.26
[7.0.25]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.25
[7.0.24]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.24
[7.0.23]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.23
[7.0.22]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.22
[7.0.21]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.21
[7.0.20]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.20
[7.0.19]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.19
[7.0.18]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.18
[7.0.17]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.17
[7.0.16]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.16
[7.0.15]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.15
[7.0.14]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.14
[7.0.13]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.13
[7.0.11]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.11
[7.0.10]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.10
[7.0.9]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.9
[7.0.8]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.8
[7.0.7]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.7
[7.0.6]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.6
[7.0.4]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.4
[7.0.3]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.3
[7.0.2]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.2
[7.0.1]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.1
[7.0.0]: http://git.open-dsi.fr/dolibarr-extension/banking4dolibarr/commits/v7.0.0
