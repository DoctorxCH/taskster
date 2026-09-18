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
