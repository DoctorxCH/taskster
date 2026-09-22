import { computed, ref } from 'vue'
import { useAuth } from './useAuth'

export const usePlanLimits = () => {
  const { user } = useAuth()

  const showUpgradeModal = ref(false)
  const upgradeReason = ref('')

  const effectivePlan = computed<'basic' | 'pro' | 'enterprise'>(() => {
    if (!user.value) return 'basic'
    if (user.value.is_superadmin) return 'enterprise'
    if (user.value.plan) return user.value.plan
    if (user.value.company_id) {
      if (user.value.company_role === 'admin' || user.value.license_type === 'enterprise') {
        return 'enterprise'
      }
      return 'pro'
    }
    if (user.value.is_pro) return 'pro'
    return 'basic'
  })

  const isTrialActive = computed<boolean>(() => {
    if (!user.value) return false
    return Boolean(user.value.is_trial && (user.value.trial_days_left ?? 0) > 0)
  })

  const trialDaysRemaining = computed<number>(() => {
    if (!user.value) return 0
    return user.value.trial_days_left || 0
  })

  const maxFolders = computed<number>(() => {
    if (effectivePlan.value === 'basic') return 1
    return Infinity
  })

  const maxProjects = computed<number>(() => {
    if (effectivePlan.value === 'basic') return 3
    if (effectivePlan.value === 'pro') return 30
    return Infinity
  })

  const maxTasksPerProject = computed<number>(() => {
    if (effectivePlan.value === 'basic') return 30
    return Infinity
  })

  const canUseCustomFields = computed<boolean>(() => {
    return effectivePlan.value !== 'basic'
  })

  const canTrackTime = computed<boolean>(() => {
    return effectivePlan.value !== 'basic'
  })

  const canExportProject = computed<boolean>(() => {
    return effectivePlan.value === 'enterprise'
  })

  const canUseSectionAutomation = computed<boolean>(() => {
    return effectivePlan.value !== 'basic'
  })

  const checkFolderLimit = (currentCount: number): boolean => {
    if (currentCount >= maxFolders.value) {
      promptUpgrade('folders', 'Im Free-Tarif ist maximal 1 Projektordner erlaubt. Bitte auf Pro upgraden.')
      return false
    }
    return true
  }

  const checkProjectLimit = (currentCount: number): boolean => {
    if (currentCount >= maxProjects.value) {
      const msg = effectivePlan.value === 'basic'
        ? 'Im Free-Tarif sind maximal 3 aktive Projekte erlaubt. Bitte auf Pro upgraden.'
        : 'Im Pro-Tarif sind maximal 30 Projekte erlaubt. Bitte auf Enterprise upgraden.'
      promptUpgrade('projects', msg)
      return false
    }
    return true
  }

  const checkTaskLimit = (currentCount: number): boolean => {
    if (currentCount >= maxTasksPerProject.value) {
      promptUpgrade('tasks', 'Im Free-Tarif sind maximal 30 Aufgaben pro Projekt erlaubt. Bitte auf Pro upgraden.')
      return false
    }
    return true
  }

  const promptUpgrade = (featureKey: string, customMessage?: string) => {
    upgradeReason.value = customMessage || `Diese Funktion erfordert ein Tarif-Upgrade.`
    showUpgradeModal.value = true
  }

  return {
    effectivePlan,
    isTrialActive,
    trialDaysRemaining,
    maxFolders,
    maxProjects,
    maxTasksPerProject,
    canUseCustomFields,
    canTrackTime,
    canExportProject,
    canUseSectionAutomation,
    showUpgradeModal,
    upgradeReason,
    checkFolderLimit,
    checkProjectLimit,
    checkTaskLimit,
    promptUpgrade
  }
}
