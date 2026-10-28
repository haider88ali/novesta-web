<script setup>
import { Icon } from '@iconify/vue'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Accordion from '@/components/ui/accordion/Accordion.vue'
import AccordionContent from '@/components/ui/accordion/AccordionContent.vue'
import AccordionItem from '@/components/ui/accordion/AccordionItem.vue'
import AccordionTrigger from '@/components/ui/accordion/AccordionTrigger.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Button from '@/components/ui/button/Button.vue'
import { useSeoMetaTags } from '@/composables/useSeoMetaTags.js'
import WebLayout from '@/layouts/WebLayout.vue'

const props = defineProps({
  canLogin: { type: Boolean },
  canRegister: { type: Boolean },
  plans: { type: Array, default: () => [] },
  trialDays: { type: Number, default: 7 },
  downloads: { type: Object, default: () => ({}) },
  seo: { type: Object, default: () => null },
})

useSeoMetaTags(props.seo)

const page = usePage()
const loggedIn = computed(() => Boolean(page.props.auth?.user))
const startHref = computed(() => (loggedIn.value ? '/dashboard' : '/register'))
const trialPlan = computed(() => props.plans.find(p => p.key === 'pro'))

// The desktop app's languages (voice-assistant/languages.js)
const languages = [
  { label: 'English' }, { label: 'اردو', urdu: true, rtl: true }, { label: 'Roman Urdu' }, { label: 'हिन्दी' },
  { label: 'العربية', rtl: true }, { label: 'বাংলা' }, { label: 'فارسی', rtl: true }, { label: 'Türkçe' },
  { label: 'Español' }, { label: 'Français' }, { label: 'Deutsch' }, { label: 'Português' }, { label: 'Italiano' },
  { label: 'Русский' }, { label: '中文' }, { label: '日本語' }, { label: '한국어' }, { label: 'Bahasa Indonesia' },
]

const examples = [
  { say: 'What\'s new in Laravel this month?', does: 'Searches the web and answers with sources', icon: 'lucide:globe' },
  { say: 'Work on my shop project and run the tests', does: 'Opens the project, runs them after you approve', icon: 'lucide:square-terminal' },
  { say: 'Look at my screen — what does this error mean?', does: 'Reads your screen and explains it', icon: 'lucide:scan-eye' },
  { say: 'Design a login screen, then make it in React', does: 'Designs it, sends it to Figma, writes the code', icon: 'lucide:palette' },
  { say: 'Good morning', does: 'Weather, your reminders and the top news in one briefing', icon: 'lucide:sunrise' },
  { say: 'نووا، کل صبح 9 بجے علی کو کال کرنے کی یاد دہانی', does: 'Understands and replies in Urdu, sets the reminder', icon: 'lucide:languages', rtl: true },
  { say: 'Aaj Lahore mein Asr kitne baje hai?', does: 'Today\'s prayer times for your city, in Roman Urdu', icon: 'lucide:moon-star' },
  { say: 'WhatsApp Ahmed that I\'ll be 10 minutes late', does: 'Finds the contact and gets the message ready to send', icon: 'lucide:message-circle' },
  { say: 'Abre Slack y pon el volumen al 30', does: 'Controls your computer — in Spanish, Arabic, French and more', icon: 'lucide:monitor-cog' },
]

const features = [
  { icon: 'lucide:audio-lines', title: 'Talk from any app', text: 'Press Option+Space (Alt+Space on Windows) wherever you are and just talk. Nova waits until you\'ve finished — pauses and "umm"s don\'t cut you off.' },
  { icon: 'lucide:search', title: 'Live web answers', text: 'Ask about news, prices, docs or anything current. Nova searches the web and answers with links to its sources.' },
  { icon: 'lucide:code-xml', title: 'Code on command', text: 'Ask for scripts, components or fixes. Code is highlighted, one click to copy, and Nova can save it straight to a file.' },
  { icon: 'lucide:square-terminal', title: 'Developer project mode', text: 'Say "work on my shop project". Nova reads and searches your code, then runs tests or edits files — always after you approve the exact command or change.' },
  { icon: 'lucide:scan-eye', title: 'Sees your screen', text: 'Say "look at my screen" and Nova explains the error, chart or page in front of you — only when you ask.' },
  { icon: 'lucide:notebook-pen', title: 'Meeting copilot', text: 'Nova listens to your meeting and writes live notes, then gives you a summary, decisions, action items and a ready follow-up email.' },
  { icon: 'lucide:figma', title: 'Design → Figma → code', text: 'Describe a screen and Nova designs it and sends it to Figma as editable layers. Change it by voice, then turn it into Vue, React or HTML with Tailwind.' },
  { icon: 'lucide:monitor-cog', title: 'Controls your computer', text: 'Open apps, files, folders and websites, change volume, play music, take screenshots, check your battery and prepare WhatsApp messages.' },
  { icon: 'lucide:sunrise', title: 'Routines, reminders & memory', text: 'Say "good morning" for your daily briefing, schedule routines and set reminders by voice. Nova remembers your preferences and history — on your own computer.' },
  { icon: 'lucide:languages', title: `Speaks ${languages.length} languages`, text: 'Talk and get answers in English, Español, Français, العربية, हिन्दी, 中文, 日本語 and more — including Urdu, Roman Urdu and namaz times for your city.' },
  { icon: 'lucide:clipboard-type', title: 'Quick actions on copied text', text: 'Copy any text and press Cmd/Ctrl+Shift+Space to summarise, translate, fix the grammar or write a reply.' },
  { icon: 'lucide:smile', title: 'Your voice, your style', text: 'Pick from natural AI voices and personalities — calm JARVIS, friendly, professional, or Bhai mode in Roman Urdu.' },
]

const steps = [
  { icon: 'lucide:download', title: 'Download Nova', text: 'Install the app on your Mac or Windows PC.' },
  { icon: 'lucide:log-in', title: 'Sign in once', text: 'Nova opens this website to confirm it\'s you — no passwords typed into the app.' },
  { icon: 'lucide:mic', title: 'Press Option+Space and talk', text: 'Ask, search, code, design or control your computer — just by talking. (Alt+Space on Windows.)' },
]

const faqs = [
  { q: 'What do I need to run Nova?', a: 'A Mac or Windows computer with a microphone and an internet connection. No extra hardware or setup is needed.' },
  { q: 'Is there a free trial?', a: `Yes. Every new account gets ${props.trialDays} days of ${trialPlan.value?.name ?? 'Pro'} for free, with no card required. After that you stay on the Free plan unless you choose to upgrade.` },
  { q: 'Can Nova do anything on my computer without asking?', a: 'Simple actions — opening apps, folders, files and websites, volume, music, screenshots and battery status — happen when you ask for them. Anything that changes your work needs your approval: in project mode Nova shows you the exact command or file change first and only runs it when you click Approve. It never reads secrets like .env files, passwords or keys, never looks at your screen unless you ask, and never sends a WhatsApp message itself — you press send.' },
  { q: 'Does everything work on Windows?', a: 'Talking, web search, code, project mode, meeting notes, designs, routines, reminders, prayer times and quick actions work on both Mac and Windows. Controlling apps and system settings, opening files, screenshots, screen understanding and WhatsApp are Mac-only for now, with Windows support coming.' },
  { q: 'How do I talk to Nova?', a: 'Press Option+Space on a Mac or Alt+Space on Windows from any app, or click the microphone in Nova. Speak naturally; Nova answers when you stop. You can also type.' },
  { q: 'Where is my data stored?', a: 'Your chat history, memories and reminders are stored on your own computer. To answer you, each request is sent through Nova\'s servers to our AI provider (OpenAI), which may keep it for a limited time under its own data policy. Nova\'s servers record usage counts for your plan limits, not the content of your conversations.' },
  { q: 'Which languages does Nova speak?', a: `${languages.length} languages for listening, written answers and the spoken voice: English, Urdu, Roman Urdu (Urdu in English letters), Hindi, Arabic, Bengali, Persian, Turkish, Spanish, French, German, Portuguese, Italian, Russian, Chinese, Japanese, Korean and Indonesian. Pick one in the app; you can also mix it with English in the same sentence. Code, file names and technical terms stay in English.` },
  { q: 'How does the Figma integration work?', a: 'Ask Nova for a design and it appears in Nova instantly. With the free Nova Bridge plugin open in Figma\'s desktop app, the same design is created on your Figma page as editable frames, text and auto layout.' },
  { q: 'How do I pay, and can I cancel?', a: 'Payments are handled securely by Paddle, which supports cards and local taxes worldwide. You can switch plans or cancel anytime from your account; you keep your plan until the end of the paid month.' },
  { q: 'How many computers can I use?', a: 'It depends on your plan — see the device limit in each plan above. You can sign out an old computer from your account at any time.' },
]
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister">
    <!-- ================= Hero ================= -->
    <section class="relative overflow-hidden border-b">
      <div class="hero-glow pointer-events-none absolute inset-0" aria-hidden="true" />
      <div class="container relative mx-auto grid items-center gap-12 px-4 py-20 sm:py-28 lg:grid-cols-2">
        <div class="text-center lg:text-left">
          <Badge variant="outline" class="mb-6 rounded-full border-cyan-500/40 bg-cyan-500/10 px-4 py-1 text-xs sm:text-sm">
            🎙️ Speaks {{ languages.length }} languages · English · Español · <bdi>العربية</bdi> · <bdi class="font-urdu">اردو</bdi> · 中文
          </Badge>
          <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">
            Your voice AI assistant for
            <span class="bg-gradient-to-r from-cyan-400 to-violet-500 bg-clip-text text-transparent">Mac & Windows</span>
          </h1>
          <p class="mx-auto mt-6 max-w-xl text-lg text-muted-foreground lg:mx-0">
            Press <strong class="text-foreground">Option+Space</strong> from any app and just talk. Search the web, work on your code,
            take meeting notes, design screens for Figma and control your computer — in your language.
          </p>
          <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
            <Button size="lg" :as="Link" :href="startHref" class="text-base">
              {{ loggedIn ? 'Go to your account' : `Start your ${trialDays}-day free trial` }}
              <Icon icon="lucide:arrow-right" class="size-4" />
            </Button>
            <Button size="lg" variant="outline" as="a" href="#download" class="text-base">
              <Icon icon="lucide:download" class="size-4" /> Download
            </Button>
          </div>
          <p v-if="!loggedIn" class="mt-4 text-sm text-muted-foreground">
            No card needed for the trial · Cancel anytime
          </p>
        </div>

        <!-- Holographic orb with sample voice commands -->
        <div class="relative mx-auto aspect-square w-full max-w-md" aria-hidden="true">
          <div class="orb">
            <span class="ring r1" /><span class="ring r2" /><span class="ring r3" />
            <span class="core" />
          </div>
          <div class="bubble b1">
            “Design a pricing page in React”
          </div>
          <div class="bubble b2">
            ✓ Opened Visual Studio Code
          </div>
          <div class="bubble b3" dir="rtl">
            ما هو الطقس في دبي اليوم؟
          </div>
        </div>
      </div>
    </section>

    <!-- ================= Say it, Nova does it ================= -->
    <section class="border-b py-20">
      <div class="container mx-auto px-4">
        <div class="mx-auto mb-12 max-w-2xl text-center">
          <h2 class="text-3xl font-bold sm:text-4xl">
            Say it. Nova does it.
          </h2>
          <p class="mt-4 text-muted-foreground">
            Real things people ask Nova every day.
          </p>
        </div>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div v-for="ex in examples" :key="ex.say" class="rounded-xl border bg-card p-5 transition-colors hover:border-cyan-500/50">
            <p class="text-lg font-medium" :class="ex.rtl ? 'font-urdu text-right leading-loose' : ''" :dir="ex.rtl ? 'rtl' : 'ltr'">
              “{{ ex.say }}”
            </p>
            <p class="mt-3 flex items-center gap-2 text-sm text-muted-foreground">
              <Icon :icon="ex.icon" class="size-4 shrink-0 text-cyan-500" /> {{ ex.does }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ================= Features ================= -->
    <section id="features" class="border-b py-20">
      <div class="container mx-auto px-4">
        <div class="mx-auto mb-12 max-w-2xl text-center">
          <h2 class="text-3xl font-bold sm:text-4xl">
            Everything you need, one voice away
          </h2>
          <p class="mt-4 text-muted-foreground">
            Nova combines a fast AI model, live web search and real, approved control of your computer and your code.
          </p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="f in features" :key="f.title" class="rounded-xl border bg-card p-6">
            <div class="mb-4 inline-flex size-11 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-500">
              <Icon :icon="f.icon" class="size-6" />
            </div>
            <h3 class="font-semibold">
              {{ f.title }}
            </h3>
            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
              {{ f.text }}
            </p>
          </div>
        </div>
        <div class="mx-auto mt-12 max-w-4xl text-center">
          <p class="mb-4 text-sm font-medium text-muted-foreground">
            Talk to Nova in your language
          </p>
          <ul class="flex flex-wrap justify-center gap-2">
            <li
              v-for="l in languages"
              :key="l.label"
              :dir="l.rtl ? 'rtl' : 'ltr'"
              class="rounded-full border bg-card px-3 py-1 text-sm"
              :class="l.urdu ? 'font-urdu' : ''"
            >
              {{ l.label }}
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- ================= How it works ================= -->
    <section class="border-b py-20">
      <div class="container mx-auto px-4">
        <h2 class="mb-12 text-center text-3xl font-bold sm:text-4xl">
          Up and running in a minute
        </h2>
        <ol class="mx-auto grid max-w-4xl gap-8 md:grid-cols-3">
          <li v-for="(s, i) in steps" :key="s.title" class="text-center">
            <div class="relative mx-auto mb-4 flex size-14 items-center justify-center rounded-full border border-cyan-500/40 bg-cyan-500/10 text-cyan-500">
              <Icon :icon="s.icon" class="size-6" />
              <span class="absolute -right-1 -top-1 flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">{{ i + 1 }}</span>
            </div>
            <h3 class="font-semibold">
              {{ s.title }}
            </h3>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ s.text }}
            </p>
          </li>
        </ol>
      </div>
    </section>

    <!-- ================= Pricing ================= -->
    <section id="pricing" class="border-b py-20">
      <div class="container mx-auto px-4">
        <div class="mx-auto mb-12 max-w-2xl text-center">
          <h2 class="text-3xl font-bold sm:text-4xl">
            Simple pricing
          </h2>
          <p class="mt-4 text-muted-foreground">
            Start with {{ trialDays }} days of {{ trialPlan?.name ?? 'Pro' }} free. Upgrade, switch or cancel anytime.
          </p>
        </div>
        <div class="mx-auto grid max-w-5xl gap-6 md:grid-cols-3">
          <div
            v-for="plan in plans"
            :key="plan.key"
            class="relative flex flex-col rounded-2xl border bg-card p-6"
            :class="plan.key === 'pro' ? 'border-cyan-500 shadow-lg shadow-cyan-500/10 md:-translate-y-2' : ''"
          >
            <Badge v-if="plan.key === 'pro'" class="absolute -top-3 left-1/2 -translate-x-1/2">
              Most popular
            </Badge>
            <h3 class="text-lg font-semibold">
              {{ plan.name }}
            </h3>
            <p class="mt-1 text-sm text-muted-foreground">
              {{ plan.description }}
            </p>
            <p class="my-6 text-4xl font-bold">
              ${{ plan.price }}<span class="text-base font-normal text-muted-foreground">/month</span>
            </p>
            <ul class="flex-1 space-y-3 text-sm">
              <li v-for="item in plan.features" :key="item" class="flex items-start gap-2">
                <Icon icon="lucide:check" class="mt-0.5 size-4 shrink-0 text-cyan-500" /> {{ item }}
              </li>
            </ul>
            <Button
              class="mt-8 w-full"
              :variant="plan.key === 'pro' ? 'default' : 'outline'"
              :as="Link"
              :href="loggedIn ? '/billing' : '/register'"
            >
              {{ plan.price === 0 ? 'Start free' : loggedIn ? `Choose ${plan.name}` : `Try ${plan.name} free` }}
            </Button>
          </div>
        </div>
        <p class="mt-8 text-center text-sm text-muted-foreground">
          Prices in USD. Taxes are calculated at checkout by Paddle.
        </p>
      </div>
    </section>

    <!-- ================= Download ================= -->
    <section id="download" class="border-b py-20">
      <div class="container mx-auto px-4">
        <div class="mx-auto max-w-3xl rounded-2xl border bg-card p-8 text-center sm:p-12">
          <h2 class="text-3xl font-bold sm:text-4xl">
            Get Nova
          </h2>
          <p class="mt-4 text-muted-foreground">
            Download the app, sign in with your account, and press Option+Space (Alt+Space on Windows) to talk.
          </p>
          <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <Button size="lg" :as="downloads.mac ? 'a' : 'button'" :href="downloads.mac || undefined" :disabled="!downloads.mac" class="text-base">
              <Icon icon="mdi:apple" class="size-5" />
              {{ downloads.mac ? 'Download for macOS' : 'macOS — coming soon' }}
            </Button>
            <Button size="lg" variant="outline" :as="downloads.windows ? 'a' : 'button'" :href="downloads.windows || undefined" :disabled="!downloads.windows" class="text-base">
              <Icon icon="mdi:microsoft-windows" class="size-5" />
              {{ downloads.windows ? 'Download for Windows' : 'Windows — coming soon' }}
            </Button>
          </div>
          <p class="mt-6 text-sm text-muted-foreground">
            macOS · Windows 10 & 11 · Microphone and internet connection
          </p>
          <p v-if="!downloads.mac && !downloads.windows" class="mt-2 text-sm text-muted-foreground">
            <template v-if="loggedIn">
              The download will appear in <Link href="/dashboard" class="text-cyan-500 underline-offset-4 hover:underline">your account</Link> as soon as it's released.
            </template>
            <template v-else>
              <Link :href="startHref" class="text-cyan-500 underline-offset-4 hover:underline">
                Create your account now
              </Link> — your trial starts when you sign up, and the download appears in your account.
            </template>
          </p>
        </div>
      </div>
    </section>

    <!-- ================= FAQ ================= -->
    <section id="faq" class="border-b py-20">
      <div class="container mx-auto max-w-3xl px-4">
        <h2 class="mb-10 text-center text-3xl font-bold sm:text-4xl">
          Questions
        </h2>
        <Accordion type="single" collapsible class="w-full">
          <AccordionItem v-for="(f, i) in faqs" :key="f.q" :value="`faq-${i}`">
            <AccordionTrigger class="text-left">
              {{ f.q }}
            </AccordionTrigger>
            <AccordionContent class="text-muted-foreground">
              {{ f.a }}
            </AccordionContent>
          </AccordionItem>
        </Accordion>
      </div>
    </section>

    <!-- ================= Final CTA ================= -->
    <section class="relative overflow-hidden py-24">
      <div class="hero-glow pointer-events-none absolute inset-0 opacity-60" aria-hidden="true" />
      <div class="container relative mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold sm:text-5xl">
          Stop typing. Start talking.
        </h2>
        <p class="mx-auto mt-4 max-w-xl text-muted-foreground">
          Try every {{ trialPlan?.name ?? 'Pro' }} feature free for {{ trialDays }} days.
        </p>
        <Button size="lg" :as="Link" :href="startHref" class="mt-8 text-base">
          {{ loggedIn ? 'Go to your account' : 'Start free trial' }} <Icon icon="lucide:arrow-right" class="size-4" />
        </Button>
      </div>
    </section>
  </WebLayout>
</template>

<style scoped>
.font-urdu { font-family: 'Noto Nastaliq Urdu', serif; }

.hero-glow {
  background:
    radial-gradient(600px 400px at 75% 40%, rgb(34 211 238 / 0.15), transparent 70%),
    radial-gradient(500px 360px at 15% 20%, rgb(139 92 246 / 0.12), transparent 70%);
}

/* Animated holographic orb (pure CSS) */
.orb { position: absolute; inset: 12%; }
.orb .ring {
  position: absolute; inset: 0; border-radius: 9999px;
  border: 1px solid rgb(34 211 238 / 0.45);
  box-shadow: 0 0 30px rgb(34 211 238 / 0.15) inset;
}
.orb .r1 { border-top-color: rgb(34 211 238); border-width: 2px; animation: spin 9s linear infinite; }
.orb .r2 { inset: 9%; border-style: dashed; animation: spin 14s linear infinite reverse; }
.orb .r3 { inset: 18%; border-right-color: rgb(139 92 246); border-width: 2px; animation: spin 6s linear infinite; }
.orb .core {
  position: absolute; inset: 31%; border-radius: 9999px;
  background: radial-gradient(circle at 40% 35%, #fff 0%, #67e8f9 18%, #06b6d4 45%, #164e63 80%);
  box-shadow: 0 0 60px 10px rgb(34 211 238 / 0.45), 0 0 140px 30px rgb(34 211 238 / 0.2);
  animation: pulse 2.6s ease-in-out infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse { 50% { transform: scale(1.06); box-shadow: 0 0 80px 16px rgb(34 211 238 / 0.55), 0 0 160px 40px rgb(34 211 238 / 0.25); } }

.bubble {
  position: absolute; padding: 0.55rem 0.9rem; border-radius: 0.75rem; font-size: 0.85rem;
  border: 1px solid rgb(34 211 238 / 0.35); background: color-mix(in oklab, var(--card) 88%, transparent);
  backdrop-filter: blur(8px); box-shadow: 0 10px 30px -10px rgb(0 0 0 / 0.4);
  animation: float 6s ease-in-out infinite; white-space: nowrap; z-index: 2;
}
.b1 { top: 4%; left: -2%; }
.b2 { top: 58%; right: -8%; animation-delay: -2s; color: rgb(16 185 129); font-weight: 500; }
.b3 { bottom: 6%; left: 6%; animation-delay: -4s; font-size: 1rem; line-height: 2; }
@keyframes float { 50% { transform: translateY(-10px); } }

@media (max-width: 640px) {
  .bubble { font-size: 0.75rem; }
  .b1 { left: 0; } .b2 { right: 0; }
}
@media (prefers-reduced-motion: reduce) {
  .orb .ring, .orb .core, .bubble { animation: none; }
}
</style>
