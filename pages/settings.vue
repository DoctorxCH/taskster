<template>
  <div class="w-full max-w-[1920px] 2xl:max-w-[2400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <!-- Header Card -->
    <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <!-- Breadcrumb -->
        <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-2">
          <NuxtLink to="/dashboard" class="hover:text-cyan-800 transition-colors flex items-center gap-1">
            <LayoutDashboard class="w-3.5 h-3.5" />
            <span>{{ $t('common.dashboard') }}</span>
          </NuxtLink>
          <span>/</span>
          <span class="text-slate-800 font-medium flex items-center gap-1">
            <SettingsIcon class="w-3.5 h-3.5 text-[#0891B2]" />
            <span>{{ $t('common.einstellungen') }}</span>
          </span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $t('common.einstellungen') }}</h1>
        <p class="text-sm text-slate-600 mt-1">
          {{ $t('settings.profil_kalender_benachrichtigungen_') }}
        </p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <span
          v-if="dirty"
          class="inline-flex items-center gap-1.5 h-9 px-3 rounded-md bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-800"
        >
          <CircleDot class="w-3.5 h-3.5" />
          {{ $t('common.ungespeicherte_anderungen') }}
        </span>
        <button
          type="button"
          class="taskster_button cursor-pointer"
          :disabled="saving || !dirty"
          @click="saveAll"
        >
          <Save class="w-4 h-4" />
          <span>{{ saving ? $t('settings.speichern_laeuft') : $t('common.alle_anderungen_speichern') }}</span>
        </button>
      </div>
    </div>

    <!-- Feedback -->
    <div v-if="successMsg" class="mb-5 p-3.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-2">
      <CheckCircle2 class="w-4 h-4 shrink-0" />
      {{ successMsg }}
    </div>
    <div v-if="errorMsg" class="mb-5 p-3.5 rounded-md bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center gap-2">
      <AlertCircle class="w-4 h-4 shrink-0" />
      {{ errorMsg }}
    </div>

    <div class="flex flex-col lg:flex-row gap-6 items-start">
      <!-- ================= Kategorien-Navigation ================= -->
      <nav class="w-full lg:w-60 shrink-0 lg:sticky lg:top-20">
        <div class="bg-white border border-slate-200 rounded-lg p-2">
          <button
            v-for="s in sections"
            :key="s.key"
            type="button"
            class="w-full flex items-center gap-3 px-3 h-10 rounded-md text-sm font-medium transition-colors text-left"
            :class="active === s.key
              ? 'bg-cyan-50 text-cyan-800 font-semibold'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            @click="active = s.key"
          >
            <component :is="s.icon" class="w-4 h-4 shrink-0" :class="active === s.key ? 'text-[#0891B2]' : 'text-slate-400'" />
            <span class="flex-1">{{ s.label }}</span>
            <span
              v-if="s.key === 'notifications' && settings.notifications.browser"
              class="w-1.5 h-1.5 rounded-full bg-emerald-500"
              :title="$t('settings.browserbenachrichtigungen_aktiv')"
            />          </button>
        </div>

        <!-- Konto-Info -->
        <div class="mt-3 bg-white border border-slate-200 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-[#0891B2] text-white flex items-center justify-center font-bold text-sm shrink-0 overflow-hidden">
              <img v-if="user?.avatar" :src="user.avatar" :alt="user.name" class="w-full h-full object-cover" />
              <span v-else>{{ (user?.name || '?').charAt(0).toUpperCase() }}</span>
            </div>
            <div class="min-w-0">
              <div class="text-sm font-semibold text-slate-900 truncate">{{ user?.name }}</div>
              <div class="text-[11px] text-slate-500 truncate">{{ user?.email }}</div>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold border" :class="planBadgeClass">
              {{ planLabel }}
            </span>
            <span v-if="user?.company_name" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200 truncate max-w-full">
              {{ user.company_name }}
            </span>
          </div>
        </div>
      </nav>

      <!-- ================= Inhalt ================= -->
      <div class="flex-1 min-w-0 space-y-5">

        <!-- ---------- PROFIL ---------- -->
        <section v-show="active === 'profile'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <User class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('common.profil') }}</h2>
          </header>
          <div class="p-5 space-y-5">
            <!-- Profilbild (Avatar) -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
              <div class="relative group">
                <div class="w-20 h-20 rounded-2xl bg-[#0891B2] text-white flex items-center justify-center font-black text-2xl shrink-0 overflow-hidden shadow-md border-2 border-white">
                  <img v-if="profileAvatar" :src="profileAvatar" :alt="profileName" class="w-full h-full object-cover" />
                  <span v-else>{{ (profileName || user?.name || '?').charAt(0).toUpperCase() }}</span>
                </div>
                <button
                  type="button"
                  @click="triggerAvatarUpload"
                  class="absolute inset-0 bg-slate-900/50 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-[10px] font-bold cursor-pointer"
                  :title="$t('settings.bild_aendern')"
                >
                  <Camera class="w-5 h-5 mb-0.5" />
                  <span>{{ $t('settings.aendern') }}</span>
                </button>
              </div>

              <div class="space-y-1.5 flex-1">
                <h4 class="text-xs font-bold text-slate-900">{{ $t('settings.dein_profilbild') }}</h4>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                  {{ $t('settings.profilbild_hinweis') }}
                </p>
                <div class="flex items-center gap-2 pt-1">
                  <input
                    ref="avatarInputRef"
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    class="hidden"
                    @change="onAvatarFileSelected"
                  />
                  <button
                    type="button"
                    class="taskster_button_light px-4 text-xs h-[34px] rounded-lg cursor-pointer"
                    @click="triggerAvatarUpload"
                  >
                    <Upload class="w-3.5 h-3.5 mr-1" />
                    <span>{{ $t('settings.bild_hochladen') }}</span>
                  </button>
                  <button
                    v-if="profileAvatar"
                    type="button"
                    class="taskster_button_accent px-4 text-xs h-[34px] rounded-lg cursor-pointer"
                    @click="removeAvatar"
                  >
                    <Trash2 class="w-3.5 h-3.5 mr-1" />
                    <span>{{ $t('settings.entfernen') }}</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('login.vollständiger_name') }}</label>
                <input v-model="profileName" type="text" class="ts-input" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.email_adresse') }}</label>
                <input :value="user?.email" type="email" disabled class="ts-input bg-slate-50 text-slate-500 cursor-not-allowed" />
                <p class="text-[11px] text-slate-500 mt-1.5">{{ $t('settings.dient_als_loginkennung_und_kann_nic') }}</p>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.darstellung_sprache') }}</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('common.app_sprache') }}</label>
                  <select v-model="settings.language" @change="onLanguageChange" class="ts-input cursor-pointer">
                    <option value="de">Deutsch (DE)</option>
                    <option value="en">English (EN)</option>
                    <option value="sk">Slovenčina (SK)</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    {{ $t('settings.whisper_sprache') }} <span class="text-[#0891B2] font-mono text-[10px]">AI</span>
                  </label>
                  <select v-model="settings.whisper_language" class="ts-input">
                    <option value="de">{{ $t('settings.whisper_lang_de') }}</option>
                    <option value="de-CH">{{ $t('settings.whisper_lang_dech') }}</option>
                    <option value="en">{{ $t('settings.whisper_lang_en') }}</option>
                    <option value="fr">{{ $t('settings.whisper_lang_fr') }}</option>
                    <option value="it">{{ $t('settings.whisper_lang_it') }}</option>
                    <option value="auto">{{ $t('settings.automatisch_erkennen') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.design') }}</label>
                  <select v-model="settings.theme" class="ts-input">
                    <option value="light">{{ $t('settings.hell') }}</option>
                    <option value="dark">{{ $t('settings.dunkel_in_vorbereitung') }}</option>
                    <option value="system">{{ $t('settings.systemeinstellung') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.listen_dichte') }}</label>
                  <select v-model="settings.density" class="ts-input">
                    <option value="comfortable">{{ $t('settings.komfortabel') }}</option>
                    <option value="compact">{{ $t('settings.kompakt') }}</option>
                  </select>
                </div>
              </div>
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.startseite_nach_dem_login') }}</label>
                  <select v-model="settings.start_page" class="ts-input">
                    <option value="dashboard">{{ $t('common.dashboard') }}</option>
                    <option value="calendar">{{ $t('common.kalender') }}</option>
                    <option value="time">{{ $t('common.zeitrapporte') }}</option>
                    <option value="contacts">{{ $t('common.kontakte') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    {{ $t('settings.zeitzone') }}
                    <span class="text-slate-400 font-normal">({{ currentTimeInSelectedTimezone }})</span>
                  </label>
                  <select v-model="settings.timezone" class="ts-input cursor-pointer">
                    <option v-for="tz in timezones" :key="tz.value" :value="tz.value">
                      {{ tz.label }}
                    </option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ---------- KALENDER ---------- -->
        <section v-show="active === 'calendar'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <CalendarDays class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('common.kalender') }}</h2>
          </header>
          <div class="p-5 space-y-6">

            <!-- Ansicht & Woche -->
            <div>
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.ansicht_woche') }}</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_ansicht') }}</label>
                  <select v-model="settings.calendar.default_view" class="ts-input">
                    <option value="month">{{ $t('settings.monat') }}</option>
                    <option value="week">{{ $t('settings.woche') }}</option>
                    <option value="day">{{ $t('settings.tag') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.erster_wochentag') }}</label>
                  <select v-model.number="settings.calendar.week_start" class="ts-input">
                    <option :value="1">{{ $t('settings.montag') }}</option>
                    <option :value="0">{{ $t('settings.sonntag') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.zeitformat') }}</label>
                  <select v-model="settings.calendar.time_format" class="ts-input">
                    <option value="24h">{{ $t('settings.24_stunden_1700') }}</option>
                    <option value="12h">{{ $t('settings.12_stunden_500_pm') }}</option>
                  </select>
                </div>
              </div>

              <div class="mt-4 space-y-1">
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_week_numbers" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.kalenderwochen_anzeigen') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.zeigt_die_kwnummer_links_neben_jede') }}</span>
                  </span>
                </label>
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_weekends" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.wochenenden_anzeigen') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.samstag_und_sonntag_in_der_wochenan') }}</span>
                  </span>
                </label>
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_tasks" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.aufgaben_mit_fälligkeitsdatum_anzei') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.aufgaben_erscheinen_als_schreibgesc') }}</span>
                  </span>
                </label>
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_declined" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.abgesagte_termine_anzeigen') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.termine_die_du_abgelehnt_hast_weite') }}</span>
                  </span>
                </label>
              </div>
            </div>

            <!-- Arbeitszeit -->
            <div class="pt-5 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">{{ $t('settings.arbeitszeit') }}</h3>
              <p class="text-[11px] text-slate-500 mb-3">
                {{ $t('settings.bestimmt_den_hervorgehobenen_bereic') }}
              </p>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.arbeitsbeginn') }}</label>
                  <input v-model="settings.calendar.workday_start" type="time" class="ts-input" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.arbeitsende') }}</label>
                  <input v-model="settings.calendar.workday_end" type="time" class="ts-input" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.rasterauflösung') }}</label>
                  <select v-model.number="settings.calendar.slot_minutes" class="ts-input">
                    <option :value="15">{{ $t('settings.15_minuten') }}</option>
                    <option :value="30">{{ $t('settings.30_minuten') }}</option>
                    <option :value="60">{{ $t('settings.60_minuten') }}</option>
                  </select>
                </div>
              </div>
              <p v-if="workdayInvalid" class="mt-2 text-[11px] font-medium text-rose-600">
                {{ $t('settings.das_arbeitsende_muss_nach_dem_arbei') }}
              </p>
            </div>

            <!-- Neue Termine -->
            <div class="pt-5 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.voreinstellungen_für_neue_termine') }}</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_dauer') }}</label>
                  <select v-model.number="settings.calendar.default_duration_minutes" class="ts-input">
                    <option :value="15">{{ $t('settings.15_minuten') }}</option>
                    <option :value="30">{{ $t('settings.30_minuten') }}</option>
                    <option :value="45">{{ $t('settings.45_minuten') }}</option>
                    <option :value="60">{{ $t('settings.1_stunde') }}</option>
                    <option :value="90">{{ $t('settings.15_stunden') }}</option>
                    <option :value="120">{{ $t('settings.2_stunden') }}</option>
                    <option :value="480">{{ $t('settings.ganzer_arbeitstag') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_erinnerung') }}</label>
                  <select v-model="reminderModel" class="ts-input">
                    <option value="none">{{ $t('settings.keine') }}</option>
                    <option value="5">{{ $t('settings.5_minuten_vorher') }}</option>
                    <option value="15">{{ $t('settings.15_minuten_vorher') }}</option>
                    <option value="30">{{ $t('settings.30_minuten_vorher') }}</option>
                    <option value="60">{{ $t('settings.1_stunde_vorher') }}</option>
                    <option value="1440">{{ $t('settings.1_tag_vorher') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_kategorie') }}</label>
                  <select v-model="settings.calendar.default_category_id" class="ts-input">
                    <option :value="null">{{ $t('settings.keine') }}</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_sichtbarkeit') }}</label>
                  <select v-model="settings.calendar.default_visibility" class="ts-input">
                    <option value="private">{{ $t('settings.privat_nur_ich_eingeladene') }}</option>
                    <option v-if="user?.company_id" value="company">{{ $t('settings.für_firma_sichtbar') }}</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ---------- BENACHRICHTIGUNGEN ---------- -->
        <section v-show="active === 'notifications'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Bell class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('common.benachrichtigungen') }}</h2>
          </header>
          <div class="p-5 space-y-6">

            <!-- Kanäle -->
            <div>
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.kanäle') }}</h3>
              <div class="space-y-1">
                <label class="ts-toggle">
                  <input v-model="settings.notifications.in_app" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.in_der_app') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.glockensymbol_in_der_navigation_emp') }}</span>
                  </span>
                </label>

                <label class="ts-toggle">
                  <input v-model="settings.notifications.browser" type="checkbox" class="ts-check" @change="onBrowserToggle" />
                  <span class="flex-1">
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.browser_benachrichtigungen') }}</span>
                    <span class="block text-[11px] text-slate-500">
                      {{ $t('settings.hinweise_direkt_im_betriebssystem_a') }}
                      <span v-if="browserPermission === 'denied'" class="text-rose-600 font-medium">
                        {{ $t('settings.der_browser_hat_die_erlaubnis_block') }}
                      </span>
                      <span v-else-if="browserPermission === 'granted'" class="text-emerald-600 font-medium">
                        {{ $t('settings.erlaubnis_erteilt') }}
                      </span>
                    </span>
                  </span>
                  <span
                    v-if="browserPermission === 'granted'"
                    class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                  >
                    {{ $t('settings.aktiv') }}
                  </span>
                </label>

                <label class="ts-toggle">
                  <input v-model="settings.notifications.email" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.email_benachrichtigungen') }}</span>
                    <span class="block text-[11px] text-slate-500">
                      {{ $t('settings.erhalte_emails_für_zugewiesene_aufg') }}
                    </span>
                  </span>
                </label>

                <label class="ts-toggle">
                  <input v-model="settings.notifications.sound" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.ton') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.kurzer_signalton_bei_neuen_benachri') }}</span>
                  </span>
                </label>
              </div>

              <div class="mt-4 max-w-xs">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.zusammenfassung') }}</label>
                <select v-model="settings.notifications.digest" class="ts-input">
                  <option value="off">{{ $t('settings.keine_zusammenfassung') }}</option>
                  <option value="daily">{{ $t('settings.täglich_morgens') }}</option>
                  <option value="weekly">{{ $t('settings.wöchentlich_montag') }}</option>
                </select>
              </div>
            </div>

            <!-- Ereignisse -->
            <div class="pt-5 border-t border-slate-100">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide">{{ $t('settings.worüber_informieren') }}</h3>
                <div class="flex gap-2">
                  <button type="button" class="text-[11px] font-semibold text-[#0891B2] hover:underline" @click="setAllEvents(true)">{{ $t('settings.alle_an') }}</button>
                  <span class="text-slate-300">·</span>
                  <button type="button" class="text-[11px] font-semibold text-slate-500 hover:underline" @click="setAllEvents(false)">{{ $t('settings.alle_aus') }}</button>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                <label v-for="e in eventOptions" :key="e.key" class="ts-toggle">
                  <input v-model="settings.notifications.events[e.key]" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ e.label }}</span>
                    <span class="block text-[11px] text-slate-500">{{ e.hint }}</span>
                  </span>
                </label>
              </div>
            </div>
          </div>
        </section>

        <!-- ---------- ABRECHNUNG ---------- -->
        <section v-show="active === 'billing'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Clock class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('settings.zeiterfassung_abrechnung') }}</h2>
          </header>
          <div class="p-5 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_stundenlohn') }}</label>
                <div class="relative">
                  <input v-model="hourlyRate" type="number" step="0.01" min="0" placeholder="120.00" class="ts-input pr-14 font-semibold" />
                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-semibold pointer-events-none">{{ $t('settings.std') }}</span>
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standardwährung') }}</label>
                <select v-model="userCurrency" class="ts-input">
                  <option value="CHF">{{ $t('settings.chf_schweizer_franken') }}</option>
                  <option value="EUR">{{ $t('settings.eur_euro') }}</option>
                  <option value="USD">{{ $t('settings.usd_us_dollar') }}</option>
                  <option value="GBP">{{ $t('settings.gbp_britisches_pfund') }}</option>
                </select>
              </div>
            </div>
            <p class="text-[11px] text-slate-500">
              {{ $t('settings.dieser_stundensatz_wird_bei_der_erf') }}
            </p>
          </div>
        </section>

        <!-- ---------- SICHERHEIT ---------- -->
        <section v-show="active === 'security'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Lock class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('settings.passwort_sicherheit') }}</h2>
          </header>
          <div class="p-5">
            <form class="space-y-4 max-w-lg" @submit.prevent="changePassword">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.aktuelles_passwort') }}</label>
                <input v-model="currentPassword" type="password" required placeholder="••••••••" class="ts-input" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.neues_passwort_min_8_zeichen') }}</label>
                <input v-model="newPassword" type="password" required placeholder="••••••••" class="ts-input" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.neues_passwort_bestätigen') }}</label>
                <input v-model="confirmPassword" type="password" required placeholder="••••••••" class="ts-input" />
              </div>
              <div class="pt-1">
                <button type="submit" class="taskster_button" :disabled="savingPassword">
                  <ShieldCheck class="w-4 h-4" />
                  <span>{{ savingPassword ? $t('settings.wird_geaendert') : $t('settings.passwort_aktualisieren') }}</span>
                </button>
              </div>
            </form>
          </div>
        </section>

        <!-- ---------- TARIF ---------- -->
        <section v-show="active === 'plan'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Zap class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('common.tarifplan') }}</h2>
          </header>
          <div class="p-5">
            <div class="p-4 rounded-md bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <div class="text-[11px] text-slate-500 uppercase font-semibold mb-1">{{ $t('settings.dein_aktiver_plan') }}</div>
                <div class="text-xl font-bold tracking-tight" :class="planTextClass">{{ planLabel }}</div>
                <p class="text-xs text-slate-600 mt-1">
                  <template v-if="user?.company_name">
                    {{ $t('settings.du_profitierst_vom_plan_deines_unte') }} ({{ user.company_name }})
                  </template>
                  <template v-else-if="user?.is_pro">{{ $t('settings.du_geniesst_alle_provorteile') }}</template>
                  <template v-else>{{ $t('settings.für_änderungen_wende_dich_an_den_su') }}</template>
                </p>
              </div>
              <button
                v-if="!user?.is_pro && !user?.company_name"
                type="button"
                class="taskster_button shrink-0"
                :disabled="upgradeSent"
                @click="requestUpgrade"
              >
                <Zap class="w-4 h-4" />
                <span>{{ upgradeSent ? $t('settings.anfrage_gesendet') : $t('settings.upgrade_anfragen') }}</span>
              </button>
            </div>
          </div>
        </section>

        <!-- ---------- UNTERNEHMEN ---------- -->
        <section v-if="isCompanyAdmin" v-show="active === 'company'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center justify-between border-b border-slate-200">
            <div class="flex items-center gap-2">
              <Building2 class="w-4 h-4 text-[#0891B2]" />
              <h2 class="text-base font-semibold text-slate-900">{{ $t('common.unternehmen') }}</h2>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-cyan-800 border border-cyan-200">
              Company Admin
            </span>
          </header>
          <div class="p-5 space-y-5">
            <div>
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.mitarbeiter_einladen') }}</h3>
              <form class="flex flex-col sm:flex-row gap-2" @submit.prevent="inviteCompanyMember">
                <input v-model="companyInviteEmail" type="email" required placeholder="mitarbeiter@firma.ch" class="ts-input flex-1" />
                <select v-model="companyInviteRole" class="ts-input sm:w-48">
                  <option value="member">{{ $t('settings.mitarbeiter') }}</option>
                  <option value="admin">{{ $t('settings.company_admin') }}</option>
                </select>
                <button type="submit" class="taskster_button shrink-0" :disabled="sendingCompanyInvite || !companyInviteEmail.trim()">
                  <Plus class="w-4 h-4" />
                  <span>{{ sendingCompanyInvite ? $t('settings.sendet') : $t('settings.einladen') }}</span>
                </button>
              </form>
              <div v-if="companyInviteLink" class="mt-3 p-3 rounded-md bg-cyan-50 border border-cyan-200">
                <div class="text-xs font-semibold text-cyan-900 mb-1">{{ $t('settings.einladungslink') }}</div>
                <input readonly :value="companyInviteLink" class="w-full h-8 px-2.5 rounded bg-white border border-cyan-200 text-xs font-mono" @click="($event.target as HTMLInputElement).select()" />
              </div>
            </div>

            <div class="pt-5 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">
                {{ $t('settings.aktuelle_mitarbeiter') }} ({{ companyMembers.length }})
              </h3>
              <div v-if="loadingCompanyMembers" class="text-xs text-slate-500 py-3">{{ $t('settings.lade_mitglieder') }}</div>
              <div v-else-if="companyMembers.length === 0" class="text-xs text-slate-500 py-3 italic">{{ $t('settings.noch_keine_weiteren_mitarbeiter_vor') }}</div>
              <div v-else class="divide-y divide-slate-100 border border-slate-200 rounded-md overflow-hidden">
                <div v-for="m in companyMembers" :key="m.id" class="flex items-center justify-between px-3 py-2.5 hover:bg-slate-50">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-semibold text-xs shrink-0">
                      {{ (m.name || m.email || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <div class="text-sm font-medium text-slate-900 truncate">
                        {{ m.name }}
                        <span v-if="m.id === user?.id" class="text-slate-400 font-normal">{{ $t('settings.du') }}</span>
                      </div>
                      <div class="text-[11px] text-slate-500 truncate">{{ m.email }}</div>
                    </div>
                  </div>
                  <span
                    class="text-[10px] font-semibold px-2 py-0.5 rounded border shrink-0"
                    :class="m.company_role === 'admin'
                      ? 'bg-cyan-50 text-cyan-800 border-cyan-200'
                      : 'bg-slate-100 text-slate-600 border-slate-200'"
                  >
                    {{ m.company_role === 'admin' ? $t('settings.company_admin') : $t('settings.mitarbeiter') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ---------- DATENSCHUTZ & DSGVO (GDPR) ---------- -->
        <section v-show="active === 'privacy'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <ShieldCheck class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('settings.datenschutz_dsgvo') }}</h2>
          </header>
          <div class="p-5 space-y-6">
            <!-- 1. Datenexport (Art. 20 DSGVO) -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                  <FileDown class="w-4 h-4 text-[#0891B2]" />
                  <span>{{ $t('settings.datenübertragbarkeit_art_20') }}</span>
                </h3>
                <p class="text-xs text-slate-600 leading-relaxed max-w-xl">
                  {{ $t('settings.du_hast_das_recht_auf_eine_kopie_a') }}
                </p>
              </div>
              <button
                type="button"
                class="taskster_button shrink-0"
                :disabled="exportingData"
                @click="exportUserData"
              >
                <FileDown class="w-4 h-4" />
                <span>{{ exportingData ? $t('settings.export_wird_erstellt') : $t('settings.daten_als_json_exportieren') }}</span>
              </button>
            </div>

            <!-- 2. Transparenz & Speicherfristen (Art. 13 & 14 DSGVO) -->
            <div class="space-y-3">
              <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wide">
                {{ $t('settings.datenspeicherung_sicherheit') }}
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="p-3.5 rounded-lg border border-slate-200 bg-white">
                  <div class="text-xs font-semibold text-slate-900 mb-1 flex items-center gap-1.5">
                    <Building2 class="w-3.5 h-3.5 text-[#0891B2]" />
                    <span>{{ $t('settings.hosting_standort') }}</span>
                  </div>
                  <p class="text-[11px] text-slate-500 leading-relaxed">
                    {{ $t('settings.rechenzentrum_hostcreators_ch_eu') }}
                  </p>
                </div>
                <div class="p-3.5 rounded-lg border border-slate-200 bg-white">
                  <div class="text-xs font-semibold text-slate-900 mb-1 flex items-center gap-1.5">
                    <Lock class="w-3.5 h-3.5 text-[#0891B2]" />
                    <span>{{ $t('settings.verschlüsselung') }}</span>
                  </div>
                  <p class="text-[11px] text-slate-500 leading-relaxed">
                    {{ $t('settings.tls_13_in_transit_bcrypt_fuer_pa') }}
                  </p>
                </div>
                <div class="p-3.5 rounded-lg border border-slate-200 bg-white">
                  <div class="text-xs font-semibold text-slate-900 mb-1 flex items-center gap-1.5">
                    <Clock class="w-3.5 h-3.5 text-[#0891B2]" />
                    <span>{{ $t('settings.aufbewahrungsfristen') }}</span>
                  </div>
                  <p class="text-[11px] text-slate-500 leading-relaxed">
                    {{ $t('settings.zeiterfassungen_10_jahre_geset') }}
                  </p>
                </div>
              </div>
            </div>

            <!-- 3. Account löschen (Art. 17 DSGVO) -->
            <div class="pt-5 border-t border-slate-200">
              <div class="p-4 rounded-xl bg-rose-50/70 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h3 class="text-xs font-bold text-rose-900 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                    <Trash2 class="w-4 h-4 text-rose-600" />
                    <span>{{ $t('settings.recht_auf_loeschung_art_17') }}</span>
                  </h3>
                  <p class="text-xs text-rose-800/80 leading-relaxed max-w-xl">
                    {{ $t('settings.loescht_dein_konto_und_alle_zuge') }}
                  </p>
                </div>
                <button
                  type="button"
                  class="taskster_button_accent shrink-0"
                  @click="showDeleteAccountModal = true"
                >
                  <Trash2 class="w-4 h-4" />
                  <span>{{ $t('settings.account_endgueltig_loeschen') }}</span>
                </button>
              </div>
            </div>
          </div>
        </section>

        <!-- ---------- TEAM & GRUPPENVERWALTUNG (Free & Company) ---------- -->
        <section v-show="active === 'team'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center justify-between border-b border-slate-200">
            <div class="flex items-center gap-2">
              <Users class="w-4 h-4 text-[#0891B2]" />
              <h2 class="text-base font-semibold text-slate-900">{{ $t('settings.team_und_gruppen') }}</h2>
            </div>
            <!-- Sub-Tabs: Übersicht vs Gruppen -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg border border-slate-200">
              <button
                type="button"
                class="px-3 py-1 text-xs font-semibold rounded-md transition-colors cursor-pointer"
                :class="teamSubTab === 'matrix' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                @click="teamSubTab = 'matrix'"
              >
                {{ $t('settings.zugriffsübersicht') }}
              </button>
              <button
                type="button"
                class="px-3 py-1 text-xs font-semibold rounded-md transition-colors cursor-pointer"
                :class="teamSubTab === 'groups' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                @click="teamSubTab = 'groups'"
              >
                {{ $t('settings.gruppen') }} ({{ groupsList.length }})
              </button>
            </div>
          </header>

          <div class="p-5 space-y-6">
            <!-- SUBTAB 1: ZUGRIFFSÜBERSICHT (Wer wurde wo eingeladen, wer hat wo Zugriff) -->
            <div v-if="teamSubTab === 'matrix'" class="space-y-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">{{ $t('settings.kollaborateure_und_berechtigung') }}</h3>
                  <p class="text-xs text-slate-500">{{ $t('settings.uebersicht_ueber_alle_mitglieder') }}</p>
                </div>
                <input
                  v-model="matrixSearch"
                  type="text"
                  :placeholder="$t('settings.nach_name_oder_email_filtern')"
                  class="ts-input sm:w-64 text-xs"
                />
              </div>

              <!-- Matrix Tabelle -->
              <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs">
                  <thead class="bg-slate-50 text-slate-600 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                      <th class="py-3 px-3.5">{{ $t('common.benutzer') }}</th>
                      <th class="py-3 px-3.5">{{ $t('common.status') }}</th>
                      <th class="py-3 px-3.5">{{ $t('settings.gruppen') }}</th>
                      <th class="py-3 px-3.5">{{ $t('settings.zugriff_ordner_und_projekte') }}</th>
                      <th class="py-3 px-3.5 text-right">{{ $t('common.aktionen') }}</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 text-slate-800">
                    <!-- Aktive Mitglieder -->
                    <tr v-for="m in filteredMatrixMembers" :key="m.id" class="hover:bg-slate-50/80 transition">
                      <td class="py-3 px-3.5">
                        <div class="flex items-center gap-2.5">
                          <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-cyan-600 to-teal-500 text-white flex items-center justify-center text-xs font-bold shrink-0">
                            {{ (m.name || m.email || '?').charAt(0).toUpperCase() }}
                          </div>
                          <div class="min-w-0">
                            <div class="font-semibold text-slate-900 flex items-center gap-1.5">
                              <span>{{ m.name }}</span>
                              <span v-if="m.is_self" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">DU</span>
                            </div>
                            <div class="text-[11px] text-slate-500 truncate">{{ m.email }}</div>
                          </div>
                        </div>
                      </td>
                      <td class="py-3 px-3.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                          {{ $t('settings.aktiv') }}
                        </span>
                      </td>
                      <td class="py-3 px-3.5">
                        <div class="flex flex-wrap gap-1">
                          <span
                            v-for="g in m.groups"
                            :key="g.id"
                            class="px-2 py-0.5 rounded text-[10px] font-semibold text-white shrink-0"
                            :style="{ backgroundColor: g.color || '#0891B2' }"
                          >
                            {{ g.name }}
                          </span>
                          <span v-if="!m.groups || m.groups.length === 0" class="text-slate-400 text-[11px] italic">
                            —
                          </span>
                        </div>
                      </td>
                      <td class="py-3 px-3.5">
                        <div class="flex flex-wrap gap-1.5 max-w-md">
                          <!-- Ordner-Badges -->
                          <template v-for="f in (matrixData?.folders || [])" :key="'f_' + f.id">
                            <span
                              v-if="m.folder_access?.[f.id] && m.folder_access[f.id].role !== 'none'"
                              class="px-2 py-0.5 rounded text-[10px] font-medium border flex items-center gap-1"
                              :class="getRoleBadgeClass(m.folder_access[f.id].role)"
                              :title="f.name + ' (' + m.folder_access[f.id].role + ' via ' + (m.folder_access[f.id].source || 'direct') + ')'"
                            >
                              <span>📁 {{ f.name }}</span>
                              <span class="font-bold uppercase text-[9px]">({{ m.folder_access[f.id].role }})</span>
                            </span>
                          </template>

                          <!-- Einzelne Projekte falls kein übergeordneter Ordnerzugriff -->
                          <template v-for="p in (matrixData?.projects || [])" :key="'p_' + p.id">
                            <span
                              v-if="m.project_access?.[p.id] && m.project_access[p.id].role !== 'none' && (!m.folder_access || !m.folder_access[p.folder_id] || m.folder_access[p.folder_id].role === 'none')"
                              class="px-2 py-0.5 rounded text-[10px] font-medium border flex items-center gap-1"
                              :class="getRoleBadgeClass(m.project_access[p.id].role)"
                              :title="p.title + ' (' + m.project_access[p.id].role + ' via ' + (m.project_access[p.id].source || 'direct') + ')'"
                            >
                              <span>📄 {{ p.title }}</span>
                              <span class="font-bold uppercase text-[9px]">({{ m.project_access[p.id].role }})</span>
                            </span>
                          </template>

                          <span v-if="hasNoAccess(m)" class="text-slate-400 text-[11px] italic">
                            {{ $t('settings.kein_spezifischer_zugriff') }}
                          </span>
                        </div>
                      </td>
                      <td class="py-3 px-3.5 text-right">
                        <button
                          type="button"
                          class="taskster_button_light px-3 text-xs h-[30px] rounded-md cursor-pointer"
                          @click="openMemberAccessModal(m)"
                        >
                          {{ $t('settings.rechte_anpassen') }}
                        </button>
                      </td>
                    </tr>

                    <!-- Offene Einladungen -->
                    <tr v-for="inv in matrixData.invitations" :key="inv.id" class="bg-amber-50/30 hover:bg-amber-50/50 transition">
                      <td class="py-3 px-3.5">
                        <div class="flex items-center gap-2.5">
                          <div class="w-7 h-7 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-xs font-bold shrink-0">
                            ✉️
                          </div>
                          <div class="min-w-0">
                            <div class="font-semibold text-slate-900">{{ inv.email }}</div>
                            <div class="text-[11px] text-slate-500">{{ $t('settings.eingeladen_von') }}: {{ inv.invited_by_name || 'Admin' }}</div>
                          </div>
                        </div>
                      </td>
                      <td class="py-3 px-3.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                          <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                          {{ $t('settings.ausstehend') }}
                        </span>
                      </td>
                      <td class="py-3 px-3.5 text-slate-400 italic text-[11px]">—</td>
                      <td class="py-3 px-3.5 text-slate-500 text-[11px]">
                        {{ inv.role === 'admin' ? $t('settings.co_admin_vollzugriff') : $t('settings.standard_mitarbeiter') }}
                      </td>
                      <td class="py-3 px-3.5 text-right">
                        <div class="inline-flex items-center gap-1.5">
                          <button
                            type="button"
                            class="taskster_button_light px-2.5 text-xs h-[30px] rounded-md cursor-pointer"
                            :title="$t('settings.einladungslink_kopieren')"
                            @click="copyInviteToken(inv.token)"
                          >
                            🔗 {{ $t('settings.link') }}
                          </button>
                          <button
                            type="button"
                            class="taskster_button_accent px-2.5 text-xs h-[30px] rounded-md cursor-pointer"
                            :title="$t('settings.einladung_widerrufen')"
                            @click="revokeInvitation(inv.id)"
                          >
                            ✕
                          </button>
                        </div>
                      </td>
                    </tr>

                    <tr v-if="filteredMatrixMembers.length === 0 && (!matrixData.invitations || matrixData.invitations.length === 0)">
                      <td colspan="5" class="py-8 text-center text-slate-500 text-xs italic">
                        {{ $t('settings.keine_mitglieder_oder_einladun') }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- SUBTAB 2: GRUPPENVERWALTUNG (Aus eingeladenen Gruppen erstellen & Rechte geben) -->
            <div v-else-if="teamSubTab === 'groups'" class="space-y-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">{{ $t('settings.benutzergruppen') }}</h3>
                  <p class="text-xs text-slate-500">{{ $t('settings.erstelle_gruppen_um_mehreren_per') }}</p>
                </div>
                <button
                  type="button"
                  class="taskster_button shrink-0"
                  @click="openCreateGroupModal"
                >
                  <Plus class="w-4 h-4" />
                  <span>{{ $t('settings.neue_gruppe_erstellen') }}</span>
                </button>
              </div>

              <!-- Gruppen-Grid -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                  v-for="g in groupsList"
                  :key="g.id"
                  class="p-4 rounded-xl border border-slate-200 bg-white hover:shadow-xs transition space-y-3"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div class="w-4 h-4 rounded-full shrink-0" :style="{ backgroundColor: g.color || '#0891B2' }" />
                      <div>
                        <h4 class="text-sm font-bold text-slate-900 truncate">{{ g.name }}</h4>
                        <p v-if="g.description" class="text-[11px] text-slate-500 line-clamp-1">{{ g.description }}</p>
                      </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                      <button
                        type="button"
                        class="p-1 text-slate-400 hover:text-slate-700 rounded cursor-pointer"
                        :title="$t('settings.gruppe_bearbeiten')"
                        @click="openEditGroupModal(g)"
                      >
                        <Pencil class="w-3.5 h-3.5" />
                      </button>
                      <button
                        type="button"
                        class="p-1 text-slate-400 hover:text-rose-600 rounded cursor-pointer"
                        :title="$t('settings.gruppe_löschen')"
                        @click="deleteGroup(g.id)"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>

                  <!-- Mitglieder -->
                  <div>
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                      {{ $t('settings.mitglieder') }} ({{ g.members?.length || 0 }})
                    </div>
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="m in g.members"
                        :key="m.user_id"
                        class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200"
                      >
                        {{ m.name }}
                      </span>
                      <span v-if="!g.members || g.members.length === 0" class="text-[11px] text-slate-400 italic">
                        {{ $t('settings.noch_keine_mitglieder_zugeordnet') }}
                      </span>
                    </div>
                  </div>

                  <!-- Zugewiesene Ordner & Projekte -->
                  <div class="pt-2 border-t border-slate-100">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                      {{ $t('settings.zugewiesene_rechte') }}
                    </div>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                      <span
                        v-for="f in g.folders"
                        :key="f.folder_id"
                        class="px-2 py-0.5 rounded text-[10px] font-medium border border-cyan-200 bg-cyan-50 text-cyan-800"
                      >
                        📁 {{ f.folder_name }} ({{ f.role }})
                      </span>
                      <span
                        v-for="p in g.projects"
                        :key="p.project_id"
                        class="px-2 py-0.5 rounded text-[10px] font-medium border border-teal-200 bg-teal-50 text-teal-800"
                      >
                        📄 {{ p.project_title }} ({{ p.role }})
                      </span>
                      <span v-if="(!g.folders || g.folders.length === 0) && (!g.projects || g.projects.length === 0)" class="text-[11px] text-slate-400 italic">
                        {{ $t('settings.keine_ordner_oder_projekte_zuge') }}
                      </span>
                    </div>
                    <button
                      type="button"
                      class="taskster_button_light px-3 text-xs h-[30px] rounded-md cursor-pointer"
                      @click="openAssignGroupModal(g)"
                    >
                      + {{ $t('settings.ordner_oder_projekt_zuweisen') }}
                    </button>
                  </div>
                </div>
              </div>

              <div v-if="groupsList.length === 0" class="p-8 text-center rounded-xl border border-dashed border-slate-200">
                <Users class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                <h4 class="text-sm font-bold text-slate-900 mb-1">{{ $t('settings.noch_keine_gruppen_vorhanden') }}</h4>
                <p class="text-xs text-slate-500 mb-4 max-w-sm mx-auto">
                  {{ $t('settings.erstelle_deine_erste_gruppe_z_b') }}
                </p>
                <button
                  type="button"
                  class="taskster_button"
                  @click="openCreateGroupModal"
                >
                  <Plus class="w-4 h-4" />
                  <span>{{ $t('settings.erste_gruppe_erstellen') }}</span>
                </button>
              </div>
            </div>
          </div>
        </section>

      </div>
    </div>

    <!-- ================= MODAL: Account löschen (Art. 17 DSGVO) ================= -->
    <div v-if="showDeleteAccountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white border border-rose-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-3 text-rose-600">
          <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
            <AlertCircle class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">{{ $t('settings.account_unwiderruflich_löschen') }}</h3>
            <p class="text-xs text-rose-600 font-medium">{{ $t('settings.diese_aktion_kann_nicht_rueckga') }}</p>
          </div>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">
          {{ $t('settings.alle_deine_persoenlichen_daten_t') }}
        </p>
        <form @submit.prevent="confirmDeleteAccount" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              {{ $t('settings.zur_bestaetigung_aktuelles_pass') }}
            </label>
            <input
              v-model="deleteAccountPassword"
              type="password"
              required
              placeholder="••••••••"
              class="ts-input border-rose-300 focus:border-rose-500"
            />
          </div>
          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              class="taskster_button_light px-4 text-xs h-[38px] rounded-lg cursor-pointer"
              @click="showDeleteAccountModal = false; deleteAccountPassword = ''"
            >
              {{ $t('common.abbrechen') }}
            </button>
            <button
              type="submit"
              class="taskster_button_accent px-5 text-xs h-[38px] rounded-lg cursor-pointer"
              :disabled="deletingAccount || !deleteAccountPassword"
            >
              {{ deletingAccount ? $t('settings.wird_geloescht') : $t('settings.unwiderruflich_loeschen') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ================= MODAL: Gruppe erstellen / bearbeiten ================= -->
    <div v-if="showGroupModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="text-base font-bold text-slate-900">
            {{ editingGroupId ? $t('settings.gruppe_bearbeiten') : $t('settings.neue_gruppe_erstellen') }}
          </h3>
          <button @click="showGroupModal = false" class="text-slate-400 hover:text-slate-700 font-bold p-1 cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="saveGroup" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $t('settings.gruppenname') }}</label>
            <input
              v-model="groupForm.name"
              type="text"
              required
              :placeholder="$t('settings.z_b_bauleiter_architekten_finan')"
              class="ts-input"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $t('settings.beschreibung_optional') }}</label>
            <input
              v-model="groupForm.description"
              type="text"
              :placeholder="$t('settings.kurze_beschreibung_der_rolle_ode')"
              class="ts-input"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.farbkennzeichnung') }}</label>
            <div class="flex items-center gap-2">
              <button
                v-for="c in colorOptions"
                :key="c"
                type="button"
                class="w-7 h-7 rounded-full transition-transform cursor-pointer flex items-center justify-center"
                :style="{ backgroundColor: c }"
                :class="groupForm.color === c ? 'ring-2 ring-offset-2 ring-slate-800 scale-110' : ''"
                @click="groupForm.color = c"
              >
                <Check v-if="groupForm.color === c" class="w-3.5 h-3.5 text-white" />
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-2">
              {{ $t('settings.mitglieder_zuweisen') }} ({{ groupForm.member_ids.length }})
            </label>
            <div class="max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1">
              <label
                v-for="u in availableTeamUsers"
                :key="u.id"
                class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 cursor-pointer text-xs"
              >
                <input
                  type="checkbox"
                  :value="u.id"
                  v-model="groupForm.member_ids"
                  class="ts-check"
                />
                <span class="font-medium text-slate-800">{{ u.name }}</span>
                <span class="text-slate-400 font-mono text-[10px]">({{ u.email }})</span>
              </label>
              <div v-if="availableTeamUsers.length === 0" class="text-[11px] text-slate-400 italic py-2 text-center">
                {{ $t('settings.keine_weiteren_mitglieder_verfue') }}
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              class="taskster_button_light px-4 text-xs h-[38px] rounded-lg cursor-pointer"
              @click="showGroupModal = false"
            >
              {{ $t('common.abbrechen') }}
            </button>
            <button
              type="submit"
              class="taskster_button px-5 text-xs h-[38px] rounded-lg cursor-pointer"
              :disabled="savingGroup || !groupForm.name.trim()"
            >
              {{ savingGroup ? $t('settings.speichern_laeuft') : $t('common.speichern') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ================= MODAL: Gruppenrechte zuweisen ================= -->
    <div v-if="showAssignGroupModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-bold text-slate-900">{{ $t('settings.gruppenrechte_zuweisen') }}</h3>
            <p class="text-xs text-slate-500">{{ assignGroupTarget?.name }}</p>
          </div>
          <button @click="showAssignGroupModal = false" class="text-slate-400 hover:text-slate-700 font-bold p-1 cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="submitAssignGroup" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $t('settings.typ_auswaehlen') }}</label>
            <select v-model="assignForm.type" class="ts-input">
              <option value="folder">{{ $t('common.ordner') }}</option>
              <option value="project">{{ $t('common.projekt') }}</option>
            </select>
          </div>

          <div v-if="assignForm.type === 'folder'">
            <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $t('common.ordner') }}</label>
            <select v-model="assignForm.target_id" class="ts-input" required>
              <option value="">-- {{ $t('settings.ordner_waehlen') }} --</option>
              <option v-for="f in matrixData.folders" :key="f.id" :value="f.id">
                📁 {{ f.name }}
              </option>
            </select>
          </div>

          <div v-else>
            <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $t('common.projekt') }}</label>
            <select v-model="assignForm.target_id" class="ts-input" required>
              <option value="">-- {{ $t('settings.projekt_waehlen') }} --</option>
              <option v-for="p in matrixData.projects" :key="p.id" :value="p.id">
                📄 {{ p.title }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $t('settings.berechtigung') }}</label>
            <select v-model="assignForm.role" class="ts-input">
              <option value="editor">{{ $t('settings.editor_bearbeiten_und_erstellen') }}</option>
              <option value="viewer">{{ $t('settings.viewer_nur_lesezugriff') }}</option>
              <option value="admin">{{ $t('settings.admin_vollzugriff_inkl_freigabe') }}</option>
              <option value="none">{{ $t('settings.zugriff_entfernen') }}</option>
            </select>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              class="taskster_button_light px-4 text-xs h-[38px] rounded-lg cursor-pointer"
              @click="showAssignGroupModal = false"
            >
              {{ $t('common.abbrechen') }}
            </button>
            <button
              type="submit"
              class="taskster_button px-5 text-xs h-[38px] rounded-lg cursor-pointer"
              :disabled="savingAssignment || !assignForm.target_id"
            >
              {{ savingAssignment ? $t('settings.speichern_laeuft') : $t('settings.zuweisen') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ================= MODAL: Individuelle Benutzerrechte bearbeiten ================= -->
    <div v-if="showMemberAccessModal && selectedMember" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-bold text-slate-900">{{ $t('settings.rechte_fuer_benutzer_anpassen') }}</h3>
            <p class="text-xs text-slate-500">{{ selectedMember.name }} ({{ selectedMember.email }})</p>
          </div>
          <button @click="showMemberAccessModal = false" class="text-slate-400 hover:text-slate-700 font-bold p-1 cursor-pointer">✕</button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">{{ $t('settings.ordner_berechtigungen') }}</label>
            <div class="space-y-2">
              <div
                v-for="f in matrixData.folders"
                :key="f.id"
                class="flex items-center justify-between p-2 rounded-lg border border-slate-200 bg-slate-50 text-xs"
              >
                <span class="font-medium text-slate-900">📁 {{ f.name }}</span>
                <select
                  :value="selectedMember.folder_access?.[f.id]?.role || 'none'"
                  @change="onUpdatePermission(selectedMember.id, 'folder', f.id, ($event.target as HTMLSelectElement).value)"
                  class="ts-input w-32 h-7 text-xs bg-white"
                >
                  <option value="none">{{ $t('settings.kein_zugriff') }}</option>
                  <option value="viewer">{{ $t('settings.viewer') }}</option>
                  <option value="editor">{{ $t('settings.editor') }}</option>
                  <option value="admin">{{ $t('settings.admin') }}</option>
                </select>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">{{ $t('settings.projekt_berechtigungen') }}</label>
            <div class="space-y-2">
              <div
                v-for="p in matrixData.projects"
                :key="p.id"
                class="flex items-center justify-between p-2 rounded-lg border border-slate-200 bg-slate-50 text-xs"
              >
                <span class="font-medium text-slate-900">📄 {{ p.title }}</span>
                <select
                  :value="selectedMember.project_access?.[p.id]?.role || 'none'"
                  @change="onUpdatePermission(selectedMember.id, 'project', p.id, ($event.target as HTMLSelectElement).value)"
                  class="ts-input w-32 h-7 text-xs bg-white"
                >
                  <option value="none">{{ $t('settings.kein_zugriff') }}</option>
                  <option value="viewer">{{ $t('settings.viewer') }}</option>
                  <option value="editor">{{ $t('settings.editor') }}</option>
                  <option value="admin">{{ $t('settings.admin') }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end pt-3 border-t border-slate-100">
          <button
            type="button"
            class="taskster_button px-5 text-xs h-[38px] rounded-lg cursor-pointer"
            @click="showMemberAccessModal = false"
          >
            {{ $t('common.fertig') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  LayoutDashboard, Settings as SettingsIcon, User, Clock, Lock, Zap, Building2,
  ShieldCheck, CalendarDays, Bell, Save, CircleDot, CheckCircle2, AlertCircle, Plus,
  Camera, Upload, Trash2, Shield, Users, FileDown, Pencil, Check
} from 'lucide-vue-next'

const { user, token, setAuth, authHeaders, initAuth, logout } = useAuth()
const { t, setLocale } = useI18n()

async function onLanguageChange() {
  if (settings.value.language && ['de', 'en', 'sk'].includes(settings.value.language)) {
    try {
      await setLocale(settings.value.language)
    } catch (err) {
      console.error('[i18n] Failed to switch locale:', err)
    }
  }
}

// ---------------------------------------------------------------------------
// Kategorien-Navigation
// ---------------------------------------------------------------------------
const isCompanyAdmin = computed(() => user.value?.company_role === 'admin' && Boolean(user.value?.company_name))

const sections = computed(() => {
  const list: any[] = [
    { key: 'profile', label: t('common.profil'), icon: User },
    { key: 'team', label: t('settings.teamverwaltung') || 'Team & Gruppen', icon: Users },
    { key: 'calendar', label: t('common.kalender'), icon: CalendarDays },
    { key: 'notifications', label: t('common.benachrichtigungen'), icon: Bell },
    { key: 'billing', label: t('common.abrechnung'), icon: Clock },
    { key: 'security', label: t('common.sicherheit'), icon: Lock },
    { key: 'privacy', label: t('settings.datenschutz_gdpr') || 'Datenschutz (DSGVO)', icon: Shield },
    { key: 'plan', label: t('common.tarifplan'), icon: Zap }
  ]
  if (isCompanyAdmin.value) list.push({ key: 'company', label: t('common.unternehmen'), icon: Building2 })
  return list
})

const active = ref('profile')

// ---------------------------------------------------------------------------
// Zeitzonen
// ---------------------------------------------------------------------------
const timezones = [
  { value: 'Europe/Zurich', label: 'Zürich, Bern, Genf (MEZ/MESZ)' },
  { value: 'Europe/Berlin', label: 'Berlin, Wien (MEZ/MESZ)' },
  { value: 'Europe/London', label: 'London, Dublin (GMT/BST)' },
  { value: 'Europe/Paris', label: 'Paris, Madrid, Rom (MEZ/MESZ)' },
  { value: 'Europe/Bratislava', label: 'Bratislava, Prag (MEZ/MESZ)' },
  { value: 'Europe/Athens', label: 'Athen, Bukarest (OEZ/OESZ)' },
  { value: 'America/New_York', label: 'New York, Toronto (EST/EDT)' },
  { value: 'America/Chicago', label: 'Chicago (CST/CDT)' },
  { value: 'America/Denver', label: 'Denver (MST/MDT)' },
  { value: 'America/Los_Angeles', label: 'Los Angeles, San Francisco (PST/PDT)' },
  { value: 'Asia/Dubai', label: 'Dubai (GST)' },
  { value: 'Asia/Singapore', label: 'Singapur, Hong Kong (SGT/HKT)' },
  { value: 'Asia/Tokyo', label: 'Tokio (JST)' },
  { value: 'Australia/Sydney', label: 'Sydney, Melbourne (AEST/AEDT)' },
  { value: 'UTC', label: 'UTC (Koordinierte Weltzeit)' }
]

const currentTimeInSelectedTimezone = computed(() => {
  try {
    const tz = settings.value.timezone || 'Europe/Zurich'
    return new Intl.DateTimeFormat('de-CH', {
      timeZone: tz,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    }).format(new Date())
  } catch {
    return ''
  }
})

// ---------------------------------------------------------------------------
// Formular-Zustand
// ---------------------------------------------------------------------------
const profileName = ref('')
const profileAvatar = ref<string | null>(null)
const avatarInputRef = ref<HTMLInputElement | null>(null)
const hourlyRate = ref<number | string>(120)
const userCurrency = ref('CHF')
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const saving = ref(false)
const savingPassword = ref(false)
const successMsg = ref('')
const errorMsg = ref('')
const upgradeSent = ref(false)

const categories = ref<any[]>([])

/** Lokale Kopie der Einstellungen – wird erst beim Speichern übertragen. */
const settings = ref<any>({
  language: 'de',
  whisper_language: 'de',
  theme: 'light',
  density: 'comfortable',
  start_page: 'dashboard',
  timezone: 'Europe/Zurich',
  calendar: {
    default_view: 'month',
    week_start: 1,
    show_week_numbers: false,
    show_weekends: true,
    workday_start: '07:00',
    workday_end: '17:00',
    slot_minutes: 30,
    default_duration_minutes: 60,
    default_reminder_minutes: 15,
    default_category_id: null,
    default_visibility: 'private',
    show_tasks: true,
    show_declined: false,
    time_format: '24h'
  },
  notifications: {
    browser: false,
    email: true,
    in_app: true,
    sound: false,
    digest: 'off',
    events: {
      calendar_invite: true,
      calendar_change: true,
      calendar_cancel: true,
      calendar_reminder: true,
      task_assigned: true,
      task_due: true,
      task_comment: true,
      mention: true,
      budget_warning: true
    }
  }
})

/** Snapshot beim Laden – dient der Erkennung ungespeicherter Änderungen. */
const baseline = ref('')

function snapshot() {
  return JSON.stringify({
    name: profileName.value,
    avatar: profileAvatar.value,
    hourly_rate: Number(hourlyRate.value) || 0,
    currency: userCurrency.value,
    settings: settings.value
  })
}

const dirty = computed(() => baseline.value !== '' && snapshot() !== baseline.value)

const workdayInvalid = computed(() =>
  String(settings.value.calendar.workday_end) <= String(settings.value.calendar.workday_start)
)

/** Erinnerung: null bedeutet "Keine" – das Select braucht dafür einen String. */
const reminderModel = computed({
  get: () => settings.value.calendar.default_reminder_minutes === null
    ? 'none'
    : String(settings.value.calendar.default_reminder_minutes),
  set: (v: string) => {
    settings.value.calendar.default_reminder_minutes = v === 'none' ? null : Number(v)
  }
})

const eventOptions = computed(() => [
  { key: 'calendar_invite', label: t('settings.termineinladungen'), hint: t('settings.jemand_lädt_dich_zu_einem_termin_ei') },
  { key: 'calendar_change', label: t('settings.terminänderungen'), hint: t('settings.zeit_oder_ort_eines_termins_ändert_') },
  { key: 'calendar_cancel', label: t('settings.terminabsagen'), hint: t('settings.ein_termin_wird_abgesagt_oder_gelös') },
  { key: 'calendar_reminder', label: t('settings.terminerinnerungen'), hint: t('settings.vor_beginn_eines_termins') },
  { key: 'task_assigned', label: t('settings.aufgabenzuweisung'), hint: t('settings.dir_wird_eine_aufgabe_zugewiesen') },
  { key: 'task_due', label: t('settings.fällige_aufgaben'), hint: t('settings.aufgaben_die_bald_fällig_sind') },
  { key: 'task_comment', label: t('settings.kommentare'), hint: t('settings.neue_kommentare_zu_deinen_aufgaben') },
  { key: 'mention', label: t('settings.erwähnungen'), hint: t('settings.jemand_erwähnt_dich_mit_name') },
  { key: 'budget_warning', label: t('settings.budgetwarnungen'), hint: t('settings.ein_projektbudget_ist_erreicht') }
])

function setAllEvents(value: boolean) {
  for (const e of eventOptions.value) settings.value.notifications.events[e.key] = value
}

// ---------------------------------------------------------------------------
// Browser-Benachrichtigungen
// ---------------------------------------------------------------------------
const browserPermission = ref<'unsupported' | 'default' | 'granted' | 'denied'>('default')

function readBrowserPermission() {
  if (import.meta.client && typeof Notification !== 'undefined') {
    browserPermission.value = Notification.permission as any
  } else {
    browserPermission.value = 'unsupported'
  }
}

async function onBrowserToggle() {
  if (!import.meta.client || typeof Notification === 'undefined') {
    settings.value.notifications.browser = false
    errorMsg.value = t('settings.dieser_browser_unterstützt_keine_be')
    return
  }
  if (settings.value.notifications.browser && Notification.permission !== 'granted') {
    const result = await Notification.requestPermission()
    browserPermission.value = result as any
    if (result !== 'granted') {
      settings.value.notifications.browser = false
      errorMsg.value = t('settings.ohne_erlaubnis_des_browsers_können_')
    }
  }
}

// ---------------------------------------------------------------------------
// Profilbild (Avatar) Handling
// ---------------------------------------------------------------------------
function triggerAvatarUpload() {
  avatarInputRef.value?.click()
}

function removeAvatar() {
  profileAvatar.value = null
  if (avatarInputRef.value) {
    avatarInputRef.value.value = ''
  }
}

function onAvatarFileSelected(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    errorMsg.value = t('settings.bitte_gueltige_bilddatei')
    return
  }

  const reader = new FileReader()
  reader.onload = (event) => {
    const img = new Image()
    img.onload = () => {
      // Quadratisch auf 256x256 px skalieren & zuschneiden
      const size = 256
      const canvas = document.createElement('canvas')
      canvas.width = size
      canvas.height = size
      const ctx = canvas.getContext('2d')
      if (!ctx) return

      const minDim = Math.min(img.width, img.height)
      const sx = (img.width - minDim) / 2
      const sy = (img.height - minDim) / 2

      ctx.drawImage(img, sx, sy, minDim, minDim, 0, 0, size, size)

      let dataUrl = canvas.toDataURL('image/webp', 0.85)
      if (!dataUrl.startsWith('data:image/webp')) {
        dataUrl = canvas.toDataURL('image/jpeg', 0.85)
      }

      profileAvatar.value = dataUrl
      input.value = ''
    }
    img.src = event.target?.result as string
  }
  reader.readAsDataURL(file)
}

// ---------------------------------------------------------------------------
// Laden
// ---------------------------------------------------------------------------
function applyUser() {
  const u = user.value
  if (!u) return
  profileName.value = u.name || ''
  profileAvatar.value = u.avatar || null
  hourlyRate.value = u.hourly_rate !== undefined && u.hourly_rate !== null ? u.hourly_rate : 120
  userCurrency.value = u.currency || 'CHF'

  if (u.settings && typeof u.settings === 'object') {
    // Tief zusammenführen, damit neue Felder aus den Defaults erhalten bleiben
    settings.value = {
      ...settings.value,
      ...u.settings,
      calendar: { ...settings.value.calendar, ...(u.settings.calendar || {}) },
      notifications: {
        ...settings.value.notifications,
        ...(u.settings.notifications || {}),
        events: { ...settings.value.notifications.events, ...(u.settings.notifications?.events || {}) }
      }
    }
    if (settings.value.language && ['de', 'en', 'sk'].includes(settings.value.language)) {
      try {
        setLocale(settings.value.language)
      } catch (err) {
        console.error('[i18n] Failed to set initial locale:', err)
      }
    }
  }

  baseline.value = snapshot()
}

async function loadCategories() {
  try {
    const res = await $fetch<any>('/api/event-categories', { headers: authHeaders() })
    categories.value = res.categories || []
  } catch {
    categories.value = []
  }
}

onMounted(async () => {
  if (!user.value) await initAuth()
  if (!user.value) {
    navigateTo('/login')
    return
  }
  applyUser()
  readBrowserPermission()
  loadCategories()
  loadTeamData()
})

// ---------------------------------------------------------------------------
// Speichern
// ---------------------------------------------------------------------------
async function saveAll() {
  successMsg.value = ''
  errorMsg.value = ''

  if (!profileName.value.trim()) {
    errorMsg.value = t('settings.bitte_gib_einen_namen_an')
    return
  }
  if (workdayInvalid.value) {
    errorMsg.value = t('settings.das_arbeitsende_muss_nach_dem_arbei')
    active.value = 'calendar'
    return
  }

  saving.value = true
  try {
    const res = await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      headers: authHeaders(),
      body: {
        name: profileName.value.trim(),
        avatar: profileAvatar.value,
        hourly_rate: Number(hourlyRate.value) || 0,
        currency: userCurrency.value,
        settings: settings.value
      }
    })

    if (user.value && res.user) {
      user.value.name = res.user.name
      user.value.hourly_rate = res.user.hourly_rate
      user.value.currency = res.user.currency
      user.value.avatar = res.user.avatar || null
      if (res.user.settings) user.value.settings = res.user.settings
      setAuth(token.value || '', res.user)
    }

    // Normalisierte Serverantwort als neue Basis übernehmen
    if (res.user?.settings) settings.value = res.user.settings
    if (settings.value.language && ['de', 'en', 'sk'].includes(settings.value.language)) {
      try {
        await setLocale(settings.value.language)
      } catch (err) {
        console.error('[i18n] Failed to switch locale on save:', err)
      }
    }
    baseline.value = snapshot()

    successMsg.value = t('settings.einstellungen_gespeichert') || 'Einstellungen gespeichert.'
    setTimeout(() => { successMsg.value = '' }, 4000)
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || t('settings.einstellungen_konnten_nicht_gespeic') || 'Einstellungen konnten nicht gespeichert werden.'
  } finally {
    saving.value = false
  }
}

async function changePassword() {
  successMsg.value = ''
  errorMsg.value = ''

  if (newPassword.value !== confirmPassword.value) {
    errorMsg.value = t('settings.die_neuen_passwörter_stimmen_nicht_')
    return
  }

  savingPassword.value = true
  try {
    await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      headers: authHeaders(),
      body: {
        name: profileName.value,
        current_password: currentPassword.value,
        new_password: newPassword.value
      }
    })
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
    successMsg.value = t('settings.passwort_erfolgreich_geändert')
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || t('settings.passwort_konnte_nicht_geändert_werd')
  } finally {
    savingPassword.value = false
  }
}

async function requestUpgrade() {
  try {
    await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      headers: authHeaders(),
      body: { upgrade_request: true }
    })
  } catch {
    // Feld existiert serverseitig evtl. noch nicht – Anfrage gilt trotzdem als gestellt
  }
  upgradeSent.value = true
  successMsg.value = t('settings.upgradeanfrage_gesendet_wir_melden_')
}

// ---------------------------------------------------------------------------
// Unternehmen (Company Admin)
// ---------------------------------------------------------------------------
const companyInviteEmail = ref('')
const companyInviteRole = ref('member')
const companyInviteLink = ref('')
const sendingCompanyInvite = ref(false)
const companyMembers = ref<any[]>([])
const loadingCompanyMembers = ref(false)

async function loadCompanyMembers() {
  if (!isCompanyAdmin.value) return
  loadingCompanyMembers.value = true
  try {
    const res = await $fetch<{ members: any[] }>('/api/companies/members', { headers: authHeaders() })
    companyMembers.value = res.members || []
  } catch {
    companyMembers.value = []
  } finally {
    loadingCompanyMembers.value = false
  }
}

async function inviteCompanyMember() {
  if (!companyInviteEmail.value.trim()) return
  sendingCompanyInvite.value = true
  errorMsg.value = ''
  try {
    const res = await $fetch<any>('/api/companies/invitations', {
      method: 'POST',
      headers: authHeaders(),
      body: { email: companyInviteEmail.value.trim(), role: companyInviteRole.value }
    })
    companyInviteLink.value = res.invite_link || res.link || ''
    companyInviteEmail.value = ''
    successMsg.value = t('settings.einladung_erstellt')
    await loadCompanyMembers()
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || t('settings.einladung_konnte_nicht_erstellt_wer')
  } finally {
    sendingCompanyInvite.value = false
  }
}

watch(isCompanyAdmin, (v) => { if (v) loadCompanyMembers() }, { immediate: true })

// ---------------------------------------------------------------------------
// Badges
// ---------------------------------------------------------------------------
const planLabel = computed(() =>
  (user.value?.company_plan || (user.value?.is_pro ? 'pro' : 'free')).toUpperCase()
)
const planBadgeClass = computed(() => {
  if (user.value?.company_name) return 'bg-violet-50 text-violet-700 border-violet-200'
  if (user.value?.is_pro) return 'bg-cyan-50 text-cyan-800 border-cyan-200'
  return 'bg-slate-100 text-slate-600 border-slate-200'
})
// ---------------------------------------------------------------------------
// DSGVO / GDPR
// ---------------------------------------------------------------------------
const exportingData = ref(false)
const showDeleteAccountModal = ref(false)
const deletingAccount = ref(false)
const deleteAccountPassword = ref('')

async function exportUserData() {
  exportingData.value = true
  errorMsg.value = ''
  try {
    const data = await $fetch<any>('/api/gdpr/export', { headers: authHeaders() })
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `taskster-export-${new Date().toISOString().slice(0, 10)}.json`
    a.click()
    URL.revokeObjectURL(url)
    successMsg.value = t('settings.export_erfolgreich_heruntergeladen') || 'Daten erfolgreich exportiert.'
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Fehler beim Exportieren der Daten.'
  } finally {
    exportingData.value = false
  }
}

async function confirmDeleteAccount() {
  if (!deleteAccountPassword.value) return
  deletingAccount.value = true
  errorMsg.value = ''
  try {
    await $fetch<any>('/api/gdpr/account', {
      method: 'DELETE',
      headers: authHeaders(),
      body: { password: deleteAccountPassword.value }
    })
    showDeleteAccountModal.value = false
    logout()
    navigateTo('/login?deleted=1')
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Konto konnte nicht gelöscht werden. Bitte Passwort prüfen.'
  } finally {
    deletingAccount.value = false
  }
}

// ---------------------------------------------------------------------------
// Team- & Gruppenverwaltung (Zugriffsmatrix)
// ---------------------------------------------------------------------------
const teamSubTab = ref<'matrix' | 'groups'>('matrix')
const matrixSearch = ref('')
const loadingMatrix = ref(false)
const matrixData = ref<{
  members: any[]
  invitations: any[]
  folders: any[]
  projects: any[]
}>({
  members: [],
  invitations: [],
  folders: [],
  projects: []
})

const filteredMatrixMembers = computed(() => {
  const q = matrixSearch.value.trim().toLowerCase()
  if (!q) return matrixData.value.members
  return matrixData.value.members.filter(m =>
    m.name?.toLowerCase().includes(q) || m.email?.toLowerCase().includes(q)
  )
})

const groupsList = ref<any[]>([])
const loadingGroups = ref(false)
const showGroupModal = ref(false)
const editingGroupId = ref<string | null>(null)
const savingGroup = ref(false)
const colorOptions = ['#0891B2', '#0D9488', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899', '#F59E0B', '#EF4444']

const groupForm = ref({
  name: '',
  description: '',
  color: '#0891B2',
  member_ids: [] as string[]
})

const availableTeamUsers = computed(() => {
  return matrixData.value.members.map(m => ({ id: m.id, name: m.name, email: m.email }))
})

const showAssignGroupModal = ref(false)
const assignGroupTarget = ref<any>(null)
const savingAssignment = ref(false)
const assignForm = ref({
  type: 'folder' as 'folder' | 'project',
  target_id: '',
  role: 'editor' as string
})

const showMemberAccessModal = ref(false)
const selectedMember = ref<any>(null)

function getRoleBadgeClass(role: string) {
  if (role === 'owner') return 'bg-purple-100 text-purple-900 border-purple-300'
  if (role === 'admin') return 'bg-emerald-100 text-emerald-900 border-emerald-300'
  if (role === 'editor') return 'bg-cyan-100 text-cyan-900 border-cyan-300'
  return 'bg-slate-100 text-slate-700 border-slate-300'
}

function hasNoAccess(m: any) {
  const hasFolder = m.folder_access && Object.values(m.folder_access).some((a: any) => a.role !== 'none')
  const hasProject = m.project_access && Object.values(m.project_access).some((a: any) => a.role !== 'none')
  return !hasFolder && !hasProject
}

async function loadTeamData() {
  loadingMatrix.value = true
  loadingGroups.value = true
  try {
    const [matrixRes, groupsRes] = await Promise.all([
      $fetch<any>('/api/team/access-matrix', { headers: authHeaders() }).catch(() => ({ members: [], invitations: [], folders: [], projects: [] })),
      $fetch<any>('/api/groups', { headers: authHeaders() }).catch(() => ({ groups: [] }))
    ])
    matrixData.value = matrixRes
    groupsList.value = groupsRes.groups || []
  } catch (err) {
    console.error('Failed to load team data', err)
  } finally {
    loadingMatrix.value = false
    loadingGroups.value = false
  }
}

function openCreateGroupModal() {
  editingGroupId.value = null
  groupForm.value = {
    name: '',
    description: '',
    color: '#0891B2',
    member_ids: []
  }
  showGroupModal.value = true
}

function openEditGroupModal(g: any) {
  editingGroupId.value = g.id
  groupForm.value = {
    name: g.name,
    description: g.description || '',
    color: g.color || '#0891B2',
    member_ids: (g.members || []).map((m: any) => m.user_id)
  }
  showGroupModal.value = true
}

async function saveGroup() {
  if (!groupForm.value.name.trim()) return
  savingGroup.value = true
  errorMsg.value = ''
  try {
    if (editingGroupId.value) {
      await $fetch(`/api/groups/${editingGroupId.value}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: groupForm.value
      })
    } else {
      await $fetch('/api/groups', {
        method: 'POST',
        headers: authHeaders(),
        body: groupForm.value
      })
    }
    showGroupModal.value = false
    await loadTeamData()
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Fehler beim Speichern der Gruppe.'
  } finally {
    savingGroup.value = false
  }
}

async function deleteGroup(groupId: string) {
  if (!confirm('Möchtest du diese Gruppe wirklich löschen?')) return
  try {
    await $fetch(`/api/groups/${groupId}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await loadTeamData()
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Fehler beim Löschen der Gruppe.'
  }
}

function openAssignGroupModal(g: any) {
  assignGroupTarget.value = g
  assignForm.value = {
    type: 'folder',
    target_id: matrixData.value.folders[0]?.id || '',
    role: 'editor'
  }
  showAssignGroupModal.value = true
}

async function submitAssignGroup() {
  if (!assignGroupTarget.value || !assignForm.value.target_id) return
  savingAssignment.value = true
  errorMsg.value = ''
  try {
    await $fetch(`/api/groups/${assignGroupTarget.value.id}/assign`, {
      method: 'POST',
      headers: authHeaders(),
      body: assignForm.value
    })
    showAssignGroupModal.value = false
    await loadTeamData()
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Fehler beim Zuweisen der Rechte.'
  } finally {
    savingAssignment.value = false
  }
}

function openMemberAccessModal(m: any) {
  selectedMember.value = m
  showMemberAccessModal.value = true
}

async function onUpdatePermission(userId: string, targetType: 'folder' | 'project', targetId: string, role: string) {
  try {
    await $fetch('/api/team/access-matrix/permissions', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        user_id: userId,
        target_type: targetType,
        target_id: targetId,
        role: role
      }
    })
    if (selectedMember.value && selectedMember.value.id === userId) {
      if (targetType === 'folder') {
        if (!selectedMember.value.folder_access) selectedMember.value.folder_access = {}
        if (role === 'none') delete selectedMember.value.folder_access[targetId]
        else selectedMember.value.folder_access[targetId] = { role }
      } else {
        if (!selectedMember.value.project_access) selectedMember.value.project_access = {}
        if (role === 'none') delete selectedMember.value.project_access[targetId]
        else selectedMember.value.project_access[targetId] = { role }
      }
    }
    await loadTeamData()
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Berechtigung konnte nicht aktualisiert werden.'
  }
}

async function revokeInvitation(id: string) {
  if (!confirm('Einladung wirklich widerrufen?')) return
  try {
    await $fetch(`/api/companies/invitations/${id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await loadTeamData()
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Fehler beim Widerrufen der Einladung.'
  }
}

function copyInviteToken(token: string) {
  if (typeof navigator !== 'undefined' && navigator.clipboard) {
    const url = `${window.location.origin}/invite?token=${token}`
    navigator.clipboard.writeText(url)
    successMsg.value = t('settings.einladungslink_kopiert') || 'Einladungslink in Zwischenablage kopiert.'
    setTimeout(() => { successMsg.value = '' }, 3000)
  }
}

watch(active, (v) => {
  if (v === 'team') {
    loadTeamData()
  }
})
</script>

<style scoped>
.ts-input {
  width: 100%;
  height: 36px;
  padding: 0 12px;
  font-size: 14px;
  border-radius: 6px;
  background: #fff;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.ts-input:focus {
  border-color: #0891B2;
  box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.15);
}
.ts-input:disabled {
  cursor: not-allowed;
}

.ts-toggle {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.15s;
}
.ts-toggle:hover {
  background: #f8fafc;
}

.ts-check {
  width: 16px;
  height: 16px;
  margin-top: 2px;
  flex-shrink: 0;
  border-radius: 4px;
  border: 1px solid #cbd5e1;
  accent-color: #0891B2;
  cursor: pointer;
}
</style>
