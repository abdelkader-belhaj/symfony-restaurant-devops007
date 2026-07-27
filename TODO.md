# TODO - Modification Réservations

## ✅ Étape 1: Ajouter `reservationStatus` à l'entité Table
- [x] Modifier src/Entity/Table.php - Ajouter champ reservationStatus
- [x] Modifier src/Service/FirebaseService.php - Mettre à jour tableToArray, createTable, updateTable
- [x] Créer migration Doctrine

## ✅ Étape 2: Admin - Page de gestion des réservations améliorée
- [x] Modifier src/Controller/TableFirebaseController.php - Ajouter route changement statut + recherche
- [x] Modifier templates/tableFirebase/index.html.twig - Barre recherche, filtres, boutons statut

## ✅ Étape 3: Profil client amélioré
- [x] Modifier src/Controller/FrontController.php - Route pour les réservations du client
- [x] Modifier templates/profile/client.html.twig - Afficher les réservations, annulation

## ✅ Étape 4: Migration et finalisation
- [x] Exécuter la migration Doctrine

