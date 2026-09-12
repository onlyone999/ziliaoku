/**
 * TabBar 图标动态着色
 * Canvas 灰度化 + 颜色叠加
 * 选中态 → 主题色，未选中态 → #999999（与文字一致）
 */

const ICONS = [
  { index: 0, src: '/static/icons/home-active.png' },
  { index: 1, src: '/static/icons/category-active.png' },
  { index: 2, src: '/static/icons/user-active.png' }
]

const UNSELECTED_ICONS = [
  { index: 0, src: '/static/icons/home.png' },
  { index: 1, src: '/static/icons/category.png' },
  { index: 2, src: '/static/icons/user.png' }
]

// 缓存已生成的图标路径，避免重复生成
const cache = {}

function hexToRgb (hex) {
  hex = hex.replace('#', '')
  return {
    r: parseInt(hex.substring(0, 2), 16),
    g: parseInt(hex.substring(2, 4), 16),
    b: parseInt(hex.substring(4, 6), 16)
  }
}

/**
 * 通过 Canvas 将图标着色并导出临时文件
 */
function tintAndExport (src, color) {
  return new Promise(function (resolve) {
    uni.getImageInfo({
      src: src,
      success: function (info) {
        var w = info.width || 81
        var h = info.height || 81
        try {
          var canvas = wx.createOffscreenCanvas({ type: '2d', width: w, height: h })
          var ctx = canvas.getContext('2d')
          var img = canvas.createImage()
          img.onload = function () {
            try {
              ctx.clearRect(0, 0, w, h)
              ctx.drawImage(img, 0, 0, w, h)
              var imageData = ctx.getImageData(0, 0, w, h)
              var d = imageData.data
              var rgb = hexToRgb(color)
              for (var i = 0; i < d.length; i += 4) {
                if (d[i + 3] === 0) continue
                // 只用 alpha 通道保留形状，颜色精确匹配目标色
                var alpha = d[i + 3] / 255
                d[i] = Math.round(rgb.r * alpha)
                d[i + 1] = Math.round(rgb.g * alpha)
                d[i + 2] = Math.round(rgb.b * alpha)
              }
              ctx.putImageData(imageData, 0, 0)
              wx.canvasToTempFilePath({
                canvas: canvas,
                fileType: 'png',
                success: function (r) { resolve(r.tempFilePath) },
                fail: function () { resolve(null) }
              })
            } catch (e) { resolve(null) }
          }
          img.onerror = function () { resolve(null) }
          img.src = src
        } catch (e) { resolve(null) }
      },
      fail: function () { resolve(null) }
    })
  })
}

/**
 * 更新 tabBar：文字色 + 选中/未选中图标着色
 */
async function updateTabBarIcons (themeName, primaryColor) {
  var unselectedColor = '#999999'

  try {
    uni.setTabBarStyle({ color: unselectedColor, selectedColor: primaryColor })
  } catch (e) {}

  // 无 wx 或 OffscreenCanvas 时跳过图标着色
  if (typeof wx === 'undefined' || !wx.createOffscreenCanvas) return

  var cacheKey = themeName
  if (cache[cacheKey]) {
    // 已缓存，直接设置
    var cached = cache[cacheKey]
    for (var j = 0; j < cached.selected.length; j++) {
      var c = cached.selected[j]
      if (c.path) {
        try { uni.setTabBarItem({ index: c.index, selectedIconPath: c.path }) } catch (e) {}
      }
    }
    for (var k = 0; k < cached.unselected.length; k++) {
      var u = cached.unselected[k]
      if (u.path) {
        try { uni.setTabBarItem({ index: u.index, iconPath: u.path }) } catch (e) {}
      }
    }
    return
  }

  // 逐个生成着色图标（选中 + 未选中）
  var selectedResults = []
  var unselectedResults = []

  for (var i = 0; i < ICONS.length; i++) {
    var item = ICONS[i]
    var path = await tintAndExport(item.src, primaryColor)
    selectedResults.push({ index: item.index, path: path })
    if (path) {
      try { uni.setTabBarItem({ index: item.index, selectedIconPath: path }) } catch (e) {}
    }
  }

  for (var m = 0; m < UNSELECTED_ICONS.length; m++) {
    var uItem = UNSELECTED_ICONS[m]
    var uPath = await tintAndExport(uItem.src, unselectedColor)
    unselectedResults.push({ index: uItem.index, path: uPath })
    if (uPath) {
      try { uni.setTabBarItem({ index: uItem.index, iconPath: uPath }) } catch (e) {}
    }
  }

  cache[cacheKey] = { selected: selectedResults, unselected: unselectedResults }
}

export default { updateTabBarIcons }
