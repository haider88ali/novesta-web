import { useSeoMeta } from '@unhead/vue'

// Default SEO meta tags
const defaultSeoMeta = {
  title: 'Home',
  titleTemplate: '%s | Nova — Voice AI Assistant',
  description:
        'Nova is a voice AI assistant for Mac and Windows. Talk to search the web, write code, design UIs for Figma and control your computer — in English, Urdu and Hindi.',
  keywords:
        'Nova, voice assistant, AI assistant, Urdu voice assistant, JARVIS, Mac assistant, Windows assistant, Figma AI',
  robots: 'index, follow',
  themeColor: '#000000',

  // Open Graph
  ogTitle: '%s | Nova — Voice AI Assistant',
  ogDescription:
        'Nova is a voice AI assistant for Mac and Windows. Talk to search the web, write code, design UIs for Figma and control your computer — in English, Urdu and Hindi.',
  ogUrl: typeof window !== 'undefined' ? window.location.origin : undefined,
  ogType: 'website',
  // TODO: add a 1200×630 Nova share image at public/images/og.png and set ogImage/twitterImage to it
  ogSiteName: 'Nova',
  ogLocale: 'en_US',

  // Twitter
  twitterTitle: '%s | Nova — Voice AI Assistant',
  twitterDescription:
        'Nova is a voice AI assistant for Mac and Windows. Talk to search the web, write code, design UIs for Figma and control your computer — in English, Urdu and Hindi.',
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
