import bcrypt from 'bcryptjs'
import { db } from '../../db'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event).catch(() => ({}))

  const password = body?.password
  if (!password || typeof password !== 'string') {
    throw createError({
      statusCode: 400,
      statusMessage: 'Bitte gib dein aktuelles Passwort ein, um die Löschung zu bestätigen.'
    })
  }

  // Benutzer mit Password-Hash abrufen
  const userRow = db.prepare('SELECT id, email, password_hash, company_id, company_role FROM users WHERE id = ?').get(user.id) as any
  if (!userRow) {
    throw createError({ statusCode: 404, statusMessage: 'Benutzer nicht gefunden' })
  }

  const isValidPassword = bcrypt.compareSync(password, userRow.password_hash)
  if (!isValidPassword) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Das eingegebene Passwort ist nicht korrekt. Die Löschung wurde abgebrochen.'
    })
  }

  // 1. Persönliche Daily Todos löschen
  db.prepare('DELETE FROM daily_todos WHERE user_id = ?').run(user.id)

  // 2. Benachrichtigungen löschen
  db.prepare('DELETE FROM notifications WHERE user_id = ?').run(user.id)

  // 3. Private Kontakte löschen
  db.prepare("DELETE FROM contacts WHERE user_id = ? AND share_scope = 'private'").run(user.id)

  // 4. Mitgliedschaften entfernen
  db.prepare('DELETE FROM project_members WHERE user_id = ?').run(user.id)
  db.prepare('DELETE FROM folder_members WHERE user_id = ?').run(user.id)
  db.prepare('DELETE FROM user_group_members WHERE user_id = ?').run(user.id)
  db.prepare('DELETE FROM company_invitations WHERE LOWER(email) = LOWER(?)').run(userRow.email)

  // 5. Aufgaben-Zuweisungen auf NULL setzen
  db.prepare('UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?').run(user.id)

  // 6. Eigene Gruppen löschen (Kaskadiert Gruppenmitgliedschaften & Rechte)
  db.prepare('DELETE FROM user_groups WHERE owner_id = ?').run(user.id)

  // 7. Eigene Ordner und Projekte löschen (ON DELETE CASCADE löscht verknüpfte Projekte, Listen, Tasks)
  db.prepare('DELETE FROM project_folders WHERE owner_id = ?').run(user.id)

  // 8. Benutzerdatensatz löschen
  db.prepare('DELETE FROM users WHERE id = ?').run(user.id)

  return {
    success: true,
    message: 'Dein Benutzerkonto und alle personenbezogenen Daten wurden erfolgreich und unwiderruflich gelöscht.'
  }
})
