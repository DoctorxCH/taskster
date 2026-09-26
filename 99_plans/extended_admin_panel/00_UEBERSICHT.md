# Erweiterte Admin-Panel Einstellungen – Übersicht

Dieser Ordner enthält die detaillierten Implementierungspläne für jede Funktion des erweiterten Admin-Panels (Superadmin). Jede Funktion hat eine eigene `.md`-Datei mit Schritt-für-Schritt-Anleitung.

## Funktionen (aus der Spezifikation)

| Nr. | Funktion | Datei | Beschreibung |
|-----|----------|-------|--------------|
| 1 | **Design-System, Theming & UI-Tokens** | `01_design_system_theming.md` | CSS-Variablen/Tokens über DB, Light/Dark/High-Contrast, Liquid-Glass, Typografie, Branding |
| 2 | **Dynamische Task-, Workflow- & Projekt-Engine** | `02_workflow_engine.md` | Lookup-Tabellen, State-Machine, Prioritäten, Kategorien, EAV/Custom Fields, Projekt-Hierarchie |
| 3 | **I18n, Lokalisierung & Content-Management (CMS)** | `03_i18n_cms.md` | DB-basierte Übersetzungen, Live-Editing, Platzhalter, Fallback-Kaskade, Rechtstexte, Banner |
| 4 | **Zero-Trust Berechtigungsmatrix, Rollen & Mandanten** | `04_rbac_tenants.md` | Multi-Tenancy, Quotas, Feature-Flags, RBAC+ABAC, Feld-Level-Permissions, Auth-Policies, SSO |
| 5 | **Zeiterfassungs-, Abrechnungs- & Finanzlogik** | `05_time_billing_finance.md` | Buchungsregeln, Rundung, Limits, Stundensatz-Hierarchie, Zeittypen, Währungen |
| 6 | **Kommunikation, E-Mail & Notification-Engine** | `06_notifications.md` | SMTP/Provider, Templates (Blade/Twig), Kanäle (E-Mail, Push, Webhook), Queue/Rate-Limit |
| 7 | **KI-Orchestrierung, Voice & Externe Schnittstellen** | `07_ai_orchestration.md` | Provider-Profile, Model-Routing, Token-Budgets, System-Prompts, Speech-to-Text, Geocoding, Webhooks |
| 8 | **Storage, Datei- & System-Infrastruktur** | `08_storage_infrastructure.md` | Treiber (S3, MinIO, R2), Upload-Policies, ClamAV, Bildoptimierung, Retention, Export |

## Vorgehensweise pro Funktion

Für jede Funktion wird in der jeweiligen `.md`-Datei folgendes dokumentiert:

1. **Datenbank-Schema** – Tabellen, Spalten, Indizes, Foreign Keys
2. **API-Endpoints** – REST-Routen, Request/Response, Validierung
3. **Admin-UI** – Vue-Komponenten, Formulare, Validierung, UX
4. **Business-Logik** – Services, Events, Listener, Cache-Invalidierung
5. **Tests** – Unit, Integration, E2E
6. **Rollout** – Migration, Feature-Flags, Deployment-Schritte

## Nächster Schritt

Wir beginnen mit **Funktion 1: Design-System, Theming & UI-Tokens** (`01_design_system_theming.md`).