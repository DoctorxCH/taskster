import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess, evaluateFolderAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'
import parseEmailHandler from '../projects/[id]/journal/parse-email.post'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  let projectId = body.project_id
  const folderId = body.folder_id

  // If no projectId but folderId provided, resolve project
  if ((!projectId || projectId === 'auto') && folderId) {
    const p = db.prepare('SELECT id FROM projects WHERE folder_id = ? ORDER BY is_default DESC, created_at ASC LIMIT 1').get(folderId) as any
    projectId = p?.id || null
  }

  // If still no projectId, pick any accessible project
  if (!projectId) {
    const p = db.prepare('SELECT p.id FROM projects p JOIN project_folders pf ON pf.id = p.folder_id WHERE pf.company_id = ? OR pf.owner_id = ? LIMIT 1').get(user.company_id || '', user.id) as any
    projectId = p?.id || null
  }

  if (projectId) {
    // Inject projectId into context param so parseEmailHandler works
    event.context.params = { id: projectId }
    return parseEmailHandler(event)
  }

  throw createError({ statusCode: 400, statusMessage: 'Kein passendes Projekt für die KI-Analyse gefunden.' })
})
