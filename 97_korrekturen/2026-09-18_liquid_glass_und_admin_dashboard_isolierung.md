# 2026-09-18: Liquid-Glass-Design & Admin-Dashboard-Isolierung

## 1. Problemstellung
- Text war vor bunten oder dunklen Hintergrundbildern (Wallpapers wie Bamboo Forest, Cloudy Mountain) an manchen Stellen schwer lesbar oder die Kacheln waren nicht transparent genug (zu weiße / deckende Blöcke).
- Der Admin hat in seinem persönlichen Dashboard (/dashboard und /folders) Projekte und Ordner fremder Benutzer gesehen (Root-Cause: Live-Backend `api/index.php` auf Apache hatte noch Superadmin-Bypass).
- Benutzerverwaltung im Admin-Bereich: Direkter 1-Klick-Button "Zu Pro hochstufen" sollte durch ein modales Einstellungsfenster mit umfassenden Benutzer-Optionen ersetzt werden.

## 2. Durchgeführte Änderungen

### A. Admin-Dashboard-Isolierung (PHP & Nitro)
- `api/index.php`, `public/api/index.php`, `server-php/index.php`:
  - `GET folders`: Superadmin-Bypass entfernt. Auf persönlicher Workspace-Ebene sehen auch Superadmins nur noch eigene Ordner, Ordner ihrer Company oder Ordner mit Projektmitgliedschaft.
  - `GET tasks`: Aufgaben-Abfrage auf zugewiesene/eigene Projekte isoliert.
  - `PATCH admin/users/:id`: Vollständige Parameterunterstützung für `name`, `email`, `is_pro`, `is_superadmin`, `company_id`, `company_role`.
- `server/api/folders/index.get.ts` & `server/api/admin/users/[id].patch.ts`:
  - Gleiche Logik für lokale Entwicklungsumgebung synchronisiert.

### B. Liquid-Glass-Design & Höhere Transparenz
- `app.vue`:
  - Transparenz von 62–68% auf 42–48% erhöht:
    - `.liquid_glass`: `rgba(255, 255, 255, 0.42)` mit `blur(24px) saturate(190%)`
    - `.liquid_glass_pill`: `rgba(255, 255, 255, 0.45)` mit `blur(16px) saturate(180%)`
    - `.liquid_glass_card`: `rgba(255, 255, 255, 0.48)` mit `blur(26px) saturate(200%)`
  - Hintergrundbild scheint klar und plastisch durch, während Kontrast und Typografie dank Backdrop-Filterung und Kanten-Glanzlichtern gestochen scharf bleiben.
- `pages/admin/index.vue`:
  - Plattform-Header, Metriken-Kacheln, Tab-Leiste und alle 4 Tabs (Kunden, Unternehmen, Zugriffsregeln, Projekt-Vorlagen) vollständig auf `.liquid_glass` und `.liquid_glass_card` umgestellt.
  - Keine blickdichten, reinweißen (`bg-white`) Flächen mehr.
- `pages/dashboard.vue`:
  - Ordner-Kacheln und Status-Pills auf echtes Liquid Glass umgestellt.
  - Modals für Ordnererstellung und -bearbeitung mit Liquid-Glass-Backdrop versehen.

### C. Admin-Benutzerverwaltung: Einstellungen-Modal
- In der Kundentabelle (`pages/admin/index.vue`) wurde die 1-Klick-Schaltfläche ersetzt durch:
  - Button `taskster_button_light`: `⚙️ Einstellungen`
- Modales Dialogfenster (`showEditUserModal`):
  - Name und E-Mail bearbeiten
  - Plan-Auswahl: Free Plan vs. PRO Plan
  - Firmenzuweisung: Auswahl aus registrierten Unternehmen oder Privatkunde
  - Rolle im Unternehmen: Mitarbeiter (member) vs. Unternehmens-Admin (admin)
  - Superadmin-Status: Checkbox für administrative Plattform-Rechte
  - Standard-Taskster-Buttons (`taskster_button` & `taskster_button_light`).
