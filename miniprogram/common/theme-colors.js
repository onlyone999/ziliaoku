/**
 * 主题色配置 - 提供渐变/文字色值供 :style 绑定使用
 * CSS变量在小程序scoped样式中不可靠，改用数据绑定
 */

const THEME_COLORS = {
  green: {
    headerGradient: 'linear-gradient(180deg, #1abc9c 0%, #27ae60 30%, #2ed573 60%, #f0fdf4 85%, #ffffff 100%)',
    headerGradientLong: 'linear-gradient(180deg, #1abc9c 0%, #27ae60 18%, #2ed573 35%, #a7f3d0 50%, #d1fae5 65%, #f0fdf4 80%, #f0f0f2 92%, #f5f5f5 100%)',
    primary: '#2ed573',
    primaryDark: '#27ae60',
    accent: '#1abc9c',
    btnGradient: 'linear-gradient(135deg, #3be88a 0%, #2ed573 25%, #27ae60 55%, #1abc9c 100%)',
    freeTextGradient: 'linear-gradient(135deg, #2ed573, #1abc9c)',
    rgbPrimary: '46,213,115',
    tabBarSelected: '#2ed573',
    tintLight: '#f0fdf4',
    badgeBg: 'rgba(46,213,115,0.10)',
    badgeBorder: 'rgba(46,213,115,0.18)',
    vipBannerBg: 'linear-gradient(150deg, #f0fdf4 0%, #d1fae5 35%, #a7f3d0 65%, #f0fdf4 100%)',
    vipBtnGradient: 'linear-gradient(135deg, #15803d 0%, #16a34a 50%, #15803d 100%)',
    vipTextColor: '#14532d',
    vipDescColor: '#4ade80',
    vipFeatColor: '#15803d',
    vipBtnShadow: 'rgba(22,163,74,0.35)'
  },
  blue: {
    headerGradient: 'linear-gradient(180deg, #06b6d4 0%, #2563eb 30%, #3b82f6 60%, #eff6ff 85%, #ffffff 100%)',
    headerGradientLong: 'linear-gradient(180deg, #06b6d4 0%, #2563eb 18%, #3b82f6 35%, #bfdbfe 50%, #dbeafe 65%, #eff6ff 80%, #f0f0f2 92%, #f5f5f5 100%)',
    primary: '#3b82f6',
    primaryDark: '#2563eb',
    accent: '#06b6d4',
    btnGradient: 'linear-gradient(135deg, #60a5fa 0%, #3b82f6 25%, #2563eb 55%, #06b6d4 100%)',
    freeTextGradient: 'linear-gradient(135deg, #3b82f6, #06b6d4)',
    rgbPrimary: '59,130,246',
    tabBarSelected: '#3b82f6',
    tintLight: '#eff6ff',
    badgeBg: 'rgba(59,130,246,0.10)',
    badgeBorder: 'rgba(59,130,246,0.18)',
    vipBannerBg: 'linear-gradient(150deg, #eff6ff 0%, #dbeafe 35%, #bfdbfe 65%, #eff6ff 100%)',
    vipBtnGradient: 'linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #1d4ed8 100%)',
    vipTextColor: '#1e3a5f',
    vipDescColor: '#60a5fa',
    vipFeatColor: '#1d4ed8',
    vipBtnShadow: 'rgba(37,99,235,0.35)'
  },
  purple: {
    headerGradient: 'linear-gradient(180deg, #c084fc 0%, #7c3aed 30%, #8b5cf6 60%, #f5f3ff 85%, #ffffff 100%)',
    headerGradientLong: 'linear-gradient(180deg, #c084fc 0%, #7c3aed 18%, #8b5cf6 35%, #ddd6fe 50%, #ede9fe 65%, #f5f3ff 80%, #f0f0f2 92%, #f5f5f5 100%)',
    primary: '#8b5cf6',
    primaryDark: '#7c3aed',
    accent: '#c084fc',
    btnGradient: 'linear-gradient(135deg, #a78bfa 0%, #8b5cf6 25%, #7c3aed 55%, #c084fc 100%)',
    freeTextGradient: 'linear-gradient(135deg, #8b5cf6, #c084fc)',
    rgbPrimary: '139,92,246',
    tabBarSelected: '#8b5cf6',
    tintLight: '#f5f3ff',
    badgeBg: 'rgba(139,92,246,0.10)',
    badgeBorder: 'rgba(139,92,246,0.18)',
    vipBannerBg: 'linear-gradient(150deg, #f5f3ff 0%, #ede9fe 35%, #ddd6fe 65%, #f5f3ff 100%)',
    vipBtnGradient: 'linear-gradient(135deg, #6d28d9 0%, #7c3aed 50%, #6d28d9 100%)',
    vipTextColor: '#3b0764',
    vipDescColor: '#a78bfa',
    vipFeatColor: '#6d28d9',
    vipBtnShadow: 'rgba(124,58,237,0.35)'
  },
  orange: {
    headerGradient: 'linear-gradient(180deg, #f97316 0%, #d97706 30%, #f59e0b 60%, #fffbeb 85%, #ffffff 100%)',
    headerGradientLong: 'linear-gradient(180deg, #f97316 0%, #d97706 18%, #f59e0b 35%, #fde68a 50%, #fef3c7 65%, #fffbeb 80%, #f0f0f2 92%, #f5f5f5 100%)',
    primary: '#f59e0b',
    primaryDark: '#d97706',
    accent: '#f97316',
    btnGradient: 'linear-gradient(135deg, #fbbf24 0%, #f59e0b 25%, #d97706 55%, #f97316 100%)',
    freeTextGradient: 'linear-gradient(135deg, #f59e0b, #f97316)',
    rgbPrimary: '245,158,11',
    tabBarSelected: '#f59e0b',
    tintLight: '#fffbeb',
    badgeBg: 'rgba(245,158,11,0.10)',
    badgeBorder: 'rgba(245,158,11,0.18)',
    vipBannerBg: 'linear-gradient(150deg, #fffbeb 0%, #fef3c7 35%, #fde68a 65%, #fffbeb 100%)',
    vipBtnGradient: 'linear-gradient(135deg, #b45309 0%, #d97706 50%, #b45309 100%)',
    vipTextColor: '#78350f',
    vipDescColor: '#fbbf24',
    vipFeatColor: '#b45309',
    vipBtnShadow: 'rgba(217,119,6,0.35)'
  },
  pink: {
    headerGradient: 'linear-gradient(180deg, #f9a8d4 0%, #db2777 30%, #ec4899 60%, #fdf2f8 85%, #ffffff 100%)',
    headerGradientLong: 'linear-gradient(180deg, #f9a8d4 0%, #db2777 18%, #ec4899 35%, #fbcfe8 50%, #fce7f3 65%, #fdf2f8 80%, #f0f0f2 92%, #f5f5f5 100%)',
    primary: '#ec4899',
    primaryDark: '#db2777',
    accent: '#f9a8d4',
    btnGradient: 'linear-gradient(135deg, #f472b6 0%, #ec4899 25%, #db2777 55%, #f9a8d4 100%)',
    freeTextGradient: 'linear-gradient(135deg, #ec4899, #f9a8d4)',
    rgbPrimary: '236,72,153',
    tabBarSelected: '#ec4899',
    tintLight: '#fdf2f8',
    badgeBg: 'rgba(236,72,153,0.10)',
    badgeBorder: 'rgba(236,72,153,0.18)',
    vipBannerBg: 'linear-gradient(150deg, #fdf2f8 0%, #fce7f3 35%, #fbcfe8 65%, #fdf2f8 100%)',
    vipBtnGradient: 'linear-gradient(135deg, #be185d 0%, #db2777 50%, #be185d 100%)',
    vipTextColor: '#831843',
    vipDescColor: '#f472b6',
    vipFeatColor: '#be185d',
    vipBtnShadow: 'rgba(219,39,119,0.35)'
  },
  red: {
    headerGradient: 'linear-gradient(180deg, #fca5a5 0%, #dc2626 30%, #ef4444 60%, #fef2f2 85%, #ffffff 100%)',
    headerGradientLong: 'linear-gradient(180deg, #fca5a5 0%, #dc2626 18%, #ef4444 35%, #fecaca 50%, #fee2e2 65%, #fef2f2 80%, #f0f0f2 92%, #f5f5f5 100%)',
    primary: '#ef4444',
    primaryDark: '#dc2626',
    accent: '#fca5a5',
    btnGradient: 'linear-gradient(135deg, #f87171 0%, #ef4444 25%, #dc2626 55%, #fca5a5 100%)',
    freeTextGradient: 'linear-gradient(135deg, #ef4444, #fca5a5)',
    rgbPrimary: '239,68,68',
    tabBarSelected: '#ef4444',
    tintLight: '#fef2f2',
    badgeBg: 'rgba(239,68,68,0.10)',
    badgeBorder: 'rgba(239,68,68,0.18)',
    vipBannerBg: 'linear-gradient(150deg, #fef2f2 0%, #fee2e2 35%, #fecaca 65%, #fef2f2 100%)',
    vipBtnGradient: 'linear-gradient(135deg, #b91c1c 0%, #dc2626 50%, #b91c1c 100%)',
    vipTextColor: '#7f1d1d',
    vipDescColor: '#f87171',
    vipFeatColor: '#b91c1c',
    vipBtnShadow: 'rgba(220,38,38,0.35)'
  }
}

function getThemeColors (name) {
  return THEME_COLORS[name] || THEME_COLORS.green
}

export { THEME_COLORS, getThemeColors }
export default { THEME_COLORS, getThemeColors }
