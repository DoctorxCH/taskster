export interface BootstrapPayload {
  design_tokens: Record<string, string>
  features: Record<string, any>
  locales_delta: Record<string, string>
}

export const useAdmin = () => {
  const isBootstrapped = useState<boolean>('admin_bootstrapped', () => false)
  const tokens = useState<Record<string, string>>('admin_tokens', () => ({}))
  const features = useState<Record<string, any>>('admin_features', () => ({}))
  const locales = useState<Record<string, string>>('admin_locales', () => ({}))
  
  const { authHeaders } = useAuth()
  
  // Dynamic CSS Variables Injector
  const applyTokensToCSS = (tokenMap: Record<string, string>) => {
    if (import.meta.client) {
      let styleTag = document.getElementById('taskster-design-tokens')
      if (!styleTag) {
        styleTag = document.createElement('style')
        styleTag.id = 'taskster-design-tokens'
        document.head.appendChild(styleTag)
      }
      
      let css = ':root {\n'
      for (const [key, value] of Object.entries(tokenMap)) {
        const varName = '--' + key.replace(/[^a-zA-Z0-9-]/g, '-')
        css += `  ${varName}: ${value};\n`
      }
      css += '}\n'
      
      styleTag.innerHTML = css
    }
  }

  const bootstrapSystem = async (force = false) => {
    if (isBootstrapped.value && !force) return
    
    try {
      const payload = await $fetch<BootstrapPayload>('/api/system/bootstrap', {
        headers: authHeaders()
      })
      
      tokens.value = payload.design_tokens || {}
      features.value = payload.features || {}
      locales.value = payload.locales_delta || {}
      
      isBootstrapped.value = true
      
      // Apply design tokens automatically
      applyTokensToCSS(tokens.value)
      
    } catch (e) {
      console.error('Failed to bootstrap system', e)
    }
  }

  return {
    isBootstrapped,
    tokens,
    features,
    locales,
    bootstrapSystem,
    applyTokensToCSS
  }
}
