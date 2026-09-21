export interface TemplateOption {
  value: string
  label_key: string
  label: string
}

export interface TemplateFieldDefinition {
  field_key: string
  label_key: string
  label: string
  field_type: 'select' | 'text' | 'number' | 'date' | 'checkbox' | 'textarea' | 'url'
  entity_type: 'project' | 'task'
  options?: TemplateOption[]
  is_required?: boolean
  logic_rules?: any
}

export interface ProjectTemplate {
  id: string
  name: string
  name_key: string
  category: string
  subcategory: string
  description: string
  description_key: string
  icon: string
  lists: string[]
  fields: TemplateFieldDefinition[]
}

export interface TemplateCustomFieldItem {
  key: string
  label_key: string
  label: string
  icon: string
  type: 'select' | 'text' | 'number' | 'date' | 'checkbox' | 'textarea' | 'url'
  entity_type: 'project' | 'task'
  options?: TemplateOption[]
}

export const CONSTRUCTION_TEMPLATES: ProjectTemplate[] = [
  {
    id: 'template_building_construction',
    name: 'Hochbau',
    name_key: 'templates.building_construction.name',
    category: 'job',
    subcategory: 'Bauwesen & Hochbau',
    description: 'Projektstruktur für Hochbau, Rohbau, Innenausbau und schlüsselfertige Übergabe.',
    description_key: 'templates.building_construction.description',
    icon: 'Building2',
    lists: [
      'sections.preparation',
      'sections.shell_construction',
      'sections.interior_fitting',
      'sections.handover'
    ],
    fields: [
      {
        field_key: 'objekt_typ',
        label_key: 'fields.objekt_typ.label',
        label: 'Objekttyp',
        field_type: 'select',
        entity_type: 'project',
        options: [
          { value: 'wohnbau', label_key: 'fields.options.wohnbau', label: 'Wohnbau' },
          { value: 'gewerbe', label_key: 'fields.options.gewerbe', label: 'Gewerbe & Industrie' },
          { value: 'oeffentlich', label_key: 'fields.options.oeffentlich', label: 'Öffentliche Bauten' },
          { value: 'sanierung', label_key: 'fields.options.sanierung', label: 'Sanierung & Umbau' }
        ],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'baugesuch_status',
        label_key: 'fields.baugesuch_status.label',
        label: 'Baugesuchs-Status',
        field_type: 'select',
        entity_type: 'project',
        options: [
          { value: 'pendent', label_key: 'fields.options.pendent', label: 'Pendent / Eingereicht' },
          { value: 'bewilligt', label_key: 'fields.options.bewilligt', label: 'Bewilligt' },
          { value: 'auflagen_offen', label_key: 'fields.options.auflagen_offen', label: 'Auflagen offen' }
        ],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'baubeginn_soll',
        label_key: 'fields.baubeginn_soll.label',
        label: 'Geplanter Baubeginn',
        field_type: 'date',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'bauabnahme_rohbau',
        label_key: 'fields.bauabnahme_rohbau.label',
        label: 'Bauabnahme Rohbau erfolgt',
        field_type: 'checkbox',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'bezugstermin',
        label_key: 'fields.bezugstermin.label',
        label: 'Bezugstermin',
        field_type: 'date',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'haupt_gu',
        label_key: 'fields.haupt_gu.label',
        label: 'Haupt-Generalunternehmer (GU)',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'template_civil_engineering',
    name: 'Tiefbau & Strassenbau',
    name_key: 'templates.civil_engineering.name',
    category: 'job',
    subcategory: 'Tiefbau & Infrastruktur',
    description: 'Ablaufplanung für Aushub, Werkleitungstrassen, Fundationsschichten und Belagsarbeiten.',
    description_key: 'templates.civil_engineering.description',
    icon: 'HardHat',
    lists: [
      'sections.planning_traffic',
      'sections.excavation_utilities',
      'sections.road_base',
      'sections.surfacing'
    ],
    fields: [
      {
        field_key: 'strassenklasse',
        label_key: 'fields.strassenklasse.label',
        label: 'Strassenklasse',
        field_type: 'select',
        entity_type: 'project',
        options: [
          { value: 'gemeinde', label_key: 'fields.options.gemeinde', label: 'Gemeindestrasse' },
          { value: 'kanton', label_key: 'fields.options.kanton', label: 'Kantonsstrasse / Landstrasse' },
          { value: 'bund', label_key: 'fields.options.bund', label: 'Nationalstrasse / Autobahn' }
        ],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'grabebewilligung_status',
        label_key: 'fields.grabebewilligung_status.label',
        label: 'Grabebewilligung',
        field_type: 'select',
        entity_type: 'project',
        options: [
          { value: 'beantragt', label_key: 'fields.options.beantragt', label: 'Beantragt' },
          { value: 'erteilt', label_key: 'fields.options.erteilt', label: 'Erteilt' },
          { value: 'nicht_noetig', label_key: 'fields.options.nicht_noetig', label: 'Nicht erforderlich' }
        ],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'verkehrsdienst_erforderlich',
        label_key: 'fields.verkehrsdienst_erforderlich.label',
        label: 'Verkehrsdienst erforderlich',
        field_type: 'checkbox',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'belagstyp',
        label_key: 'fields.belagstyp.label',
        label: 'Belagstyp',
        field_type: 'select',
        entity_type: 'project',
        options: [
          { value: 'deckbelag_asphalt', label_key: 'fields.options.deckbelag_asphalt', label: 'Deckbelag Asphalt' },
          { value: 'pflasterstein', label_key: 'fields.options.pflasterstein', label: 'Pflasterstein' },
          { value: 'kieskoffer', label_key: 'fields.options.kieskoffer', label: 'Kieskoffer / Schotter' }
        ],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'bauherr_gemeinde',
        label_key: 'fields.bauherr_gemeinde.label',
        label: 'Bauherr / Gemeinde',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'termin_fertigstellung',
        label_key: 'fields.termin_fertigstellung.label',
        label: 'Fertigstellungstermin',
        field_type: 'date',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'template_network_infrastructure',
    name: 'Netzbau & Telekommunikation',
    name_key: 'templates.network_infrastructure.name',
    category: 'job',
    subcategory: 'Netzbau & Telekommunikation',
    description: 'Trassenbau, Rohranlagen, Glasfaser-Einblasen, Spleissarbeiten und OTDR-Endabnahme.',
    description_key: 'templates.network_infrastructure.description',
    icon: 'Network',
    lists: [
      'sections.prep_tracing',
      'sections.pipe_ducts',
      'sections.cable_pull_splice',
      'sections.measurement_commissioning'
    ],
    fields: [
      {
        field_key: 'sparte',
        label_key: 'fields.sparte.label',
        label: 'Sparte / Medium',
        field_type: 'select',
        entity_type: 'project',
        options: [
          { value: 'ftth', label_key: 'fields.options.ftth', label: 'FTTH (Glasfaser)' },
          { value: 'strom_ns_ms', label_key: 'fields.options.strom_ns_ms', label: 'Strom (NS / MS)' },
          { value: 'kupfer', label_key: 'fields.options.kupfer', label: 'Kupfernetz' },
          { value: 'mobilfunk', label_key: 'fields.options.mobilfunk', label: 'Mobilfunk (5G)' }
        ],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'kundenreferenz',
        label_key: 'fields.kundenreferenz.label',
        label: 'Kundenreferenz / Projekt-ID',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'subunternehmer_montage',
        label_key: 'fields.subunternehmer_montage.label',
        label: 'Montage-Subunternehmer',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'bep_inbetriebnahme_soll',
        label_key: 'fields.bep_inbetriebnahme_soll.label',
        label: 'Soll-Inbetriebnahme (BEP)',
        field_type: 'date',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'otdr_messung_erledigt',
        label_key: 'fields.otdr_messung_erledigt.label',
        label: 'OTDR-Messung erledigt',
        field_type: 'checkbox',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'abnahmeprotokoll_vorhanden',
        label_key: 'fields.abnahmeprotokoll_vorhanden.label',
        label: 'Abnahmeprotokoll vorhanden',
        field_type: 'checkbox',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  },
  {
    id: 'template_property_maintenance',
    name: 'Liegenschaftsunterhalt & Sanierung',
    name_key: 'templates.property_maintenance.name',
    category: 'job',
    subcategory: 'Sanierung & Bewirtschaftung',
    description: 'Gebäudeinstandhaltung, Schadstoffsanierung, Mieterabnahmen und Sanierungskoordination.',
    description_key: 'templates.property_maintenance.description',
    icon: 'Wrench',
    lists: [
      'sections.survey_offer',
      'sections.contractor_scheduling',
      'sections.execution',
      'sections.final_inspection'
    ],
    fields: [
      {
        field_key: 'liegenschaft_id',
        label_key: 'fields.liegenschaft_id.label',
        label: 'Liegenschafts-Nr. / Gebäude-ID',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'mieter_kontakt',
        label_key: 'fields.mieter_kontakt.label',
        label: 'Mieterkontakt',
        field_type: 'text',
        entity_type: 'project',
        options: [],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'sanierungsbereich',
        label_key: 'fields.sanierungsbereich.label',
        label: 'Sanierungsbereich',
        field_type: 'select',
        entity_type: 'project',
        options: [
          { value: 'kueche_bad', label_key: 'fields.options.kueche_bad', label: 'Küche & Bad' },
          { value: 'fassade_dach', label_key: 'fields.options.fassade_dach', label: 'Fassade & Dach' },
          { value: 'heizung_lueftung', label_key: 'fields.options.heizung_lueftung', label: 'Heizung & Lüftung' },
          { value: 'komplett', label_key: 'fields.options.komplett', label: 'Komplettsanierung' }
        ],
        is_required: true,
        logic_rules: []
      },
      {
        field_key: 'asbest_schadstoff_pruefung',
        label_key: 'fields.asbest_schadstoff_pruefung.label',
        label: 'Asbest- & Schadstoffprüfung',
        field_type: 'select',
        entity_type: 'project',
        options: [
          { value: 'geprueft_negativ', label_key: 'fields.options.geprueft_negativ', label: 'Geprüft (schadstofffrei)' },
          { value: 'belastet_sanierung_laeuft', label_key: 'fields.options.belastet_sanierung_laeuft', label: 'Belastet (Sanierung läuft)' },
          { value: 'nicht_relevant', label_key: 'fields.options.nicht_relevant', label: 'Nicht relevant' }
        ],
        is_required: false,
        logic_rules: []
      },
      {
        field_key: 'abnahme_mieter_erfolgt',
        label_key: 'fields.abnahme_mieter_erfolgt.label',
        label: 'Mieterabnahme erfolgt',
        field_type: 'checkbox',
        entity_type: 'task',
        options: [],
        is_required: false,
        logic_rules: []
      }
    ]
  }
]

// Vorlagen-Zusatzfelder für den schnellen CSV/Excel-Import und das Feld-Mapping
export const TEMPLATE_CUSTOM_FIELDS: TemplateCustomFieldItem[] = [
  // Hochbau
  { key: 'objekt_typ', label_key: 'fields.objekt_typ.label', label: 'Objekttyp', icon: '🏢', type: 'select', entity_type: 'project', options: [
    { value: 'wohnbau', label_key: 'fields.options.wohnbau', label: 'Wohnbau' },
    { value: 'gewerbe', label_key: 'fields.options.gewerbe', label: 'Gewerbe & Industrie' },
    { value: 'oeffentlich', label_key: 'fields.options.oeffentlich', label: 'Öffentliche Bauten' },
    { value: 'sanierung', label_key: 'fields.options.sanierung', label: 'Sanierung & Umbau' }
  ]},
  { key: 'baugesuch_status', label_key: 'fields.baugesuch_status.label', label: 'Baugesuchs-Status', icon: '📑', type: 'select', entity_type: 'project', options: [
    { value: 'pendent', label_key: 'fields.options.pendent', label: 'Pendent / Eingereicht' },
    { value: 'bewilligt', label_key: 'fields.options.bewilligt', label: 'Bewilligt' },
    { value: 'auflagen_offen', label_key: 'fields.options.auflagen_offen', label: 'Auflagen offen' }
  ]},
  { key: 'baubeginn_soll', label_key: 'fields.baubeginn_soll.label', label: 'Geplanter Baubeginn', icon: '📅', type: 'date', entity_type: 'project' },
  { key: 'bauabnahme_rohbau', label_key: 'fields.bauabnahme_rohbau.label', label: 'Bauabnahme Rohbau erfolgt', icon: '✅', type: 'checkbox', entity_type: 'task' },
  { key: 'bezugstermin', label_key: 'fields.bezugstermin.label', label: 'Bezugstermin', icon: '🔑', type: 'date', entity_type: 'project' },
  { key: 'haupt_gu', label_key: 'fields.haupt_gu.label', label: 'Haupt-Generalunternehmer (GU)', icon: '👷', type: 'text', entity_type: 'project' },

  // Tiefbau & Strassenbau
  { key: 'strassenklasse', label_key: 'fields.strassenklasse.label', label: 'Strassenklasse', icon: '🛣️', type: 'select', entity_type: 'project', options: [
    { value: 'gemeinde', label_key: 'fields.options.gemeinde', label: 'Gemeindestrasse' },
    { value: 'kanton', label_key: 'fields.options.kanton', label: 'Kantonsstrasse / Landstrasse' },
    { value: 'bund', label_key: 'fields.options.bund', label: 'Nationalstrasse / Autobahn' }
  ]},
  { key: 'grabebewilligung_status', label_key: 'fields.grabebewilligung_status.label', label: 'Grabebewilligung', icon: '📋', type: 'select', entity_type: 'project', options: [
    { value: 'beantragt', label_key: 'fields.options.beantragt', label: 'Beantragt' },
    { value: 'erteilt', label_key: 'fields.options.erteilt', label: 'Erteilt' },
    { value: 'nicht_noetig', label_key: 'fields.options.nicht_noetig', label: 'Nicht erforderlich' }
  ]},
  { key: 'verkehrsdienst_erforderlich', label_key: 'fields.verkehrsdienst_erforderlich.label', label: 'Verkehrsdienst erforderlich', icon: '🚦', type: 'checkbox', entity_type: 'task' },
  { key: 'belagstyp', label_key: 'fields.belagstyp.label', label: 'Belagstyp', icon: '🧱', type: 'select', entity_type: 'project', options: [
    { value: 'deckbelag_asphalt', label_key: 'fields.options.deckbelag_asphalt', label: 'Deckbelag Asphalt' },
    { value: 'pflasterstein', label_key: 'fields.options.pflasterstein', label: 'Pflasterstein' },
    { value: 'kieskoffer', label_key: 'fields.options.kieskoffer', label: 'Kieskoffer / Schotter' }
  ]},
  { key: 'bauherr_gemeinde', label_key: 'fields.bauherr_gemeinde.label', label: 'Bauherr / Gemeinde', icon: '🏛️', type: 'text', entity_type: 'project' },
  { key: 'termin_fertigstellung', label_key: 'fields.termin_fertigstellung.label', label: 'Fertigstellungstermin', icon: '🏁', type: 'date', entity_type: 'project' },

  // Netzbau & Telekommunikation
  { key: 'sparte', label_key: 'fields.sparte.label', label: 'Sparte / Medium', icon: '⚡', type: 'select', entity_type: 'project', options: [
    { value: 'ftth', label_key: 'fields.options.ftth', label: 'FTTH (Glasfaser)' },
    { value: 'strom_ns_ms', label_key: 'fields.options.strom_ns_ms', label: 'Strom (NS / MS)' },
    { value: 'kupfer', label_key: 'fields.options.kupfer', label: 'Kupfernetz' },
    { value: 'mobilfunk', label_key: 'fields.options.mobilfunk', label: 'Mobilfunk (5G)' }
  ]},
  { key: 'kundenreferenz', label_key: 'fields.kundenreferenz.label', label: 'Kundenreferenz / Projekt-ID', icon: '🔖', type: 'text', entity_type: 'project' },
  { key: 'subunternehmer_montage', label_key: 'fields.subunternehmer_montage.label', label: 'Montage-Subunternehmer', icon: '🔧', type: 'text', entity_type: 'project' },
  { key: 'bep_inbetriebnahme_soll', label_key: 'fields.bep_inbetriebnahme_soll.label', label: 'Soll-Inbetriebnahme (BEP)', icon: '📡', type: 'date', entity_type: 'project' },
  { key: 'otdr_messung_erledigt', label_key: 'fields.otdr_messung_erledigt.label', label: 'OTDR-Messung erledigt', icon: '📊', type: 'checkbox', entity_type: 'task' },
  { key: 'abnahmeprotokoll_vorhanden', label_key: 'fields.abnahmeprotokoll_vorhanden.label', label: 'Abnahmeprotokoll vorhanden', icon: '📄', type: 'checkbox', entity_type: 'task' },

  // Liegenschaftsunterhalt & Sanierung
  { key: 'liegenschaft_id', label_key: 'fields.liegenschaft_id.label', label: 'Liegenschafts-Nr. / Gebäude-ID', icon: '🏠', type: 'text', entity_type: 'project' },
  { key: 'mieter_kontakt', label_key: 'fields.mieter_kontakt.label', label: 'Mieterkontakt', icon: '👤', type: 'text', entity_type: 'project' },
  { key: 'sanierungsbereich', label_key: 'fields.sanierungsbereich.label', label: 'Sanierungsbereich', icon: '🔨', type: 'select', entity_type: 'project', options: [
    { value: 'kueche_bad', label_key: 'fields.options.kueche_bad', label: 'Küche & Bad' },
    { value: 'fassade_dach', label_key: 'fields.options.fassade_dach', label: 'Fassade & Dach' },
    { value: 'heizung_lueftung', label_key: 'fields.options.heizung_lueftung', label: 'Heizung & Lüftung' },
    { value: 'komplett', label_key: 'fields.options.komplett', label: 'Komplettsanierung' }
  ]},
  { key: 'asbest_schadstoff_pruefung', label_key: 'fields.asbest_schadstoff_pruefung.label', label: 'Asbest- & Schadstoffprüfung', icon: '☣️', type: 'select', entity_type: 'project', options: [
    { value: 'geprueft_negativ', label_key: 'fields.options.geprueft_negativ', label: 'Geprüft (schadstofffrei)' },
    { value: 'belastet_sanierung_laeuft', label_key: 'fields.options.belastet_sanierung_laeuft', label: 'Belastet (Sanierung läuft)' },
    { value: 'nicht_relevant', label_key: 'fields.options.nicht_relevant', label: 'Nicht relevant' }
  ]},
  { key: 'abnahme_mieter_erfolgt', label_key: 'fields.abnahme_mieter_erfolgt.label', label: 'Mieterabnahme erfolgt', icon: '🤝', type: 'checkbox', entity_type: 'task' }
]

export const useProjectTemplates = () => {
  return {
    templates: CONSTRUCTION_TEMPLATES,
    templateCustomFields: TEMPLATE_CUSTOM_FIELDS
  }
}
