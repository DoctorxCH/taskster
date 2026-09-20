import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')
  const body = await readBody(event)

  const entry = db.prepare('SELECT * FROM time_entries WHERE id = ?').get(id) as any
  if (!entry) {
    throw createError({ statusCode: 404, statusMessage: 'Zeiteintrag nicht gefunden' })
  }

  // Permission: author or project editor/owner
  const context = evaluateProjectAccess(user, entry.project_id, event, 'write')
  const isAuthor = entry.user_id === user.id
  const isElevated = context.userRole === 'owner' || context.userRole === 'admin'

  if (!isAuthor && !isElevated) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Bearbeiten dieses Eintrags' })
  }

  const durationMinutes = body.duration_minutes !== undefined
    ? Math.max(1, parseInt(body.duration_minutes) || Math.round((parseFloat(body.duration_hours) || 0) * 60) || entry.duration_minutes)
    : (body.duration_hours !== undefined ? Math.round((parseFloat(body.duration_hours) || 0) * 60) : entry.duration_minutes)

  const description = body.description !== undefined ? String(body.description).trim() : entry.description
  const entryDate = body.entry_date !== undefined ? String(body.entry_date).substring(0, 10) : entry.entry_date
  const hourlyRate = body.hourly_rate !== undefined ? Math.max(0, parseFloat(body.hourly_rate) || 0) : entry.hourly_rate
  const currency = body.currency !== undefined ? String(body.currency).trim() : entry.currency

  // Any manual edit sets is_manual = 1!
  db.prepare(`
    UPDATE time_entries
    SET duration_minutes = ?,
        description = ?,
        entry_date = ?,
        hourly_rate = ?,
        currency = ?,
        is_manual = 1,
        updated_at = datetime('now')
    WHERE id = ?
  `).run(durationMinutes, description, entryDate, hourlyRate, currency, id)

  const updated = db.prepare(`
    SELECT te.*, u.name as user_name, u.email as user_email, t.title as task_title
    FROM time_entries te
    JOIN users u ON u.id = te.user_id
    LEFT JOIN tasks t ON t.id = te.task_id
    WHERE te.id = ?
  `).get(id) as any

  return {
    entry: {
      ...updated,
      is_manual: true,
      duration_hours: Number(((Number(updated.duration_minutes) || 0) / 60).toFixed(2)),
      calculated_amount: Number((((Number(updated.duration_minutes) || 0) / 60) * (Number(updated.hourly_rate) || 0)).toFixed(2))
    }
  }
})
