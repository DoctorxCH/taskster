const fs = require('fs')
const path = require('path')

const sourceDir = path.join(__dirname, '..', '.output', 'public')
const targetDir = path.join(__dirname, '..')

if (!fs.existsSync(sourceDir)) {
  console.error('❌ .output/public existiert nicht. Bitte zuerst "npm run generate" ausführen.')
  process.exit(1)
}

function copyRecursiveSync(src, dest) {
  const exists = fs.existsSync(src)
  const stats = exists && fs.statSync(src)
  const isDirectory = exists && stats.isDirectory()

  if (isDirectory) {
    if (!fs.existsSync(dest)) {
      fs.mkdirSync(dest, { recursive: true })
    }
    fs.readdirSync(src).forEach((childItemName) => {
      copyRecursiveSync(path.join(src, childItemName), path.join(dest, childItemName))
    })
  } else {
    fs.copyFileSync(src, dest)
  }
}

console.log('🔄 Synchronisiere .output/public in das Git-Root-Verzeichnis...')

// 1. Sync all entries from .output/public to root
const entries = fs.readdirSync(sourceDir)
for (const entry of entries) {
  const src = path.join(sourceDir, entry)
  const dest = path.join(targetDir, entry)
  copyRecursiveSync(src, dest)
  console.log(`  ✓ Kopiert: ${entry}`)
}

// 2. Clean up obsolete files in _nuxt directory
const targetNuxtDir = path.join(targetDir, '_nuxt')
const sourceNuxtDir = path.join(sourceDir, '_nuxt')
if (fs.existsSync(targetNuxtDir) && fs.existsSync(sourceNuxtDir)) {
  const sourceFiles = new Set(fs.readdirSync(sourceNuxtDir))
  const targetFiles = fs.readdirSync(targetNuxtDir)
  for (const f of targetFiles) {
    if (f !== 'builds' && !sourceFiles.has(f)) {
      fs.rmSync(path.join(targetNuxtDir, f), { recursive: true, force: true })
      console.log(`  🗑️ Altes Asset gelöscht: _nuxt/${f}`)
    }
  }
}

console.log('✅ Synchronisation nach Git-Root erfolgreich abgeschlossen!')
