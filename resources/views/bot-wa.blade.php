@extends('layouts.app')

@section('title', 'WhatsApp Bot Sobat Napas — NapasAman Kediri Raya')

@push('styles')
<style>
    .wa-bg {
        background-color: #EFEAE2;
        background-image: radial-gradient(rgba(11, 53, 102, 0.06) 1px, transparent 1px);
        background-size: 18px 18px;
    }
</style>
@endpush

@section('content')
<div x-data="sobatNapasChat()" x-init="init()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <!-- Header -->
    <div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-[#2E9E3F] text-xs font-bold border border-emerald-200">
            💬 Simulasi Langsung + REST Webhook Siap Pakai
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0B3566] mt-2">WhatsApp Bot "Sobat Napas"</h1>
        <p class="text-sm text-slate-600 mt-1 max-w-2xl">
            Uji langsung asisten kualitas udara Kediri Raya lewat antarmuka chat WhatsApp di bawah ini. Seluruh pesan diproses langsung oleh controller &amp; database asli NapasAman (termasuk mencatat laporan asap baru ke tabel <code>smoke_reports</code>).
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- WhatsApp Simulator Window -->
        <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200 shadow-lg overflow-hidden flex flex-col">
            <!-- WA Header -->
            <div class="bg-[#075E54] text-white px-5 py-3.5 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-white p-1 flex items-center justify-center shrink-0 shadow">
                        <img src="{{ asset('images/icon-192.png') }}" alt="Sobat Napas" class="w-full h-full object-contain rounded-full">
                    </div>
                    <div>
                        <h2 class="font-extrabold text-sm sm:text-base leading-tight flex items-center gap-1.5">
                            Sobat Napas — NapasAman
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[#2E9E3F] text-[10px]">✓</span>
                        </h2>
                        <p class="text-[11px] text-emerald-100" x-text="sending ? 'sedang mengetik...' : 'online • Khusus Eks-Karesidenan Kediri'"></p>
                    </div>
                </div>
                <div class="hidden sm:flex items-center gap-1.5 text-[11px] bg-white/15 px-2.5 py-1 rounded-full">
                    <span>No. Simulasi:</span>
                    <input type="text" x-model="senderPhone" class="bg-transparent w-28 text-white font-mono focus:outline-none border-b border-white/40 text-xs">
                </div>
            </div>

            <!-- Chat Messages Area -->
            <div id="waChatContainer" class="wa-bg p-4 sm:p-5 h-[440px] overflow-y-auto space-y-3">
                <!-- Initial Welcome Bubble -->
                <div class="flex justify-start">
                    <div class="max-w-[85%] rounded-2xl rounded-tl-sm bg-white px-4 py-3 shadow-sm text-xs sm:text-sm text-slate-800 space-y-1.5">
                        <p class="font-bold text-[#2E9E3F] text-xs">Sobat Napas 🌿</p>
                        <p class="leading-relaxed">
                            Halo warga Kediri Raya! Kirim pesan seperti <strong>CEK KEDIRI</strong>, <strong>JOGGING TULUNGAGUNG</strong>, atau <strong>LAPOR SAMPAH Dekat SLG Kediri</strong> untuk mencoba langsung respons otomatis saya.
                        </p>
                        <p class="text-[10px] text-slate-400 text-right">Aktif • Real-Time Engine</p>
                    </div>
                </div>

                <!-- Dynamic Chat Bubbles -->
                <template x-for="(msg, index) in messages" :key="index">
                    <div :class="msg.from === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.from === 'user'
                                ? 'bg-[#D9FDD3] rounded-2xl rounded-tr-sm text-slate-900'
                                : 'bg-white rounded-2xl rounded-tl-sm text-slate-800'"
                             class="max-w-[88%] px-4 py-2.5 shadow-sm text-xs sm:text-sm space-y-1">
                            <div class="leading-relaxed break-words" x-html="formatWaText(msg.text)"></div>
                            <div class="flex items-center justify-end gap-1 text-[10px] text-slate-500">
                                <span x-text="msg.time"></span>
                                <span x-show="msg.from === 'user'" class="text-[#38A8E8] font-bold">✓✓</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Quick Command Chips -->
            <div class="bg-slate-50 border-t border-slate-200 px-4 py-2.5 flex items-center gap-2 overflow-x-auto">
                <span class="text-[11px] font-bold text-slate-500 shrink-0">Coba cepat:</span>
                <template x-for="cmd in quickCommands" :key="cmd">
                    <button type="button" @click="sendQuick(cmd)"
                            class="shrink-0 px-3 py-1.5 rounded-full bg-white hover:bg-[#2E9E3F] text-[#0B3566] hover:text-white border border-slate-200 text-xs font-bold transition shadow-xs"
                            x-text="cmd"></button>
                </template>
            </div>

            <!-- Message Composer -->
            <form @submit.prevent="sendMessage()" class="bg-[#F0F2F5] px-4 py-3 flex items-center gap-2">
                <input type="text" x-model="draft" :disabled="sending"
                       placeholder="Ketik pesan (misal: CEK KEDIRI, JOGGING BLITAR, LAPOR SAMPAH ...)"
                       class="flex-1 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-[#2E9E3F] focus:outline-none">
                <button type="submit" :disabled="sending || !draft.trim()"
                        class="px-5 py-2.5 rounded-full bg-[#2E9E3F] hover:bg-[#237E31] disabled:opacity-50 text-white font-bold text-sm transition flex items-center gap-1.5 shrink-0">
                    <span x-text="sending ? '...' : 'Kirim'"></span>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </form>
        </div>

        <!-- Right Column: Commands Guide & Webhook API Spec -->
        <div class="lg:col-span-5 space-y-5">

            <!-- Command Guide -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-4">
                <h2 class="text-lg font-extrabold text-[#0B3566]">Daftar Perintah Sobat Napas</h2>

                <div class="space-y-3 text-xs sm:text-sm">
                    <div class="p-3.5 rounded-xl bg-sky-50/70 border border-sky-200 space-y-1">
                        <p class="font-extrabold text-[#0B3566] font-mono">CEK [Nama Wilayah]</p>
                        <p class="text-slate-600 text-xs">Menampilkan AQI, PM2.5, PM10, NO₂, analogi batang rokok, saran aktivitas, dan link langsung ke <code>/rute-bersih</code>.</p>
                        <p class="text-[11px] text-[#38A8E8] font-semibold">Contoh: CEK KEDIRI • CEK TULUNGAGUNG • CEK NGANJUK</p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 space-y-1">
                        <p class="font-extrabold text-[#2E9E3F] font-mono">JOGGING [Nama Wilayah]</p>
                        <p class="text-slate-600 text-xs">Memberikan rekomendasi jam lari/olahraga paling bersih pagi &amp; sore hari ini di wilayah tersebut.</p>
                        <p class="text-[11px] text-[#2E9E3F] font-semibold">Contoh: JOGGING BLITAR • JOGGING TRENGGALEK</p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-orange-50/70 border border-orange-200 space-y-1">
                        <p class="font-extrabold text-orange-700 font-mono">LAPOR [Kategori] [Lokasi]</p>
                        <p class="text-slate-600 text-xs">Otomatis menyimpan laporan warga ke tabel <code>smoke_reports</code> &amp; menandainya sebagai hotspot aktif pada Peta Rute Bersih.</p>
                        <p class="text-[11px] text-orange-700 font-semibold">Contoh: LAPOR SAMPAH Dekat Simpang Lima Gumul Kediri</p>
                    </div>
                </div>
            </div>

            <!-- Webhook REST API Info (Baileys + Gemini AI + Fonnte & Meta Ready) -->
            <div class="bg-[#0B3566] text-white rounded-2xl p-5 sm:p-6 shadow-md space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-base font-extrabold">WhatsApp Asli (Baileys) &amp; Gemini AI</h3>
                    <span class="px-2.5 py-0.5 rounded-full {{ $geminiEnabled ? 'bg-[#2E9E3F]' : 'bg-amber-500' }} text-[10px] font-bold">
                        {{ $geminiEnabled ? $geminiModel . ' Aktif' : 'Fallback Mode' }}
                    </span>
                </div>
                <p class="text-xs text-sky-100 leading-relaxed">
                    Mendukung <strong>tanya-jawab bebas Gemini AI</strong>, <strong>Share Location</strong>, serta <strong>Foto + caption LAPOR</strong> dari aplikasi WhatsApp asli melalui service <code>wa-bot/</code> (Baileys) maupun Fonnte &amp; Meta Cloud API:
                </p>
                <div class="rounded-xl bg-black/30 px-3.5 py-2.5 font-mono text-xs text-[#6CE07D] break-all">
                    POST {{ $webhookUrl }}
                </div>
                <pre class="rounded-xl bg-black/30 p-3 text-[11px] text-sky-100 overflow-x-auto leading-relaxed">{
  "sender": "6281234567890",
  "message": "Aman gak jogging sore ini di Kediri?",
  "latitude": -7.8166,
  "longitude": 112.0114
}</pre>
                <p class="text-[11px] text-sky-200">
                    Seluruh interaksi tercatat otomatis pada tabel MySQL <code>whatsapp_logs</code> dan foto laporan tersimpan di <code>storage/app/public/reports/</code>.
                </p>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const INITIAL_WA_LOGS = @json($recentLogs);

    function sobatNapasChat() {
        return {
            senderPhone: '6281234567890',
            draft: '',
            sending: false,
            messages: [],
            quickCommands: [
                'Aman gak jogging sore ini di Kediri?',
                'Kualitas udara Tulungagung gimana?',
                'CEK KEDIRI',
                'JOGGING BLITAR',
                'LAPOR SAMPAH Dekat Simpang Lima Gumul Kediri',
                'Cek kualitas udara Jakarta dong'
            ],

            init() {
                // Show last 2 sample interactions if available
                const tail = INITIAL_WA_LOGS.slice(-2);
                tail.forEach((log) => {
                    const t = new Date(log.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    this.messages.push({ from: 'user', text: log.incoming_message, time: t });
                    this.messages.push({ from: 'bot', text: log.bot_reply, time: t });
                });
                this.scrollToBottom();
            },

            sendQuick(cmd) {
                this.draft = cmd;
                this.sendMessage();
            },

            async sendMessage() {
                const text = this.draft.trim();
                if (!text || this.sending) return;

                const nowTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                this.messages.push({ from: 'user', text: text, time: nowTime });
                this.draft = '';
                this.sending = true;
                this.scrollToBottom();

                try {
                    const res = await fetch('{{ route('bot-wa.simulate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            phone: this.senderPhone,
                            message: text
                        })
                    });

                    const data = await res.json();
                    this.messages.push({
                        from: 'bot',
                        text: data.reply || 'Maaf, terjadi kendala memproses pesan.',
                        time: data.timestamp || nowTime
                    });
                } catch (err) {
                    this.messages.push({
                        from: 'bot',
                        text: 'Koneksi ke server simulasi terputus. Silakan coba kembali.',
                        time: nowTime
                    });
                } finally {
                    this.sending = false;
                    this.scrollToBottom();
                }
            },

            formatWaText(str) {
                const escaped = String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                return escaped
                    .replace(/\*([^*]+)\*/g, '<strong>$1</strong>')
                    .replace(/_([^_]+)_/g, '<em class="text-slate-500">$1</em>')
                    .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-[#1F8FD0] underline font-semibold">$1</a>')
                    .replace(/\n/g, '<br>');
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const box = document.getElementById('waChatContainer');
                    if (box) box.scrollTop = box.scrollHeight;
                });
            }
        }
    }
</script>
@endpush
