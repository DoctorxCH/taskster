/// <reference path="../.nuxt/nuxt.d.ts" />
/// <reference path="../.nuxt/imports.d.ts" />
/// <reference path="../.nuxt/types/imports.d.ts" />
/// <reference path="../.nuxt/types/plugins.d.ts" />
/// <reference path="../.nuxt/types/i18n-plugin.d.ts" />

import type { Composer } from 'vue-i18n'

declare module 'vue' {
  interface ComponentCustomProperties {
    $t: Composer['t']
    $rt: Composer['rt']
    $n: Composer['n']
    $d: Composer['d']
    $tm: Composer['tm']
    $te: Composer['te']
    $i18n: Composer
  }
}

export {}
