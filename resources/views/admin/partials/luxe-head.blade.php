{{-- WooCommerce/WordPress-style admin theme: clean sans-serif (Inter), tight spacing, dark sidebar. --}}
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
<script id="admin-luxe-tailwind-config">
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: {
          // WordPress / WooCommerce inspired palette
          'wp-bg':         '#f0f0f1',
          'wp-card':       '#ffffff',
          'wp-border':     '#dcdcde',
          'wp-text':       '#1d2327',
          'wp-text-muted': '#646970',
          'wp-link':       '#2271b1',
          'wp-link-hover': '#135e96',
          'wp-accent':     '#2271b1',
          'wp-success':    '#00a32a',
          'wp-warning':    '#dba617',
          'wp-danger':     '#d63638',
          'wp-sidebar':    '#1d2327',
          'wp-sidebar-hover': '#2c3338',
          'wp-sidebar-active': '#2271b1',

          // Legacy luxe-storefront tokens (kept so existing views don't crash). They map to neutral
          // values inside the admin so the typography stops shouting.
          'secondary-fixed-dim': '#dcdcde',
          'inverse-on-surface': '#f6f7f7',
          'on-surface': '#1d2327',
          'secondary-fixed': '#f0f0f1',
          secondary: '#2271b1',
          'surface-container-highest': '#dcdcde',
          'inverse-primary': '#dcdcde',
          'on-primary': '#ffffff',
          'surface-container-high': '#f0f0f1',
          'on-secondary-fixed': '#1d2327',
          'tertiary-fixed-dim': '#dcdcde',
          'on-error': '#ffffff',
          'on-secondary': '#ffffff',
          'on-secondary-fixed-variant': '#646970',
          'tertiary-container': '#1d2327',
          'on-background': '#1d2327',
          'error-container': '#fcf0f1',
          'outline-variant': '#dcdcde',
          'surface-container-low': '#f6f7f7',
          'on-primary-fixed-variant': '#50575e',
          error: '#d63638',
          'on-tertiary': '#ffffff',
          'on-tertiary-fixed-variant': '#50575e',
          'inverse-surface': '#1d2327',
          'on-tertiary-fixed': '#1d2327',
          'on-surface-variant': '#646970',
          'on-tertiary-container': '#646970',
          'surface-dim': '#f0f0f1',
          'on-error-container': '#b32d2e',
          outline: '#8c8f94',
          'surface-variant': '#f0f0f1',
          'primary-fixed': '#f0f0f1',
          'primary-container': '#2271b1',
          background: '#f0f0f1',
          primary: '#2271b1',
          surface: '#ffffff',
          'tertiary-fixed': '#f0f0f1',
          'secondary-container': '#dbeafe',
          'surface-container': '#f6f7f7',
          'surface-bright': '#ffffff',
          tertiary: '#2271b1',
          'on-secondary-container': '#2271b1',
          'primary-fixed-dim': '#dcdcde',
          'on-primary-container': '#ffffff',
          'surface-tint': '#dcdcde',
          'on-primary-fixed': '#1d2327',
          'surface-container-lowest': '#ffffff',
        },
        borderRadius: { DEFAULT: '0.1875rem', lg: '0.25rem', xl: '0.375rem', full: '9999px' },
        spacing: {
          gutter: '1.25rem',
          'section-py-mobile': '2rem',
          'margin-mobile': '1rem',
          unit: '4px',
          'max-container': '1400px',
          'section-py-desktop': '2.5rem',
        },
        fontFamily: {
          'body-md': ['Inter', 'system-ui', 'sans-serif'],
          'button-text': ['Inter', 'system-ui', 'sans-serif'],
          'body-lg': ['Inter', 'system-ui', 'sans-serif'],
          'headline-lg-mobile': ['Inter', 'system-ui', 'sans-serif'],
          'display-lg-mobile': ['Inter', 'system-ui', 'sans-serif'],
          'display-lg': ['Inter', 'system-ui', 'sans-serif'],
          'label-caps': ['Inter', 'system-ui', 'sans-serif'],
          'headline-md': ['Inter', 'system-ui', 'sans-serif'],
          'headline-lg': ['Inter', 'system-ui', 'sans-serif'],
        },
        fontSize: {
          'body-md': ['14px', { lineHeight: '20px', letterSpacing: '0', fontWeight: '400' }],
          'button-text': ['13px', { lineHeight: '18px', letterSpacing: '0', fontWeight: '500' }],
          'body-lg': ['15px', { lineHeight: '22px', letterSpacing: '0', fontWeight: '400' }],
          'headline-lg-mobile': ['20px', { lineHeight: '28px', letterSpacing: '0', fontWeight: '600' }],
          'display-lg-mobile': ['22px', { lineHeight: '28px', letterSpacing: '0', fontWeight: '600' }],
          'display-lg': ['24px', { lineHeight: '32px', letterSpacing: '0', fontWeight: '600' }],
          'label-caps': ['11px', { lineHeight: '14px', letterSpacing: '0.04em', fontWeight: '500' }],
          'headline-md': ['16px', { lineHeight: '22px', letterSpacing: '0', fontWeight: '600' }],
          'headline-lg': ['20px', { lineHeight: '28px', letterSpacing: '0', fontWeight: '600' }],
        },
      },
    },
  };
</script>
<style>
  /* Hard reset of any leftover Material Symbols icon font spans so they never render as raw words. */
  .material-symbols-outlined { display: none !important; }
  .admin-luxe-root .material-symbols-outlined { display: none !important; }

  html:has(.admin-luxe-root),
  .admin-luxe-root {
    height: 100%;
  }

  .admin-luxe-root {
    background-color: #f0f0f1;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    color: #1d2327;
    -webkit-font-smoothing: antialiased;
    overflow: hidden;
  }

  /* Shell layout is defined here so the dashboard still scrolls if the Tailwind CDN
     is blocked or its utility classes do not apply. */
  .admin-shell {
    display: flex;
    height: 100vh;
    height: 100dvh;
    max-height: 100dvh;
    width: 100%;
    overflow: hidden;
  }
  .admin-sidebar-desktop {
    height: 100%;
    min-height: 0;
  }
  @media (min-width: 1024px) {
    .admin-sidebar-desktop { display: flex !important; }
  }
  @media (max-width: 1023.98px) {
    .admin-sidebar-desktop { display: none !important; }
  }
  .admin-main {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-width: 0;
    min-height: 0;
    height: 100%;
    overflow: hidden;
  }
  .admin-main-body {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
  }
  .admin-main-header { flex: 0 0 auto; }
  .admin-main-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
  }

  /* Neutralize the fashion-magazine typography that the existing admin views still use. */
  .admin-luxe-root .font-headline-lg,
  .admin-luxe-root .font-headline-md,
  .admin-luxe-root .font-display-lg,
  .admin-luxe-root .font-display-lg-mobile,
  .admin-luxe-root .font-headline-lg-mobile,
  .admin-luxe-root .admin-page-title {
    font-family: 'Inter', system-ui, sans-serif !important;
    font-weight: 600;
    letter-spacing: 0;
    text-transform: none;
  }
  .admin-luxe-root .font-label-caps,
  .admin-luxe-root .font-button-text {
    font-family: 'Inter', system-ui, sans-serif !important;
    letter-spacing: 0.02em;
  }
  .admin-luxe-root .tracking-widest,
  .admin-luxe-root .tracking-tight,
  .admin-luxe-root .tracking-tighter,
  .admin-luxe-root .tracking-\[0\.25em\],
  .admin-luxe-root .tracking-\[0\.3em\],
  .admin-luxe-root .tracking-\[0\.08em\] {
    letter-spacing: 0.01em !important;
  }
  .admin-luxe-root .uppercase {
    text-transform: none !important;
  }
  /* The very top page title is the only spot we keep slight emphasis. */
  .admin-luxe-root h2.font-headline-lg,
  .admin-luxe-root h2.font-headline-md {
    font-size: 20px;
    font-weight: 600;
    color: #1d2327;
  }
  .admin-luxe-root .text-display-lg,
  .admin-luxe-root .text-\[40px\] {
    font-size: 28px !important;
    line-height: 1.2 !important;
  }

  .luxe-grid-pattern { display: none; }
  .luxe-pattern-bg { background: #f0f0f1; }

  .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
  .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
  .custom-scrollbar::-webkit-scrollbar-thumb { background: #c3c4c7; border-radius: 3px; }
  .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #8c8f94; }

  /* Keep white surfaces flat. Do not force radius or border on every bg-white
     node — that collapses nested page-editor cards into one cramped box. */
  .admin-luxe-root .bg-surface-container-lowest,
  .admin-luxe-root .bg-white {
    background: #ffffff !important;
    box-shadow: none !important;
  }
  .admin-luxe-root .border-outline-variant { border-color: #dcdcde !important; }
  .admin-luxe-root .bg-surface-container-low { background: #f6f7f7 !important; }
  .admin-luxe-root .bg-surface-container { background: #f6f7f7 !important; }
  .admin-luxe-root .bg-primary { background: #2271b1 !important; color: #fff !important; }
  .admin-luxe-root .text-primary { color: #2271b1 !important; }
  .admin-luxe-root .text-on-primary { color: #fff !important; }
  .admin-luxe-root .border-primary { border-color: #2271b1 !important; }
  .admin-luxe-root .text-on-surface-variant { color: #646970 !important; }
  .admin-luxe-root .text-secondary { color: #2271b1 !important; }
  .admin-luxe-root .bg-secondary-fixed\/20 { background: #f0f6fc !important; }
  .admin-luxe-root .border-secondary\/30 { border-color: #c5d9ed !important; }
  .admin-luxe-root .hover\:scale-\[1\.02\]:hover { transform: none !important; }
  .admin-luxe-root .hover\:scale-105:hover { transform: none !important; }
  .admin-luxe-root .hover\:scale-110:hover { transform: none !important; }
  .admin-luxe-root .transition-transform { transition: none; }

  /* Tables: WooCommerce list style. */
  .admin-luxe-root table { border-collapse: collapse; }
  .admin-luxe-root table thead th {
    background: #f6f7f7 !important;
    color: #1d2327 !important;
    font-weight: 600;
    font-size: 12px;
    text-transform: none !important;
    letter-spacing: 0 !important;
    padding: 10px 12px !important;
    border-bottom: 1px solid #dcdcde !important;
  }
  .admin-luxe-root table tbody td {
    padding: 12px !important;
    font-size: 13px;
    color: #1d2327;
    border-bottom: 1px solid #f0f0f1 !important;
  }
  .admin-luxe-root .admin-luxe-table tbody tr:hover td,
  .admin-luxe-root table tbody tr:hover { background: #f6f7f7 !important; }

  /* Primary action button (Add product, Save, etc.) */
  .admin-luxe-btn-primary,
  .admin-luxe-root .admin-btn-primary,
  .admin-luxe-root .admin-luxe-btn-primary {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
    background: #2271b1; color: #fff !important;
    padding: 0.5rem 0.875rem;
    font-size: 13px; font-weight: 500;
    letter-spacing: 0; text-transform: none;
    border: 1px solid #2271b1;
    border-radius: 3px;
    transition: background 0.15s ease;
  }
  .admin-luxe-btn-primary:hover,
  .admin-luxe-root .admin-btn-primary:hover,
  .admin-luxe-root .admin-luxe-btn-primary:hover {
    background: #135e96; border-color: #135e96; opacity: 1; transform: none !important;
  }

  .admin-luxe-btn-secondary,
  .admin-luxe-root .admin-luxe-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: #fff;
    color: #1d2327 !important;
    padding: 0.5rem 0.875rem;
    font-size: 13px;
    font-weight: 500;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
  }
  .admin-luxe-btn-secondary:hover,
  .admin-luxe-root .admin-luxe-btn-secondary:hover {
    background: #f6f7f7;
    border-color: #8c8f94;
  }

  /* Secondary outline buttons */
  .admin-luxe-root button[type="button"]:not(.admin-luxe-btn-primary):not(.admin-btn-primary):not(.admin-luxe-btn-secondary):not([class*="bg-primary"]),
  .admin-luxe-root .border.border-outline-variant {
    border-color: #c3c4c7;
  }

  /* Status badge baseline */
  .admin-luxe-root .active-status-badge {
    background-color: #00a32a;
    color: #ffffff;
    border: none;
  }

  /* Inputs */
  .admin-luxe-root input[type="text"],
  .admin-luxe-root input[type="search"],
  .admin-luxe-root input[type="email"],
  .admin-luxe-root input[type="number"],
  .admin-luxe-root input[type="password"],
  .admin-luxe-root select,
  .admin-luxe-root textarea {
    background: #fff !important;
    border: 1px solid #8c8f94 !important;
    border-radius: 3px !important;
    padding: 6px 10px !important;
    font-size: 14px !important;
    color: #1d2327 !important;
  }
  .admin-luxe-root input:focus,
  .admin-luxe-root select:focus,
  .admin-luxe-root textarea:focus {
    outline: 2px solid transparent !important;
    border-color: #2271b1 !important;
    box-shadow: 0 0 0 1px #2271b1 !important;
  }

  /* Pagination, keep functional but cleaner. */
  .admin-luxe-pagination nav { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 0.25rem; }
  .admin-luxe-pagination nav a,
  .admin-luxe-pagination nav span {
    display: inline-flex; min-width: 2.125rem; height: 2.125rem; align-items: center; justify-content: center;
    border: 1px solid #c3c4c7; padding: 0 0.5rem; font-size: 13px; font-weight: 500;
    letter-spacing: 0; text-transform: none; text-decoration: none; color: #2271b1;
    background: #fff;
    border-radius: 3px;
  }
  .admin-luxe-pagination nav a:hover { background: #f0f6fc; }
  .admin-luxe-pagination nav span[aria-current="page"],
  .admin-luxe-pagination nav span[aria-current="page"] span {
    background: #2271b1; color: #fff; border-color: #2271b1;
  }

  /* Sidebar nav: dark, dense, WordPress-admin feel. */
  .admin-sidebar { background: #1d2327; color: #c3c4c7; }
  .admin-sidebar a, .admin-sidebar button { color: #c3c4c7; }
  .admin-sidebar a:hover, .admin-sidebar button:hover { background: #2c3338; color: #ffffff; }
  .admin-sidebar a.is-active,
  .admin-sidebar a[aria-current="page"] { background: #2271b1 !important; color: #ffffff !important; }
  .admin-sidebar .sidebar-brand { color: #ffffff; }
  .admin-sidebar .sidebar-meta { color: #8c8f94; }

  /* Reset the old fashion overrides on Volt/Tailwind UI components, in case something still ships them. */
  .admin-luxe-root .rounded-xl.border-zinc-200,
  .admin-luxe-root .border-zinc-200.bg-white {
    border-color: #dcdcde !important; border-radius: 4px !important; background: #ffffff !important;
    box-shadow: none !important;
  }
  .admin-luxe-root .text-zinc-900 { color: #1d2327 !important; }
  .admin-luxe-root .text-zinc-600,
  .admin-luxe-root .text-zinc-500 { color: #646970 !important; }
  .admin-luxe-root .divide-zinc-200 > :not([hidden]) ~ :not([hidden]) { border-color: #dcdcde; }
  .admin-luxe-root .border-zinc-100 { border-color: #f0f0f1 !important; }
</style>
