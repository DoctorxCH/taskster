# 07 KI-Orchestrierung, Voice & Externe Schnittstellen

**Ziel:** Provider-Profile (OpenRouter, OpenAI, Anthropic, lokale), Model-Routing, Token-Budgets, System-Prompts, Speech-to-Text, Geocoding, Outbound-Webhooks – alles konfigurierbar im Admin-Panel.

---

## 1. Datenbank-Schema

### Tabelle: `ai_providers`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(50) UNIQUE | `openrouter`, `openai`, `anthropic`, `ollama`, `vllm` |
| `name` | VARCHAR(100) | Anzeigename |
| `type` | ENUM('api','local') | |
| `base_url` | VARCHAR(500) | API-Endpoint (z. B. `https://openrouter.ai/api/v1`) |
| `auth_config` | JSON | `{"type":"bearer","header":"Authorization","prefix":"Bearer"}` |
| `default_headers` | JSON | Zusätzliche Header |
| `rate_limit_rpm` | INT DEFAULT 60 | Requests pro Minute |
| `rate_limit_tpm` | INT DEFAULT 100000 | Tokens pro Minute |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `is_default` | BOOLEAN DEFAULT FALSE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `ai_models`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `provider_id` | BIGINT UNSIGNED FK | |
| `model_id` | VARCHAR(100) | `openai/gpt-4o`, `anthropic/claude-3.5-sonnet`, `llama3.1:70b` |
| `display_name` | VARCHAR(100) | `GPT-4o`, `Claude 3.5 Sonnet` |
| `context_window` | INT | Max Tokens (Input + Output) |
| `max_output_tokens` | INT | |
| `supports_streaming` | BOOLEAN DEFAULT TRUE | |
| `supports_functions` | BOOLEAN DEFAULT FALSE | |
| `supports_vision` | BOOLEAN DEFAULT FALSE | |
| `input_price_per_1m` | DECIMAL(10,4) | Kosten in USD |
| `output_price_per_1m` | DECIMAL(10,4) | |
| `capabilities` | JSON | `{"chat":true,"embeddings":false,"audio":false}` |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `ai_model_routing` (Use-Case → Model Mapping)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `use_case` | VARCHAR(50) UNIQUE | `task_extraction`, `summary`, `analysis`, `chat`, `code_generation`, `translation`, `voice_transcription` |
| `primary_model_id` | BIGINT UNSIGNED FK | `ai_models.id` |
| `fallback_model_id` | BIGINT UNSIGNED NULL FK | |
| `parameters` | JSON | `{"temperature":0.3,"max_tokens":2000,"top_p":0.9}` |
| `tenant_id` | BIGINT UNSIGNED NULL FK | NULL = Global, sonst Tenant-Override |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `ai_system_prompts`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(50) UNIQUE | `default`, `task_extraction`, `summary`, `analysis`, `project_manager`, `code_review` |
| `name` | VARCHAR(100) | |
| `prompt` | LONGTEXT | System-Prompt Text |
| `variables` | JSON | Erwartete Variablen: `["project_context","user_role","language"]` |
| `locale_code` | VARCHAR(10) NULL | NULL = alle, sonst spezifisch |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `ai_token_budgets`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `scope` | ENUM('tenant','user') | |
| `tenant_id` | BIGINT UNSIGNED NULL FK | Bei scope=tenant |
| `user_id` | BIGINT UNSIGNED NULL FK | Bei scope=user |
| `monthly_limit` | BIGINT | Token-Limit pro Monat |
| `current_usage` | BIGINT DEFAULT 0 | Verbrauch aktueller Monat |
| `period_start` | DATE | Monatserster |
| `alert_threshold` | INT DEFAULT 80 | Warnung bei % |
| `block_on_exceed` | BOOLEAN DEFAULT FALSE | Blockieren bei Überschreitung |
| `created_at`, `updated_at` | TIMESTAMP | |
| **Unique Index** | (`scope`, `tenant_id`, `user_id`, `period_start`) | |

### Tabelle: `voice_settings`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED NULL FK | NULL = Global |
| `provider` | ENUM('openai_whisper','groq_whisper','self_hosted_whisper') | |
| `api_endpoint` | VARCHAR(500) NULL | Bei self-hosted |
| `api_key` | VARCHAR(200) NULL | Verschlüsselt |
| `default_language` | VARCHAR(10) NULL | `de`, `en`, `auto` |
| `auto_detect_language` | BOOLEAN DEFAULT TRUE | |
| `technical_vocabulary_prompt` | TEXT NULL | Prompting für Fachbegriffe |
| `max_audio_duration_seconds` | INT DEFAULT 300 | 5 Min |
| `max_file_size_mb` | INT DEFAULT 25 | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `geocoding_settings`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED NULL FK | |
| `provider` | ENUM('nominatim','mapbox','google_maps') | |
| `api_key` | VARCHAR(200) NULL | Verschlüsselt |
| `tile_server_url` | VARCHAR(500) NULL | Für Mapbox/Custom |
| `autocomplete_rate_limit` | INT DEFAULT 10 | Pro Sekunde |
| `default_country_bias` | VARCHAR(2) NULL | `DE`, `CH`, `AT` |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `outbound_webhooks` (AI-spezifisch)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED FK | |
| `name` | VARCHAR(100) | |
| `url` | VARCHAR(500) | Target URL |
| `secret` | VARCHAR(100) | HMAC-SHA256 |
| `events` | JSON | `["ai.task_extracted","ai.summary_generated","ai.analysis_completed"]` |
| `retry_strategy` | ENUM('exponential','fixed') DEFAULT 'exponential' | |
| `max_retries` | INT DEFAULT 3 | |
| `timeout_seconds` | INT DEFAULT 30 | |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `created_at`, `updated_at` | TIMESTAMP | |

---

## 2. API-Endpoints

| Methode | Route | Beschreibung |
|---------|-------|--------------|
| **Providers & Models** |
| GET/POST/PUT/DELETE | `/api/admin/ai/providers` | CRUD |
| GET/POST/PUT/DELETE | `/api/admin/ai/models` | CRUD (Sync von Provider API möglich) |
| POST | `/api/admin/ai/models/sync` | Modelle von Provider abrufen |
| **Model Routing** |
| GET/PUT | `/api/admin/ai/routing?tenant_id=1` | Mapping lesen/schreiben |
| **System Prompts** |
| GET/POST/PUT/DELETE | `/api/admin/ai/prompts` | CRUD |
| POST | `/api/admin/ai/prompts/{id}/test` | Mit Sample-Daten testen |
| **Token Budgets** |
| GET | `/api/admin/ai/budgets?tenant_id=1` | Liste |
| PUT | `/api/admin/ai/budgets/{id}` | Limit, Alert, Block aktualisieren |
| GET | `/api/admin/ai/budgets/{id}/usage` | Aktueller Verbrauch, Trend |
| **Voice** |
| GET/PUT | `/api/admin/ai/voice?tenant_id=1` | Settings |
| POST | `/api/admin/ai/voice/test` | Audio-Datei transkribieren (Test) |
| **Geocoding** |
| GET/PUT | `/api/admin/ai/geocoding?tenant_id=1` | Settings |
| POST | `/api/admin/ai/geocoding/test` | Adresse → Koordinaten testen |
| **Outbound Webhooks** |
| GET/POST/PUT/DELETE | `/api/admin/ai/webhooks` | CRUD |
| POST | `/api/admin/ai/webhooks/{id}/test` | Test-Event senden |

---

## 3. Admin-UI

### Seitenstruktur
```
/admin/settings/ai
├── Providers/
│   ├── Index.vue          # Karten-Grid: Provider, Status, Models, Limits
│   ├── ProviderForm.vue   # API-Key (verschlüsselt), Endpoint, Rate-Limits
│   └── ModelSync.vue      # Button: Modelle von Provider abrufen
├── ModelRouting/
│   ├── Index.vue          # Tabelle: Use-Case → Primary/Fallback Model + Parameter
│   └── RoutingForm.vue    # Use-Case Select, Model Select, Parameter JSON-Editor
├── SystemPrompts/
│   ├── Index.vue
│   ├── PromptEditor.vue   # Monaco Editor + Variable-Picker + Live-Test
│   └── PromptVersions.vue # Historie (optional)
├── TokenBudgets/
│   ├── Index.vue          # Tabelle Tenant/User, Limit, Usage Bar, Alert
│   ├── BudgetForm.vue
│   └── UsageChart.vue     # Tagesverlauf, Projektion
├── Voice/
│   ├── SettingsForm.vue   # Provider, Language, Prompting, Limits
│   └── TestTranscription.vue # Upload → Transkription anzeigen
├── Geocoding/
│   └── SettingsForm.vue   # Provider, API-Key, Rate-Limit, Bias
└── Webhooks/
    ├── Index.vue
    ├── WebhookForm.vue
    └── DeliveryLog.vue
```

### Besondere Features
- **Model Cost Calculator:** Bei Routing-Auswahl geschätzte Kosten pro 1k Requests anzeigen
- **Prompt Playground:** System-Prompt + User-Prompt + Variablen → Live-Response (Streaming)
- **Token Budget Dashboard:** Charts, Alerts, Auto-Block bei Überschreitung

---

## 4. Business-Logik

### AI Gateway Service
```php
class AiGateway {
    public function chat(string $useCase, array $messages, array $options = []): AiResponse {
        // 1. Routing auflösen
        $routing = $this->getRouting($useCase);
        $model = $routing->primaryModel;
        $params = array_merge($routing->parameters, $options);
        
        // 2. Token Budget prüfen
        $this->checkTokenBudget($model, $this->estimateTokens($messages));
        
        // 3. Provider-Client holen
        $client = $this->getProviderClient($model->provider);
        
        // 4. System-Prompt injizieren
        $systemPrompt = $this->getSystemPrompt($useCase);
        $fullMessages = array_merge([['role' => 'system', 'content' => $systemPrompt]], $messages);
        
        // 5. Request mit Retry & Fallback
        try {
            return $client->chat($model->model_id, $fullMessages, $params);
        } catch (RateLimitException $e) {
            if ($routing->fallbackModel) {
                return $this->chatWithModel($routing->fallbackModel, $fullMessages, $params);
            }
            throw $e;
        }
    }
    
    private function checkTokenBudget(Model $model, int $estimatedTokens): void {
        $budget = TokenBudget::getCurrent($this->currentTenant, $this->currentUser);
        if ($budget && $budget->current_usage + $estimatedTokens > $budget->monthly_limit) {
            if ($budget->block_on_exceed) {
                throw new TokenBudgetExceededException();
            }
            // Alert feuern
            event(new TokenBudgetAlert($budget));
        }
    }
}
```

### Token Usage Tracking (Middleware)
```php
class TrackAiUsage {
    public function handle(Request $request, Closure $next) {
        $response = $next($request);
        
        if ($request->route()->named('ai.*')) {
            $usage = $this->extractUsage($response); // input_tokens, output_tokens
            TokenBudget::incrementUsage($this->currentTenant, $this->currentUser, $usage);
        }
        
        return $response;
    }
}
```

### Voice Transcription
```php
class VoiceTranscriptionService {
    public function transcribe(UploadedFile $audio, array $options = []): string {
        $settings = VoiceSettings::getCurrent($this->tenant);
        
        $client = match($settings->provider) {
            'openai_whisper' => new OpenAiWhisperClient($settings->api_key),
            'groq_whisper' => new GroqWhisperClient($settings->api_key),
            'self_hosted_whisper' => new SelfHostedWhisperClient($settings->api_endpoint),
        };
        
        return $client->transcribe($audio, [
            'language' => $options['language'] ?? $settings->default_language,
            'prompt' => $settings->technical_vocabulary_prompt,
            'temperature' => 0.0,
        ]);
    }
}
```

---

## 5. Tests

### Unit
- `AiGatewayTest` – Routing, Fallback, Budget-Check, Parameter-Merging
- `TokenBudgetTest` – Increment, Alert, Block, Monthly Reset
- `VoiceTranscriptionTest` – Provider-Mock, Language-Detect, Prompting

### Integration
- Model Sync von Provider APIs
- Chat Completion über verschiedene Provider
- Webhook Delivery für AI-Events

### E2E
- Admin: Provider hinzufügen → Models syncen → Routing setzen → Chat testen
- Token Budget: Limit setzen → Requests senden → Alert → Block prüfen
- Voice: Audio aufnehmen → Transkribieren → Ergebnis prüfen
- Prompt: System-Prompt bearbeiten → Playground testen → Speichern

---

## 6. Rollout & Migration

### Migrationen
1. `create_ai_providers_table` + Seeder (OpenRouter, OpenAI, Anthropic, Ollama)
2. `create_ai_models_table` + Seeder (Initial Sync via Script)
3. `create_ai_model_routing_table` + Seeder (Defaults pro Use-Case)
4. `create_ai_system_prompts_table` + Seeder (Default-Prompts)
5. `create_ai_token_budgets_table`
6. `create_voice_settings_table` + Seeder
7. `create_geocoding_settings_table` + Seeder
8. `create_outbound_webhooks_table` (AI-spezifisch)

### Feature-Flags
- `FEATURE_AI_GATEWAY` – Zentrale AI-Orchestrierung
- `FEATURE_AI_TOKEN_BUDGETS` – Budget-Limits
- `FEATURE_VOICE_TRANSCRIPTION` – Speech-to-Text
- `FEATURE_GEOCODING` – Adress-Autocomplete

---

## 7. Offene Fragen

- [ ] **Provider Secrets:** Verschlüsselung in DB (Laravel Encrypter) vs. Vault? (Laravel Encrypter für Start)
- [ ] **Model Sync:** Automatisch (Cron) oder manuell? (Beides: Button + Daily Cron)
- [ ] **Streaming Support:** SSE für Chat-Responses im Frontend? (Ja, für UX)
- [ ] **Function Calling:** Einheitliches Schema über Provider hinweg? (Später, v2)
- [ ] **RAG/Knowledge Base:** Dokument-Upload für Context? (Separates Feature, v2)

---

*Status: **Geplant***