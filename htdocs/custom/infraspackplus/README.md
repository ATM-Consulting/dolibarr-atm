<img src = "/custom/infraspackplus/img/InfrasSPackPlus.png" width="500px" height="auto">



# ***InfraSPack Plus v7***

* Le pack de modèles ***InfraS*** apporte de nombreuses modifications aux modèles de base : c'est plus de 308 options dont
	 * Les cadres arrondis, l’organisation des colonnes (ordre, affichage, largeur, …)
	 * Le choix des couleurs de texte, de fond, des images, des filigranes, des types épaisseur et couleurs des lignes, …
	 * L’affichage 'full' TTC
	 * L'affichage du total en toutes lettres
	 * Une gestion plus claire des options, une gestion complète des images (logo, image en pied, image des produits / services)
	 * L’activation des adresses multiples pour votre société comme pour les tiers et l'utilisation des adresses de livraison (y compris en saisie manuelle)
	 * Un en-tête simplifié pour les pages suivantes
	 * L’utilisation du nom commercial (marque) des tiers
	 * La gestion des attributs supplémentaires (attributs liés au document comme ceux liés aux lignes de document
	 * Une réelle intégration des CGV (conditions générales de vente) et des éléments techniques concaténés automatiquement avec leur prise en compte dans la pagination
	 * La gestion des éléments légaux nécessaires à l’export
	 * Etc...



## Licence

***InfraSPack Plus v7*** est distribué sous les termes de la licence GNU General Public License v3+ ou supérieure. ![GPLv3](/custom/infraspackplus/img/gplv3.png)

Copyright (C) 2016-2021 Sylvain Legrand - InfraS

voir le fichier LICENSE pour plus d'informations

## Autres Licences

Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README



## Ce qu'est ***InfraSPack Plus v7***

***InfraSPack Plus v7*** est un module optionnel de Dolibarr ERP & CRM enrichissant les modèles de document de base par une série de modèles configurables (on n'active que les modèles que l'on désire) .
***InfraSPack Plus v7*** ajoute un modèle aux modules suivants :
* Chaîne des ventes
	 * Devis, compatibilité module ***“Sous-Total” - ATM Consulting***, ***“Milestone/Jalon” - iNodbox*** et ***“Ouvrage/Forfait” - Inovea***
	 * Devis sans total général (liste de prix ou tarif), compatibilité module ***“Sous-Total” - ATM Consulting***, ***“Milestone/Jalon” - iNodbox*** et ***“Ouvrage/Forfait” - Inovea***
	 * Commandes client, compatibilité module ***“Sous-Total” - ATM Consulting***, ***“Custom Link” - Patas-Monkey***,  ***“Milestone/Jalon” - iNodbox*** et ***“Ouvrage/Forfait” - Inovea***
	 * Contrats
	 * Factures, compatibilité module ***“Sous-Total” - ATM Consulting***, ***“Équipement” - Patas-Monkey***, ***“Milestone/Jalon” - iNodbox*** et ***“Ouvrage/Forfait” - Inovea***
* Chaîne des achâts
	 * Devis fournisseur
	 * Commandes fournisseur
	 * Factures fournisseur
* Documents techniques
	 * Fiches produit / services
	 * Page d'étiquettes de produit / services avec code-barre, libellé et tarif de base
	 * Fiches d’intervention, compatibilité module ***“Équipement” - Patas-Monkey***
	 * Bons de livraison / Expéditions, compatibilité module ***“Équipement” - Patas-Monkey***
	 * Projet, compatibilité module ***“Note de Frais Plus” - Mikael Carlavan***
* Documents administratifs
	 * Notes de frais



## Core Change
Pour le bon fonctionnement des modèles ***InfraS*** (chaîne des achats, gestion de l'email et / ou de l'url associé à chaque adresse) :
* Après toute mise à jour de Dolibarr et / ou du module
	 * cliquez sur le bouton ***Changer*** situé en haut de l'onglet 'Paramètres spécifiques InfraS'



## Activation des modifications
Après la mise à jour
* par copie (écrasement) des fichiers du répertoire ***./htdocs/custom/infraspackplus/*** dans le cas d'une utilisation sous ***Dolibarr v6.0.x*** :
* ou en utilisant l'outil de déploiement des modules externes dans le cas d'une utilisation sous ***Dolibarr v7.0.x*** et ultérieur :
	* Il est IMPERATIF :
		 * D'effectuer le Core change (voir point précédent)
		 * De désactivez puis réactivez le module pour effectuer les modifications nécessaires de la Base de données



## Fonctionnalités (toutes optionnelles)

* Gérer les marges et le format papier à appliquer aux documents
* Coisir la taille de police utilisée comme base
* Désactiver l'impression du logo de la société
* Utilisez le logo original (avec sa résolution plus élevée) dans le PDF au lieu de l'image réduite. ***ATTENTION !*** Cela peut augmenter considérablement la taille du fichier PDF !
* Intervertir les cadres “émetteur” et “destinataire”
* Inclure les alias dans le nom des tiers
* Utiliser le nom du contact plutôt que celui de l'entreprise cliente
* Utiliser la position standard française (La Poste) pour la position de l'adresse client
* Cacher l'identifiant de TVA Intracommunautaire dans les adresses sur les documents
* Afficher les identifiants professionnels dans les adresses documents (Id. prof. 1 “SIREN”, Id. prof. 2 “SIRET”, Id. prof. 3 “NAF – APE”, Id. prof. 4 “RCS/RM”)
* Afficher une ligne de séparation entre chaque élément présent dans le document PDF
* Activer le mode 'full details' pour l'éditeur WYSIWYG de saisie des descriptions produits / services (permet l'insertion d'image à la volée - non enregistrée en bibliothèque)
* Améliorer la visibilité des éléments importants, comme la référence ou le numéro de série, dans la description
* Cacher la référence et/ou la description des produits
* Inverser la description longue des produits / services et leur libellé (à partir de Dolibarr 10+)
* Masquer la colonne 'Poids / Volume' sur les bons de livraison (expédition)
* Activer l'utilisation des unités (issues du dictionnaire) pour les produits / services
* Inclure la documentation produit / service aux devis (Les documents à inclure sont à sélectionner sur la fiche produit / service en bas de l'onglet 'fichiers joints' !)
* Modifier le type de référence produit utilisé dans la chaîne des achats
	 * 0 = référence interne, puis référence fournisseur
	 * 1 = référence fournisseur seule
	 * 2 = référence fournisseur, puis référence interne
* Pour le mode de règlement par chèque n'afficher que l'ordre (l'adresse d'envoi du chèque est masquée)
* Pour le mode de règlement par virement n'afficher que l'IBAN / BIC
* Considérer les factures d'acompte comme des règlements (Utilisées dans une facture finale elles n'apparaissent pas dans les lignes de détail mais sont incluses aux règlements déjà effectués)
* Cacher les conditions et / ou les modes de règlements dans les devis
* Masquer les détails des règlements effectués dans les factures
* Sauvegarder / Restaurer l'ensemble des paramètres du module (une copie de sécurité de la sauvegarde est systématiquement créée dans le répertoire d'administration des documents)
* Installer ou mettre à jour automatiquement via l'outil intégré à Dolibarr et effectuer le "core change" à partir des paramètres du module
* Activer / Désactiver la génération automatique ("à la volée") des PDF : ATTENTION ! Le module active nativement cette fonction pour des raisons d'ergonomie ("oublie d'option") Vous pouvez forcer ce choix
* Autoriser l'enregistrement de plusieurs fichiers PDF pour un même document quand plusieurs modèles sont disponibles (un fichier par modèle et par document)
* Horodater le nom du fichier des fiches projet pour garder un historique de l'évolution
* Proposer aussi les pièces jointes du projet (affaire) associé au document comme fichiers fusionnables
* Importer des fichiers True Type Font (ttf) comme nouvelle police de caractère à utiliser
* Tester la police sélectionnée (création d'un document test avec 84 caractères différents => affichage standard, gras, italique et gras italique => alphabet latin en miniuscules et majuscules, chiffres, ponctuation, accentuation, symboles monétaires, etc soit 98% d'un clavier AZERTY)
* Choisir la police de caractères désirée pour la génération des documents
* Choisir la couleur de texte de l’en-tête de page et / ou du corps du document indépendamment
* Afficher la référence du document et sa date en haut à droite des éléments concaténés (CGV, documentation technique, commerciale, etc...)
* Choisir la valeur du rayon des angles des tableaux et cadres (comprise entre 0 pour angles aigus et 5)
* Ajouter un texte à afficher en filigrane sur les factures réglées
* Afficher le symbole monétaire dans les tableaux et détails du document
* Laisser la première page vide => seuls l'en-tête et le pied de page seront visibles (actif pour les : Devis et Devis sans total ; Commandes, Commande avec code-barres et Proforma ; Contrats ; Factures)
* Activer un en-tête réduit après la première page
* Choisir la couleur du texte à appliquer à l'en-tête du document
* Déplacer les informations de l'en-tête sous les blocs d'adresses (titre et référence du document, dates, références client, documents liés, etc...)
* Choisir la taille de la police du titre du document
* Aligner les informations de l'en-tête sur la gauche
* Afficher le nom du créateur du document dans l'entête
* Définir la hauteur de l'espace supplémentaire entre le bloc d'adresses et les informations de l'en-tête
* Afficher les dates du document sur des lignes séparées
* Afficher les dates du documents en gras
* Choisir la couleur de la date d'échéance des factures (si l'option d'affichage des dates du document sur des lignes séparées est active)
* Afficher la date de création des commandes fournisseurs
* Afficher la référence client comme référence du document dans les devis / Propositions commerciales
* Masquer la date des objets liés au document (seule le type et la référence apparaitront)
* Afficher les devis dans la liste des objets liés
* Afficher les commandes dans la liste des objets liés
* Afficher les références clients des commandes dans la liste des objets liés (entre parenthèses)
* Afficher les expéditions dans la liste des objets liés
* Afficher les contrats dans la liste des objets liés
* Afficher les fiches d'interventioàn dans la liste des objets liés
* Afficher les projets dans la liste des objets liés
* Afficher la description des projets en plus de leur référence
* Masquer les libellés “Émetteur” et “ Adressé à”
* Supprimer le cadre et les informations concernant l'émetteur de l'en-tête (ces informations seront obligatoirement disponibles dans le pied de page)
* Choisir l'épaisseur, le type de ligne et la couleur pour les cadres d'entourages d'adresses émetteur et destinataire indépendemment
* Choisir la couleur de fond des cadres d'adresses émetteur et destinataire indépendemment par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV)
* Choisir l'opacité des cadres d'entourages et du fond des cadres d'adresses émetteur et destinataire indépendemment
* Choisir la couleur du texte des cadres d'adresses émetteur et destinataire indépendemment par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV)
* Afficher tous les détails disponibles pour l'émetteur (minimum => adresse, + téléphones, + fax, + email, + web)
* Afficher tous les détails disponibles pour le destinataire (minimum => adresse, + téléphones, + fax, + email, + web, + IDs professionnel)
* Afficher les informations société dans l'en-tête des fiches produits
* Afficher le numéro client dans les documents de la chaîne des ventes (devis, commandes, fiches d'intervention, contrats, bons de livraison, factures client)
* Afficher le numéro client dans l'en-tête sous la référence client au lieu du cadre d'adresse destinataire (si l'option précédente est activée)
* Afficher les identifiants professionnels de l'émetteur dans l'en-tête (choix indépendant pour chaque identifiant)
* Afficher la date d'ouverture du projet associé dans les notes des Ordres de Fabrication (OF) => modèle de commande client
* Afficher les informations concernant le commercial dans les notes
* Dans les fiches d'intervention afficher les notes (saisies sur le document) dans un tableau indépendant sous celui des consommations de pièces / services
* Afficher une marque de pliage à droite et à gauche de chaque page (choix de la longueur des marques)
* Choisir la couleur de fond des éléments marqués du tableau (minimum => totaux) par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV)
* Appliquer la couleur de fond définie à l'en-tête du tableau
* Choisir le calcul automatique de la couleur (noire ou blanche) de police adaptée au choix précédent ou imposer une couleur choisie (choix graphique ou par code RVB, hexa, ou HSV)
* Choisier la hauteur de l'en-tête des colonnes du tableau (comprise entre 4 et 5 au pas de 0.1 => cette valeur doit être supérieur ou égale à 2 x la valeur du rayon des angles du tableau)
* Afficher / Cacher l’en-tête des colonnes du tableau après la 1ère page
* Choisir l'épaisseur et le type de ligne pour les cadres et lignes des tableaux
* Choisir la couleur pour les cadres, les lignes verticales et les lignes horizontales (indépendamment) des tableaux
* Choisir la hauteur de l'espace de séparation des lignes (éléments) d'un document (si l'option native d'afficage d'une ligne de séparation est désactivée)
* Choisir la hauteur et la largeur des codes barres dans les documents commerciaux (hors fiche produit)
* Choisir la couleur de fond des sous-titres et sous totaux indépendemment par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV) => demande le module sous-total d'ATM ou Milestone/Jalon d'iNodbox
* Choisir la couleur du texte des sous-titres et sous totaux indépendemment par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV) => demande le module sous-total d'ATM ou Milestone/Jalon d'iNodbox
* Désactiver l'utilisation d'une couleur de fond (surlignage) pour les sous-totaux d'ATM
* Coordonner la couleur de fond (surlignage) des sous-totaux avec celle des sous-titres (module d'ATM)
* Choisir la couleur du texte des Ouvrages/Forfaits par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV) => demande le module Ouvrages/ForfaitsOuvrages/Forfaits d'Inovea
* Choisir la couleur de fond des Ouvrages/Forfaits par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV) => demande le module Ouvrages/ForfaitsOuvrages/Forfaits d'Inovea
* Choisir le style du texte des Ouvrages/Forfaits => demande le module Ouvrages/ForfaitsOuvrages/Forfaits d'Inovea
* Afficher une colonne 'Num.' (numéro de ligne) dans les documents de la chaîne des ventes => désactive automatiquement l'affichage de la colonne référence de la chaîne des ventes
* Afficher une colonne “Réf.” Dans les documents de la chaîne des ventes et celle des achats indépendamment (Référence) => désactive automatiquement l'affichage de la référence avec la description
* Choisir l'alignement des colonnes 'Num.' (numéro de ligne) ou 'Réf.' (référence) => à gauche, centré ou à droite
* Afficher les colonnes 'Num.' (numéro de ligne) ou 'Réf.' (référence) en gras
* Dans les documents de la chaîne des ventes, si la colonne référence et la gestion des codes barres sont actives, ajouter la valeur numérique du code barre (Gencode) à la référence
* Forcer l'utilisation de la police par défaut (type, taille et couleur) dans les description longues des produits / services
* Afficher la description sur toute la largeur d'une ligne
* Définir la largeur de la ligne de séparation quand l'option d'affichage de la description sur toute la largeur est active
* Choisir la couleur de la ligne de séparation quand l'option d'affichage de la description sur toute la largeur est active
* Choisir la taille et la couleur des périodes associées aux services présents sur le document
* Cacher les libellés courts des produits / services
* Afficher la description des produits seulement dans les devis / propositions commerciales (active l'option générale masquant la description des produits)
* Dans les documents de la chaine des ventes n'afficher qu'une fois la description longue d'un produit / service utilisé plusieurs fois dans le même document
* Afficher en gras les libellés courts des produits / services (fonctionne uniquement sur les éléments en bibliothèques)
* Positionner les détails additionnels (attributs supplémentaires, informations douanières) avant ou après la description longue des produits / services
* Cacher les dates (de début, de fin, réelles et / ou planifiées) associées aux services dans les documents (devis, commandes, contrats, fiches d'intervention, factures)
* Cacher les durées (totale et ligne par ligne) sur les fiches d'intervention
* Afficher les horaires de début et de fin d'intervention (onglet rapport de la fiche d'intervention. Nécessite le module 'management' - ***Patas-Monkey***)
* Cacher le quantité par ligne dans les documents de la chaîne des ventes (devis, commandes et factures client)
* Cacher le prix unitaire dans les documents de la chaîne des ventes (Devis, commandes et Factures client)
* Cacher la remise par ligne
* Afficher la remise par ligne même pour les lignes optionnelles (sans quantité)
* Afficher une colonne “Prix Unitaire Remisé” dans les documents de vente
* Quand un prix client est défini, afficher le prix par défaut dans la colonne "PU", le prix client dans la colonne "PU remisé" et calculer automatiquement la remise correspondante
* Afficher une colonne total TTC (les totaux HT et TTC seront visibles pour chaque ligne)
* Cacher uniquement la colonne TVA
* Afficher tous les prix en TTC + la TVA en fin de document
* Cacher toutes les informations en rapport avec la TVA (Toutes les valeurs affichées sont TTC)
* Cacher toutes les informations en rapport avec la TVA (Toutes les valeurs affichées sont HT)
* Masquer toutes les colonnes sauf la description produit / service (Devis ou commande client)
* Masquer le total par ligne (colonne) dans les devis sans totaux en pied de document (InfraSPalus-D-ST)
* Afficher les informations de poids, dimensions, volume, surface, la nomenclature douanière (Code SH) et / ou le pays d'origine sur les documents de vente (Devis, commande, Bons de livraison / expéditions ou facture client)
* Gérer le positionnement et la largeur de chaque colonne du document (sauf bons de livraison)
* Afficher une colonne 'Code Barre' dans les bons de livraison (expédition) en lieu et place de la référence produit
* Afficher une colonne contenant un attribut supplémentaire issue des Produits (exemple : position dans le stock) dans les bons de livraison (expédition)
* Choisir le code de l'attribut supplémentaire issue des Produits à afficher
* Cacher la quantité commandée dans les bons de livraison (expédition ou réception)
* Afficher une colonne 'Reliquat' dans les bons de livraison (expédition)
* Gérer le positionnement et la largeur de chaque colonne des bons de livraison (expéditions)
* Afficher une colonne 'Code Barre' dans les bons de réception (livraison) en lieu et place de la référence produit
* Afficher une colonne 'Commentaire' dans les bons de réception (livraison)
* Afficher une colonne 'Reliquat' dans les bons de réception (livraison)
* Gérer le positionnement et la largeur de chaque colonne des bons de réception (livraison)
* Choisir la hauteur de l'espace entre le corps du document (tableau) et les informations de pied de document 
* Choisir la hauteur de l'espace entre le corps du document (tableau) et le total général
* Afficher le nombre total d'éléments de type produit dans le document (Devis, commandes et factures client)
* Afficher le total des remises accordées dans le document
* Afficher l'encours client en pied de facture
* Présenter les totaux des factures de situation suivant 2 méthodes au choix :
	 * le total HT, le total TVA et le total TTC corespondent au cumul des situations, les situations précédentes sont considérées comme des règlements anticipés (sur le TTC) et le TTC de la situation en cours est présenté comme reste à payer.
	 * le cumul des situations et les situations précédentes sont affichés HT, puis le total Ht de la situation en cours est présenté avec son total TVA et son total TTC
* Pour les société Suisse (CH) non assujettie à la TVA l'utilisation de la TVA forfaitaire est possible dans les factures (Présentation client seule, aucun calcul n'est fait en comptabilité)
* Afficher séparément les totaux HT des produits et des services et ventilés par type de TVA (Facture client)
* Afficher une ligne de total TTC supplémentaire dans la monnaie locale, pour les documents en devise
* Activer la mention “Arrêté à” + Total TTC en toutes lettres dans les documents de vente. Nécessite le module Number Words actif ! (Téléchargeable)
* Sélectionner le mode pour lequel le lien de paiement en ligne est activé (Stripe, Paypal, etc...)
* Utiliser des modes de règlements spéciaux (pour ce type de paiement le tableau des détails des règlements sera désactivé et les montants seront séparés du reste des paiements) => gestion de réglements spéciaux type prime tierse (aide gouvernementale, associative, etc...)
* N'afficher QUE l'IBAN et le code BIC du compte proposé pour les règlements (Devis, Commandes, Factures)
* Dans les documents de vente afficher le RIB (et/ou IBAN) du compte bancaire lié même pour le mode de règlement de type CB
* Choisir la hauteur des zones de signature (valeur comprise entre 8 et 48. La largeur est fixe)
* Choisir l'épaisseur, le type de ligne et la couleur pour les cadres des zones de signature
* Recueillir la signature client (signature PAD) et l'appliquer sur le document dans la zone prévue à cet effet
* Choisir la couleur des signatures clients
* Afficher une zone de signature client sur les devis, les devis sans total général, les commandes, le bons de livraison (expéditions) et/ou les fiches d’intervention
	 * fiches d'intervention => 2 zones de signature : technicien et représentant client gérées indépendamment
	 * Ordres de fabrication (OF à partir des commandes) => 2 zones de signature : client et sous-traitant (si module customLink installé et suivant les options choisies)
	 * Bons de livraison (expéditions) => 2 zones de signature : client et sous-traitant (si module customLink installé et suivant les options choisies)
* Afficher le nom de l'intervenant dans la zone de signature société des fiches d'intervention
* Afficher les détails de la société en pied de page (chaque ligne peut être affichée ou masquée indé-pendamment les unes des autres)
	 * Ligne 1 => Adresse du siège social
		* Prévoir 2 lignes pour l'affichage de cette adresse (les lignes suivantes sont décalées vers le bas)
	 * Ligne 2 => Contacts (téléphone, fax, web et mail)
	 * Ligne 3 => Direction
	 * Ligne 3 => Forme juridique et capital
	 * Ligne 4 => Identifiants professionnels
* Afficher les informations du pied de page en gras
* Cacher la numérotation de pages (page x/y) dans les éditions
* Définir la position de la numérotation de page sur les éléments concaténés au document (CGV, documentation technique, …)
* Imprimer la LCR avec les factures client quand c'est le moyen de paiement sélectionné
* Insérer des CGV dans les devis, commandes, contrats ou factures
	 * Plusieurs fichiers de CGV différents possible (différentes langues, différentes activités, etc...)
	 * Choix du réglage par défaut (fichier spécifique ou pas de CGV) pour chaque type de document indépendamment (Devis, commande, contrat ou facture)
	 * Choix d'une gestion en fonction de la langue (si l'option multi-langues est activée dans Dolibarr) => le fichier proposé par défaut pour chaque Tiers est en fonction de la langue renseignée pour ce Tiers
* Insérer des CGI dans les fiches d'intervention
	 * Plusieurs fichiers de CGI différents possible (différentes langues, différentes activités, etc...)
	 * Choix d'une gestion en fonction de la langue (si l'option multi-langues est activée dans Dolibarr) => le fichier proposé par défaut pour chaque Tiers est en fonction de la langue renseignée pour ce Tiers
* Insérer des CGA dans les devis ou commandes fournisseurs
	 * Plusieurs fichiers de CGA différents possible (différentes langues, différentes activités, etc...)
	 * Choix du réglage par défaut (fichier spécifique ou pas de CGA) pour chaque type de document indépendemment (Devis ou commande fournisseur)
	 * Choix d'une gestion en fonction de la langue (si l'option multi-langues est activée dans Dolibarr) => le fichier proposé par défaut pour chaque Tiers fournisseur est en fonction de la langue renseignée pour ce Tiers
* Gérer les logos / images de pied de page (téléchargement, nommage, effacement, …)
* Choisir un fichier à afficher par défaut en pied de page (logos partenaires ...)
* Choisir une image à afficher par défaut en filigrane
* Choisir la hauteur du logo dans l’entête principal des éditions (valeur comprise entre 10 et 50. La largeur est calculée proportionnellement mais ne pourra pas être supérieure à 130)
* Choisir la largeur maximale de l’image de pied de page (la hauteur est calculée proportionnellement)
* Borner la hauteur maximale de l’image de pied de page (la largeur est calculée proportionnellement)
* Éditer un lien à coté de l'image affichée sur le document (le texte affiché est renseigné dans les paramètres et la cible est l'url publique du produit / service)
* Dans les tiers, Enregistrer un des logos de la société émetrice comme fichier à utliser par défaut pour les documents concernant ce tiers
* Dans les documents de la chaine des ventes et / ou les commandes fournisseur afficher l'image de chaque produit / service qui contient au moins une image (la première)
* Dans les documents de la chaine des ventes afficher l'image dans la colonne référence (ou numéro de ligne)
* N'afficher que l'image dans la colonne référence (ou numéro de ligne), celle-ci vient en remplacement des valeurs de référence ou de numéro
* Dans les documents de la chaine des ventes n'afficher qu'une fois l'image d'un produit / service utilisé plusieurs fois dans le même document
* Afficher l'image après la description longue des produits / services
* Coisir la taille de l'intervalle entre l'image du produit / service et le texte de description
* Choisir le texte à afficher comme lien de téléchargement associé à l'image produit / service (renseigné, un lien est créé à coté de l'image du produit avec l'url publique du produit / service)
* Largeur maximale des images affichées (la hauteur est calculée proportionnellement)
* Borner la hauteur maximale des images affichées (la largeur est calculée proportionnellement)
* Rechercher les images en utilisant aussi le chemin antérieur à la version 3.7
* Interdire l'utilisation de vignettes (thumb) en lieu et place d'image HQ
* Gérer l’opacité des filigranes (du texte utilisé et / ou de l’image de fond indépendamment l’un de l’autre)
* Imprimer dans les fiches produits les images associées en respectant l’ordre d’organisation choisi dans l’onglet des fichiers joints
* Imprimer le code barre dans les fiches produits (le module natif de gestion des codes barres doit être activé et paramétré et un code doit être saisie sur la fiche produit concernée)
* Gérer la fonction multi-adresses de votre société (création, modification, suppression, …)
* Choisir une adresse de livraison par défaut (dans la chaîne des achats)
* Si l'option native pour utiliser le nom du contact plutôt que celui de l'entreprise cliente est activée, ajouter la raison sociale en plus du nom du contact (sinon seul le nom sera affiché)
* Forcer l'affichage du pays dans les adresses (sinon il n'est affiché qu'en cas de différence entre le pays de l'émetteur et celui du destinataire du document)
* Utiliser l'adresse de facturation de la maison mère (si renseignée) en lieu et place de l'adresse client => l'ensemble de la configuration des automatismes s'applique à la société mère
* Utiliser le nom commercial alternatif (alias) en remplacement de la raison sociale si une adresse émetteur secondaire est sélectionnée pour la génératrion du document
* Automatiser l’utilisation d’une adresse de facturation client spécifique en choisissant le ‘label’ caractérisant cette adresse
* Utiliser la gestion native des adresses de livraison (par les contacts de suivi livraison)
* Dans le cadre de l'utilisation de la gestion native des adresses de livraison, remplacer les informations du destinataire par celles du contact de livraison sélectionné pour les les commandes (clients ou fournisseurs)
* Afficher l'adresse de livraison sur les documents fournisseurs (devis, commandes)
* Afficher l'adresse de livraison sur les documents clients (devis, commandes et factures ; active automatiquement l'option cachée de gestion multi-adresse des tiers)
* Saisir manuellement l'adresse de livraison => utilisation d'un attribut supplémentaire dédié
* Demander la livraison directement à une adresse client (principale ou secondaire) si l'adresse de livraison sur les documents fournisseurs (devis, commandes) est activée, => Liste de choix avec auto-complétions au 2ème caractère entrée
* Création d’un menu utilisateur non administrateur pour gérer les paramètres du module
* Gérer les droits de modification d’un utilisateur non administrateur onglet par onglet
* Sauvegarder automatiquement les paramètres spécifiques du module lors de la désactivation et réinjecter lesdits paramètres à la réactivation du module
* Activer automatiquement les nouveaux modèles InfraSPack (dans chaque module concerné, exemple : devis, commandes, …) à l’activation du module
* Choisir la couleur du texte appliquée aux valeurs des attributs supplémentaires des documents (choix graphique ou par code RVB, hexa, ou HSV)
* Sélectionner les attributs supplémentaires de documents à imprimer en fonction d'un préfixe appliqué au code de l'attribut
* Sélectionner les attributs supplémentaires liés à un règlement spécial (Ces attributs doivent contenir une valeur numérique ou monétaire ; ils ne seront pas affichés dans les notes mais intégrés au calcul des totaux comme déductions spéciales)
* Sélectionner l'attribut supplémentaire utilisé pour la gestion des acomptes (deposit)(Cet attribut doit contenir une valeur numérique ; il ne sera pas affiché dans les notes mais mentionné après le total TTC comme pourcentage d'acompte demandé)
* Afficher les attributs supplémentaires des devis, commandes, fiches d’intervention, expéditions, factures, demandes de prix, commandes et / ou factures fournisseur dans les notes de ces documents (l’activation de cet affichage pour chaque type de document est indépendant)
* Choisir la couleur du texte appliquée aux valeurs des attributs supplémentaires des lignes de document (choix graphique ou par code RVB, hexa, ou HSV)
* Sélectionner les attributs supplémentaires de lignes à imprimer en fonction d'un préfixe appliqué au code de l'attribut
* Afficher les attributs supplémentaires des lignes de devis, commandes, fiches d’intervention, expéditions, factures, demandes de prix, commandes et / ou factures fournisseur (l’activation de cet affichage pour chaque type de document est indépendant)
* Ajouter des mentions complémentaires sur les fiches produits
* Créer plusieurs types de mentions complémentaire distincts pour chaque type de document
* Gérer les mentions complémentaires des différents types de documents (devis, commandes, fiches d’intervention, expéditions, factures, demandes de prix, commandes et / ou factures fournisseur plus fiche produit) d'une même page de paramètres
* Intégrer systématiquement les mentions complémentaires de base (le choix se fait pour chaque type de document indépendemment les uns des autres)
* Afficher les mentions complémentaires en dernier et sur la largeur de la page
* Automatiser l'utilisation d'une mention liée à une banque (Factor)
* Gérer automatiquement les mentions obligatoires relatives à la TVA (franchise en base de TVA, autoliquidation, export)
* Enregistrer la mention obligatoire à utiliser pour les différentes situations possibles (Micro-entreprise, autoliquidation, exonération, Sous-traitance BTP)
* Ajouter des notes publiques standards sur les fiches produits
* Créer plusieurs types de notes publiques distincts pour chaque type de document
* Utiliser un type de note publique pour créer une page de garde dans les documents clients
* Gérer les notes publiques standards des différents types de documents (devis, commandes, fiches d’intervention, expéditions, factures, demandes de prix, commandes et / ou factures fournisseur plus fiche produit) d'une même page de paramètres
* Intégrer systématiquement les notes publiques standards de base (le choix se fait pour chaque type de document indépendemment les uns des autres)



## Réglages disponibles avant génération du document

* ***Gérer les droits d'accès aux réglages avant génération du document*** (Sans droits, seule la collecte de la signature client est disponible si elle est activée)
* Choisir le logo, l’adresse expéditeur et / ou l’image en pied de page (marques commerciales, partenaires, communication, …)
* Choisir la / les mention(s) complémentaire(s) disponible(s) pour ce type de document à intégrer au fichier PDF généré
* Choisir la / les note(s) publique(s) standrad(s) disponible(s) pour ce type de document à intégrer au fichier PDF généré
* Choisir une adresse de livraison (dans les documents fournisseurs. Cette adresse peut inclure les adresses société comme celles des clients)
	 * Saisie rapide disponible
* Choisir un sous-traitant dans la liste des contacts externes déclarés via le module customLink et choisir son adresse (si plusieurs adresses sont déclarées pour ce tiers sous-traitant)
	 * Saisie rapide disponible
* ***Gérer les droits d'accès aux choix de CGV, CGA ou CGI avant génération du document*** (Sans droits, le choix est masqué et les paramètres généraux sont imposés à l'utilisateur)
* Choisir les CGV, CGA ou CGI à inclure ou ne pas en inclure du tout
* Choisir un ou plusieurs fichier(s) joint(s) pour le(s) fusionner avec le document généré.
* Inclure ou exclure les informations douanières (dimensions, poids, volume, surface et / ou code SH) pour chaque ligne, dans les documents de vente (export à l’international)
* Afficher l'image de chaque produit / service qui contient au moins une image (la première)
* Cacher la description longue des produits / services (seul le libellé court sera imprimé) => En fonction des paramètres généraux validés cette option est activée automatiquement pour les commandes client, les bons de livraison (expédition) et les factures
* Cacher la remise par ligne (prix nets)
* Imprimer sur les lignes de détails seulement la description des produits / services (pour les fiches d'intervention associée au module Management des Patas-Monkey l'affichage de la colonne Quantité n'est pas géré par cette option)
* Afficher le total des remises accordées en pied de document
* Afficher / masquer l'utilisation des modes de règlements spéciaux (devis / proposition commerciales)
* Afficher ou masquer les totaux (HTs, TVAs, TTC) en pied de document (dans les fiches d'intervention associée au module Management des Patas-Monkey)
* Désactiver l'adresse de facturation client automatique
* Recueillir la signature client (signature PAD) et l'appliquer sur le document dans la zone prévue à cet effet
	 * Saisie rapide disponible
* Enregistrer automatiquement les réglages utilisateur par utilisateur pour chaque type de document
* Enregistrer automatiquement les réglages documents (adresses, sous-traitant)



## CE QUI EST NOUVEAU

Voir fichier ChangeLog.



## DOCUMENTATION

La documentation est disponible sous forme d'un document PDF téléchargeable sur le site [InfraS.fr/teclechargements](https://www.infras.fr/index.php?option=com_jdownloads&view=category&catid=11&Itemid=116).


