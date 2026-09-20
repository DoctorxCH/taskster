import { db } from '~/server/db'
import { randomUUID } from 'crypto'

/**
 * Free-/Single-User (ohne Company) sehen die Ordner-Ebene nicht.
 * Projekte werden intern einem impliziten "Standard"-Ordner zugeordnet,
 * damit das Datenmodell (projects.folder_id NOT NULL) unverändert bleibt.
 */
export const DEFAULT_FOLDER_NAME = 'Meine Projekte'

/**
 * Liefert den impliziten Standard-Ordner eines Free-/Single-Users.
 * Existiert noch keiner, wird er automatisch angelegt.
 */
export function getOrCreateDefaultFolder(user: { id: string; company_id?: string | null }): any {
  const existing = db.prepare(
    'SELECT * FROM project_folders WHERE owner_id = ? ORDER BY created_at ASC LIMIT 1'
  ).get(user.id) as any

  if (existing) return existing

  const folderId = 'fld_' + randomUUID().substring(0, 8)
  db.prepare(`
    INSERT INTO project_folders (id, owner_id, company_id, name, icon, visibility)
    VALUES (?, ?, ?, ?, ?, 'private')
  `).run(folderId, user.id, user.company_id || null, DEFAULT_FOLDER_NAME, '📁')

  return db.prepare('SELECT * FROM project_folders WHERE id = ?').get(folderId) as any
}
