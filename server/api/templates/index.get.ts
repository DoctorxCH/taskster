import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireAuth(event)
  const query = getQuery(event)
  const cat = query.category as string | undefined
  const q = query.q ? String(query.q).trim() : ''

  let sql = 'SELECT * FROM project_templates WHERE 1=1'
  const params: any[] = []

  if (cat && cat !== 'all') {
    sql += ' AND category = ?'
    params.push(cat)
  }

  if (q) {
    sql += ' AND (name LIKE ? OR description LIKE ? OR subcategory LIKE ?)'
    params.push(`%${q}%`, `%${q}%`, `%${q}%`)
  }

  sql += ' ORDER BY is_system DESC, category ASC, name ASC'

  const templates = (db.prepare(sql).all(...params) as any[]).map((t) => ({
    ...t,
    lists: t.lists ? JSON.parse(t.lists) : [],
    fields: t.fields ? JSON.parse(t.fields) : []
  }))

  return { templates }
})
