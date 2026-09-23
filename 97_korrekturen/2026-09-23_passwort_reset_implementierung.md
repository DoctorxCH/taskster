# 2026-09-23 Implementierung Passwort-Reset (Passwort vergessen) & Admin-Integration

## Kontext & Änderungen
- **Datenbank-Schema:**
  - Neue Tabelle `password_resets` (id, user_id, email, token, expires_at, created_at) in [api/index.php](file:///c:/Users/marti/Taskster/api/index.php).
- **Backend API Routes:**
  - `POST /api/auth/forgot-password`: Generierung von cryptographischen 32-Byte Reset-Tokens, Speicherung und Auslösung der E-Mail-Vorlage `password_reset`.
  - `GET /api/auth/verify-reset-token`: Token-Validierung und Ablaufprüfung.
  - `POST /api/auth/reset-password`: Passwortaktualisierung mit BCrypt-Hashing und Löschung genutzter Tokens.
- **E-Mail Trigger & Templates:**
  - Neue System-Vorlage `password_reset` in `getDefaultEmailTemplates()` mit Absender-Zuweisung `system@kurka.ch`.
- **Frontend Pages:**
  - [pages/login.vue](file:///c:/Users/marti/Taskster/pages/login.vue): Link "Passwort vergessen?" hinzugefügt.
  - [pages/forgot-password.vue](file:///c:/Users/marti/Taskster/pages/forgot-password.vue): Formular zur Passwort-Reset-Anforderung im Liquid-Glass-Design.
  - [pages/reset-password.vue](file:///c:/Users/marti/Taskster/pages/reset-password.vue): Formular mit Token-Verifikation und Passworteingabe.
