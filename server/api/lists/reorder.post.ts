import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const { project_id, lists } = body

  if (!project_id) {
    throw createError({ statusCode: 400, statusMessage: 'Projekt-ID erforderlich' })
  }

  // Permission check
  evaluateProjectAccess(user, project_id, event, 'write')

  if (Array.isArray(lists)) {
    const updateStmt = db.prepare(`
      UPDATE lists
      SET sort_order = ?, title = COALESCE(?, title), color = ?, is_completed_target = ?
      WHERE id = ? AND project_id = ?
    `)

    const updateMany = db.transaction((items) => {
      items.forEach((item: any, index: number) => {
        const listId = typeof item === 'string' ? item : item.id
        const title = typeof item === 'object' && item.title ? item.title.trim() : null
        const sort = typeof item === 'object' && typeof item.sort_order === 'number' ? item.sort_order : index + 1
        const color = typeof item === 'object' && 'color' in item ? (item.color || null) : null
        const target = typeof item === 'object' && item.is_completed_target ? 1 : 0
        updateStmt.run(sort, title, color, target, listId, project_id)
      })
    })

    updateMany(lists)
  }

  return { success: true }
})
