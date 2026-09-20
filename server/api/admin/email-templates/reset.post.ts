import { db } from '~/server/db'
import { requireAdminPermission } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireAdminPermission(event, 'any_admin')

  db.prepare('DELETE FROM email_templates').run()

  const defaultTemplates = [
    {
      id: 'tmpl_task_assigned',
      trigger_event: 'task_assigned',
      name: 'Aufgabe zugewiesen',
      subject: '[Taskster] Neue Aufgabe: {{task_title}}',
      variables: JSON.stringify(['user_name', 'task_title', 'project_title', 'assigned_by', 'due_date', 'action_url']),
      body_text: "Hallo {{user_name}},\n\nDir wurde die Aufgabe \"{{task_title}}\" im Projekt \"{{project_title}}\" zugewiesen.\nFälligkeitsdatum: {{due_date}}\n\nZur Aufgabe: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
      body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #00A3C4; margin-bottom: 16px;">Neue Aufgabe zugewiesen</h2>
        <p>Hallo <strong>{{user_name}}</strong>,</p>
        <p>Dir wurde eine neue Aufgabe zugewiesen:</p>
        <div style="background: #f8fafc; border-left: 4px solid #00A3C4; padding: 12px; margin: 16px 0;">
          <div style="font-size: 16px; font-weight: bold; color: #0f172a;">{{task_title}}</div>
          <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Projekt: {{project_title}}</div>
          <div style="font-size: 13px; color: #64748b;">Fällig am: {{due_date}}</div>
        </div>
        <p><a href="{{action_url}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Aufgabe öffnen</a></p>
      </div>`
    },
    {
      id: 'tmpl_task_due',
      trigger_event: 'task_due',
      name: 'Aufgabe fällig',
      subject: '[Taskster] Erinnerung: Aufgabe {{task_title}} ist fällig',
      variables: JSON.stringify(['user_name', 'task_title', 'project_title', 'due_date', 'action_url']),
      body_text: "Hallo {{user_name}},\n\nDie Aufgabe \"{{task_title}}\" im Projekt \"{{project_title}}\" ist heute bzw. bald fällig ({{due_date}}).\n\nZur Aufgabe: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
      body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #ea580c; margin-bottom: 16px;">Aufgabe ist fällig</h2>
        <p>Hallo <strong>{{user_name}}</strong>,</p>
        <p>Die folgende Aufgabe erfordert deine Aufmerksamkeit:</p>
        <div style="background: #fff7ed; border-left: 4px solid #ea580c; padding: 12px; margin: 16px 0;">
          <div style="font-size: 16px; font-weight: bold; color: #9a3412;">{{task_title}}</div>
          <div style="font-size: 13px; color: #7c2d12; margin-top: 4px;">Projekt: {{project_title}} | Fällig: {{due_date}}</div>
        </div>
        <p><a href="{{action_url}}" style="display: inline-block; background: #ea580c; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Jetzt bearbeiten</a></p>
      </div>`
    },
    {
      id: 'tmpl_task_comment',
      trigger_event: 'task_comment',
      name: 'Neuer Aufgaben-Kommentar',
      subject: '[Taskster] Neuer Kommentar zu {{task_title}}',
      variables: JSON.stringify(['user_name', 'author_name', 'task_title', 'comment_content', 'action_url']),
      body_text: "Hallo {{user_name}},\n\n{{author_name}} hat einen Kommentar zu \"{{task_title}}\" verfasst:\n\n\"{{comment_content}}\"\n\nZur Aufgabe: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
      body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #00A3C4; margin-bottom: 16px;">Neuer Kommentar</h2>
        <p>Hallo <strong>{{user_name}}</strong>,</p>
        <p><strong>{{author_name}}</strong> hat zu <em>{{task_title}}</em> geschrieben:</p>
        <blockquote style="background: #f8fafc; border-left: 4px solid #cbd5e1; padding: 10px 14px; margin: 14px 0; font-style: italic;">{{comment_content}}</blockquote>
        <p><a href="{{action_url}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Kommentar ansehen & antworten</a></p>
      </div>`
    },
    {
      id: 'tmpl_calendar_invite',
      trigger_event: 'calendar_invite',
      name: 'Termineinladung',
      subject: '[Taskster] Termineinladung: {{event_title}}',
      variables: JSON.stringify(['user_name', 'inviter_name', 'event_title', 'event_start', 'event_end', 'event_location', 'action_url']),
      body_text: "Hallo {{user_name}},\n\n{{inviter_name}} hat dich zu folgendem Termin eingeladen:\n\nTermin: {{event_title}}\nZeit: {{event_start}} bis {{event_end}}\nOrt: {{event_location}}\n\nZum Termin: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
      body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #00A3C4; margin-bottom: 16px;">Termineinladung</h2>
        <p>Hallo <strong>{{user_name}}</strong>,</p>
        <p><strong>{{inviter_name}}</strong> hat dich zu einem Termin eingeladen:</p>
        <div style="background: #f0fdfa; border-left: 4px solid #00A3C4; padding: 12px; margin: 16px 0;">
          <div style="font-size: 16px; font-weight: bold; color: #134e4a;">{{event_title}}</div>
          <div style="font-size: 13px; color: #115e59; margin-top: 4px;">📅 {{event_start}} - {{event_end}}</div>
          <div style="font-size: 13px; color: #115e59;">📍 {{event_location}}</div>
        </div>
        <p><a href="{{action_url}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Termin im Kalender öffnen</a></p>
      </div>`
    },
    {
      id: 'tmpl_calendar_reminder',
      trigger_event: 'calendar_reminder',
      name: 'Terminerinnerung',
      subject: '[Taskster] Erinnerung: {{event_title}}',
      variables: JSON.stringify(['user_name', 'event_title', 'event_start', 'event_location', 'action_url']),
      body_text: "Hallo {{user_name}},\n\nErinnerung an deinen bevorstehenden Termin:\n\n{{event_title}}\nBeginn: {{event_start}}\nOrt: {{event_location}}\n\nZum Kalender: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
      body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #0284c7; margin-bottom: 16px;">Terminerinnerung</h2>
        <p>Hallo <strong>{{user_name}}</strong>,</p>
        <p>Dein Termin beginnt in Kürze:</p>
        <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 12px; margin: 16px 0;">
          <div style="font-size: 16px; font-weight: bold; color: #0369a1;">{{event_title}}</div>
          <div style="font-size: 13px; color: #0284c7; margin-top: 4px;">⏰ {{event_start}} | 📍 {{event_location}}</div>
        </div>
        <p><a href="{{action_url}}" style="display: inline-block; background: #0284c7; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Kalender anzeigen</a></p>
      </div>`
    },
    {
      id: 'tmpl_mention',
      trigger_event: 'mention',
      name: 'Erwähnung (@Name)',
      subject: '[Taskster] {{author_name}} hat dich erwähnt',
      variables: JSON.stringify(['user_name', 'author_name', 'context_title', 'mention_text', 'action_url']),
      body_text: "Hallo {{user_name}},\n\n{{author_name}} hat dich in \"{{context_title}}\" erwähnt:\n\n\"{{mention_text}}\"\n\nÖffnen: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
      body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #7c3aed; margin-bottom: 16px;">Du wurdest erwähnt</h2>
        <p>Hallo <strong>{{user_name}}</strong>,</p>
        <p><strong>{{author_name}}</strong> hat dich in <em>{{context_title}}</em> erwähnt:</p>
        <div style="background: #faf5ff; border-left: 4px solid #7c3aed; padding: 12px; margin: 16px 0; font-style: italic;">{{mention_text}}</div>
        <p><a href="{{action_url}}" style="display: inline-block; background: #7c3aed; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Zur Notiz / Aufgabe</a></p>
      </div>`
    },
    {
      id: 'tmpl_budget_warning',
      trigger_event: 'budget_warning',
      name: 'Budgetwarnung',
      subject: '[Taskster] Budget-Warnung: {{project_title}}',
      variables: JSON.stringify(['user_name', 'project_title', 'budget_percent', 'tracked_hours', 'budget_hours', 'action_url']),
      body_text: "Hallo {{user_name}},\n\nDas Projekt \"{{project_title}}\" hat {{budget_percent}}% des geplanten Budgets erreicht ({{tracked_hours}} von {{budget_hours}} Stunden gebucht).\n\nDetails: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
      body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #dc2626; margin-bottom: 16px;">Budgetwarnung</h2>
        <p>Hallo <strong>{{user_name}}</strong>,</p>
        <p>Das Projekt <strong>{{project_title}}</strong> hat die Budgetgrenze erreicht:</p>
        <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px; margin: 16px 0;">
          <div style="font-size: 16px; font-weight: bold; color: #991b1b;">{{budget_percent}}% verbraucht</div>
          <div style="font-size: 13px; color: #b91c1c; margin-top: 4px;">{{tracked_hours}} von {{budget_hours}} Std. erfasst</div>
        </div>
        <p><a href="{{action_url}}" style="display: inline-block; background: #dc2626; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Controlling ansehen</a></p>
      </div>`
    },
    {
      id: 'tmpl_company_invite',
      trigger_event: 'company_invite',
      name: 'Unternehmen-Einladung',
      subject: 'Einladung zu {{company_name}} auf Taskster',
      variables: JSON.stringify(['inviter_name', 'company_name', 'invite_link']),
      body_text: "Hallo,\n\n{{inviter_name}} hat dich eingeladen, dem Unternehmen \"{{company_name}}\" auf Taskster beizutreten.\n\nKlicke auf den folgenden Link, um deine Registrierung abzuschliessen:\n{{invite_link}}\n\nBeste Grüsse,\nDein Taskster Team",
      body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #00A3C4; margin-bottom: 16px;">Willkommen bei Taskster</h2>
        <p>Hallo,</p>
        <p><strong>{{inviter_name}}</strong> hat dich eingeladen, dem Unternehmen <strong>{{company_name}}</strong> auf Taskster beizutreten.</p>
        <p style="margin: 24px 0;"><a href="{{invite_link}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold;">Einladung annehmen & registrieren</a></p>
        <p style="font-size: 12px; color: #64748b;">Oder kopiere diesen Link in deinen Browser:<br><span style="font-family: monospace; color: #0f172a;">{{invite_link}}</span></p>
      </div>`
    }
  ]

  const insTmpl = db.prepare(`
    INSERT INTO email_templates (id, trigger_event, name, subject, variables, body_text, body_html, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
  `)
  for (const t of defaultTemplates) {
    insTmpl.run(t.id, t.trigger_event, t.name, t.subject, t.variables, t.body_text, t.body_html)
  }

  return { success: true }
})
