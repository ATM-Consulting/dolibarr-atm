# ADVANCEDPRODUCTSEARCH FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Features

Ajoute sur le formulaire d'ajout de ligne des documents devis, commandes et factures clients la possibilité d'effectuer des recherches de produits sur des ensembles de critères.

Ce module est compatible avec le module DiscountRule en version supérieur ou égale à 2.7 les prix et remises utilisent alors le moteur du module DiscountRules et permet de béneficier de toute la puissance de la gestion tarifaire de ce dernier.

Evolutions à apporter :
- Gestion des extrafields de ligne (à minima pour ceux obligatoires)
- Sur la liste de recherche permettre la sélection des colonnes à afficher
- Permettre une recherche sur les extrafields des produits.

<!--
![Screenshot advancedproductsearch](img/screenshot_advancedproductsearch.png?raw=true "AdvancedProductSearch"){imgmd}
-->

Other external modules are available on [Dolistore.com](https://www.dolistore.com).

## Translations

Translations can be completed manually by editing files into directories *langs*.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->

<!--

## Installation

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you there is no custom directory, check your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### From a GIT repository

- Clone the repository in ```$dolibarr_main_document_root_alt/advancedproductsearch```

```sh
cd ....../custom
git clone git@github.com:gitlogin/advancedproductsearch.git advancedproductsearch
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

-->

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readmes are licensed under GFDL.
