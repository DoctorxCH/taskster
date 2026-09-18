<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans antialiased selection:bg-emerald-500 selection:text-slate-950">
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
.taskster_button {
  @apply bg-blue-600 hover:bg-blue-500 text-white font-semibold transition inline-flex items-center justify-center space-x-2 shadow-lg shadow-blue-900/30 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed;
}
.taskster_button_accent {
  @apply bg-rose-600 hover:bg-rose-500 text-white font-semibold transition inline-flex items-center justify-center space-x-2 shadow-lg shadow-rose-900/30 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed;
}
.taskster_button_light {
  @apply bg-white hover:bg-slate-100 text-slate-900 border-[3px] border-blue-600 font-semibold transition inline-flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>

