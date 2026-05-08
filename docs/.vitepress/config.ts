import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "VitePress",
  description: "A VitePress implemented site",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Examples', link: '/markdown-examples' }
    ],

    sidebar: [
      {
        text: 'Examples',
        items: [
            { text: 'Markdown Examples',
              link: '/markdown-examples' },
            { text: 'Runtime API Examples',
              link: '/api-examples' },
            { text: 'Invoice Amount Magnifier',
              link: '/INVOICE_AMOUNT_MAGNIFIER' },
            { text: 'Family Commalist Picker',
              link: '/FAMILY_COMMALIST_PICKER' },
            { text: 'Prometheus Integration',
              link: '/PROMETHEUS_INTEGRATION' },
            { text: 'Prometheus Menu Integration',
              link: '/PROMETHEUS_MENU_INTEGRATION' },
            { text: 'SonarCloud Setup',
              link: '/SONARCLOUD_SETUP' },
            { text: 'Netbeans IDE 25-GUIDE',
              link: '/NETBEANS_IDE25_GUIDE' },
            { text: 'Netbeans Sync Guide',
              link: '/NETBEANS_SYNC_GUIDE' },
            { text: 'Php Product Selection Workflow',
              link: '/PHP_PRODUCT_SELECTION_WORKFLOW' },
            { text: 'Security Commands',
              link: '/SECURITY_COMMANDS' },
            { text: 'Typescript Build Process',
              link: '/TYPESCRIPT_BUILD_PROCESS' },
            { text: 'Typescript ES2023 Modernization',
              link: '/TYPESCRIPT_ES2023_MODERNIZATION' },
            { text: 'Typescript ES2024 Modernization',
              link: '/TYPESCRIPT_ES2024_MODERNIZATION' },
            { text: 'Typescript Go V7 Compatability Testing Guide',
              link: '/TYPESCRIPT_GO_V7_COMPATIBILITY_TESTING_GUIDE' },
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/vuejs/vitepress' },
      { icon: 'yii', link: 'https://github.com/yiisoft/docs/pull/276/checks' }
    ]
  }
})
