const { Client } = require('ssh2')
const fs = require('fs')
const path = require('path')

const sftpConfig = JSON.parse(fs.readFileSync('.vscode/sftp.json', 'utf8'))

async function syncDir(sftp, localDir, remoteDir) {
  const entries = fs.readdirSync(localDir)

  // Ensure remote directory exists
  await new Promise((resolve) => {
    sftp.mkdir(remoteDir, () => resolve())
  })

  for (const entry of entries) {
    const localPath = path.join(localDir, entry)
    const remotePath = `${remoteDir}/${entry}`.replace(/\/+/g, '/')
    const stat = fs.statSync(localPath)

    if (stat.isDirectory()) {
      await syncDir(sftp, localPath, remotePath)
    } else {
      await new Promise((resolve, reject) => {
        sftp.fastPut(localPath, remotePath, (err) => {
          if (err) reject(err)
          else {
            sftp.chmod(remotePath, 0o644, () => resolve())
          }
        })
      })
      console.log(`Uploaded: ${entry} -> ${remotePath}`)
    }
  }
}

const conn = new Client()
console.log('Connecting via SFTP to deploy frontend to /sub/taskster...')

conn.on('ready', () => {
  conn.sftp(async (err, sftp) => {
    if (err) {
      console.error('SFTP Error:', err)
      conn.end()
      return
    }

    try {
      const publicDir = path.join(__dirname, '..', '.output', 'public')
      console.log(`Syncing from ${publicDir} to /sub/taskster...`)
      await syncDir(sftp, publicDir, '/sub/taskster')
      console.log('✅ Deployment to /sub/taskster completed!')
    } catch (deployErr) {
      console.error('Deployment error:', deployErr)
    } finally {
      conn.end()
    }
  })
}).connect({
  host: sftpConfig.host,
  port: 22,
  username: sftpConfig.username,
  password: sftpConfig.password
})
