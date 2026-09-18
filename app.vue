<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-sans antialiased selection:bg-cyan-500 selection:text-white">
    <Navbar />
    <main class="flex-1">
      <NuxtPage />
    </main>
  </div>
</template>

<script setup lang="ts">
const { initAuth } = useAuth()

onMounted(async () => {
  await initAuth()
})
</script>

<style>
/* Taskster Button Standards (per User Global Rule) */
.taskster_button {
  @apply bg-[#00A3C4] hover:bg-[#008ba8] text-white font-semibold transition inline-flex items-center justify-center space-x-2 shadow-sm shadow-cyan-900/10 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99];
}
.taskster_button_accent {
  @apply bg-rose-600 hover:bg-rose-500 text-white font-semibold transition inline-flex items-center justify-center space-x-2 shadow-sm shadow-rose-900/10 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99];
}
.taskster_button_light {
  @apply bg-white hover:bg-slate-50 text-slate-800 border-[3px] border-[#00A3C4] font-semibold transition inline-flex items-center justify-center space-x-2 shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99];
}
</style>

