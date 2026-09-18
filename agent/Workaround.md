Workaround.md – Phasenplan & Meilensteine zur Implementierung
Da das Gesamtsystem Taskster aufgrund seiner Komplexität nicht in einem Durchlauf erstellt werden kann, erfolgt die Umsetzung strikt modular in 6 aufeinander aufbauenden Meilensteinen. Jeder Meilenstein muss vollständig funktionsfähig, getestet und abgenommen sein, bevor der nächste begonnen wird.
Meilenstein 1: Basis-Infrastruktur, Authentifizierung & Mandanten (Company)
Ziel: Lauffähiges Backend und Frontend-Grundgerüst mit Authentifizierung, Mandanten-Logik (companies) und Rollenmodell.

Aufgaben:

Datenbank & Migrationen: Tabellen companies und users anlegen; settings JSON für Unternehmensrichtlinien.
Auth-System: Registrierung, Login, Token-Handling (JWT/Bearer), Middleware für Company-Scope.
Frontend-Setup: SPA-Grundgerüst mit Vue/Tailwind, Auth-Flow.

Definition of Done: User kann sich registrieren/einloggen, ist einer Company zugeordnet; Company-Admin kann settings toggeln.
Meilenstein 2: Projekt- & Listenstruktur mit granularer Zugriffsmatrix
Ziel: Vollständige Projektordner- und Listenverwaltung inklusive des Zero-Trust-Berechtigungssystems (inherit vs. custom).

Aufgaben:

Tabellen: project_folders, projects, project_members, lists, list_access.
Business-Logik: Free-User-Limit (max. 1 Ordner); Rollen (Owner, Editor, Viewer); custom Listenzugriff (404 für nicht freigeschaltete); Schreibschutz für Viewer.
Frontend: Projektordner-Übersicht, Mitgliederverwaltung, Dialog für Listensichtbarkeit.

Definition of Done: Free-Limit greift; Viewer kann nur lesen; custom Liste ist für Unberechtigte unsichtbar.
Meilenstein 3: Dynamisches Feldsystem (Custom Fields) & Pro-Logik
Ziel: Flexible, sortierbare Felddefinitionen auf Ordnerebene mit Formeln und bedingter Sichtbarkeit.

Aufgaben:

Tabellen: folder_field_definitions und tasks (inkl. custom_data JSON).
Feldkonfigurator: CRUD für Felder (Text, Zahl, Dropdown, Datum, Formel etc.), Drag-and-Drop sort_order, Vorlagen.
Logik-Engine: Reaktive Anzeige abhängiger Felder im Frontend; serverseitige Formelberechnung; Free-User-Sperre für Pro-Felder.

Definition of Done: Abhängige Felder reagieren dynamisch; Formelfelder berechnen Werte automatisch.
Meilenstein 4: Projektjournal, E-Mail Drag-and-Drop & Sprachnotizen
Ziel: Rechtssichere Dokumentation vor Ort und nahtloser Import von Kommunikationsdaten.

Aufgaben:

Tabellen: project_journals und project_documents.
Company-Compliance: Globale Sperre für Datei-Uploads bei entsprechender Company-Policy.
Outlook Drag-and-Drop: Drop-Zone für .msg/.eml, Backend-Parser für Header/Text/Anhänge.
Native Sprachnotizen: Aufnahme im Browser/App via MediaRecorder API (.m4a/.opus), integrierter Audio-Player.

Definition of Done: E-Mail erzeugt Journaleintrag mit extrahierten Anhängen; Sprachnotiz kann mit 1 Klick aufgenommen und abgehört werden.
Meilenstein 5: Zeiterfassung, Kostencontrolling & Export-Engine
Ziel: Wirtschaftliche Auswertung von Projekten und Generierung von Kundenberichten.

Aufgaben:

Zeiterfassung: 1-Klick Start/Stopp-Timer, manuelle Schnelleingabe.
Controlling-Logik: Hinterlegung von Stundensätzen, Soll/Ist-Vergleich von Zeit und Budget.
Export-Pipeline: PDF-Berichte (Baustellenjournal mit Logo/Fotos), Excel-Export (.xlsx), Word-Export (.docx), ZIP-Archiv.

Definition of Done: Zeiteinträge fließen in Budgets ein; Exporte sind vollständig und formatiert.
Meilenstein 6: Mobile Runtime (Capacitor) & Offline-Synchronisation
Ziel: Lauffähige iOS- und Android-Apps mit stabiler lokaler Offline-Fähigkeit für Baustellen und Feldeinsatz.

Aufgaben:

Capacitor Integration: Android- und iOS-Projekte aus der Web-Codebasis einrichten; native Plugins (Kamera, Audio, Biometrie).
Offline-Storage: Lokaler Cache via SQLite/IndexedDB; strikte Isolierung (keine fremden Daten).
Sync-Engine: Lokale Queue für Aktionen; asynchroner Abgleich bei Netzverbindung.

Definition of Done: App läuft im Flugmodus; Daten werden bei Wiederverbindung synchronisiert.
