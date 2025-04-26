# Guide de test - Interface Livreur

## Configuration initiale

1. Importer les données de test :
   ```sql
   mysql -u your_username -p your_database < tests/delivery_test_data.sql
   ```

2. Comptes de test :
   - **Livreur 1**
     - Email: jean.dupont@test.com
     - Mot de passe: password
   - **Livreur 2**
     - Email: marie.martin@test.com
     - Mot de passe: password

## Scénarios de test

### 1. Tableau de bord (index.php)

1. **Connexion livreur**
   - Se connecter avec jean.dupont@test.com
   - Vérifier l'accès au tableau de bord

2. **Statistiques du jour**
   - Vérifier l'affichage des statistiques :
     - Total des livraisons
     - Livraisons complétées
     - Livraisons en cours

3. **Livraison en cours**
   - Vérifier les détails de la commande active
   - Tester le bouton "Ouvrir dans Maps"
   - Marquer la livraison comme complétée
   - Vérifier la mise à jour des statistiques

4. **Liste des livraisons en attente**
   - Vérifier l'affichage des commandes
   - Vérifier les informations des restaurants
   - Vérifier les détails des clients

### 2. Historique des livraisons (history.php)

1. **Filtres**
   - Tester le filtre par statut
   - Tester le filtre par date
   - Vérifier la réinitialisation des filtres

2. **Liste des livraisons**
   - Vérifier l'affichage des détails
   - Vérifier le tri par date
   - Tester les liens vers les détails

3. **Statistiques**
   - Vérifier le calcul du total des livraisons
   - Vérifier le calcul des montants

### 3. Détails de livraison (delivery.php)

1. **Informations générales**
   - Accéder aux détails d'une livraison
   - Vérifier la timeline de statut
   - Vérifier les informations client

2. **Détails des restaurants**
   - Vérifier les adresses des restaurants
   - Tester les liens Maps
   - Vérifier les articles commandés

3. **Actions**
   - Tester le bouton de mise à jour de statut
   - Vérifier les calculs de totaux
   - Tester la navigation retour

### 4. Profil (profile.php)

1. **Informations personnelles**
   - Modifier les informations de base
   - Vérifier la validation des champs
   - Tester la mise à jour du numéro de téléphone

2. **Véhicule**
   - Modifier le type de véhicule
   - Mettre à jour le numéro d'immatriculation
   - Vérifier la sauvegarde

3. **Mot de passe**
   - Tester le changement avec mot de passe incorrect
   - Tester le changement avec nouveau mot de passe invalide
   - Tester un changement valide

4. **Statistiques**
   - Vérifier les métriques du mois
   - Vérifier l'historique mensuel
   - Vérifier les calculs de gains

## Cas d'erreur à tester

1. **Validation des formulaires**
   - Soumettre des formulaires vides
   - Tester des formats d'email invalides
   - Tester des numéros de téléphone invalides

2. **Sécurité**
   - Tenter d'accéder aux pages sans connexion
   - Tenter d'accéder aux livraisons d'autres livreurs
   - Vérifier la validation des rôles

3. **Gestion des erreurs**
   - Tester avec des IDs de livraison invalides
   - Vérifier les messages d'erreur
   - Tester la gestion des timeouts

## Notes importantes

- Les données de test incluent 50 livraisons historiques pour tester les statistiques
- Le mot de passe par défaut pour tous les comptes est "password"
- Les adresses sont fictives mais fonctionnent avec Google Maps
- Les montants incluent les frais de livraison (2.99€)
