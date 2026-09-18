# 2026-09-18: Liquid-Glass-Design & Admin-Dashboard-Isolierung

## 1. Problemstellung
- Text war vor bunten Hintergrundbildern (Wallpapers wie Bamboo Forest, Pilzwelt etc.) an manchen Stellen schwer lesbar (z.B. Breadcrumbs, Headers, Toolbar).
- Der Admin hat in seinem persönlichen Dashboard (/dashboard und /folders) Projekte und Ordner fremder Benutzer gesehen.

## 2. Durchgeführte Änderungen

### A. Admin-Dashboard-Isolierung
- server/api/folders/index.get.ts:
  - Superadmin-Bypass auf der persönlichen Ebene entfernt.
  - Auch Superadmins sehen auf /dashboard und /folders nur noch ihre eigenen Ordner, Ordner ihres zugewiesenen Unternehmens oder Ordner, in deren Projekten sie Mitglied sind.
  - Die systemweite Kunden- und Projektübersicht bleibt strikt dem Site-Admin-Bereich (/admin) vorbehalten.
- server/api/tasks/index.get.ts:
  - Superadmin-Bypass entfernt, sodass auf dem Dashboard nur persönliche oder zugewiesene Aufgaben gelistet werden.

### B. Liquid-Glass-Design & Lesbarkeit
- pp.vue:
  - Hintergrund-Overlay verstärkt (g-slate-950/25 backdrop-blur-[1px]), um Kontrast unabhängig vom gewählten Wallpaper zu garantieren.
  - Globale CSS-Klassen .liquid_glass, .liquid_glass_pill und .liquid_glass_card mit ackdrop-filter: blur(...) saturate(180%), transluzentem Weiß und Kantenakzenten definiert.
- components/Navbar.vue:
  - Header auf .liquid_glass mit weißer Kante umgestellt.
- pages/folders/[id].vue:
  - Breadcrumb in Liquid-Glass-Pill eingebettet.
  - Ordner-Header und Projektkacheln mit Liquid-Glass-Effekt und tiefem Kontrast.
  - Toolbar und Ansichts-Umschalter in Liquid-Glass-Pille platziert.
- pages/projects/[id].vue:
  - Breadcrumb, Header und Ansichtsleiste in Liquid-Glass-Stil umgestaltet.
  - Board-Spalten als Liquid-Glass-Container für maximale Klarheit.
- pages/dashboard.vue:
  - Datumsanzeige und Begrüßung in Liquid-Glass-Pillen für 100%ige Lesbarkeit.
  - Aufgaben-, Ordner- und Benachrichtigungs-Widgets auf Liquid Glass umgestellt.
- pages/settings.vue:
  - Breadcrumbs und Einstellungs-Karten in Liquid-Glass-Design.
