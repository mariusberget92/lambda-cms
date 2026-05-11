import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Lambda CMS',
  description: 'Documentation for Lambda CMS — a modern, self-hosted content management system.',
  base: '/',

  themeConfig: {
    logo: '/logo.svg',
    siteTitle: 'Lambda CMS',

    nav: [
      { text: 'Getting Started', link: '/getting-started/introduction' },
      { text: 'Admin Guide', link: '/admin-guide/posts' },
      { text: 'Block Editor', link: '/block-editor/overview' },
      { text: 'Templates', link: '/templates/overview' },
      { text: 'API', link: '/api/overview' },
    ],

    sidebar: {
      '/getting-started/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Introduction', link: '/getting-started/introduction' },
            { text: 'Requirements', link: '/getting-started/requirements' },
            { text: 'Installation', link: '/getting-started/installation' },
            { text: 'Configuration', link: '/getting-started/configuration' },
          ],
        },
      ],
      '/admin-guide/': [
        {
          text: 'Admin Guide',
          items: [
            { text: 'Posts', link: '/admin-guide/posts' },
            { text: 'Pages', link: '/admin-guide/pages' },
            { text: 'Media', link: '/admin-guide/media' },
            { text: 'Categories & Tags', link: '/admin-guide/categories-tags' },
            { text: 'Comments', link: '/admin-guide/comments' },
            { text: 'Users', link: '/admin-guide/users' },
            { text: 'Navigation', link: '/admin-guide/navigation' },
            { text: 'Webhooks', link: '/admin-guide/webhooks' },
            { text: 'Settings', link: '/admin-guide/settings' },
          ],
        },
      ],
      '/block-editor/': [
        {
          text: 'Block Editor',
          items: [
            { text: 'Overview', link: '/block-editor/overview' },
            { text: 'Content Blocks', link: '/block-editor/content-blocks' },
            { text: 'Layout Blocks', link: '/block-editor/layout-blocks' },
            { text: 'Dynamic Blocks', link: '/block-editor/dynamic-blocks' },
            { text: 'Post Blocks', link: '/block-editor/post-blocks' },
            { text: 'Interactive Blocks', link: '/block-editor/interactive-blocks' },
            { text: 'Styling', link: '/block-editor/styling' },
            { text: 'Dynamic Bindings', link: '/block-editor/dynamic-bindings' },
          ],
        },
      ],
      '/templates/': [
        {
          text: 'Templates',
          items: [
            { text: 'Overview', link: '/templates/overview' },
            { text: 'Creating Templates', link: '/templates/creating-templates' },
            { text: 'Template Resolver', link: '/templates/template-resolver' },
          ],
        },
      ],
      '/api/': [
        {
          text: 'Public API',
          items: [
            { text: 'Overview', link: '/api/overview' },
            { text: 'Posts', link: '/api/posts' },
            { text: 'Categories & Tags', link: '/api/categories-tags' },
            { text: 'Query Builder', link: '/api/query-builder' },
          ],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/mariusberget92/lambda-cms' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2024–present Lambda CMS',
    },

    search: {
      provider: 'local',
    },
  },
})
