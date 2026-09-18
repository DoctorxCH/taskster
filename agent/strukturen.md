Strukturen.md – Datenmodelle & Berechtigungslogik
Diese Dokumentation definiert das zugrunde liegende Datenmodell sowie die logischen Prüfschritte der Berechtigungs-Pipeline für die Applikation. Sie dient als verbindliche Referenz für die Backend-Architektur und API-Entwicklung.
1. Datenbank-Schema (Entity-Relationship-Übersicht)
Das Schema ist in logische Module unterteilt, um die Trennung zwischen globalen Entitäten, Projektstrukturen und Inhalten zu gewährleisten.
Unternehmen & Benutzer
Diese Tabellen bilden das Fundament der Mandantenfähigkeit und Authentifizierung.

Tabelle
Felder
Beschreibung
companies
id (UUID), name (VARCHAR), subscription_plan (VARCHAR), settings (JSON), created_at (TIMESTAMP)
Enthält Mandanten-Einstellungen wie Upload-Limits oder 2FA-Pflicht.
users
id (UUID), company_id (UUID), company_role (VARCHAR), is_superadmin (BOOLEAN), is_pro (BOOLEAN), name (VARCHAR), email (VARCHAR), password_hash (VARCHAR), created_at (TIMESTAMP)
Kern-Benutzerdaten; company_role definiert 'admin' oder 'member'.

Projektordner, Felder & Zugriffsmatrix
Hierarchische Organisation der Projekte und dynamische Felddefinitionen.

Tabelle
Felder
Beschreibung
project_folders
id (UUID), owner_id (UUID), company_id (UUID), name (VARCHAR), created_at (TIMESTAMP)
Oberste Ebene der Projektorganisation.
folder_field_definitions
id (UUID), folder_id (UUID), field_key (VARCHAR), label (VARCHAR), field_type (VARCHAR), is_pro_only (BOOLEAN), formula (TEXT), logic_rules (JSON), options (JSON), is_required (BOOLEAN), sort_order (INT)
Definiert benutzerdefinierte Felder, Formeln und Logikregeln pro Ordner.
projects
id (UUID), folder_id (UUID), title (VARCHAR), status (VARCHAR), created_at (TIMESTAMP)
Konkrete Projektinstanzen innerhalb eines Ordners.
project_members
id (UUID), project_id (UUID), user_id (UUID), role (VARCHAR)
Rollenzuweisung ('editor' oder 'viewer') auf Projektebene.
lists
id (UUID), project_id (UUID), title (VARCHAR), access_mode (VARCHAR), sort_order (INT)
Aufgabenlisten; access_mode kann 'inherit' oder 'custom' sein.
list_access
id (UUID), list_id (UUID), user_id (UUID), is_visible (BOOLEAN)
Feinjustierung der Sichtbarkeit bei access_mode='custom'.

Aufgaben, Journal & Dokumente
Operative Datenobjekte, die innerhalb der Projekte generiert werden.

Tabelle
Felder
Beschreibung
tasks
id (UUID), list_id (UUID), title (VARCHAR), description (TEXT), status (VARCHAR), custom_data (JSON), due_date (TIMESTAMP), sort_order (INT), created_at (TIMESTAMP)
Die zentralen Arbeitseinheiten; custom_data speichert Werte der Felddefinitionen.
project_journals
id (UUID), project_id (UUID), task_id (UUID), author_id (UUID), entry_type (VARCHAR), title (VARCHAR), content (TEXT), metadata (JSON), created_at (TIMESTAMP)
Historie und Logs (manuell, System, E-Mail, Voice).
project_documents
id (UUID), project_id (UUID), task_id (UUID), journal_id (UUID), file_name (VARCHAR), mime_type (VARCHAR), file_size (BIGINT), storage_path (TEXT), version (INT), created_at (TIMESTAMP)
Datei-Referenzen und Metadaten für das Dokumentenmanagement.

2. Berechtigungs-Pipeline (Evaluation Flow)
Um maximale Sicherheit und Datenintegrität zu gewährleisten, durchläuft jede Anfrage an die API eine vierstufige Validierungskette. Ein Scheitern auf einer Stufe bricht den Prozess sofort ab.
Stufe 1: Company Policy Check
Prüfung globaler Restriktionen auf Mandantenebene.

Logik: Überprüfung der settings in der companies-Tabelle.
Beispiel: Falls allow_document_upload auf false gesetzt ist und ein Upload versucht wird.
Resultat bei Fehler: 403 Forbidden.
Stufe 2: Project Membership Check
Prüfung der generellen Berechtigung für den Zugriff auf den Projektkontext.

Logik: Der Anfragende muss entweder der owner des project_folders sein oder ein Eintrag in project_members für das spezifische Projekt existieren.
Resultat bei Fehler: 404 Not Found (Vermeidung von Information Leakage über die Existenz von Projekten).
Stufe 3: List Scope Check
Sicherstellung der Sichtbarkeit innerhalb der Projektstruktur.

Logik:
Wenn lists.access_mode == 'inherit': Zugriff wird gewährt, da Projektmitgliedschaft besteht.
Wenn lists.access_mode == 'custom': Zugriff wird nur gewährt, wenn der Nutzer der owner ist oder ein Eintrag in list_access mit is_visible=true vorliegt.
Resultat bei Fehler: 404 Not Found.
Stufe 4: Role Action Check
Prüfung der funktionalen Berechtigung basierend auf der zugewiesenen Rolle.

Logik:
Nutzer mit der Rolle viewer: Nur Lesemethoden (GET) sind erlaubt. Schreibzugriffe (POST, PUT, DELETE) werden blockiert.
Nutzer mit der Rolle editor oder owner: Alle CRUD-Aktionen sind autorisiert.
Resultat bei Fehler: 403 Forbidden.

