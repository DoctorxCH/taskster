# 06 Kommunikation, E-Mail & Notification-Engine

**Ziel:** SMTP/Provider-Konfiguration, dynamische Templates (Blade/Twig), Versandkanäle (E-Mail, In-App-Push, WebPush, Webhook), Queue/Rate-Limiting.

---

## 1. Datenbank-Schema

### Tabelle: `mail_transports` (SMTP/Provider)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED NULL FK | NULL = System-Default |
| `name` | VARCHAR(100) | `SMTP Primary`, `SendGrid`, `Resend` |
| `driver` | ENUM('smtp','resend','sendgrid','ses','mailgun') | |
| `config` | JSON | `{"host":"smtp.example.com","port":587,"encryption":"tls","username":"...","password":"...","from_name":"Taskster","from_email":"noreply@taskster.app","reply_to":"support@taskster.app","return_path":"bounce@taskster.app"}` |
| `rate_limit_per_second` | INT DEFAULT 10 | |
| `rate_limit_per_minute` | INT DEFAULT 100 | |
| `is_default` | BOOLEAN DEFAULT FALSE | |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `notification_templates`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(100) UNIQUE | `user.invited`, `task.assigned`, `task.mentioned`, `task.deadline_approaching`, `task.status_changed`, `budget.exceeded` |
| `name` | VARCHAR(100) | Anzeigename |
| `description` | TEXT NULL | |
| `channels` | JSON | `["email","in_app","webpush","webhook"]` – aktivierte Kanäle |
| `subject` | VARCHAR(200) | Betreff (mit Platzhaltern) |
| `body_html` | LONGTEXT | Blade/Twig Template |
| `body_text` | LONGTEXT NULL | Plain-Text Fallback |
| `variables` | JSON | Erwartete Variablen: `["user_name","task_title","task_url","deadline"]` |
| `locale_code` | VARCHAR(10) FK | `locales.code` |
| `is_system` | BOOLEAN DEFAULT FALSE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `notification_preferences` (User-Level)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `user_id` | BIGINT UNSIGNED FK | |
| `template_key` | VARCHAR(100) FK | `notification_templates.key` |
| `channel` | ENUM('email','in_app','webpush','webhook') | |
| `enabled` | BOOLEAN DEFAULT TRUE | |
| `created_at`, `updated_at` | TIMESTAMP | |
| **Unique Index** | (`user_id`, `template_key`, `channel`) | |

### Tabelle: `notifications` (In-App Notifications)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `user_id` | BIGINT UNSIGNED FK | Empfänger |
| `template_key` | VARCHAR(100) | |
| `title` | VARCHAR(200) | Gerenderter Titel |
| `message` | TEXT | Gerenderte Nachricht |
| `data` | JSON | Zusatzdaten: `{"task_id":123,"url":"/tasks/123"}` |
| `read_at` | TIMESTAMP NULL | |
| `archived_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP | |

### Tabelle: `webhook_endpoints`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED FK | |
| `name` | VARCHAR(100) | |
| `url` | VARCHAR(500) | Target URL |
| `secret` | VARCHAR(100) | HMAC-SHA256 Secret |
| `events` | JSON | `["task.created","task.status_changed","time.logged","budget.exceeded"]` |
| `retry_strategy` | ENUM('exponential','fixed','none') DEFAULT 'exponential' | |
| `max_retries` | INT DEFAULT 5 | |
| `timeout_seconds` | INT DEFAULT 30 | |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `last_triggered_at` | TIMESTAMP NULL | |
| `last_status` | ENUM('success','failed') NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `webhook_deliveries` (Log)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `webhook_endpoint_id` | BIGINT UNSIGNED FK | |
| `event` | VARCHAR(100) | |
| `payload` | JSON | |
| `response_code` | INT NULL | |
| `response_body` | TEXT NULL | |
| `attempt` | INT DEFAULT 1 | |
| `status` | ENUM('pending','success','failed','retrying') | |
| `next_retry_at` | TIMESTAMP NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |

---

## 2. API-Endpoints

| Methode | Route | Beschreibung |
|---------|-------|--------------|
| **Mail Transports** |
| GET/POST/PUT/DELETE | `/api/admin/mail-transports` | CRUD |
| POST | `/api/admin/mail-transports/{id}/test` | Test-E-Mail senden |
| **Notification Templates** |
| GET | `/api/admin/notification-templates?locale=de` | Liste |
| GET/POST/PUT/DELETE | `/api/admin/notification-templates` | CRUD |
| POST | `/api/admin/notification-templates/{id}/preview` | Preview mit Sample-Daten |
| **Webhooks** |
| GET/POST/PUT/DELETE | `/api/admin/webhooks` | CRUD |
| POST | `/api/admin/webhooks/{id}/test` | Test-Event senden |
| GET | `/api/admin/webhooks/{id}/deliveries` | Delivery-Log |
| **Notification Preferences (User)** |
| GET | `/api/user/notification-preferences` | Eigene Präferenzen |
| PUT | `/api/user/notification-preferences` | Bulk-Update |

---

## 3. Admin-UI

### Seitenstruktur
```
/admin/settings/notifications
├── MailTransports/
│   ├── Index.vue          # Liste mit Status, Rate-Limits
│   ├── TransportForm.vue  # Driver-spezifisches Formular (SMTP vs API)
│   └── TestEmail.vue      # Test-Mail versenden
├── Templates/
│   ├── Index.vue          # Gruppiert nach Kategorie, Locale-Tabs
│   ├── TemplateEditor.vue # **Kern**: Blade/Twig Editor + Live-Preview
│   ├── VariableReference.vue # Sidebar: verfügbare Variablen
│   └── ChannelToggles.vue # Email/In-App/WebPush/Webhook pro Template
├── Webhooks/
│   ├── Index.vue
│   ├── WebhookForm.vue    # URL, Secret, Events (Multi-Select), Retry-Config
│   └── DeliveryLog.vue    # Tabelle mit Status, Retry-Button, Payload-View
└── QueueMonitor/
    └── Index.vue          # Horizon-Style: Jobs, Failed, Retries, Rate
```

### TemplateEditor.vue – Kern-Feature
- **Split View:** Links Editor (Monaco/CodeMirror), Rechts Live-Preview
- **Syntax Highlighting** für Blade/Twig
- **Variable-Picker:** Klick auf Variable fügt `{{ $variable }}` ein
- **Locale-Tabs:** DE/EN/FR nebeneinander bearbeiten
- **Test-Daten:** JSON-Editor für Sample-Daten → Preview rendert sofort

---

## 4. Business-Logik

### Notification Dispatcher
```php
class NotificationDispatcher {
    public function dispatch(string $event, array $data, ?User $actor = null): void {
        $template = NotificationTemplate::where('key', $event)
            ->where('locale_code', $this->getLocale($data))
            ->first();
        
        if (!$template) return;
        
        $recipients = $this->resolveRecipients($event, $data);
        $rendered = $this->renderTemplate($template, $data);
        
        foreach ($recipients as $user) {
            $prefs = $this->getPreferences($user, $event);
            
            // In-App
            if ($prefs['in_app'] ?? true) {
                Notification::create([
                    'user_id' => $user->id,
                    'template_key' => $event,
                    'title' => $rendered['subject'],
                    'message' => $rendered['html'],
                    'data' => $data,
                ]);
                // WebSocket/Push Event feuern
                broadcast(new NotificationCreated($user->id, $notification));
            }
            
            // E-Mail
            if (($prefs['email'] ?? true) && $template->channels->contains('email')) {
                SendEmailJob::dispatch($user, $template, $rendered, $data);
            }
            
            // WebPush
            if (($prefs['webpush'] ?? false) && $template->channels->contains('webpush')) {
                SendWebPushJob::dispatch($user, $rendered);
            }
            
            // Webhook
            if ($template->channels->contains('webhook')) {
                DispatchWebhooksJob::dispatch($event, $data);
            }
        }
    }
}
```

### Email Queue & Rate Limiting
```php
class SendEmailJob implements ShouldQueue {
    use Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    
    public function handle(MailTransportManager $transportManager) {
        $transport = $this->transport ?? $transportManager->getDefault();
        
        // Rate Limiting pro Transport
        $limiter = RateLimiter::for("mail:{$transport->id}");
        if ($limiter->tooManyAttempts($this->message->getMessageId(), $transport->rate_limit_per_minute)) {
            return $this->release(60); // 1 Minute warten
        }
        
        $limiter->hit($this->message->getMessageId(), 60);
        
        Mail::transport($transport->driver)->to($this->user->email)->send($this->message);
    }
}
```

### Webhook Dispatcher mit Retry
```php
class DispatchWebhooksJob implements ShouldQueue {
    public function handle() {
        $endpoints = WebhookEndpoint::where('is_active', true)
            ->whereJsonContains('events', $this->event)
            ->get();
        
        foreach ($endpoints as $endpoint) {
            WebhookDelivery::create([
                'webhook_endpoint_id' => $endpoint->id,
                'event' => $this->event,
                'payload' => $this->payload,
                'status' => 'pending',
            ])->deliver();
        }
    }
}

// WebhookDelivery Model Method
public function deliver() {
    $signature = hash_hmac('sha256', json_encode($this->payload), $this->endpoint->secret);
    
    $response = Http::timeout($this->endpoint->timeout_seconds)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Event' => $this->event,
            'X-Webhook-Delivery' => $this->id,
        ])
        ->post($this->endpoint->url, $this->payload);
    
    $this->update([
        'response_code' => $response->status(),
        'response_body' => $response->body(),
        'status' => $response->successful() ? 'success' : 'failed',
    ]);
    
    if (!$response->successful() && $this->attempt < $this->endpoint->max_retries) {
        $delay = $this->calculateBackoff($this->attempt);
        $this->update(['status' => 'retrying', 'next_retry_at' => now()->addSeconds($delay)]);
        RedeliverWebhookJob::dispatch($this)->delay($delay);
    }
}
```

---

## 5. Tests

### Unit
- `NotificationDispatcherTest` – Channel-Routing, Preferences, Rendering
- `WebhookDeliveryTest` – HMAC-Signatur, Retry-Strategien, Backoff
- `TemplateRendererTest` – Blade/Twig Rendering, Missing Variables, XSS-Schutz

### Integration
- Email Versand über verschiedene Transports (Mock)
- Webhook Delivery Log + Retry
- In-App Notification + WebSocket Broadcast

### E2E
- Admin: Template bearbeiten → Preview → Test-Mail → Empfangen prüfen
- Webhook: Endpoint anlegen → Event triggern → Delivery-Log prüfen → Retry bei 500
- User: Präferenzen setzen → Event triggern → Nur gewünschte Kanäle erhalten

---

## 6. Rollout & Migration

### Migrationen
1. `create_mail_transports_table` + Seeder (System-Default SMTP)
2. `create_notification_templates_table` + Seeder (Standard-Templates für alle Events)
3. `create_notification_preferences_table`
4. `create_notifications_table` (In-App)
5. `create_webhook_endpoints_table` + `create_webhook_deliveries_table`

### Feature-Flags
- `FEATURE_NOTIFICATION_TEMPLATES` – DB-Templates statt Hardcoded
- `FEATURE_WEBHOOKS` – Outbound Webhooks
- `FEATURE_WEBPUSH` – WebPush (VAPID Keys nötig)

---

## 7. Offene Fragen

- [ ] **Template Engine:** Blade (Laravel) vs. Twig (Symfony) vs. Eigenes? (Blade, da Laravel-Stack)
- [ ] **In-App Real-time:** Laravel Echo + Pusher/Soketi vs. SSE? (SSE für Einfachheit)
- [ ] **Email Tracking:** Open/Click Tracking via Pixel/Link-Wrapping? (Datenschutz beachten)
- [ ] **Digest/Batch Notifications:** Zusammenfassung alle X Minuten statt sofort? (User-Setting)

---

*Status: **Geplant***