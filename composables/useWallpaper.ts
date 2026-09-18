export interface WallpaperOption {
  id: string
  name: string
  file: string
  category: 'nature' | 'abstract' | 'space'
}

export const WALLPAPERS: WallpaperOption[] = [
  { id: 'mountain-lake', name: 'Bergsee & Nebel', file: '/wallpapers/mountain-lake.jpg', category: 'nature' },
  { id: 'nordic-hills', name: 'Nordische Dünen', file: '/wallpapers/nordic-hills.jpg', category: 'nature' },
  { id: 'bamboo-forest', name: 'Bambuswald', file: '/wallpapers/bamboo-forest.jpg', category: 'nature' },
  { id: 'ocean-cliffs', name: 'Ozean & Klippen', file: '/wallpapers/ocean-cliffs.jpg', category: 'nature' },
  { id: 'blue-whale', name: 'Tiefsee Wal', file: '/wallpapers/blue-whale.jpg', category: 'nature' },
  { id: 'arctic-wolf', name: 'Polarwolf', file: '/wallpapers/arctic-wolf.jpg', category: 'nature' },
  { id: 'chameleon', name: 'Chamäleon Tropen', file: '/wallpapers/chameleon.jpg', category: 'nature' },
  { id: 'storm-thunder', name: 'Gewitterfront', file: '/wallpapers/storm-thunder.jpg', category: 'nature' },
  { id: 'orbit-earth', name: 'Erde im Orbit', file: '/wallpapers/orbit-earth.jpg', category: 'space' },
  { id: 'deep-galaxy', name: 'Sternengalaxie', file: '/wallpapers/deep-galaxy.jpg', category: 'space' },
  { id: 'space-blackhole', name: 'Kosmisches Phänomen', file: '/wallpapers/space-blackhole.jpg', category: 'space' },
  { id: 'volcano-lava', name: 'Vulkan & Lava', file: '/wallpapers/volcano-lava.jpg', category: 'nature' },
  { id: 'crystal-tree', name: 'Kristallbaum', file: '/wallpapers/crystal-tree.jpg', category: 'abstract' },
  { id: 'fluid-glass', name: 'Flüssiges Glas', file: '/wallpapers/fluid-glass.jpg', category: 'abstract' },
  { id: 'neon-mushrooms', name: 'Neon Märchenwald', file: '/wallpapers/neon-mushrooms.jpg', category: 'abstract' }
]

export const useWallpaper = () => {
  const currentWallpaper = useState<string>('taskster_wallpaper', () => '/wallpapers/mountain-lake.jpg')

  const initWallpaper = () => {
    if (import.meta.client) {
      const saved = localStorage.getItem('taskster_wallpaper')
      if (saved) {
        currentWallpaper.value = saved
      }
    }
  }

  const setWallpaper = (path: string) => {
    currentWallpaper.value = path
    if (import.meta.client) {
      localStorage.setItem('taskster_wallpaper', path)
    }
  }

  return {
    wallpapers: WALLPAPERS,
    currentWallpaper,
    initWallpaper,
    setWallpaper
  }
}
