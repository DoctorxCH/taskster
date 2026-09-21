import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { getOrCreateDefaultFolder } from '~/server/utils/defaultFolder'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const { title, template_id, custom_lists, import_tasks } = body
  let { folder_id } = body

  if (!title || !title.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'Projekttitel ist erforderlich' })
  }

  // Free-/Single-User (ohne Company) sehen die Ordner-Ebene nicht.
  // Ohne folder_id wird der implizite Standard-Ordner verwendet/angelegt.
  const isFreeUser = !user.is_pro && !user.company_id && !user.is_superadmin
  if (!folder_id) {
    if (!isFreeUser) {
      throw createError({ statusCode: 400, statusMessage: 'Ordner-ID ist erforderlich' })
    }
    folder_id = getOrCreateDefaultFolder(user).id
  }

  const folder = db.prepare('SELECT * FROM project_folders WHERE id = ?').get(folder_id) as any
  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Projektordner nicht gefunden' })
  }

  // Check write access to folder
  const canCreate = folder.owner_id === user.id || (user.company_id && user.company_id === folder.company_id && user.company_role === 'admin')
  if (!canCreate) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zur Projekterstellung in diesem Ordner' })
  }

  // Free user limit check: "Free user darf max. in 3 projekten gleichzeitig mitarbeiten."
  if (!user.is_pro && !user.company_id && !user.is_superadmin) {
    const activeProjectsCount = (db.prepare(`
      SELECT COUNT(*) as count FROM projects p
      JOIN project_folders pf ON pf.id = p.folder_id
      LEFT JOIN project_members pm ON pm.project_id = p.id
      WHERE (pf.owner_id = ? OR pm.user_id = ?) AND p.status = 'active'
    `).get(user.id, user.id) as any).count

    if (activeProjectsCount >= 3) {
      throw createError({
        statusCode: 403,
        statusMessage: 'Free-Plan Limit erreicht: Im kostenlosen Plan darfst du maximal in 3 Projekten gleichzeitig mitarbeiten. Bitte auf Pro upgraden oder einer Company beitreten.'
      })
    }
  }

  const projectId = 'prj_' + randomUUID().substring(0, 8)
  const currency = String(body.currency || 'CHF').trim()
  const budgetHours = body.budget_hours != null && body.budget_hours !== '' ? Number(body.budget_hours) : 0
  const budgetAmount = body.budget_amount != null && body.budget_amount !== '' ? Number(body.budget_amount) : 0
  const customData = body.custom_data && typeof body.custom_data === 'object' ? JSON.stringify(body.custom_data) : '{}'
  const visibility = (user.company_id && body.visibility === 'company') ? 'company' : 'private'

  db.prepare(`
    INSERT INTO projects (id, folder_id, title, status, visibility, currency, budget_hours, budget_amount, custom_data)
    VALUES (?, ?, ?, 'active', ?, ?, ?, ?, ?)
  `).run(projectId, folder_id, title.trim(), visibility, currency, budgetHours, budgetAmount, customData)

  // Determine lists to create
  const listsToCreate: string[] = []
  if (Array.isArray(custom_lists) && custom_lists.length > 0) {
    for (const cl of custom_lists) {
      const s = String(cl || '').trim()
      if (s && !listsToCreate.includes(s)) {
        listsToCreate.push(s)
      }
    }
  }

  let tmpl: any = null
  if (template_id) {
    tmpl = db.prepare('SELECT * FROM project_templates WHERE id = ?').get(template_id) as any
    if (tmpl && listsToCreate.length === 0) {
      const parsedLists = tmpl.lists ? JSON.parse(tmpl.lists) : []
      if (Array.isArray(parsedLists)) {
        for (const pl of parsedLists) {
          const s = String(pl || '').trim()
          if (s && !listsToCreate.includes(s)) {
            listsToCreate.push(s)
          }
        }
      }
    }
  }

  if (Array.isArray(import_tasks) && import_tasks.length > 0) {
    for (const it of import_tasks) {
      const s = String(it.list_title || '').trim()
      if (s && !listsToCreate.includes(s)) {
        listsToCreate.push(s)
      }
    }
  }

  if (listsToCreate.length === 0) {
    listsToCreate.push('Aufgabenliste 1')
  }

  const listMap = new Map<string, string>()
  let firstListId = ''
  let order = 1
  for (const listTitle of listsToCreate) {
    const listId = 'lst_' + randomUUID().substring(0, 8)
    if (!firstListId) firstListId = listId
    db.prepare(`
      INSERT INTO lists (id, project_id, title, access_mode, sort_order)
      VALUES (?, ?, ?, 'inherit', ?)
    `).run(listId, projectId, listTitle, order++)
    listMap.set(listTitle.toLowerCase(), listId)
  }

  if (tmpl) {
    const fields = tmpl.fields ? JSON.parse(tmpl.fields) : []
    if (fields && fields.length > 0) {
      const existingKeys = (db.prepare('SELECT field_key FROM folder_field_definitions WHERE folder_id = ?').all(folder_id) as any[]).map((f) => f.field_key)
      const count = (db.prepare('SELECT COUNT(*) as c FROM folder_field_definitions WHERE folder_id = ?').get(folder_id) as any).c
      let sortOrder = count + 1

      for (const f of fields) {
        const fKey = f.field_key || f.label.toLowerCase().replace(/[^a-z0-9_]/g, '_')
        if (existingKeys.includes(fKey)) continue

        const fId = 'fld_def_' + randomUUID().substring(0, 8)
        db.prepare(`
          INSERT INTO folder_field_definitions (id, folder_id, field_key, label, label_key, field_type, entity_type, options, logic_rules, is_required, sort_order)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `).run(
          fId,
          folder_id,
          fKey,
          f.label || fKey,
          f.label_key || null,
          f.field_type || 'text',
          f.entity_type || 'task',
          JSON.stringify(f.options || []),
          f.logic_rules ? JSON.stringify(f.logic_rules) : null,
          f.is_required ? 1 : 0,
          sortOrder++
        )
        existingKeys.push(fKey)
      }
    }
  }

  // Benutzerdefinierte Felder aus Import / Parametern registrieren
  const customFieldDefs = Array.isArray(body.custom_field_definitions) ? body.custom_field_definitions : []
  const existingFolderKeys = (db.prepare('SELECT field_key FROM folder_field_definitions WHERE folder_id = ?').all(folder_id) as any[]).map((f) => f.field_key)
  const countFolderFields = (db.prepare('SELECT COUNT(*) as c FROM folder_field_definitions WHERE folder_id = ?').get(folder_id) as any).c
  let curSortOrder = countFolderFields + 1

  for (const cfd of customFieldDefs) {
    const rawKey = String(cfd.field_key || cfd.label || '').trim().toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/^_+|_+$/g, '')
    if (!rawKey || existingFolderKeys.includes(rawKey)) continue

    const fId = 'fld_def_' + randomUUID().substring(0, 8)
    const fLabel = String(cfd.label || rawKey).trim()
    const fLabelKey = cfd.label_key || null
    db.prepare(`
      INSERT INTO folder_field_definitions (id, folder_id, field_key, label, label_key, field_type, entity_type, options, logic_rules, is_required, sort_order)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `).run(
      fId,
      folder_id,
      rawKey,
      fLabel,
      fLabelKey,
      cfd.field_type || 'text',
      cfd.entity_type || 'task',
      JSON.stringify(cfd.options || []),
      cfd.logic_rules ? JSON.stringify(cfd.logic_rules) : '{}',
      0,
      curSortOrder++
    )
    existingFolderKeys.push(rawKey)
  }

  if (Array.isArray(import_tasks) && import_tasks.length > 0) {
    // Falls noch nicht registrierte custom_data Keys in import_tasks enthalten sind, automatisch als Felddefinitionen anlegen
    for (const taskItem of import_tasks) {
      if (taskItem.custom_data && typeof taskItem.custom_data === 'object') {
        for (const [k] of Object.entries(taskItem.custom_data)) {
          const rawKey = String(k).trim().toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/^_+|_+$/g, '')
          if (!rawKey || existingFolderKeys.includes(rawKey)) continue

          const fId = 'fld_def_' + randomUUID().substring(0, 8)
          const fLabel = rawKey.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())
          db.prepare(`
            INSERT INTO folder_field_definitions (id, folder_id, field_key, label, field_type, entity_type, options, logic_rules, is_required, sort_order)
            VALUES (?, ?, ?, ?, 'text', 'task', '[]', '{}', 0, ?)
          `).run(fId, folder_id, rawKey, fLabel, curSortOrder++)
          existingFolderKeys.push(rawKey)
        }
      }
    }

    const insTask = db.prepare(`
      INSERT INTO tasks (id, list_id, title, description, status, priority, due_date, tags, custom_data)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `)
    for (const taskItem of import_tasks) {
      const tTitle = String(taskItem.title || '').trim()
      if (!tTitle) continue

      const targetSec = String(taskItem.list_title || '').trim().toLowerCase()
      const targetListId = listMap.get(targetSec) || firstListId

      const tId = 'tsk_' + randomUUID().substring(0, 8)
      const tDesc = String(taskItem.description || '')
      const tStatus = String(taskItem.status || 'todo')
      const tPriority = String(taskItem.priority || 'normal')
      const tDueDate = taskItem.due_date ? String(taskItem.due_date) : null
      const tTags = taskItem.tags ? (Array.isArray(taskItem.tags) ? JSON.stringify(taskItem.tags) : JSON.stringify([taskItem.tags])) : '[]'
      const tCustomData = taskItem.custom_data && typeof taskItem.custom_data === 'object' ? JSON.stringify(taskItem.custom_data) : '{}'

      insTask.run(tId, targetListId, tTitle, tDesc, tStatus, tPriority, tDueDate, tTags, tCustomData)
    }
  }

  return {
    project: {
      id: projectId,
      folder_id,
      title: title.trim(),
      status: 'active'
    }
  }
})


