# 08 Storage, Datei- & System-Infrastruktur

**Ziel:** Storage-Treiber (Lokal, S3, MinIO, Wasabi, R2), Upload-Policies, ClamAV, Bildoptimierung, Retention, Export – alles konfigurierbar.

---

## 1. Datenbank-Schema

### Tabelle: `storage_disks` (Laravel Filesystem Disks)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(50) UNIQUE | `local`, `s3`, `minio`, `wasabi`, `r2`, `tenant_files` |
| `driver` | ENUM('local','s3','ftp','sftp') | |
| `config` | JSON | `{"bucket":"taskster-files","region":"eu-central-1","endpoint":"https://s3.example.com","key":"...","secret":"...","url":"https://cdn.taskster.app","root":""}` |
| `tenant_id` | BIGINT UNSIGNED NULL FK | NULL = Global, sonst Tenant-spezifisch |
| `is_default` | BOOLEAN DEFAULT FALSE | Ein Default pro Tenant/System |
| `is_public` | BOOLEAN DEFAULT FALSE | Publicly accessible URLs |
| `visibility` | ENUM('public','private') DEFAULT 'private' | Default-Visibility |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `upload_policies`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED NULL FK | NULL = Global |
| `category` | ENUM('image','video','document','audio','archive','other') | |
| `max_file_size_mb` | INT | Max Größe pro Datei |
| `allowed_mime_types` | JSON | `["image/jpeg","image/png","image/webp","application/pdf"]` |
| `allowed_extensions` | JSON | `[".jpg",".png",".webp",".pdf"]` |
| `require_virus_scan` | BOOLEAN DEFAULT TRUE | ClamAV vor Speichern |
| `auto_optimize_images` | BOOLEAN DEFAULT TRUE | WebP/AVIF Konvertierung |
| `image_max_width` | INT DEFAULT 2560 | Max Dimension |
| `image_max_height` | INT DEFAULT 2560 | |
| `image_quality` | INT DEFAULT 85 | 0-100 |
| `image_output_format` | ENUM('webp','avif','original') DEFAULT 'webp' | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `files` (Zentrale Datei-Registry)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `uuid` | CHAR(36) UNIQUE | Public ID für URLs |
| `disk_name` | VARCHAR(50) FK | `storage_disks.name` |
| `path` | VARCHAR(500) | Pfad auf Disk |
| `filename` | VARCHAR(255) | Original-Dateiname |
| `mime_type` | VARCHAR(100) | |
| `extension` | VARCHAR(20) | |
| `size_bytes` | BIGINT | |
| `checksum` | CHAR(64) | SHA-256 für Deduplikation |
| `metadata` | JSON | `{"width":1920,"height":1080,"duration":120,"pages":5}` |
| `uploaded_by` | BIGINT UNSIGNED FK | User |
| `tenant_id` | BIGINT UNSIGNED FK | |
| `entity_type` | VARCHAR(30) NULL | `task`, `project`, `message`, `user_avatar` |
| `entity_id` | BIGINT UNSIGNED NULL | |
| `status` | ENUM('uploading','scanning','optimizing','ready','failed','quarantined') DEFAULT 'uploading' | |
| `virus_scan_result` | ENUM('clean','infected','error','pending') DEFAULT 'pending' | |
| `virus_scan_at` | TIMESTAMP NULL | |
| `optimized_path` | VARCHAR(500) NULL | Pfad zur optimierten Version |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `retention_policies`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED NULL FK | NULL = Global |
| `name` | VARCHAR(100) | `Soft-Delete Cleanup`, `Audit Log Retention`, `Temp Files` |
| `target_type` | ENUM('soft_deleted','audit_logs','temp_files','webhook_logs','all') | |
| `entity_types` | JSON NULL | `["tasks","projects","time_entries"]` oder NULL für alle |
| `retention_days` | INT | Tage bis Hard-Delete |
| `schedule` | VARCHAR(100) | Cron: `0 3 * * *` (täglich 3 Uhr) |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `last_run_at` | TIMESTAMP NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `system_exports` (DSGVO/GDPR Export)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED FK | |
| `requested_by` | BIGINT UNSIGNED FK | User |
| `status` | ENUM('pending','running','completed','failed') DEFAULT 'pending' | |
| `format` | ENUM('json','sql','csv') DEFAULT 'json' | |
| `include_files` | BOOLEAN DEFAULT TRUE | Dateien mit exportieren |
| `file_path` | VARCHAR(500) NULL | Pfad zum Export-Archiv |
| `expires_at` | TIMESTAMP NULL | Download-Link Ablauf |
| `error_message` | TEXT NULL | |
| `started_at`, `completed_at` | TIMESTAMP NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |

---

## 2. API-Endpoints

| Methode | Route | Beschreibung |
|---------|-------|--------------|
| **Storage Disks** |
| GET/POST/PUT/DELETE | `/api/admin/storage/disks` | CRUD |
| POST | `/api/admin/storage/disks/{id}/test` | Verbindung testen |
| **Upload Policies** |
| GET/PUT | `/api/admin/upload-policies?tenant_id=1` | Lesen/Schreiben (pro Kategorie) |
| **Files (Admin)** |
| GET | `/api/admin/files?status=failed&tenant_id=1` | Liste mit Filtern |
| GET | `/api/admin/files/{uuid}` | Detail + Metadaten |
| POST | `/api/admin/files/{uuid}/rescan` | Virus-Scan erneut |
| POST | `/api/admin/files/{uuid}/reoptimize` | Bildoptimierung erneut |
| DELETE | `/api/admin/files/{uuid}` | Hard-Delete (Admin only) |
| **Retention Policies** |
| GET/POST/PUT/DELETE | `/api/admin/retention-policies` | CRUD |
| POST | `/api/admin/retention-policies/{id}/run` | Manuell ausführen |
| **System Exports** |
| GET/POST | `/api/admin/exports` | Liste, Neuen Export anstoßen |
| GET | `/api/admin/exports/{id}/download` | Download (signed URL) |
| DELETE | `/api/admin/exports/{id}` | Löschen |

---

## 3. Admin-UI

### Seitenstruktur
```
/admin/settings/storage
├── Disks/
│   ├── Index.vue          # Karten: Disk, Driver, Bucket, Status, Default
│   ├── DiskForm.vue       # Driver-spezifisches Formular (S3: Endpoint, Region, Keys)
│   └── ConnectionTest.vue # Test-Button → grün/rot
├── UploadPolicies/
│   ├── Index.vue          # Tabs pro Kategorie (Bild, Video, Dokument, Audio, Archiv)
│   └── PolicyForm.vue     # Limits, MIME/Ext Whitelist, Virus-Scan, Optimierung
├── Files/
│   ├── Index.vue          # Tabelle mit Status-Badges, Filter, Suche
│   ├── FileDetail.vue     # Metadaten, Virus-Scan, Optimierte Version, Download
│   └── BulkActions.vue    # Rescan, Reoptimize, Delete
├── Retention/
│   ├── Index.vue          # Liste mit Schedule, Letzter Lauf, Nächster Lauf
│   ├── PolicyForm.vue     # Target, Entity-Types, Tage, Cron
│   └── RunHistory.vue     # Log vergangener Läufe
└── Exports/
    ├── Index.vue          # Liste Exports, Status, Fortschritt
    ├── ExportForm.vue     # Format, Dateien inkludieren
    └── ExportDetail.vue   # Progress, Download, Fehler
```

---

## 4. Business-Logik

### File Upload Pipeline
```php
class FileUploadPipeline {
    public function handle(UploadedFile $file, array $options = []): File {
        $policy = UploadPolicy::resolve($file->getMimeType(), $this->tenant);
        
        // 1. Validierung
        $this->validate($file, $policy);
        
        // 2. Temporär speichern
        $tempPath = $file->store('temp', 'local');
        $checksum = hash_file('sha256', $tempPath);
        
        // 3. Deduplikation prüfen
        if ($existing = File::where('checksum', $checksum)->where('tenant_id', $this->tenant->id)->first()) {
            return $this->createReference($existing, $options);
        }
        
        // 4. File Record anlegen (Status: uploading)
        $fileRecord = File::create([
            'uuid' => Str::uuid(),
            'disk_name' => $policy->disk_name ?? 'local',
            'path' => $tempPath,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size_bytes' => $file->getSize(),
            'checksum' => $checksum,
            'uploaded_by' => auth()->id(),
            'tenant_id' => $this->tenant->id,
            'entity_type' => $options['entity_type'] ?? null,
            'entity_id' => $options['entity_id'] ?? null,
            'status' => 'uploading',
        ]);
        
        // 5. Async Pipeline starten
        ProcessFilePipelineJob::dispatch($fileRecord, $policy);
        
        return $fileRecord;
    }
}

// Job: ProcessFilePipelineJob
class ProcessFilePipelineJob implements ShouldQueue {
    public function handle(File $file, UploadPolicy $policy) {
        // Virus Scan
        if ($policy->require_virus_scan) {
            $file->update(['status' => 'scanning']);
            $result = ClamAV::scan($file->getLocalPath());
            $file->update([
                'virus_scan_result' => $result ? 'clean' : 'infected',
                'virus_scan_at' => now(),
            ]);
            if (!$result) {
                $file->update(['status' => 'quarantined']);
                event(new FileQuarantined($file));
                return;
            }
        }
        
        // Bildoptimierung
        if ($policy->auto_optimize_images && Str::startsWith($file->mime_type, 'image/')) {
            $file->update(['status' => 'optimizing']);
            $optimized = ImageOptimizer::optimize($file->getLocalPath(), [
                'max_width' => $policy->image_max_width,
                'max_height' => $policy->image_max_height,
                'quality' => $policy->image_quality,
                'format' => $policy->image_output_format,
            ]);
            $file->update([
                'optimized_path' => $optimized->path,
                'metadata' => array_merge($file->metadata ?? [], $optimized->metadata),
            ]);
        }
        
        // Final auf Ziel-Disk verschieben
        $finalPath = $this->generateFinalPath($file);
        Storage::disk($file->disk_name)->move($file->path, $finalPath);
        
        $file->update([
            'path' => $finalPath,
            'status' => 'ready',
        ]);
        
        event(new FileReady($file));
    }
}
```

### Retention Job (Scheduler)
```php
class RetentionCleanupJob {
    public function handle(RetentionPolicy $policy) {
        $cutoff = now()->subDays($policy->retention_days);
        
        $query = match($policy->target_type) {
            'soft_deleted' => $this->getSoftDeletedModels($policy->entity_types, $cutoff),
            'audit_logs' => AuditLog::where('created_at', '<', $cutoff),
            'temp_files' => File::where('status', 'uploading')->where('created_at', '<', $cutoff),
            'webhook_logs' => WebhookDelivery::where('created_at', '<', $cutoff),
            'all' => $this->getAllModels($cutoff),
        };
        
        $count = $query->forceDelete(); // Hard Delete
        
        $policy->update(['last_run_at' => now()]);
        
        Log::info("Retention cleanup: {$policy->name}", ['deleted' => $count]);
    }
}
```

### System Export (DSGVO)
```php
class SystemExportService {
    public function generate(SystemExport $export): void {
        $export->update(['status' => 'running', 'started_at' => now()]);
        
        $tenant = $export->tenant;
        $data = [
            'tenant' => $tenant->toArray(),
            'users' => $tenant->users()->get()->toArray(),
            'projects' => $tenant->projects()->with('tasks','timeEntries')->get()->toArray(),
            'contacts' => $tenant->contacts()->get()->toArray(),
            'files' => $tenant->files()->get()->toArray(),
            // ... alle relevanten Entitäten
        ];
        
        $filename = "export-{$tenant->slug}-" . now()->format('Y-m-d-H-i-s') . ".{$export->format}";
        $path = "exports/{$filename}";
        
        // Dateien sammeln wenn gewünscht
        if ($export->include_files) {
            $this->archiveFiles($tenant, $path);
        }
        
        // Daten schreiben
        Storage::disk('local')->put($path, $this->formatData($data, $export->format));
        
        $export->update([
            'status' => 'completed',
            'file_path' => $path,
            'completed_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
        
        // Download-Link per E-Mail senden
        Mail::to($export->requestedBy->email)->send(new ExportReadyMail($export));
    }
}
```

---

## 5. Tests

### Unit
- `FileUploadPipelineTest` – Validierung, Deduplikation, Virus-Scan, Optimierung
- `ImageOptimizerTest` – WebP/AVIF Konvertierung, Dimensionen, Qualität
- `RetentionCleanupTest` – Soft-Delete, Audit-Logs, Temp-Files
- `SystemExportTest` – Vollständigkeit, Format, Datei-Inklusion

### Integration
- Upload über verschiedene Disks (Local, S3, MinIO, R2)
- ClamAV Integration (Test-Virus EICAR)
- Retention Scheduler (Cron)
- Export Generation + Download

### E2E
- Admin: Disk konfigurieren (S3 Credentials) → Test → Datei hochladen → Prüfen
- Upload Policy: Max 5MB, nur PDF → 10MB JPG hochladen → Rejected
- Virus Scan: EICAR-Testdatei → Quarantined → Admin sieht Alert
- Retention: Policy 30 Tage → Soft-Delete Task → 31 Tage warten → Hard-Deleted
- Export: DSGVO-Export anstoßen → Warten → Download → JSON validieren

---

## 6. Rollout & Migration

### Migrationen
1. `create_storage_disks_table` + Seeder (Local, S3 Default)
2. `create_upload_policies_table` + Seeder (Defaults pro Kategorie)
3. `create_files_table` (Bestehende Dateien migrieren)
4. `create_retention_policies_table` + Seeder (Defaults: 90 Tage Soft-Delete, 365 Tage Audit)
5. `create_system_exports_table`

### Feature-Flags
- `FEATURE_MULTI_DISK` – Mehrere Storage-Disks
- `FEATURE_VIRUS_SCAN` – ClamAV Integration
- `FEATURE_IMAGE_OPTIMIZATION` – Auto WebP/AVIF
- `FEATURE_RETENTION_POLICIES` – Automatisches Cleanup
- `FEATURE_SYSTEM_EXPORT` – DSGVO Export

### Deployment
1. Migrationen & Seeders
2. ClamAV Daemon deployen (Sidecar/Container)
3. Image Optimizer Dependencies (libvips, cwebp, avifenc)
4. Scheduler für Retention Jobs einrichten
5. Feature-Flags aktivieren

---

## 7. Offene Fragen

- [ ] **ClamAV Deployment:** Daemon auf App-Server vs. separater Container? (Container für Isolation)
- [ ] **Image Optimizer:** `spatie/laravel-image-optimizer` vs. Custom mit `libvips`? (spatie Package)
- [ ] **CDN Invalidation:** Bei Datei-Update/Delete → CDN Purge? (Webhook an Cloudflare/CloudFront)
- [ ] **Chunked Uploads:** Für große Dateien (>100MB) Resumable Uploads? (Tus.io / Uppy, später)
- [ ] **File Versioning:** Versionen bei Überschreiben behalten? (Optional, pro Policy)

---

*Status: **Geplant***