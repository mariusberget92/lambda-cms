import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Lambda CMS',
  description: 'Documentation for Lambda CMS — the open-source block-editor CMS built on Laravel, Vue 3, and Inertia.js.',
  base: '/',

  srcExclude: ['plans/**', 'superpowers/**'],

  head: [
    ['link', { rel: 'icon', href: '/favicon.svg', type: 'image/svg+xml' }],
  ],

  themeConfig: {
    logo: { light: '/logo-light.svg', dark: '/logo-dark.svg', alt: 'Lambda CMS' },

    nav: [
      { text: 'Guide',  link: '/guide/introduction',    activeMatch: '/guide/' },
      { text: 'Blocks', link: '/blocks/',                activeMatch: '/blocks/' },
      { text: 'API',    link: '/api/overview',           activeMatch: '/api/' },
      { text: 'GitHub', link: 'https://github.com/mariusberget92/lambda-cms' },
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Introduction',    link: '/guide/introduction' },
            { text: 'Installation',    link: '/guide/installation' },
            { text: 'Configuration',   link: '/guide/configuration' },
            { text: 'Deployment',      link: '/guide/deployment' },
          ],
        },
        {
          text: 'Core Concepts',
          items: [
            { text: 'Posts & Pages',   link: '/guide/posts-pages' },
            { text: 'Block Editor',    link: '/guide/block-editor' },
            { text: 'Template System', link: '/guide/templates' },
            { text: 'Media Library',   link: '/guide/media' },
            { text: 'Image Editor',    link: '/guide/image-editor' },
            { text: 'Comments',        link: '/guide/comments' },
            { text: 'Users & Roles',   link: '/guide/users-roles' },
            { text: 'Two-Factor Auth', link: '/guide/two-factor-authentication' },
            { text: 'Settings',        link: '/guide/settings' },
            { text: 'Webhooks',        link: '/guide/webhooks' },
          ],
        },
      ],
      '/blocks/': [
        {
          text: 'Block Reference',
          items: [
            { text: 'Overview',        link: '/blocks/' },
            { text: 'Content Blocks',  link: '/blocks/content' },
            { text: 'Layout Blocks',   link: '/blocks/layout' },
            { text: 'Data Blocks',     link: '/blocks/data' },
            { text: 'Site Blocks',     link: '/blocks/site' },
            { text: 'Interactive',     link: '/blocks/interactive' },
          ],
        },
      ],
      '/api/': [
        {
          text: 'REST API',
          items: [
            { text: 'Overview',        link: '/api/overview' },
            { text: 'Posts',           link: '/api/posts' },
            { text: 'Categories',      link: '/api/categories' },
            { text: 'Tags',            link: '/api/tags' },
            { text: 'Query Endpoint',  link: '/api/query' },
          ],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/mariusberget92/lambda-cms' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2024–present Lambda CMS contributors',
    },

    search: {
      provider: 'local',
    },
  },
})
