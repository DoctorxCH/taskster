import jwt from 'jsonwebtoken'
import bcrypt from 'bcryptjs'
import type { H3Event } from 'h3'
import { db } from '../db'

const JWT_SECRET = process.env.JWT_SECRET || 'taskster-super-secret-key-2026-safe-production'

export interface AuthUser {
  id: string
  name: string
  email: string
  company_id: string | null
  company_role: string | null
  is_superadmin: number
  is_pro: number
}

export function hashPassword(password: string): string {
  return bcrypt.hashSync(password, 10)
}

export function comparePassword(password: string, hash: string): boolean {
  return bcrypt.compareSync(password, hash)
}

export function generateToken(user: AuthUser): string {
  return jwt.sign(
    {
      id: user.id,
      email: user.email,
      name: user.name,
      company_id: user.company_id,
      company_role: user.company_role,
      is_superadmin: user.is_superadmin,
      is_pro: user.is_pro
    },
    JWT_SECRET,
    { expiresIn: '30d' }
  )
}

export function getUserFromToken(token: string): AuthUser | null {
  try {
    const decoded = jwt.verify(token, JWT_SECRET) as any
    const row = db.prepare('SELECT id, name, email, company_id, company_role, is_superadmin, is_pro FROM users WHERE id = ?').get(decoded.id) as AuthUser | undefined
    return row || null
  } catch {
    return null
  }
}

export function requireAuth(event: H3Event): AuthUser {
  const authHeader = getHeader(event, 'authorization')
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Unauthorized - Bitte anmelden'
    })
  }

  const token = authHeader.substring(7)
  const user = getUserFromToken(token)
  if (!user) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Ungültiger oder abgelaufener Token'
    })
  }

  return user
}

export function requireSuperadmin(event: H3Event): AuthUser {
  const user = requireAuth(event)
  if (!user.is_superadmin) {
    throw createError({
      statusCode: 403,
      statusMessage: 'Nur Plattform-Superadmins haben Zugriff auf diesen Bereich'
    })
  }
  return user
}
