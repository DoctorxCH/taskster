import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const { name } = body

  if (!name || !name.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'Name für den Projektordner erforderlich' })
  }

  // Free plan limit check:
  // "Ein Kunde im free plan hat 1 Projektordner zur Verfügung."
  if (!user.is_pro && !user.company_id && !user.is_superadmin) {
    const existingCount = db.prepare('SELECT COUNT(*) as count FROM project_folders WHERE owner_id = ?').get(user.id) as any
    if (existingCount.count >= 1) {
      throw createError({
        statusCode: 403,
        statusMessage: 'Free-Plan Limit erreicht: Im kostenlosen Plan steht maximal 1 Projektordner zur Verfügung. Bitte auf Pro upgraden oder einer Company beitreten.'
      })
    }
  }

  const folderId = 'fld_' + randomUUID().substring(0, 8)
  const companyId = user.company_id || null

  db.prepare(`
    INSERT INTO project_folders (id, owner_id, company_id, name)
    VALUES (?, ?, ?, ?)
  `).run(folderId, user.id, companyId, name.trim())

  return {
    folder: {
      id: folderId,
      name: name.trim(),
      owner_id: user.id,
      company_id: companyId
    }
  }
})

