import { useSeoMeta } from '@unhead/vue'

// Default SEO meta tags
const defaultSeoMeta = {
  title: 'Home',
  titleTemplate: '%s | Novesta — Voice AI Assistant',
  description:
        'Novesta is a voice AI assistant for Mac and Windows. Talk to search the web, write code, design UIs for Figma, play music and YouTube, and control your computer — in 18 languages including English, Urdu and Hindi.',
  keywords:
        'Novesta, voice assistant, AI assistant, music assistant, YouTube player, Urdu voice assistant, JARVIS, Mac assistant, Windows assistant, Figma AI',
  robots: 'index, follow',
  themeColor: '#000000',

  // Open Graph
  ogTitle: '%s | Novesta — Voice AI Assistant',
  ogDescription:
        'Novesta is a voice AI assistant for Mac and Windows. Talk to search the web, write code, design UIs for Figma, play music and YouTube, and control your computer — in 18 languages including English, Urdu and Hindi.',
  ogUrl: typeof window !== 'undefined' ? window.location.origin : undefined,
  ogType: 'website',
  // TODO: add a 1200×630 Novesta share image at public/images/og.png and set ogImage/twitterImage to it
  ogSiteName: 'Novesta',
  ogLocale: 'en_US',

  // Twitter
  twitterTitle: '%s | Novesta — Voice AI Assistant',
  twitterDescription:
        'Novesta is a voice AI assistant for Mac and Windows. Talk to search the web, write code, design UIs for Figma, play music and YouTube, and control your computer — in 18 languages including English, Urdu and Hindi.',
  twitterCard: 'summary',
}

/**
 * Composable for managing SEO meta tags
 * @param {object|null} seoMeta - Custom SEO meta tags to apply
 * @param {object} options - Configuration options
 * @param {boolean} options.merge - When true, merges custom meta tags with defaults.
 *                                 When false, only uses custom meta tags.
 *                                 Useful for pages that need completely custom SEO
 *                                 without inheriting defaults.
 * @returns {void}
 */
export function useSeoMetaTags(seoMeta, options = { merge: true }) {
  if (!seoMeta)
    return useSeoMeta(defaultSeoMeta)

  return useSeoMeta(
    options.merge ? { ...defaultSeoMeta, ...seoMeta } : seoMeta,
  )
}
