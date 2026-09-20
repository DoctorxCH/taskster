# Redesign: Admin Sidebar-Submenü (Option C) & Metriken-Bereinigung

- **Datum:** 2026-09-20
- **Betroffene Komponenten:**
  - `app.vue`
  - `pages/admin/index.vue`

## Problemstellung & Anforderung
1. **Sidebar-Submenü (Option C):**
   - Unter dem Navigationspunkt "Administration" in der linken Sidebar soll bei aktiver Admin-Ansicht ein Untermenü erscheinen, welches die Admin-Bereiche direkt anwählbar macht.
   - Der Eintrag "Einstellungen" rutscht dadurch weich nach unten und schafft Platz für die Unterpunkte.
2. **Doppelte / gestapelte Metriken im Admin-Panel:**
   - Bisher wurden oben auf jeder Admin-Seite 5 globale Metriken (Kunden, Unternehmen, Projekte, Aufgaben, Journale) angezeigt.
   - Im Tab "Finanzen & Lizenzen" wurden darunter nochmals 3 Finanzkarten (MRR, Aktive Abos, Sitze) gestapelt, was zu vertikaler Überladung und Doppelungen führte.
   - Die Navigation zwischen den Admin-Bereichen war bisher nur über horizontale Tabs in der Seite möglich und nicht mit der Sidebar synchronisiert.

## Durchgeführte Änderungen

### 1. `app.vue` (Desktop-Sidebar & Mobile-Drawer)
- **Sub-Menü unter "Administration":**
  - Sobald `$route.path.startsWith('/admin')` aktiv ist, wird ein eingerücktes Submenü mit linker Border-Linie (`border-l-2 border-purple-200`) gerendert.
  - Enthaltene Links:
    - Benutzerverwaltung (`/admin?tab=users`)
    - Unternehmen (`/admin?tab=companies`)
    - Finanzen & Lizenzen (`/admin?tab=finance`)
    - Projekt-Vorlagen (`/admin?tab=templates`)
    - E-Mail & Versand (`/admin?tab=email`)
  - Jeder Link berücksichtigt die Berechtigungen des angemeldeten Administrators (`hasAdminPermission(...)`).
  - Aktive Tab-Hervorhebung (`currentAdminTab`) synchron mit `$route.query.tab`.
  - "Einstellungen" liegt darunter im Flex-Container und rückt automatisch nach unten.

### 2. `pages/admin/index.vue`
- **Synchronisation mit Routen-Query (`?tab=...`):**
  - `activeTab` liest und beobachtet `route.query.tab`.
  - `setTab(tab)` aktualisiert `activeTab` und die URL per `router.replace({ query: { ...route.query, tab } })`.
  - Bei Wechsel auf `finance` werden Finanzdaten (`loadOrdersData`) automatisch nachgeladen.
- **Fokussierte Metriken pro Bereich (Keine Doppel-Stapelung):**
  - **Benutzerverwaltung:** Globale Systemübersicht (Kunden, Unternehmen, Projekte, Aufgaben, Journale).
  - **Unternehmen:** Unternehmensmetriken (Organisationen, zugeordnete Mitarbeiter, Upload-Policies).
  - **Finanzen & Lizenzen:** Die 3 Finanzkarten (MRR, Aktive Abos, Kostenpflichtige Sitze) erscheinen als primäre Metriken direkt oben. Die redundante zweite Kartenreihe im Tab wurde entfernt.
  - **Projekt-Vorlagen:** Vorlagen-Metriken (Gesamt, Job/Gewerblich, Privat).
  - **E-Mail & Versand:** E-Mail-Metriken (Provider-Status, aktive Vorlagen, protokollierte Mails).
- **Kontextueller Header:**
  - Titel, Beschreibung, Badge und Haupt-Aktionsbutton (`+ Neuer Benutzer`, `+ Neues Unternehmen`, `🔄 Aktualisieren`, `+ Neue Vorlage`, `✉️ Test-Mail`) passen sich dynamisch an den aktiven Bereich an.
- **Design-System:**
  - Einhaltung der Taskster-Button-Standards (`taskster_button`, `taskster_button_light`, `taskster_button_accent` mit `px-6 text-xs h-[42px] rounded-lg`).
