import makeWASocket, {
  DisconnectReason,
  downloadMediaMessage,
  fetchLatestBaileysVersion,
  useMultiFileAuthState,
} from '@whiskeysockets/baileys';
import axios from 'axios';
import pino from 'pino';
import qrcode from 'qrcode-terminal';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL || 'http://127.0.0.1:8000/api/whatsapp/webhook';
const AUTH_DIR = path.join(__dirname, 'auth_info');

const baileysLogger = pino({ level: 'silent' });

/**
 * Unwrap nested WhatsApp message wrappers (ephemeral, viewOnce, etc.).
 */
function unwrapMessageContent(message) {
  if (!message) return null;
  if (message.ephemeralMessage?.message) {
    return unwrapMessageContent(message.ephemeralMessage.message);
  }
  if (message.viewOnceMessage?.message) {
    return unwrapMessageContent(message.viewOnceMessage.message);
  }
  if (message.viewOnceMessageV2?.message) {
    return unwrapMessageContent(message.viewOnceMessageV2.message);
  }
  if (message.documentWithCaptionMessage?.message) {
    return unwrapMessageContent(message.documentWithCaptionMessage.message);
  }
  return message;
}

/**
 * Clean sender phone identifier from JID.
 */
function extractSenderPhone(msg) {
  const candidate = msg.key.remoteJidAlt || msg.key.remoteJid || '';
  return candidate.split('@')[0].split(':')[0].replace(/[^0-9]/g, '') || candidate;
}

async function startSobatNapasBot() {
  console.log('==============================================================');
  console.log(' 🌿 NapasAman — WhatsApp Bot "Sobat Napas" (Baileys + Gemini)');
  console.log(` 🔗 Laravel Webhook : ${WEBHOOK_URL}`);
  console.log(` 🔐 Session Folder  : ${AUTH_DIR}`);
  console.log('==============================================================\n');

  const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
  let version;
  try {
    const latest = await fetchLatestBaileysVersion();
    version = latest.version;
  } catch {
    version = undefined;
  }

  const sock = makeWASocket({
    version,
    auth: state,
    logger: baileysLogger,
    browser: ['NapasAman Sobat Napas', 'Chrome', '1.0.0'],
    markOnlineOnConnect: true,
    syncFullHistory: false,
  });

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      console.log('\n📱 Scan QR Code berikut melalui WhatsApp -> Perangkat Tertaut (Linked Devices):\n');
      qrcode.generate(qr, { small: true });
      console.log('\nMenunggu pemindaian QR Code...\n');
    }

    if (connection === 'open') {
      console.log('✅ Terhubung ke WhatsApp! Bot "Sobat Napas" siap menerima pesan warga Kediri Raya.\n');
    }

    if (connection === 'close') {
      const statusCode = lastDisconnect?.error?.output?.statusCode;
      const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

      console.log(
        `⚠️ Koneksi WhatsApp terputus (kode: ${statusCode ?? 'unknown'}). ` +
          (shouldReconnect
            ? 'Mencoba menyambungkan ulang dalam 3 detik...'
            : 'Sesi keluar (logged out). Hapus folder wa-bot/auth_info lalu jalankan ulang node index.js.')
      );

      if (shouldReconnect) {
        setTimeout(() => startSobatNapasBot(), 3000);
      }
    }
  });

  sock.ev.on('messages.upsert', async ({ messages, type }) => {
    if (type !== 'notify') return;

    for (const msg of messages) {
      try {
        if (!msg.message) continue;
        if (msg.key.fromMe) continue;

        const remoteJid = msg.key.remoteJid || '';
        // Abaikan pesan grup, status broadcast, dan saluran/newsletter
        if (
          !remoteJid ||
          remoteJid.endsWith('@g.us') ||
          remoteJid === 'status@broadcast' ||
          remoteJid.endsWith('@newsletter')
        ) {
          continue;
        }

        const content = unwrapMessageContent(msg.message);
        if (!content) continue;

        const senderPhone = extractSenderPhone(msg);
        const pushName = msg.pushName || 'Warga Kediri Raya';

        const payload = {
          sender: senderPhone,
          push_name: pushName,
          message: '',
          message_type: 'text',
          latitude: null,
          longitude: null,
          location_name: null,
          image_base64: null,
          image_mime: null,
        };

        // 1. Pesan Share Location / Live Location
        const locMsg = content.locationMessage || content.liveLocationMessage;
        if (locMsg && typeof locMsg.degreesLatitude === 'number' && typeof locMsg.degreesLongitude === 'number') {
          payload.message_type = 'location';
          payload.latitude = locMsg.degreesLatitude;
          payload.longitude = locMsg.degreesLongitude;
          payload.location_name = locMsg.name || locMsg.address || null;
          payload.message = locMsg.comment || locMsg.caption || locMsg.name || `[Share Location: ${locMsg.degreesLatitude.toFixed(5)}, ${locMsg.degreesLongitude.toFixed(5)}]`;
        }
        // 2. Pesan Foto (dengan atau tanpa caption)
        else if (content.imageMessage) {
          payload.message_type = 'image';
          payload.message = (content.imageMessage.caption || '').trim();
          payload.image_mime = content.imageMessage.mimetype || 'image/jpeg';

          try {
            const buffer = await downloadMediaMessage(
              msg,
              'buffer',
              {},
              {
                logger: baileysLogger,
                reuploadRequest: sock.updateMediaMessage,
              }
            );
            if (buffer && buffer.length > 0) {
              payload.image_base64 = buffer.toString('base64');
            }
          } catch (dlErr) {
            console.error('❌ Gagal mengunduh foto dari WhatsApp:', dlErr.message);
          }
        }
        // 3. Pesan Teks Biasa
        else {
          const text =
            content.conversation ||
            content.extendedTextMessage?.text ||
            content.buttonsResponseMessage?.selectedDisplayText ||
            content.listResponseMessage?.title ||
            '';

          if (!text.trim()) {
            continue;
          }
          payload.message_type = 'text';
          payload.message = text.trim();
        }

        console.log(`📩 [${new Date().toLocaleTimeString('id-ID')}] Dari ${pushName} (${senderPhone}) [${payload.message_type}]: ${payload.message}`);

        // Tampilkan status "mengetik..." di WhatsApp pengirim
        await sock.sendPresenceUpdate('composing', remoteJid).catch(() => {});

        const response = await axios.post(WEBHOOK_URL, payload, {
          timeout: 25000,
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
        });

        const replyText = response.data?.reply;
        await sock.sendPresenceUpdate('paused', remoteJid).catch(() => {});

        if (replyText && typeof replyText === 'string') {
          await sock.sendMessage(remoteJid, { text: replyText }, { quoted: msg });
          console.log(`📤 [${new Date().toLocaleTimeString('id-ID')}] Balasan terkirim ke ${senderPhone} (cmd: ${response.data?.command ?? 'AI'})`);
        }
      } catch (err) {
        const errDetail = err.response?.data ? JSON.stringify(err.response.data) : err.message;
        console.error(`❌ Gagal memproses pesan masuk: ${errDetail}`);

        const remoteJid = msg.key?.remoteJid;
        if (remoteJid && !remoteJid.endsWith('@g.us')) {
          await sock
            .sendMessage(remoteJid, {
              text: 'Maaf Kak, server *NapasAman* sedang sibuk atau belum aktif. Pastikan server Laravel berjalan lalu coba beberapa saat lagi ya! 🌿',
            })
            .catch(() => {});
        }
      }
    }
  });
}

startSobatNapasBot().catch((err) => {
  console.error('❌ Fatal error menjalankan WhatsApp Bot:', err);
  process.exit(1);
});
