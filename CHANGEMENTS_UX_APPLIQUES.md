# Récapitulatif des changements UX/UI appliqués

## Où voir les changements ?

1. **Vider le cache Symfony** (en ligne de commande) :
   ```bash
   php bin/console cache:clear
   ```

2. **Recharger la page en dur** dans le navigateur : **Ctrl+F5** (Windows) ou **Cmd+Shift+R** (Mac).

3. **Les breadcrumbs** (fil d'Ariane) apparaissent **tout en haut de la zone de contenu**, juste sous la barre avec le menu hamburger et la recherche — une ligne du type :  
   `Tableau de bord > Locations > LOC-001`

---

## Fichiers modifiés et ce qui a changé

### Base (toutes les pages connectées)
- **templates/base.html.twig**
  - Ajout du bloc `{% block breadcrumbs %}` (affiché au début de la zone de contenu).
  - CSS : breadcrumbs, responsive (sidebar, search, content-area), media queries mobile.

### Dashboard
- **templates/dashboard/index.html.twig**
  - Breadcrumbs : « Tableau de bord ».
  - Alertes cliquables (locations impayées, fin bientôt).
  - Widgets avec indicateur d’occupation (barre de progression).
  - Calendrier FullCalendar des réservations (chargé en bas de page).
  - Légende du calendrier (En cours / À venir / Terminée).

### Locations
- **templates/location/index.html.twig**
  - Breadcrumbs : Tableau de bord > Locations.
  - Filtres en bloc repliable sur mobile + boutons Appliquer / Réinit.
  - Tableau dans un `table-responsive`.

- **templates/location/new.html.twig**
  - Breadcrumbs : Tableau de bord > Locations > Nouvelle location.
  - Assistant en 3 étapes (stepper).
  - Bloc « Aperçu du coût » à droite (montant mensuel, durée, total en temps réel).
  - Bloc « Disponibilité de la face » quand une face est sélectionnée.

- **templates/location/show.html.twig**
  - Breadcrumbs : Tableau de bord > Locations > LOC-xxx.
  - Titre : « Location LOC-xxx » (au lieu de « Location #id »).
  - Bouton **« Nouveau paiement »** (icône cash) dans la barre d’actions.
  - Tableau des paiements dans un `table-responsive`.

- **templates/location/edit.html.twig**  
- **templates/location/annuler.html.twig**
  - Breadcrumbs + titres avec LOC-xxx + boutons en `d-flex flex-wrap gap-2 mt-4`.

### Clients
- **templates/client/index.html.twig**
  - Breadcrumbs ; en-tête avec bouton à droite ; filtres repliables sur mobile ; Appliquer / Réinit. ; `table-responsive`.

- **templates/client/new.html.twig**  
- **templates/client/edit.html.twig**  
- **templates/client/show.html.twig**
  - Breadcrumbs ; en-tête avec boutons ; `table-responsive` sur les tableaux.

### Panneaux
- **templates/panneau/index.html.twig**
  - Filtres repliables sur mobile ; Appliquer / Réinit. ; `table-responsive`.

- **templates/panneau/new.html.twig**  
- **templates/panneau/edit.html.twig**  
- **templates/panneau/show.html.twig**
  - Breadcrumbs ; style des formulaires et boutons ; correction du typo « l' » sur la fiche panneau.

### Paiements
- **templates/paiement/index.html.twig**
  - Breadcrumbs ; en-tête avec bouton ; `table-responsive`.

- **templates/paiement/new.html.twig**  
- **templates/paiement/edit.html.twig**  
- **templates/paiement/show.html.twig**  
- **templates/paiement/annuler.html.twig**
  - Breadcrumbs ; en-têtes ; boutons en `d-flex flex-wrap gap-2 mt-4`.

### Export, Notifications, Face
- **templates/export/index.html.twig** : Breadcrumbs.
- **templates/notification/index.html.twig** : Breadcrumbs.
- **templates/face/locations.html.twig** : Breadcrumbs (Panneaux > [Référence] > Face – Locations) ; `table-responsive`.

### Contrôleurs / Back-end
- **src/Controller/LocationController.php**
  - Route `GET /location/calendrier/events` (API calendrier).
  - Route `GET /location/face/{id}/disponibilite` (API disponibilité face).

- **src/Repository/LocationRepository.php**
  - Méthode `findForCalendar()` pour le calendrier.

---

## Checklist rapide

- [ ] `php bin/console cache:clear` exécuté
- [ ] Rechargement forcé (Ctrl+F5) sur une page (ex. une fiche location)
- [ ] Fil d’Ariane visible en haut du contenu (ex. Tableau de bord > Locations > LOC-xxx)
- [ ] Sur une fiche location : bouton « Nouveau paiement » (à côté de Modifier / Annuler)
- [ ] Sur le dashboard : calendrier des réservations en bas, et alertes si des données existent

Si après ça vous ne voyez toujours pas les changements, indiquez la page exacte (URL ou nom du template) et ce que vous voyez à l’écran (ou une capture).
