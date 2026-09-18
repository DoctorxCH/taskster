Anweisungen.md – Technische Vorgaben & Coding Guidelines
Diese Richtlinien sind für alle generierten Code-Artefakte und Architektur-Entscheidungen verbindlich.
1. Technologiestack
Backend: API-First (RESTful/JSON), typsichere Controller, strikte Request-Validierung.
Frontend: Single-Page-Application (Vue.js / Nuxt oder vergleichbar modern), Tailwind CSS für komponentenbasierte UI.
Mobile Runtime: Hybrid-Container via Capacitor (iOS & Android) aus identischer Web-Codebasis.
Datenbank: Relational (MariaDB/PostgreSQL), optimiert für JSON-Attribute (JSONB) bei Custom Fields.
2. Berechtigungs- und Sicherheitsregeln
Kein Trust im Client: Jede Zugriffsregel (Free-Tier-Limit, Feldlogik, Sichtbarkeitsstatus) wird ausnahmslos serverseitig in Policies/Services geprüft.
Verschleierung gesperrter Ressourcen: Listen oder Ordner, für die ein Nutzer keine Leseberechtigung hat, müssen in API-Antworten den Status 404 Not Found (oder ein leeres Set) liefern – niemals 403 Forbidden, um die Existenz nicht preiszugeben.
Company Policy Override: Befindet sich ein Nutzer in einem Unternehmens-Account, überschreibt die Richtlinie der Company jede Projekt- und Feldeinstellung.
Offline-Hygiene: Der lokale Speicher (IndexedDB / SQLite) darf ausschließlich Datensätze enthalten, für die der angemeldete Benutzer explizite Berechtigungen besitzt.
3. Dateihandling & Upload-Verarbeitung
Keine Direktverarbeitung im Main-Thread: Das Parsing von .msg-Dateien und die Audiokonvertierung erfolgen asynchron über Hintergrund-Jobs / Queues.
Strikte Trennung von E-Mail-Inhalten: Kopfdaten, Body und Anhänge einer per Drag-and-Drop importierten E-Mail müssen atomar getrennt in der Datenbank und dem Filesystem abgelegt werden.
MIME-Type-Prüfung: Hochgeladene Dateien werden serverseitig auf Inhalt und Signatur geprüft, nicht nur anhand der Dateiendung.
4. UI/UX-Leitplanken
Kontextbezogene Benutzeroberfläche: Viewer-Accounts erhalten keine ausgegrauten Dummy-Buttons, sondern eine aufgeräumte Read-Only-Ansicht ohne visuelles Rauschen.
Feld-Reaktivität: Bedingte Felder (z. B. 'Wenn Gewerk = LWL') müssen im Frontend ohne spürbare Latenz reagieren.
Mobile-First bei Vor-Ort-Aktionen: Die Funktionen 'Zeiterfassung starten', 'Foto anhängen' und 'Sprachnotiz aufnehmen' müssen in der mobilen App innerhalb von maximal 2 Klicks erreichbar sein.

