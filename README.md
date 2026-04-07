# DolicraftDashboard

**Le tableau de bord KPI que Dolibarr aurait du avoir depuis le debut.**

[![Dolibarr 16+](https://img.shields.io/badge/Dolibarr-16.0%2B-blue)](https://www.dolibarr.org)
[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-8892BF)](https://www.php.net)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange)](https://github.com/Dolicraft/DolicraftDashboard/releases)

![DolicraftDashboard Cover](https://raw.githubusercontent.com/Dolicraft/DolicraftDashboard/main/img/cover.png)

---

## Pourquoi ce module ?

Le tableau de bord natif de Dolibarr affiche des boites basiques sans aucune vue d'ensemble sur l'activite de votre entreprise. Pas de chiffre d'affaires, pas de tendances, pas de taux de conversion.

**DolicraftDashboard** remplace cette page par un vrai dashboard metier avec 19 widgets personnalisables, des graphiques, et des indicateurs actionables.

---

## Fonctionnalites

### Indicateurs cles (KPI)

| Widget | Description |
|--------|-------------|
| Chiffre d'affaires | CA de la periode avec tendance vs periode precedente |
| Factures impayees | Nombre et montant des factures non reglees |
| Factures en retard | Factures dont la date d'echeance est depassee |
| Devis en cours | Nombre et montant des propositions commerciales ouvertes |
| Commandes en cours | Commandes clients validees/envoyees |
| Commandes fournisseurs | Commandes fournisseurs en attente |
| Nouveaux clients | Nombre de clients crees sur la periode avec tendance |
| Produits vendus | Quantite de produits factures sur la periode |
| Tresorerie | Solde total des comptes bancaires |
| Taux de conversion | Devis signes / devis totaux |
| Delai moyen de paiement | Nombre de jours entre facturation et encaissement |

### Tableaux et graphiques

- **Graphique CA 12 mois** - Evolution mensuelle du chiffre d'affaires (barres CSS)
- **Top 10 clients** - Classement par CA avec pourcentage
- **Top 5 produits** - Produits les plus vendus par CA
- **Dernieres factures** - 10 dernieres factures avec statut
- **Derniers devis** - 10 derniers devis avec statut
- **Alertes stock bas** - Produits sous le seuil d'alerte
- **Agenda du jour** - Evenements et taches du jour
- **Statistiques** - Total clients, produits, devis actifs

### Personnalisation par utilisateur

- **Drag-and-drop** pour reorganiser les widgets
- **Masquer/afficher** chaque widget individuellement (icone oeil)
- **Preferences sauvegardees** par utilisateur (chacun a sa disposition)
- **Bouton reinitialiser** pour revenir a la disposition par defaut
- **Widgets masques** affiches en chips cliquables pour les reactiver

### Remplacement du dashboard Dolibarr

Option dans la configuration pour **remplacer automatiquement la page d'accueil** de Dolibarr par DolicraftDashboard. Activable/desactivable a tout moment.

### Selecteur de periode

- Mois en cours
- Annee en cours
- 30 derniers jours
- 12 derniers mois

---

## Captures d'ecran

> Les captures d'ecran seront ajoutees prochainement.

---

## Installation

### Methode 1 : Depuis le DoliStore

1. Telecharger le module depuis le [DoliStore](https://www.dolistore.com)
2. Deposer le dossier `dolicraftdashboard` dans `htdocs/custom/`
3. Activer le module dans **Configuration > Modules**

### Methode 2 : Depuis GitHub

```bash
cd /path/to/dolibarr/htdocs/custom/
git clone https://github.com/Dolicraft/DolicraftDashboard.git dolicraftdashboard
```

Puis activer le module dans **Configuration > Modules**.

### Methode 3 : Telechargement ZIP

1. Telecharger le ZIP depuis [Releases](https://github.com/Dolicraft/DolicraftDashboard/releases)
2. Extraire dans `htdocs/custom/`
3. Activer le module

---

## Configuration

Apres activation, allez dans **DolicraftDashboard > Configuration** pour :

- Choisir la periode par defaut
- Activer/desactiver chaque widget
- Activer le remplacement du tableau de bord Dolibarr par defaut

---

## Compatibilite

| Composant | Version |
|-----------|---------|
| Dolibarr | 16.0 et superieur |
| PHP | 7.4 et superieur |
| Base de donnees | MySQL 5.7+ / MariaDB 10.3+ |

**Aucune dependance externe.** Le module utilise uniquement les classes natives de Dolibarr et du CSS pur pour les graphiques.

---

## Langues supportees

- Francais (fr_FR)
- English (en_US)
- Espanol (es_ES)
- Deutsch (de_DE)
- Italiano (it_IT)
- Portugues Brasil (pt_BR)

---

## Structure du module

```
dolicraftdashboard/
  core/modules/modDolicraftDashboard.class.php   # Descripteur du module
  class/actions_dolicraftdashboard.class.php      # Hooks (remplacement dashboard)
  admin/
    setup.php                                      # Configuration
    about.php                                      # A propos
  index.php                                        # Page principale du dashboard
  ajax/widget_prefs.php                            # Sauvegarde preferences widgets
  js/dolicraftdashboard.js                         # JavaScript du module
  css/dolicraftdashboard.css                       # Styles
  sql/llx_dolicraftdashboard_user_prefs.sql        # Table preferences utilisateur
  lib/dolicraftdashboard.lib.php                   # Fonctions utilitaires
  img/object_dolicraftdashboard.png                # Icone du module
  langs/{fr_FR,en_US,es_ES,de_DE,it_IT,pt_BR}/    # Traductions
```

---

## Contribuer

Les contributions sont les bienvenues ! Pour contribuer :

1. Fork le projet
2. Creez une branche (`git checkout -b feature/ma-feature`)
3. Committez vos changements (`git commit -m 'feat: ma feature'`)
4. Pushez (`git push origin feature/ma-feature`)
5. Ouvrez une Pull Request

### Signaler un bug

Ouvrez une [issue](https://github.com/Dolicraft/DolicraftDashboard/issues) avec :
- Votre version de Dolibarr et PHP
- Les etapes pour reproduire le bug
- Le message d'erreur (si applicable)

---

## A propos de Dolicraft

**Dolicraft** developpe des modules professionnels pour Dolibarr ERP/CRM.

Je suis Clement, developpeur independant. Je cree des modules qui manquent a la communaute Dolibarr : dashboard, telephonie, migration, IA, gestion des retours.

- Site : [dolicraft.com](https://dolicraft.com)
- Email : contact@dolicraft.com
- DoliStore : [Modules Dolicraft](https://www.dolistore.com)
- LinkedIn : [Dolicraft](https://www.linkedin.com/company/dolicraft)

### Autres modules Dolicraft

| Module | Description | Prix |
|--------|-------------|------|
| **DolicraftDashboard** | Tableau de bord KPI avance | Gratuit |
| **DolicraftS3** | Stockage cloud S3 multi-provider | Gratuit |
| **DolicraftAllo** | Integration telephonie Allo | Gratuit |
| **DolicraftAI** | Assistant IA multi-provider | 80 EUR |
| **DolicraftRevolut** | Integration Revolut Business | 59 EUR HT |
| **DolicraftRMA** | Gestion des retours et SAV | 120 EUR |
| **DolicraftMigratePro** | Migration complete Odoo vers Dolibarr | 180 EUR |

---

## Licence

Ce module est distribue sous licence [GNU General Public License v3.0](LICENSE).

Copyright (C) 2024-2026 [Dolicraft](https://dolicraft.com) - contact@dolicraft.com
