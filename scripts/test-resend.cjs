const { Resend } = require('resend');
const fs = require('fs');
const path = require('path');

// Load .env
const envPath = path.join(__dirname, '..', '.env');
let apiKey = process.env.RESEND_API_KEY;
if (!apiKey && fs.existsSync(envPath)) {
  const content = fs.readFileSync(envPath, 'utf8');
  for (const line of content.split('\n')) {
    const trimmed = line.trim();
    if (trimmed.startsWith('RESEND_API_KEY=')) {
      apiKey = trimmed.split('=')[1].trim().replace(/^['"]|['"]$/g, '');
      break;
    }
  }
}

if (!apiKey || apiKey === 're_xxxxxxxxx') {
  console.error('❌ Fehler: Bitte trage deinen echten Resend API-Key in die .env Datei ein:');
  console.error('   RESEND_API_KEY=re_dein_echter_key\n');
  process.exit(1);
}

const resend = new Resend(apiKey);

async function test() {
  const targetEmail = process.argv[2] || 'martin.kurka93@gmail.com';
  console.log(`🚀 Sende Test-E-Mail via Resend an: ${targetEmail}...`);

  try {
    const { data, error } = await resend.emails.send({
      from: 'Taskster <noreply@kurka.ch>',
      to: [targetEmail],
      subject: 'Taskster Resend Test – ' + new Date().toLocaleTimeString(),
      html: `
        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 24px; color: #1e293b;">
          <h2 style="color: #00A3C4;">Taskster E-Mail Test via Resend</h2>
          <p>Diese E-Mail wurde erfolgreich über die <strong>Resend API</strong> mit dem Absender <code>noreply@kurka.ch</code> versendet.</p>
          <p>Die Zustellung an Microsoft Outlook (@outlook.com, @hotmail.com) und Gmail funktioniert nun einwandfrei mit voller DKIM/SPF-Signatur.</p>
          <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;" />
          <span style="font-size: 12px; color: #64748b;">Taskster System • kurka.ch</span>
        </div>
      `,
      text: 'Taskster E-Mail Test via Resend von noreply@kurka.ch'
    });

    if (error) {
      console.error('❌ Resend API Fehler:', error);
      if (error.message && error.message.includes('domain')) {
        console.log('\n💡 HINWEIS ZUR DOMAIN-VERIFIZIERUNG:');
        console.log('   Um von noreply@kurka.ch zu senden, muss die Domain "kurka.ch"');
        console.log('   in deinem Resend Dashboard (https://resend.com/domains) verifiziert sein.');
      }
      process.exit(1);
    }

    console.log('✅ E-Mail erfolgreich über Resend gesendet! ID:', data.id);
  } catch (err) {
    console.error('❌ Unerwarteter Fehler:', err.message || err);
    process.exit(1);
  }
}

test();
