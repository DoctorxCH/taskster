# Technická a Architektonická Závažnosť Systému Taskster pre Strategických Investorov

---

## 1. EXECUTÍVNE ZHRNUTIE & ARCHITEKTÚRA SYSTÉMU

**Platforma Taskster** je Enterprise systém pre projektové a stavebné manažovanie (SaaS, B2B riešenie). Systém je architektonicky navrhnutý tak, aby vyplnil medzeru medzi na jednej strane ľahkými kancelárskymi nástrojmi (ako Trello, Asana), ktoré zlyhávajú v bezpečnostnej granularite a reportingovej komplexite v stavebníctve, a na druhej strane neohrabanými korporátnymi ERP systémami, ktoré sú ťažko použiteľné v teréne a pre subdodávateľov.

**Technologický Stack:**
- **Frontend / Single Codebase:** Vue.js / Nuxt 3 (SSR vypnuté pre mobilné buildy, Single Page Application mód) so stylingom cez Tailwind CSS.
- **Backend / API-First:** PHP / TypeScript (Nitro Framework), s REST architektúrou.
- **Databáza:** MariaDB / PostgreSQL s extenzívnym využitím JSONB polí pre bez-schémovú flexibilitu (napríklad vlastné dáta v úlohách `tasks.custom_data`).
- **Multi-platformové Mobilné Riešenie:** Capacitor 6 (natívny bridge), kompilujúci z jednej Nuxt 3 codebase plnohodnotné natívne kontajnery pre iOS a Android (s lokálnym asset úložiskom a prístupom k hardvéru cez natívne pluginy).

**Kľúčová Architektonická Vlastnosť:**
Platforma stavia na 4-vrstvovej Zero-Trust autorizačnej architektúre s princípom **"Ressourcenverschleierung" (Obfuskácia zdrojov)**. Neoprávnený prístup nevedie k stavu `403 Forbidden`, ale zámerne k `404 Not Found`, čím sa predchádza úniku informácií o samotnej existencii projektov, úloh alebo entít pred konkurenciou alebo neoprávnenými subdodávateľmi.

---

## 2. DETAILNÝ SÚPIS A TECHNICKÝ POPIS VŠETKÝCH IMPLEMENTOVANÝCH FUNKCIÍ

### 2.1. Štruktúra a organizácia projektov
Organizácia dát v systéme odráža prísnu hierarchiu reflektujúcu potreby veľkých projektov:
- **Hierarchia (`schema.sql`):** `Company` (Mandant) -> `project_folders` (Skupiny projektov/Portfólio) -> `projects` (Konkrétne zákazky) -> `lists` (Zoznamy úloh/Fázy/Míľniky) -> `tasks` (Samotné úlohy) -> `task_subtasks` (Podúlohy).
- **Dynamický Dátový Model (Custom Fields):** Priečinky (`project_folders`) umožňujú definovať vlastné dynamické polia v tabuľke `folder_field_definitions` (text, čísla, vzorce). Hodnoty sa serializujú do JSON stĺpca `custom_data` priamo v tabuľke `tasks`. Obsahuje i kalkulačnú logiku (vzorce) priamo definovateľnú pre konkrétne portfólio.
- **Radenie:** Implementované na úrovni databázy a API integrácie prostredníctvom stĺpcov `sort_order` u entít, podporujúc Drag & Drop reorganizáciu na frontende.

### 2.2. Granulárne riadenie prístupov (Zero-Trust & Subdodávatelia)
Architektúra oprávnení je riešená ako centralizovaná pipeline (`server/utils/permissions.ts`), skladajúca sa z presne 4 krokov pre každú požiadavku (evaluateProjectAccess):
1. **Company Policy Check:** Systém zhodnotí globálne pravidlá v `companies.settings` (napr. zákaz nahrávania súborov podľa noriem mandanta). Ak neprejde: vráti `403 Forbidden`.
2. **Project Membership Check:** Systém hľadá rolu používateľa skrz agregáciu - kontroluje priame vlastníctvo priečinka (`pf.owner_id`), explicitné priradenie v `project_members`, priradenie celého priečinka v `folder_members` alebo dedené práva skupín `project_group_access`. Pokiaľ nie je matica vyhodnotená kladne, vráti **`404 Not Found`**.
3. **List Scope Check (List-level viditeľnosť):** V stavebných projektoch je kľúčové ukrývať finančné záznamy. Vlastnosť `access_mode` v `lists` nadobúda stav `inherit` (vidia všetci členovia) alebo `custom`. Ak je list `custom`, používateľ ho uvidí iba vtedy, ak má preň vytvorený záznam v tabuľke `list_access` (s výnimkou vlastníkov). Inak vráti `404 Not Found`.
4. **Role Action Check (RBAC):** Ak používateľ má prístup (ako 'viewer', 'editor', 'admin', 'owner'), overí sa typ HTTP metódy. Ak `viewer` urobí `POST`/`PUT`/`DELETE`, vráti `403 Forbidden`. Toto technicky zaručuje, že napr. subdodávateľ nikdy nezmení dokument bez stopy, keďže UI i API vrstva ho hard-blocknú.

### 2.3. Dokumentácia, auditná stopa a právna záväznosť
- **Projektový Denník (`project_journals`):** Obsahuje revízne logy a manuálne záznamy vrátane metadát z mobilnej appky. Mapované priamo na `project_id` alebo až na granulárnu úroveň `task_id`.
- **E-mailová Integrácia:** Prepracovaný .msg/.eml Drag & Drop Ingestion modul parsuje hlavičky, extrahuje telo, oddelí prílohy a zavedie e-mail priamo do denníka s príslušnými timestampami ako dôkazový materiál.
- **Audionahrávky z terénu:** Integrácia cez `@capacitor-community/media-recorder` zaznamenáva komprimovaný formát (AAC .m4a / Opus). Súbory idú cez `POST /api/ai/transcribe` priamo do Whisper Turbo modelu, z čoho generujú kontextové stavebné denníkové zápisy bez prepisovania.
- **Správa príloh (`project_documents`):** Dedikovaná tabuľka spravujúca súbory (s natívnou podporou versioningu a metadát) zviazaná na úlohy aj žurnál. Zabezpečuje presný audit nad nahrávaním dokumentov.

### 2.4. Finančná kontrola a sledovanie času
- **Sledovanie Času (Time Tracking):** V databáze zaznamenané v tabuľke `time_entries`. Obsahuje `duration_minutes`, `entry_date`, väzbu na `task_id` a `user_id`. Kľúčové je pole `hourly_rate`, reprezentujúce reálnu hodinovú sadzbu pridelenú k práci.
- **Sledovanie Nákladov a Rozpočtov:** Na entitách `tasks` aj `projects` existujú natívne polia `budget_hours` a `budget_amount`. Back-end rutiny (napr. `/api/projects/[id].get.ts`) v reálnom čase agregujú `duration_minutes` a prenásobujú sadzbou `hourly_rate`, následne poskytujú `tracked_minutes`, `tracked_hours` a `tracked_cost` pre dashboard (v reálnom čase).
- Tým je riešené reálne priradenie financií priamo na úrovni jednotlivej odpracovanej hodiny nad konkrétnym problémom (sub-taskom).

### 2.5. Cross-Platform podpora a prevádzka offline
- **Capacitor Core Bridge:** Aplikácia využíva Capacitor pre bezprostredný prístup na HW prístroje z webovej Single-Codebase bázy. Nespúšťa sa vzdialene, bundle sa inštaluje lokálne (s protokolom `capacitor://localhost`), čo redukuje latenciu štartu appky v teréne na zlomok sekundy (nulová odozva).
- **Natívne Pluginy:**
  - `@capacitor/camera`: Inteligentný down-scaling a kompresia (JPEG max 2048px, 85% kvalita) zaisťujú zmenšenie fotografií rýh/stien na ~500 KB pre odoslanie aj na pomalých GPRS/EDGE sieťach, na úrovni hardvérového API pre iOS aj Android.
  - `@capacitor/push-notifications`: Apple APNs aj Firebase Cloud Messaging (FCM).
- **Offline Synchronizácia:** V implementačnom štádiu je SQLite databáza (`@capacitor-community/sqlite`) na mobile. Dáta sa ukladajú v 'mutation_queue' tabuľke, akcie sú pri strate signálu vykonané optimisticky (okamžitá UI reakcia) a pri detekcii sieťového pripojenia asynchrónny worker odosiela dávky (batch) cez API na resolving konfliktov.

### 2.6. Bezpečnosť a Enterprise Compliance
- **Biometria:** Nasadenie `@capgo/capacitor-native-biometric` (Face ID / Touch ID) overuje identitu na stavbe namiesto zadávania hesla na verejnosti, pričom ukladá tokeny v hardvérovo zabezpečenej `Keychain` (iOS) a `EncryptedSharedPreferences` (Android Keystore).
- **GDPR & Obfuskácia:** Mazanie dát cez dedikované `gdpr/account.delete` moduly, nulový únik metadát cez implementovaný mechanizmus plošného vracania chýb 404 (nie 403) pre neschválených pozorovateľov, aby zamedzili akýmkoľvek dohadom o menách či veľkostiach projektov konkurencie.

---

## 3. POTENCIÁL ROZŠÍRENIA & FUTURE ROADMAP (ČO JE MOŽNÉ DOPLNIŤ)

Platforma je svojim flexibilným JSONB základom i pevnou hierarchiou pripravená na škálovanie, pre investora s profilom v infraštruktúre odporúčame tieto priame rozšírenia:

- **Automatizácia a integrácie (Procesná konvergencia):**
  - **BIM & CAD Integrácia:** Pridanie natívneho modulu pre rozparsovanie IFC (Industry Foundation Classes) súborov priamo do systému na zviazanie entít budovy k úlohám (tasks) či výkazom.
  - **IoT Telemetria na Stavbe:** Integrácia API na načítanie dát zo senzorov materiálov (teplota schnutia betónu, tracking vozidiel) a vytvorenie auto-trigger úloh do tabuliek po prijatí webhooku.
  - **ERP Sync (SAP, Pohoda, Kros):** Vyvinutie špecifického obojsmerného API konektora, ktorý bude generovať výkazy odpracovaného času z `time_entries` rovno do mzdového systému bez manuálneho exportu.

- **AI & Pokročilá analytika (Prediktívne modely):**
  - **Auto-vyhodnotenie Denníka:** Využitie existujúcich Large Language Models (LLM) API priamo na celostnú sumarizáciu denných logov, detekciu prichádzajúcich sklzov z tónu audionahrávok (rozpoznanie stresu a sťažností subdodávateľov z prepisu).
  - **Prediktívne Rozpočtovanie:** Nasadenie strojového učenia, ktoré na základe predchádzajúcich projektov vypočíta riziko prepálenia budgetu z reálnych `time_entries` a varuje projektového manažéra proaktívne (predictive alerting).

- **Rozšírenie správy majetku (Asset & Facility Management):**
  - **Plynulý Prechod Lifecycle Budovy:** Rozšírenie tabuliek `projects` do módu údržby, kde sa z "úlohy" stane trvalá evidencia chladenia, údržby káblových trás či servisu s QR kódmi prilepenými fyzicky na zariadení. Mobilná aplikácia Taskster načíta QR z fotoaparátu a v `custom_data` okamžite načíta servisnú knihu daného prvku.

- **Klientsky portál a schvaľovacie procesy (Finančné toky):**
  - **Subdodávateľská Certifikácia Faktúr:** Pridanie entít na schvaľovanie mesačných fakturačných súpisov v nadväznosti na skutočne zdokumentované položky v `tasks`.
  - **Kryptografické Podpisovanie & Digitálne Odovzdávanie:** Zapojenie digitálneho elektronického podpisu priamo v Capacitor aplikácii (biometria + stylus input z iPadu / tabletu) na bezpapierové odovzdávacie protokoly (Handover protocols) a audity kvality (Snagging).
