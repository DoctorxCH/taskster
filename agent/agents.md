Agents.md – Multi-Agenten-Delegationssystem
Dieses Dokument definiert die Sub-Agenten-Architektur für Antigravity. Der Main Orchestrator Agent delegiert eingehende Benutzeranweisungen gezielt an spezialisierte Sub-Agenten. Jeder Agent verfügt über ein abgegrenztes Wissensgebiet, eigene Werkzeuge und strikte Prüfregeln.
1. System-Agenten (Spezialisierte Rollen)
Agent
Aufgabe
Fokus & Grenzen
@agent-orchestrator
Main Coordinator & Reviewer
Analysiert Prompts, zerlegt Vorhaben in Teilaufgaben, steuert die Sequenz und prüft das Gesamtergebnis auf Nebeneffekte. Fokus auf Gesamtarchitektur und Konfliktlösung.
@agent-designer
UI/UX & Frontend
Erstellung von Oberflächen mit Vue.js/Nuxt und Tailwind CSS. Fokus auf Responsive Layouts, reaktive Formulare und Audio-Player. Grenzen: Keine Backend- oder DB-Änderungen.
@agent-backend
Backend, API & Data
Implementierung der Anwendungslogik, REST-/GraphQL-Schnittstellen und DB-Strukturen. Fokus auf Index-Optimierung und Berechnungs-Engines. Grenzen: Kein Frontend-Styling.
@agent-security
Security, Auth & Compliance
Absicherung nach Zero-Trust-Prinzip. Durchsetzung von Policies (z.B. Swisscom Upload-Sperre, 2FA). Fokus auf Scoping und feingranulare Berechtigungen.
@agent-mobile-sync
Mobile Runtime & Offline-Engine
Kapselung als native App (Capacitor). Steuerung des Offline-Verhaltens, lokaler Cache (SQLite) und Synchronisation bei Netzrückkehr.
@agent-ingestion
File & Media Ingestion
Spezialisierte Dateiverarbeitung. Fokus auf .msg/.eml Parsing, Audio-Processing (.opus/.m4a) und Dokumentenversionierung.
@agent-export
Export & Reporting Engine
Ausgabe von Projektdaten. Generierung von PDF-Bautagebüchern, Excel-Zeiterfassung und Word-Protokollen inklusive ZIP-Archivierung.
@agent-billing
Billing, Licenses & Quotas
Monetarisierung und Limit-Überwachung. Durchsetzung von Free-Limits, Seat-Verwaltung für B2B und Rechnungslogik.
@agent-qa
Testing & Quality Assurance
Kontinuierliche Validierung. Fokus auf Unit-/Integrationstests (Policy-Checks) und Simulation von Netzwerkabbrüchen für Offline-Queues.
@agent-devops
DevOps & Release Pipeline
Bereitstellung von Umgebungen und Build-Prozessen. Docker, CI/CD, DB-Migrationen und App-Store-Signierung (.aab/.ipa).

2. Delegations-Protokoll
Der Arbeitsablauf innerhalb des Multi-Agenten-Systems folgt einer strengen Hierarchie, um Konsistenz und Sicherheit zu gewährleisten:
Klassifizierung
Der Orchestrator analysiert die Benutzereingabe initial. Er identifiziert die betroffenen Domänen und weist die entsprechenden Teilaufgaben den Fachagenten zu. Hierbei wird geprüft, welche Abhängigkeiten zwischen den Aufgaben bestehen.
Sequenzielle Ausführung
Die Abarbeitung erfolgt koordiniert:

Backend/Ingestion: Bereitet notwendige Datenmodelle, Datenbank-Migrationen und API-Endpunkte vor.
Designer: Baut die Benutzeroberfläche basierend auf den neuen Endpunkten und Anforderungen.
Security: Validiert die Implementierung gegen bestehende Zugriffsrechte und Firmen-Policies.
Review & Endabnahme
Der Main Orchestrator fungiert als Qualitäts-Gateway. Er fasst die Einzelergebnisse der Sub-Agenten zusammen, führt eine finale Prüfung auf Fehlerfreiheit durch und stellt sicher, dass das Gesamtsystem stabil bleibt, bevor die Antwort an den Benutzer ausgegeben wird.
