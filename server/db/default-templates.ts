export const defaultProjectTemplates = [
  {
    id: 'tmpl_lwl_tiefbau',
    name: 'Neues Projekt',
    category: 'job',
    subcategory: 'Tiefbau & Glasfaser',
    description: 'Vorkonfigurierte Bauleitung für Telekommunikation, Grabenbau, Rohrverlegung, Spleissen und OTDR-Dämpfungsmessung.',
    icon: 'HardHat',
    lists: ['Planung & Trasse', 'Tiefbau & Graben', 'Rohrverlegung & Kalibrierung', 'Einblasen & Spleissen', 'Messung & Abnahme'],
    fields: [
      {
        field_key: 'gewerk',
        label: 'Gewerk / Bauabschnitt',
        field_type: 'select',
        entity_type: 'project',
        options: ['Tiefbau & Graben', 'LWL / Spleissen', 'Kupfermontage', 'Oberflächenwiederherstellung'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'baufirma',
        label: 'Ausführendes Bauunternehmen',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'trassenlaenge_m',
        label: 'Trassenlänge (Meter)',
        field_type: 'number',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: { depends_on_field: 'gewerk', depends_on_value: 'Tiefbau & Graben' }
      },
      {
        field_key: 'otdr_messung_ok',
        label: 'OTDR Dämpfungsmessung',
        field_type: 'select',
        entity_type: 'task',
        options: ['Ja (Messprotokoll abgelegt)', 'Nein (Mangel / Nachprüfung)', 'Nicht erforderlich'],
        is_required: false,
        logic_rules: { depends_on_field: 'gewerk', depends_on_value: 'LWL / Spleissen' }
      },
      {
        field_key: 'abnahme_status',
        label: 'Bauabnahme Status',
        field_type: 'select',
        entity_type: 'task',
        options: ['Ausstehend', 'Mängelfrei abgenommen', 'Nachbesserung erforderlich'],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_it_software',
    name: 'IT-Systemhaus & Software-Entwicklung',
    category: 'job',
    subcategory: 'IT & Software',
    description: 'Agiles Aufgaben- und Ticketmanagement für IT-Projekte, Bugtracking, Code-Reviews und Deployments.',
    icon: 'Laptop',
    lists: ['Backlog & Anfragen', 'In Bearbeitung (Sprint)', 'Code Review & QA', 'Deployment / Live'],
    fields: [
      {
        field_key: 'ticket_typ',
        label: 'Ticket-Typ',
        field_type: 'select',
        entity_type: 'task',
        options: ['Feature / Neuheit', 'Bug / Fehlfunktion', 'Support & Wartung', 'Dokumentation'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'prio',
        label: 'Dringlichkeit (Prio)',
        field_type: 'select',
        entity_type: 'task',
        options: ['Prio 1 (Kritisch)', 'Prio 2 (Hoch)', 'Prio 3 (Mittel)', 'Prio 4 (Niedrig)'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'bug_severity',
        label: 'Fehler-Schweregrad',
        field_type: 'select',
        entity_type: 'task',
        options: ['Blocker (Systemausfall)', 'Major (Kernfunktion gestört)', 'Minor (Kosmetisch / UI)'],
        is_required: false,
        logic_rules: { depends_on_field: 'ticket_typ', depends_on_value: 'Bug / Fehlfunktion' }
      },
      {
        field_key: 'aufwand_stunden',
        label: 'Geschätzter Aufwand (h)',
        field_type: 'number',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_elektro_handwerk',
    name: 'Handwerk & Elektroinstallation',
    category: 'job',
    subcategory: 'Handwerk & Montage',
    description: 'Strukturierte Projektabwicklung vom Auftragseingang über Materialbeschaffung bis zur Montage und SiNa-Prüfung.',
    icon: 'Wrench',
    lists: ['Auftragseingang', 'Materialbestellung', 'Montage vor Ort', 'Messung & SiNa-Prüfung', 'Rechnung gestellt'],
    fields: [
      {
        field_key: 'auftraggeber_typ',
        label: 'Auftraggeber-Kategorie',
        field_type: 'select',
        entity_type: 'project',
        options: ['Privatkunde', 'Gewerbekunde', 'Öffentliche Hand'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'sicherheitsnachweis_nr',
        label: 'SiNa-Protokoll-Nummer',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'material_status',
        label: 'Material-Status',
        field_type: 'select',
        entity_type: 'task',
        options: ['Material bestellt', 'Im Lager vorrätig', 'Vor Ort montiert'],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'stundenaufwand',
        label: 'Geleistete Stunden',
        field_type: 'number',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_shk_gebaeudetechnik',
    name: 'Sanitär, Heizung & Haustechnik (SHK)',
    category: 'job',
    subcategory: 'Haustechnik',
    description: 'Projektsteuerung für Heizungstausch, Badumbau, Wärmepumpen-Installation und Abnahmedokumentation.',
    icon: 'Flame',
    lists: ['Offerte & Vor-Ort-Check', 'Bestellung & Disposition', 'Demontage Altbestand', 'Installation & Montage', 'Inbetriebnahme & Übergabe'],
    fields: [
      {
        field_key: 'anlagenart',
        label: 'Art der Anlage',
        field_type: 'select',
        entity_type: 'project',
        options: ['Wärmepumpe Luft/Wasser', 'Wärmepumpe Erdsonde', 'Sanitär & Badumbau', 'Pellet / Holzheizung', 'Lüftung & Klima'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'hersteller_geraet',
        label: 'Hersteller & Modell',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'druckpruefung_ok',
        label: 'Druckprüfung erfolgt',
        field_type: 'select',
        entity_type: 'task',
        options: ['Ja (Protokoll vorhanden)', 'Ausstehend', 'Nicht erforderlich'],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'foerdergelder_beantragt',
        label: 'Fördergelder beantragt',
        field_type: 'select',
        entity_type: 'project',
        options: ['Eingereicht', 'Bewilligt', 'Nicht zutreffend'],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_marketing_social',
    name: 'Marketing, Kampagnen & Social Media',
    category: 'job',
    subcategory: 'Marketing & Medien',
    description: 'Redaktions- und Kampagnenplanung von Content-Erstellung bis zu Werbeschaltung und ROI-Erfolgsmessung.',
    icon: 'Megaphone',
    lists: ['Briefing & Ideen', 'Texterstellung & Konzept', 'Grafik & Video-Assets', 'Review & Kundenfreigabe', 'Veröffentlicht & Tracking'],
    fields: [
      {
        field_key: 'plattform',
        label: 'Kanal / Plattform',
        field_type: 'select',
        entity_type: 'task',
        options: ['Instagram & TikTok', 'LinkedIn & Xing', 'Website & Blog', 'E-Mail & Newsletter', 'Google Ads / Performance'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'werbebudget_chf',
        label: 'Ad-Spend / Budget (CHF)',
        field_type: 'number',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'veroeffentlichungsdatum',
        label: 'Geplantes Go-Live-Datum',
        field_type: 'date',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_immobilien_bewirtschaftung',
    name: 'Immobilien-Verkauf & Vermietung',
    category: 'job',
    subcategory: 'Immobilien',
    description: 'Vollständige Abwicklung von Objektakquise, Exposé-Erstellung, Besichtigungsterminen bis zum Notartermin.',
    icon: 'Building',
    lists: ['Objektaufnahme & Unterlagen', 'Marketing & Exposé', 'Besichtigungstermine', 'Kaufvertrags-Vorbereitung', 'Notartermin & Übergabe'],
    fields: [
      {
        field_key: 'objekttyp',
        label: 'Objekt-Art',
        field_type: 'select',
        entity_type: 'project',
        options: ['Einfamilienhaus', 'Eigentumswohnung', 'Mehrfamilienhaus / Anlage', 'Gewerbe & Büro', 'Baugrundstück'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'verkaufspreis_chf',
        label: 'Richtpreis / Kaufpreis (CHF)',
        field_type: 'number',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'grundbuch_auszug_vorhanden',
        label: 'Grundbuchauszug vorhanden',
        field_type: 'select',
        entity_type: 'project',
        options: ['Aktuell vorliegend', 'Bestellt / Ausstehend', 'Noch nicht angefordert'],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_gastronomie_catering',
    name: 'Gastronomie & Event-Catering',
    category: 'job',
    subcategory: 'Gastronomie & Events',
    description: 'Planung von Banketten, Firmenfeiern, Menüabläufen, Personaleinsatz und Allergenmanagement.',
    icon: 'Utensils',
    lists: ['Anfrage & Menüauswahl', 'Einkauf & Vorbereitung', 'Equipment & Logistik', 'Durchführung vor Ort', 'Abrechnung & Feedback'],
    fields: [
      {
        field_key: 'anzahl_gaeste',
        label: 'Gästeanzahl (Personen)',
        field_type: 'number',
        entity_type: 'project',
        options: [],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'menue_typ',
        label: 'Menü-Art',
        field_type: 'select',
        entity_type: 'project',
        options: ['Mehrgang-Menü serviert', 'Buffet & Flying Dinner', 'Apéro Riche / Fingerfood', 'BBQ / Live-Cooking'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'allergene_hinweise',
        label: 'Diäten & Allergene',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_iso_qm_audit',
    name: 'Qualitätsmanagement & ISO-Audit',
    category: 'job',
    subcategory: 'Qualitätsmanagement',
    description: 'Auditvorbereitung, Prüfpfade, Korrekturmassnahmen (CAPA) und Sicherheits-Dokumentation.',
    icon: 'ShieldCheck',
    lists: ['Norm-Anforderungen & Lücken', 'Interne Prüfung', 'Korrekturmassnahmen (CAPA)', 'Zertifizierungsaudit', 'Abgeschlossen'],
    fields: [
      {
        field_key: 'iso_norm',
        label: 'Standard / Zertifizierung',
        field_type: 'select',
        entity_type: 'project',
        options: ['ISO 9001 (Qualität)', 'ISO 27001 (Informationssicherheit)', 'ISO 14001 (Umwelt)', 'SUVA / Arbeitssicherheit'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'audit_befund',
        label: 'Audit-Befund',
        field_type: 'select',
        entity_type: 'task',
        options: ['Konform', 'Geringfügige Abweichung (Minor)', 'Schwere Abweichung (Major)', 'Empfehlung'],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'wirksamkeit_frist',
        label: 'Frist für Wirksamkeitsprüfung',
        field_type: 'date',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_hausbau_privat',
    name: 'Hausbau & Wohnungsrenovierung',
    category: 'private',
    subcategory: 'Renovierung & Bau',
    description: 'Perfekt für private Renovierungen, Sanierungen und Umbauten inklusive Gewerke- und Kostenübersicht.',
    icon: 'Home',
    lists: ['Ideen & Recherche', 'Offerten / Angebote einholen', 'In Ausführung', 'Fertiggestellt & Abgenommen'],
    fields: [
      {
        field_key: 'raum',
        label: 'Zimmer / Bereich',
        field_type: 'select',
        entity_type: 'task',
        options: ['Wohnzimmer', 'Küche', 'Badezimmer', 'Schlafzimmer', 'Garten & Terrasse', 'Keller & Technik'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'ausfuehrung_durch',
        label: 'Ausführung durch',
        field_type: 'select',
        entity_type: 'task',
        options: ['Eigenleistung', 'Handwerker / Extern', 'Familie & Freunde'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'handwerker_firma',
        label: 'Beauftragte Firma / Handwerker',
        field_type: 'text',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: { depends_on_field: 'ausfuehrung_durch', depends_on_value: 'Handwerker / Extern' }
      },
      {
        field_key: 'budget_chf',
        label: 'Kostenbudget (CHF)',
        field_type: 'number',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'ist_kosten_chf',
        label: 'Tatsächliche Kosten (CHF)',
        field_type: 'number',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_event_privat',
    name: 'Event- & Feierplanung (Hochzeit, Fest)',
    category: 'private',
    subcategory: 'Feier & Event',
    description: 'Organisation privater Feiern von Dienstleisterverträgen bis zum detaillierten Ablaufplan am Eventtag.',
    icon: 'Sparkles',
    lists: ['Planung & Inspiration', 'Buchungen & Dienstleister', 'Woche vor dem Event', 'Tag des Events', 'Nachbereitung & Danksagung'],
    fields: [
      {
        field_key: 'kategorie',
        label: 'Event-Kategorie',
        field_type: 'select',
        entity_type: 'task',
        options: ['Location & Catering', 'Musik / DJ / Band', 'Fotograf & Video', 'Deko & Floristik', 'Gäste & Einladungen'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'anzahlung_erledigt',
        label: 'Anzahlung geleistet',
        field_type: 'select',
        entity_type: 'task',
        options: ['Ja (Quittung abgelegt)', 'Nein (Noch offen)', 'Nicht erforderlich'],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'preis_chf',
        label: 'Kosten / Honorar (CHF)',
        field_type: 'number',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'faelligkeit',
        label: 'Fälligkeitsdatum',
        field_type: 'date',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_umzug_privat',
    name: 'Privater Umzug & Wohnungswechsel',
    category: 'private',
    subcategory: 'Wohnen & Umzug',
    description: 'Reibungsloser Wohnungswechsel: Kündigungsfristen, Packliste, Transporter-Buchung und Adressänderungen.',
    icon: 'Truck',
    lists: ['Kündigungen & Verträge', 'Vorbereitung & Kisten packen', 'Umzugstag', 'Neue Wohnung einrichten', 'Behörden & Ummeldungen'],
    fields: [
      {
        field_key: 'umzug_kategorie',
        label: 'Aufgaben-Bereich',
        field_type: 'select',
        entity_type: 'task',
        options: ['Mietvertrag & Kündigung', 'Packen & Entrümpeln', 'Umzugshelfer / Transporter', 'Endreinigung & Abnahme', 'Ummeldung & Behörden'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'kisten_nummer',
        label: 'Kisten-Nr. / Zielraum',
        field_type: 'text',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'abnahmetermin',
        label: 'Wohnungsübergabetermin',
        field_type: 'date',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'tmpl_finanzen_steuer',
    name: 'Finanzabschluss & Steuererklärung',
    category: 'private',
    subcategory: 'Finanzen & Vorsorge',
    description: 'Sämtliche Steuerbelege, Lohnausweise, Vorsorgenachweise und Fristen übersichtlich gesammelt.',
    icon: 'Calculator',
    lists: ['Belege & Dokumente sammeln', 'Abzüge & Vorsorge prüfen', 'Erfassung in Steuer-Software', 'Eingereicht & Prüfbescheid'],
    fields: [
      {
        field_key: 'steuerjahr',
        label: 'Steuerjahr',
        field_type: 'select',
        entity_type: 'project',
        options: ['2024', '2025', '2026', '2027'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'beleg_art',
        label: 'Art des Nachweises',
        field_type: 'select',
        entity_type: 'task',
        options: ['Lohnausweis & Einkünfte', 'Säule 3a / Pensionskasseneinkauf', 'Krankheits- & Zahnarztkosten', 'Spendenbescheinigungen', 'Berufsauslagen & Weiterbildung', 'Liegenschaftskosten / Unterhalt'],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'beleg_betrag_chf',
        label: 'Betrag (CHF)',
        field_type: 'number',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'einreichfrist',
        label: 'Einreichungsfrist',
        field_type: 'date',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  }
]
