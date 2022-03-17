# Module Associé FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Utilisation

### Prérequis :
- Activer le module Adhérent Natif à DOLIBARR et créer des Adhérents.
- Activer le module Tiers.
- Paramétrer le module Associé.
- Chaque associé créé doit être lier à un tier pour que le module fonctionne correctement.

### Optionnel :
- Paramétrer le montant de la commission Eurochef.
- Activer l'option "Créer une ligne dans la commande associé pour chaque descriptif différent de la commande client" pour générer des commandes séparés à chaque génération de commande.
- Activer l'option "Autoriser la commande des services" pour faire passer les services dans la commande associé.
- Ajouter les services de BFA  et de commission et définir la nomenclature dans la configuration afin qi'ils ne ressortent dans les pages nécéssaires.

Le module fonctionne depuis les commandes utilisateur.<br/>
Il transforme la commande client en commande fournisseur en utilisant un adhérent créé lors des prérequis.<br/>
Lors de la création, si un Tiers n'est pas lié à l'adhérent, il n'apparaitrat pas dans la liste.

Il est également possible de déduire le prix d'achat des articles commandés auprès de fournisseurs en chochant la case "Produit(s) commandé(s) par eurochef".<br/>
🚨 Pour que cela fonctionne, il faut qu'une commande fournisseur ai déjà été passée, sinon aucune ligne n'apparaitra. 🚨

La BFA et les Commissions doivent être définis dans des services et la nomenclature de ceux-ci définie dans les paramettrages du module pour pouvoir apparaitre sur la page des commandes associés.

Les Commissions et BFA sont affichés en lignes de valeurs négatives pointant vers les services définis dans le paramétrage du module.<br/>
Lors d'une commande eurochef, le tarif décoté apparait également en valeur négative, mais ce dernier se retrouve dans une ligne libre.

## Translations

Les traductions peuvent être définies dans le dossier *langs*.

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.