# 2026-09-18 – Liquid Glass Transparenz & Settings Upgrade-Bereich

## Änderungen

### app.vue – Liquid Glass CSS
- `liquid_glass`: `rgba(255,255,255,0.42)` → `rgba(255,255,255,0.22)` | blur 24→28px | saturate 190→210% + brightness(1.08)
- `liquid_glass_pill`: `rgba(255,255,255,0.45)` → `rgba(255,255,255,0.25)` | blur 16→18px
- `liquid_glass_card`: `rgba(255,255,255,0.48)` → `rgba(255,255,255,0.26)` | blur 26→30px | saturate 200→220%

### pages/settings.vue – Tarif-Vergleich & Upgrade
- Card 3 komplett ersetzt: von simplem 2-Spalten-Grid zu vollständigem Plan-Vergleich (Free / PRO / Enterprise)
- Aktueller Plan wird dynamisch hervorgehoben (Active-Highlighting)
- CTA-Button "PRO-Upgrade anfragen" für Free-User ohne Company (feuert PATCH /api/auth/profile mit upgrade_request:true)
- Input-Felder auf liquid-glass-kompatible Klassen umgestellt (bg-white/70 statt bg-white/90)

### pages/dashboard.vue – Free-Plan Banner
- Link von `/admin` → `/settings` geändert (nur Superadmin durfte /admin sehen)
- v-if="user?.is_superadmin" entfernt (Banner zeigt Upgrade-Button für alle Free-User)
- Hintergrundfarbe: von `bg-amber-500/90` auf `liquid_glass border-amber-300/50` (konsistentes Design)
- Button-Text: "Plan verwalten" → "Auf PRO upgraden"

## Commit
90275d7 auf main, 18. September 2026
