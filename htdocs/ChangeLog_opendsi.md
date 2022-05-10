# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).

Open-DSI Dolibarr v14 - MAJ Projet Eurochef

## [11.0.0] - 06-04-2022
- Backport 15.0 - Core : Add massaction to switch status on sale / on purchase of a product (https://github.com/Dolibarr/dolibarr/commit/259b7dfe5a44fa54896e547b3c86ee9bd5acb7ef) + Page substitutions

## [10.0.0] - 30-03-2022
- Correction : Ajout de la colonne réf/libellé de la facture fournisseur sur les pages de liaison des factures fournisseurs (#20128)

## [9.0.0] - 23-03-2022
- Correction : Ajout hook manquant et total des lignes sur extrafields manquant sur module reception (#20128)

## [8.0.1] - 15-03-2022
- Correction : Problème de modification des comptes comptables dans les fiches produits protégés par droits avancés

## [8.0.0] - 15-03-2022
- Nouveau : mise en place automatique de l'état de réception totale en réception (#20065)
- Correction : envoyer les fichiers joints via dans le module réception
- Nouveau : Compte auxiliaire au niveau des acomptes (reprise dév. v12) (#20356)
- Nouveau : Définition des comptes comptables dans les fiches produits protégés par droits avancés

## [7.1.3] - 23-02-2022
- Correction : Erreur 500 sur page dictionnaire suite v7.1.2

## [7.1.2] - 23-02-2022
- Correction : Reprise des modifications infraspack sur le dictionnaire afin de pouvoir activer la constante INFRASPACKPLUS_DISABLE_CORE_CHANGE

## [7.1.1] - 23-02-2022
- Correction : Mauvais champ de tri dans c_units->fetchAll

## [7.1.0] - 23-02-2022
- Correction - Double affichage des champs supplémentaires lors de la création d'un produit

## [7.0.0] - 23-02-2022
- Correction - Dictionnaire de TVA par entité
- Correction - Liste des unités - Ordre
- Correction - Liste des unités - Champ vide lors de l'édition d'une ligne
- Correction - Facture - Compte bancaire non repris de la fiche tiers (https://github.com/Dolibarr/dolibarr/pull/20151)
- Correction - Longueur de la ref fournisseur dans le module réception

	Requête SQL à exécuter :
    ALTER TABLE llx_reception MODIFY COLUMN ref_supplier varchar(128);

    Note: la constante PRODUCT_USE_UNITS qui permet d'activer la gestion des unités sert aussi de rowid par défaut. 
          Pour sélectionner l'unité 'Pièce' par défaut => PRODUCT_USE_UNITS à 28 sur Lanef 

## [6.0.0] - 21-02-2022
- Correction - Erreur SQL dans le dictionnaire de TVA
- Correction - Saisie des écritures manuelles non réellement cloisonnées avec multicompany
- Correction export FEC3 - Problème date creation / date document inversé
  
	Requête SQL à exécuter : (Compatibilité avec MAJ ATM)
  	ALTER TABLE llx_product ADD COLUMN not_managed_in_stock INTEGER NULL;

## [5.0.0] - 19-01-2022
- Ajout d'une constante pour autoriser le caractère '@' dans les noms d'utilisateurs
	MAIN_LOGIN_BADCHARUNAUTHORIZED		value : /[,<>"\']/	sur toutes les entités

## [4.0.0] - 04-01-2022
- Correction pour accepter les espaces dans les ref produits (dol_string_nospecial2())
- Correction définitive export FEC3 suite à erreur 500 lors de la génération du fichier

## [3.1.0] - 24-12-2021
- Correction export FEC3 suite à erreur 500 lors de la génération du fichier
- Nouveau test pour une recherche dans le nom alternatif

## [3.0.0] - 23-12-2021
- Reprise du format FEC3 initié sur la v12
- Ajout d'une option pour avoir la date d'export dans le nom des fichiers FEC
- Dans le champ de recherche des tiers, recherche dans le nom alternatif (à vérifier)

## [2.0.0] - 14-12-2021
- Ajout de la notion d'entité pour séparation des taux de TVA
	Requête SQL à exécuter : 
	
		VMYSQL4.1 DROP INDEX uk_c_tva_id on llx_c_tva;
		ALTER TABLE llx_c_tva ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
		ALTER TABLE llx_c_tva ADD UNIQUE INDEX uk_c_tva_id (entity, fk_pays, code, taux, recuperableonly);

## [1.0.0] - 08-12-2021
- Ajout d'un droit avancé sur le module tiers pour ajouter/éditer les informations des paiements (https://github.com/Dolibarr/dolibarr/pull/19281)
- Ajout du tri sur les unités (https://github.com/Dolibarr/dolibarr/pull/18967)

	Requête SQL à executer : 
	
		ALTER TABLE llx_c_units ADD COLUMN sortorder smallint AFTER code;

[Non Distribué]: 
[11.0.0]: 
[10.0.0]: 
[9.0.0]: 
[8.0.1]: 
[8.0.0]: 
[7.1.3]: 
[7.1.2]: 
[7.1.1]: 
[7.1.0]: 
[7.0.0]: 
[6.0.0]: 
[5.0.0]: 
[4.0.0]: 
[3.1.0]: 
[3.0.0]: 
[2.0.0]: 
[1.0.0]: 