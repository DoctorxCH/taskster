import { db } from '../../db'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)

  // 1. Benutzerdaten
  const userRow = db.prepare(`
    SELECT id, name, email, company_id, company_role, is_superadmin, is_pro,
           hourly_rate, currency, settings, created_at
    FROM users
    WHERE id = ?
  `).get(user.id) as any

  let settings = {}
  try {
    settings = typeof userRow.settings === 'string' ? JSON.parse(userRow.settings) : userRow.settings
  } catch {}

  // 2. Unternehmensdaten (falls vorhanden)
  let company = null
  if (user.company_id) {
    company = db.prepare(`
      SELECT id, name, subscription_plan, created_at
      FROM companies
      WHERE id = ?
    `).get(user.company_id)
  }

  // 3. Ordner & Mitgliedschaften
  const ownedFolders = db.prepare(`
    SELECT id, name, icon, visibility, created_at
    FROM project_folders
    WHERE owner_id = ?
  `).all(user.id)

  const folderMemberships = db.prepare(`
    SELECT fm.folder_id, fm.role, fm.created_at, pf.name as folder_name
    FROM folder_members fm
    JOIN project_folders pf ON pf.id = fm.folder_id
    WHERE fm.user_id = ?
  `).all(user.id)

  // 4. Gruppenmitgliedschaften
  const groupMemberships = db.prepare(`
    SELECT ug.id as group_id, ug.name as group_name, ug.color, ugm.created_at
    FROM user_group_members ugm
    JOIN user_groups ug ON ug.id = ugm.group_id
    WHERE ugm.user_id = ?
  `).all(user.id)

  // 5. Projekte
  const projectMemberships = db.prepare(`
    SELECT pm.project_id, pm.role, pm.created_at, p.title as project_title, p.status
    FROM project_members pm
    JOIN projects p ON p.id = pm.project_id
    WHERE pm.user_id = ?
  `).all(user.id)

  // 6. Zugewiesene Aufgaben
  const assignedTasks = db.prepare(`
    SELECT id, list_id, title, description, status, priority, due_date, created_at
    FROM tasks
    WHERE assigned_to = ?
  `).all(user.id)

  // 7. Persönliche Daily Todos
  const dailyTodos = db.prepare(`
    SELECT id, project_id, title, target_date, is_completed, completed_at, original_date, rollover_count, created_at
    FROM daily_todos
    WHERE user_id = ?
  `).all(user.id)

  // 8. Zeiterfassungseinträge
  const timeEntries = db.prepare(`
    SELECT id, project_id, task_id, duration_minutes, hourly_rate, currency, description, entry_date, is_manual, created_at
    FROM time_entries
    WHERE user_id = ?
  `).all(user.id)

  // 9. Kommentare
  const comments = db.prepare(`
    SELECT id, task_id, content, created_at
    FROM task_comments
    WHERE author_id = ?
  `).all(user.id)

  // 10. Kontakte
  const contacts = db.prepare(`
    SELECT id, first_name, last_name, company_name, role_function, phone, mobile, email,
           category_group, address, website, notes, share_scope, created_at
    FROM contacts
    WHERE user_id = ?
  `).all(user.id)

  // 11. Kalender-Termine
  const calendarEvents = db.prepare(`
    SELECT id, title, description, location, start_at, end_at, all_day, priority, status, visibility, created_at
    FROM calendar_events
    WHERE owner_id = ?
  `).all(user.id)

  // 12. Benachrichtigungen
  const notifications = db.prepare(`
    SELECT id, type, title, message, is_read, created_at
    FROM notifications
    WHERE user_id = ?
  `).all(user.id)

  const exportData = {
    metadata: {
      exported_at: new Date().toISOString(),
      format_version: '1.0',
      system: 'Taskster GDPR Data Portability Service (Art. 20 DSGVO)',
      data_subject_id: user.id,
      data_subject_email: user.email
    },
    user_profile: {
      ...userRow,
      settings
    },
    company,
    folders: {
      owned: ownedFolders,
      memberships: folderMemberships
    },
    groups: groupMemberships,
    projects: projectMemberships,
    tasks: assignedTasks,
    daily_todos: dailyTodos,
    time_entries: timeEntries,
    comments,
    contacts,
    calendar_events: calendarEvents,
    notifications
  }

  setResponseHeader(event, 'Content-Type', 'application/json; charset=utf-8')
  setResponseHeader(
    event,
    'Content-Disposition',
    `attachment; filename="taskster_export_${user.id}_${Date.now()}.json"`
  )

  return exportData
})
