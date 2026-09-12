(global["webpackJsonp"] = global["webpackJsonp"] || []).push([["pages/resource/detail"],{

/***/ 76:
/*!****************************************************************************!*\
  !*** E:/ziliaoku/miniprogram/main.js?{"page":"pages%2Fresource%2Fdetail"} ***!
  \****************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(wx, createPage) {

var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ 4);
__webpack_require__(/*! uni-pages */ 30);
__webpack_require__(/*! @dcloudio/uni-stat/dist/uni-stat-public.es.js */ 31);
var _vue = _interopRequireDefault(__webpack_require__(/*! vue */ 25));
var _detail = _interopRequireDefault(__webpack_require__(/*! ./pages/resource/detail.vue */ 77));
// @ts-ignore
wx.__webpack_require_UNI_MP_PLUGIN__ = __webpack_require__;
createPage(_detail.default);
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/wx.js */ 1)["default"], __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/index.js */ 2)["createPage"]))

/***/ }),

/***/ 77:
/*!*********************************************************!*\
  !*** E:/ziliaoku/miniprogram/pages/resource/detail.vue ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _detail_vue_vue_type_template_id_1c436dee_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./detail.vue?vue&type=template&id=1c436dee&scoped=true& */ 78);
/* harmony import */ var _detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./detail.vue?vue&type=script&lang=js& */ 80);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[key]; }) }(__WEBPACK_IMPORT_KEY__));
/* harmony import */ var _detail_vue_vue_type_style_index_0_id_1c436dee_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./detail.vue?vue&type=style&index=0&id=1c436dee&scoped=true&lang=css& */ 82);
/* harmony import */ var _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/runtime/componentNormalizer.js */ 49);

var renderjs





/* normalize component */

var component = Object(_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _detail_vue_vue_type_template_id_1c436dee_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _detail_vue_vue_type_template_id_1c436dee_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "1c436dee",
  null,
  false,
  _detail_vue_vue_type_template_id_1c436dee_scoped_true___WEBPACK_IMPORTED_MODULE_0__["components"],
  renderjs
)

component.options.__file = "pages/resource/detail.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ 78:
/*!****************************************************************************************************!*\
  !*** E:/ziliaoku/miniprogram/pages/resource/detail.vue?vue&type=template&id=1c436dee&scoped=true& ***!
  \****************************************************************************************************/
/*! exports provided: render, staticRenderFns, recyclableRender, components */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_template_id_1c436dee_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--17-0!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/template.js!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-uni-app-loader/page-meta.js!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./detail.vue?vue&type=template&id=1c436dee&scoped=true& */ 79);
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_template_id_1c436dee_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_template_id_1c436dee_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "recyclableRender", function() { return _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_template_id_1c436dee_scoped_true___WEBPACK_IMPORTED_MODULE_0__["recyclableRender"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "components", function() { return _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_template_id_1c436dee_scoped_true___WEBPACK_IMPORTED_MODULE_0__["components"]; });



/***/ }),

/***/ 79:
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--17-0!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/template.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-uni-app-loader/page-meta.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!E:/ziliaoku/miniprogram/pages/resource/detail.vue?vue&type=template&id=1c436dee&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns, recyclableRender, components */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "recyclableRender", function() { return recyclableRender; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "components", function() { return components; });
var components
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  var g0 = _vm.images.length
  var g1 = g0 > 1 ? _vm.images.length : null
  var m0 = _vm.formatCount(_vm.resource.view_count || 0)
  var m1 = _vm.formatCount(_vm.resource.download_count || 0)
  var m2 = _vm.formatCount(_vm.resource.like_count || 0)
  var g2 = _vm.resource.rating ? _vm.comments.length : null
  var g3 =
    _vm.resource.price > 0 &&
    _vm.resource.original_price &&
    _vm.resource.original_price > _vm.resource.price
      ? Math.round((1 - _vm.resource.price / _vm.resource.original_price) * 100)
      : null
  var m3 =
    _vm.resource.file_type && _vm.resource.file_size
      ? _vm.formatSize(_vm.resource.file_size)
      : null
  var g4 = _vm.resource.tags && _vm.resource.tags.length > 0
  var l0 =
    _vm.commentEnabled && _vm.resource.rating
      ? _vm.__map(5, function (n, __i0__) {
          var $orig = _vm.__get_orig(n)
          var m4 = _vm.getRatingPercent(6 - n)
          var m5 = _vm.getRatingPercent(6 - n)
          return {
            $orig: $orig,
            m4: m4,
            m5: m5,
          }
        })
      : null
  var g5 = _vm.commentEnabled ? _vm.comments.length : null
  var l1 =
    _vm.commentEnabled && g5 > 0
      ? _vm.__map(_vm.comments, function (item, __i1__) {
          var $orig = _vm.__get_orig(item)
          var m6 = _vm.formatCommentTime(item.created_at)
          return {
            $orig: $orig,
            m6: m6,
          }
        })
      : null
  var g6 = _vm.commentEnabled ? _vm.commentForm.content.length : null
  var g7 = _vm.commentEnabled ? _vm.commentForm.content.length : null
  var g8 = _vm.relatedResources.length
  var l2 =
    g8 > 0
      ? _vm.__map(_vm.relatedResources, function (item, __i4__) {
          var $orig = _vm.__get_orig(item)
          var m7 = _vm.fixUrl(item.cover_url)
          var m8 = _vm.formatCount(item.download_count || 0)
          return {
            $orig: $orig,
            m7: m7,
            m8: m8,
          }
        })
      : null
  if (!_vm._isMounted) {
    _vm.e0 = function ($event, n) {
      var _temp = arguments[arguments.length - 1].currentTarget.dataset,
        _temp2 = _temp.eventParams || _temp["event-params"],
        n = _temp2.n
      var _temp, _temp2
      _vm.commentForm.rating = n
    }
  }
  _vm.$mp.data = Object.assign(
    {},
    {
      $root: {
        g0: g0,
        g1: g1,
        m0: m0,
        m1: m1,
        m2: m2,
        g2: g2,
        g3: g3,
        m3: m3,
        g4: g4,
        l0: l0,
        g5: g5,
        l1: l1,
        g6: g6,
        g7: g7,
        g8: g8,
        l2: l2,
      },
    }
  )
}
var recyclableRender = false
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ 80:
/*!**********************************************************************************!*\
  !*** E:/ziliaoku/miniprogram/pages/resource/detail.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/babel-loader/lib!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--13-1!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/script.js!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./detail.vue?vue&type=script&lang=js& */ 81);
/* harmony import */ var _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ 81:
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--13-1!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/script.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!E:/ziliaoku/miniprogram/pages/resource/detail.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(uni) {

var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ 4);
var _typeof = __webpack_require__(/*! @babel/runtime/helpers/typeof */ 13);
Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
var _regenerator = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/regenerator */ 38));
var _defineProperty2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/defineProperty */ 11));
var _asyncToGenerator2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ 46));
var _http = _interopRequireWildcard(__webpack_require__(/*! @/utils/http.js */ 57));
function _getRequireWildcardCache(nodeInterop) { if (typeof WeakMap !== "function") return null; var cacheBabelInterop = new WeakMap(); var cacheNodeInterop = new WeakMap(); return (_getRequireWildcardCache = function _getRequireWildcardCache(nodeInterop) { return nodeInterop ? cacheNodeInterop : cacheBabelInterop; })(nodeInterop); }
function _interopRequireWildcard(obj, nodeInterop) { if (!nodeInterop && obj && obj.__esModule) { return obj; } if (obj === null || _typeof(obj) !== "object" && typeof obj !== "function") { return { default: obj }; } var cache = _getRequireWildcardCache(nodeInterop); if (cache && cache.has(obj)) { return cache.get(obj); } var newObj = {}; var hasPropertyDescriptor = Object.defineProperty && Object.getOwnPropertyDescriptor; for (var key in obj) { if (key !== "default" && Object.prototype.hasOwnProperty.call(obj, key)) { var desc = hasPropertyDescriptor ? Object.getOwnPropertyDescriptor(obj, key) : null; if (desc && (desc.get || desc.set)) { Object.defineProperty(newObj, key, desc); } else { newObj[key] = obj[key]; } } } newObj.default = obj; if (cache) { cache.set(obj, newObj); } return newObj; }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
var _default = {
  data: function data() {
    return {
      resourceId: 0,
      resource: {},
      images: [],
      comments: [],
      relatedResources: [],
      isFavorited: false,
      isPurchased: false,
      isVip: false,
      isMemberFree: false,
      pageLoading: true,
      loadError: false,
      errorMsg: '',
      currentImageIdx: 0,
      downloading: false,
      downloadPercent: 0,
      commentForm: {
        rating: 5,
        content: ''
      },
      submittingComment: false,
      commentEnabled: true,
      userVote: null,
      upVotes: 0,
      downVotes: 0
    };
  },
  computed: {
    fileTypeIcon: function fileTypeIcon() {
      var type = (this.resource.file_type || '').toLowerCase();
      if (type.includes('pdf')) return '📄';
      if (type.includes('doc') || type.includes('word')) return '📝';
      if (type.includes('xls') || type.includes('excel')) return '📊';
      if (type.includes('ppt')) return '📽';
      if (type.includes('zip') || type.includes('rar') || type.includes('7z')) return '📦';
      if (type.includes('psd') || type.includes('ai')) return '🎨';
      if (type.includes('mp4') || type.includes('mov') || type.includes('avi')) return '🎬';
      if (type.includes('mp3') || type.includes('wav') || type.includes('flac')) return '🎵';
      if (type.includes('jpg') || type.includes('png') || type.includes('svg')) return '🖼';
      return '📁';
    },
    vipFreeText: function vipFreeText() {
      var levels = this.resource.vip_free_levels;
      if (!levels) return 'VIP免费';
      var map = {
        1: '月卡',
        2: '季卡',
        3: '年卡',
        4: '终身'
      };
      var arr = levels.split(',').map(function (l) {
        return map[l.trim()] || l;
      });
      return arr.join('/') + '免费';
    },
    previewType: function previewType() {
      var type = (this.resource.file_type || '').toLowerCase();
      if (type.includes('pdf')) return 'pdf';
      if (type.includes('jpg') || type.includes('jpeg') || type.includes('png') || type.includes('gif') || type.includes('svg') || type.includes('psd') || type.includes('ai')) return 'image';
      if (type.includes('js') || type.includes('py') || type.includes('java') || type.includes('html') || type.includes('css') || type.includes('php') || type.includes('json') || type.includes('xml') || type.includes('sql') || type.includes('ts') || type.includes('vue') || type.includes('cpp') || type.includes('go') || type.includes('rust')) return 'code';
      return 'text';
    },
    hasPreview: function hasPreview() {
      return !!(this.resource.preview_images && this.resource.preview_images.length > 0) || !!this.resource.preview_code || !!this.resource.preview_text;
    },
    canSubmitComment: function canSubmitComment() {
      return this.commentForm.content.trim().length > 0 && !this.submittingComment;
    },
    ratingHintText: function ratingHintText() {
      var hints = ['', '很差', '较差', '一般', '不错', '很好'];
      return hints[this.commentForm.rating] || '';
    },
    votePercent: function votePercent() {
      var total = this.upVotes + this.downVotes;
      if (total === 0) return 50;
      return Math.round(this.upVotes / total * 100);
    }
  },
  onLoad: function onLoad(options) {
    if (options.id) {
      this.resourceId = parseInt(options.id);
      this.loadDetail();
      this.loadComments();
      this.loadRelated();
      this.checkFavorite();
      this.checkVip();
      this.loadVoteStatus();
    }
  },
  methods: {
    goBack: function goBack() {
      uni.navigateBack({
        delta: 1
      });
    },
    retryLoad: function retryLoad() {
      this.loadDetail();
      this.loadComments();
      this.loadRelated();
    },
    loadDetail: function loadDetail() {
      var _this = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee() {
        var res;
        return _regenerator.default.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this.pageLoading = true;
                _this.loadError = false;
                _this.errorMsg = '';
                _context.prev = 3;
                _context.next = 6;
                return _http.default.get('/api/resource/detail', {
                  id: _this.resourceId
                });
              case 6:
                res = _context.sent;
                if (res.code === 0) {
                  _this.resource = res.data || {};
                  _this.images = _this.resource.images && _this.resource.images.length > 0 ? _this.resource.images : [_this.resource.cover_url].filter(Boolean);
                  _this.isMemberFree = !!_this.resource.is_member_free;
                  _this.isPurchased = !!_this.resource.is_purchased;
                  _this.commentEnabled = _this.resource.comment_enabled !== false;
                  uni.setNavigationBarTitle({
                    title: _this.resource.title || '资源详情'
                  });
                } else {
                  _this.loadError = true;
                  _this.errorMsg = res.message || res.msg || '加载失败';
                }
                _context.next = 15;
                break;
              case 10:
                _context.prev = 10;
                _context.t0 = _context["catch"](3);
                console.error('加载资源详情失败', _context.t0);
                _this.loadError = true;
                _this.errorMsg = '网络异常，请检查网络后重试';
              case 15:
                _context.prev = 15;
                _this.pageLoading = false;
                return _context.finish(15);
              case 18:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[3, 10, 15, 18]]);
      }))();
    },
    loadComments: function loadComments() {
      var _this2 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee2() {
        var res;
        return _regenerator.default.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.prev = 0;
                _context2.next = 3;
                return _http.default.get('/api/resource/comments', {
                  resource_id: _this2.resourceId,
                  page: 1,
                  page_size: 10
                });
              case 3:
                res = _context2.sent;
                if (res.code === 0) {
                  _this2.comments = res.data.list || res.data || [];
                }
                _context2.next = 10;
                break;
              case 7:
                _context2.prev = 7;
                _context2.t0 = _context2["catch"](0);
                console.error('加载评论失败', _context2.t0);
              case 10:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[0, 7]]);
      }))();
    },
    loadRelated: function loadRelated() {
      var _this3 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee3() {
        var res;
        return _regenerator.default.wrap(function _callee3$(_context3) {
          while (1) {
            switch (_context3.prev = _context3.next) {
              case 0:
                _context3.prev = 0;
                _context3.next = 3;
                return _http.default.get('/api/resource/related', {
                  id: _this3.resourceId
                });
              case 3:
                res = _context3.sent;
                if (res.code === 0) {
                  _this3.relatedResources = res.data || [];
                }
                _context3.next = 10;
                break;
              case 7:
                _context3.prev = 7;
                _context3.t0 = _context3["catch"](0);
                console.error('加载相关资源失败', _context3.t0);
              case 10:
              case "end":
                return _context3.stop();
            }
          }
        }, _callee3, null, [[0, 7]]);
      }))();
    },
    checkFavorite: function checkFavorite() {
      var _this4 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee4() {
        var token, res;
        return _regenerator.default.wrap(function _callee4$(_context4) {
          while (1) {
            switch (_context4.prev = _context4.next) {
              case 0:
                token = uni.getStorageSync('token');
                if (token) {
                  _context4.next = 3;
                  break;
                }
                return _context4.abrupt("return");
              case 3:
                _context4.prev = 3;
                _context4.next = 6;
                return _http.default.get('/api/favorite/check', {
                  resource_id: _this4.resourceId
                });
              case 6:
                res = _context4.sent;
                if (res.code === 0) {
                  _this4.isFavorited = !!res.data.is_favorited;
                }
                _context4.next = 12;
                break;
              case 10:
                _context4.prev = 10;
                _context4.t0 = _context4["catch"](3);
              case 12:
              case "end":
                return _context4.stop();
            }
          }
        }, _callee4, null, [[3, 10]]);
      }))();
    },
    checkVip: function checkVip() {
      var _this5 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee5() {
        var token, res;
        return _regenerator.default.wrap(function _callee5$(_context5) {
          while (1) {
            switch (_context5.prev = _context5.next) {
              case 0:
                token = uni.getStorageSync('token');
                if (token) {
                  _context5.next = 3;
                  break;
                }
                return _context5.abrupt("return");
              case 3:
                _context5.prev = 3;
                _context5.next = 6;
                return _http.default.get('/api/user/vipStatus');
              case 6:
                res = _context5.sent;
                if (res.code === 0) {
                  _this5.isVip = !!(res.data && res.data.is_vip);
                }
                _context5.next = 12;
                break;
              case 10:
                _context5.prev = 10;
                _context5.t0 = _context5["catch"](3);
              case 12:
              case "end":
                return _context5.stop();
            }
          }
        }, _callee5, null, [[3, 10]]);
      }))();
    },
    loadVoteStatus: function loadVoteStatus() {
      var _this6 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee6() {
        var token, res;
        return _regenerator.default.wrap(function _callee6$(_context6) {
          while (1) {
            switch (_context6.prev = _context6.next) {
              case 0:
                token = uni.getStorageSync('token');
                if (token) {
                  _context6.next = 3;
                  break;
                }
                return _context6.abrupt("return");
              case 3:
                _context6.prev = 3;
                _context6.next = 6;
                return _http.default.get('/api/vote/status', {
                  resource_id: _this6.resourceId
                }, {
                  silent: true
                });
              case 6:
                res = _context6.sent;
                if (res.code === 0) {
                  _this6.userVote = res.data.user_vote;
                  _this6.upVotes = res.data.up_votes || 0;
                  _this6.downVotes = res.data.down_votes || 0;
                }
                _context6.next = 12;
                break;
              case 10:
                _context6.prev = 10;
                _context6.t0 = _context6["catch"](3);
              case 12:
              case "end":
                return _context6.stop();
            }
          }
        }, _callee6, null, [[3, 10]]);
      }))();
    },
    toggleVote: function toggleVote(type) {
      var _this7 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee7() {
        var token, res, action;
        return _regenerator.default.wrap(function _callee7$(_context7) {
          while (1) {
            switch (_context7.prev = _context7.next) {
              case 0:
                token = uni.getStorageSync('token');
                if (token) {
                  _context7.next = 4;
                  break;
                }
                uni.showModal({
                  title: '提示',
                  content: '请先登录后再投票',
                  confirmText: '去登录',
                  success: function success(res) {
                    if (res.confirm) {
                      uni.switchTab({
                        url: '/pages/user/index'
                      });
                    }
                  }
                });
                return _context7.abrupt("return");
              case 4:
                _context7.prev = 4;
                _context7.next = 7;
                return _http.default.post('/api/vote/toggle', {
                  resource_id: _this7.resourceId,
                  vote_type: type
                });
              case 7:
                res = _context7.sent;
                if (res.code === 0) {
                  _this7.userVote = res.data.user_vote;
                  _this7.upVotes = res.data.up_votes || 0;
                  _this7.downVotes = res.data.down_votes || 0;
                  action = res.data.action;
                  if (action === 'removed') {
                    uni.showToast({
                      title: '已取消投票',
                      icon: 'none'
                    });
                  } else if (action === 'changed') {
                    uni.showToast({
                      title: '已更改投票',
                      icon: 'none'
                    });
                  }
                }
                _context7.next = 14;
                break;
              case 11:
                _context7.prev = 11;
                _context7.t0 = _context7["catch"](4);
                uni.showToast({
                  title: '投票失败',
                  icon: 'none'
                });
              case 14:
              case "end":
                return _context7.stop();
            }
          }
        }, _callee7, null, [[4, 11]]);
      }))();
    },
    toggleFavorite: function toggleFavorite() {
      var _this8 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee8() {
        var token, action, res;
        return _regenerator.default.wrap(function _callee8$(_context8) {
          while (1) {
            switch (_context8.prev = _context8.next) {
              case 0:
                token = uni.getStorageSync('token');
                if (token) {
                  _context8.next = 4;
                  break;
                }
                uni.showModal({
                  title: '提示',
                  content: '请先登录',
                  confirmText: '去登录',
                  success: function success(res) {
                    if (res.confirm) {
                      uni.switchTab({
                        url: '/pages/user/index'
                      });
                    }
                  }
                });
                return _context8.abrupt("return");
              case 4:
                _context8.prev = 4;
                action = _this8.isFavorited ? 'cancel' : 'add';
                _context8.next = 8;
                return _http.default.post('/api/favorite/toggle', {
                  resource_id: _this8.resourceId,
                  action: action
                });
              case 8:
                res = _context8.sent;
                if (res.code === 0) {
                  _this8.isFavorited = !_this8.isFavorited;
                  uni.showToast({
                    title: _this8.isFavorited ? '已收藏' : '已取消收藏',
                    icon: 'none'
                  });
                }
                _context8.next = 15;
                break;
              case 12:
                _context8.prev = 12;
                _context8.t0 = _context8["catch"](4);
                uni.showToast({
                  title: '操作失败',
                  icon: 'none'
                });
              case 15:
              case "end":
                return _context8.stop();
            }
          }
        }, _callee8, null, [[4, 12]]);
      }))();
    },
    handleDownload: function handleDownload() {
      var token = uni.getStorageSync('token');
      if (!token) {
        uni.showModal({
          title: '提示',
          content: '请先登录后再下载',
          confirmText: '去登录',
          success: function success(res) {
            if (res.confirm) {
              uni.switchTab({
                url: '/pages/user/index'
              });
            }
          }
        });
        return;
      }
      this.doDownload();
    },
    doDownload: function doDownload() {
      var _this9 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee9() {
        var _this9$resource, res, fileUrl, downloadTask;
        return _regenerator.default.wrap(function _callee9$(_context9) {
          while (1) {
            switch (_context9.prev = _context9.next) {
              case 0:
                uni.showLoading({
                  title: '获取下载链接...'
                });
                _context9.prev = 1;
                console.log('[DOWNLOAD] resourceId:', _this9.resourceId, 'resource:', (_this9$resource = _this9.resource) === null || _this9$resource === void 0 ? void 0 : _this9$resource.title);
                _context9.next = 5;
                return _http.default.post('/api/download/download', {
                  resource_id: _this9.resourceId
                });
              case 5:
                res = _context9.sent;
                if (!(res.code === 0 && res.data)) {
                  _context9.next = 19;
                  break;
                }
                fileUrl = res.data.resource && res.data.resource.file_url || res.data.file_url;
                if (fileUrl) {
                  _context9.next = 12;
                  break;
                }
                uni.hideLoading();
                uni.showToast({
                  title: '获取下载链接失败',
                  icon: 'none'
                });
                return _context9.abrupt("return");
              case 12:
                uni.hideLoading();
                _this9.downloading = true;
                _this9.downloadPercent = 0;
                downloadTask = uni.downloadFile({
                  url: fileUrl,
                  success: function success(downloadRes) {
                    _this9.downloading = false;
                    if (downloadRes.statusCode === 200) {
                      var tempPath = downloadRes.tempFilePath;
                      uni.openDocument({
                        filePath: tempPath,
                        showMenu: true,
                        success: function success() {
                          uni.showToast({
                            title: '下载成功，点右上角可保存转发',
                            icon: 'success',
                            duration: 3000
                          });
                        },
                        fail: function fail(err) {
                          uni.showModal({
                            title: '下载完成',
                            content: '该文件无法在小程序内打开，请复制链接到浏览器下载。',
                            confirmText: '复制链接',
                            success: function success(mr) {
                              if (mr.confirm) uni.setClipboardData({
                                data: fileUrl
                              });
                            }
                          });
                        }
                      });
                    } else {
                      uni.showToast({
                        title: '下载失败(HTTP ' + downloadRes.statusCode + ')',
                        icon: 'none'
                      });
                    }
                  },
                  fail: function fail(err) {
                    _this9.downloading = false;
                    console.error('[DOWNLOAD]', fileUrl, err);
                    uni.showToast({
                      title: '下载失败:' + (err.errMsg || '未知'),
                      icon: 'none',
                      duration: 5000
                    });
                  }
                });
                if (downloadTask && downloadTask.onProgressUpdate) {
                  downloadTask.onProgressUpdate(function (progressRes) {
                    _this9.downloadPercent = progressRes.progress || 0;
                  });
                }
                _context9.next = 21;
                break;
              case 19:
                uni.hideLoading();
                uni.showToast({
                  title: res.message || res.msg || '获取下载链接失败',
                  icon: 'none'
                });
              case 21:
                _context9.next = 29;
                break;
              case 23:
                _context9.prev = 23;
                _context9.t0 = _context9["catch"](1);
                uni.hideLoading();
                _this9.downloading = false;
                console.error('[DOWNLOAD] 异常:', _context9.t0);
                uni.showToast({
                  title: '下载异常:' + (_context9.t0.message || '未知'),
                  icon: 'none',
                  duration: 5000
                });
              case 29:
              case "end":
                return _context9.stop();
            }
          }
        }, _callee9, null, [[1, 23]]);
      }))();
    },
    handleBuy: function handleBuy() {
      var _this10 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee12() {
        var token, orderRes, payParams, orderId;
        return _regenerator.default.wrap(function _callee12$(_context12) {
          while (1) {
            switch (_context12.prev = _context12.next) {
              case 0:
                token = uni.getStorageSync('token');
                if (token) {
                  _context12.next = 4;
                  break;
                }
                uni.showModal({
                  title: '提示',
                  content: '请先登录后再购买',
                  confirmText: '去登录',
                  success: function success(res) {
                    if (res.confirm) {
                      uni.switchTab({
                        url: '/pages/user/index'
                      });
                    }
                  }
                });
                return _context12.abrupt("return");
              case 4:
                uni.showLoading({
                  title: '创建订单...'
                });
                _context12.prev = 5;
                _context12.next = 8;
                return _http.default.post('/api/order/create', {
                  order_type: 'resource',
                  resource_id: _this10.resourceId,
                  pay_type: 'wechat'
                });
              case 8:
                orderRes = _context12.sent;
                if (orderRes.code === 0 && orderRes.data) {
                  uni.hideLoading();
                  payParams = orderRes.data.pay_params || {};
                  orderId = orderRes.data.order_id;
                  if (payParams.wxpay) {
                    uni.requestPayment(_objectSpread(_objectSpread({
                      provider: 'wxpay'
                    }, payParams.wxpay), {}, {
                      success: function () {
                        var _success = (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee10() {
                          return _regenerator.default.wrap(function _callee10$(_context10) {
                            while (1) {
                              switch (_context10.prev = _context10.next) {
                                case 0:
                                  _context10.next = 2;
                                  return _this10.onPaySuccess(orderId);
                                case 2:
                                case "end":
                                  return _context10.stop();
                              }
                            }
                          }, _callee10);
                        }));
                        function success() {
                          return _success.apply(this, arguments);
                        }
                        return success;
                      }(),
                      fail: function fail(err) {
                        if (err.errMsg && err.errMsg.includes('cancel')) {
                          uni.showToast({
                            title: '已取消支付',
                            icon: 'none'
                          });
                        } else {
                          uni.showToast({
                            title: '支付失败',
                            icon: 'none'
                          });
                        }
                      }
                    }));
                  } else if (payParams.virtual) {
                    uni.requestVirtualPayment(_objectSpread(_objectSpread({}, payParams.virtual), {}, {
                      success: function () {
                        var _success2 = (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee11() {
                          return _regenerator.default.wrap(function _callee11$(_context11) {
                            while (1) {
                              switch (_context11.prev = _context11.next) {
                                case 0:
                                  _context11.next = 2;
                                  return _this10.onPaySuccess(orderId);
                                case 2:
                                case "end":
                                  return _context11.stop();
                              }
                            }
                          }, _callee11);
                        }));
                        function success() {
                          return _success2.apply(this, arguments);
                        }
                        return success;
                      }(),
                      fail: function fail(err) {
                        if (err.errMsg && err.errMsg.includes('cancel')) {
                          uni.showToast({
                            title: '已取消支付',
                            icon: 'none'
                          });
                        } else {
                          uni.showToast({
                            title: '支付失败',
                            icon: 'none'
                          });
                        }
                      }
                    }));
                  }
                } else {
                  uni.hideLoading();
                  uni.showToast({
                    title: orderRes.msg || '创建订单失败',
                    icon: 'none'
                  });
                }
                _context12.next = 16;
                break;
              case 12:
                _context12.prev = 12;
                _context12.t0 = _context12["catch"](5);
                uni.hideLoading();
                uni.showToast({
                  title: '支付异常',
                  icon: 'none'
                });
              case 16:
              case "end":
                return _context12.stop();
            }
          }
        }, _callee12, null, [[5, 12]]);
      }))();
    },
    onPaySuccess: function onPaySuccess(orderId) {
      var _this11 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee13() {
        return _regenerator.default.wrap(function _callee13$(_context13) {
          while (1) {
            switch (_context13.prev = _context13.next) {
              case 0:
                _context13.prev = 0;
                _context13.next = 3;
                return _http.default.post('/api/order/callback', {
                  order_id: orderId
                });
              case 3:
                _context13.next = 7;
                break;
              case 5:
                _context13.prev = 5;
                _context13.t0 = _context13["catch"](0);
              case 7:
                _this11.isPurchased = true;
                uni.showToast({
                  title: '购买成功',
                  icon: 'success'
                });
                setTimeout(function () {
                  _this11.doDownload();
                }, 1000);
              case 10:
              case "end":
                return _context13.stop();
            }
          }
        }, _callee13, null, [[0, 5]]);
      }))();
    },
    goVip: function goVip() {
      uni.navigateTo({
        url: '/pages/user/vip'
      });
    },
    onSwiperChange: function onSwiperChange(e) {
      this.currentImageIdx = e.detail.current;
    },
    previewImage: function previewImage(idx) {
      uni.previewImage({
        urls: this.images,
        current: idx
      });
    },
    previewPreviewImage: function previewPreviewImage(idx) {
      if (this.resource.preview_images) {
        uni.previewImage({
          urls: this.resource.preview_images,
          current: idx
        });
      }
    },
    renderStars: function renderStars(rating) {
      var r = Math.round(rating || 0);
      var stars = '';
      for (var i = 1; i <= 5; i++) {
        stars += i <= r ? '★' : '☆';
      }
      return stars;
    },
    submitComment: function submitComment() {
      var _this12 = this;
      return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee14() {
        var token, res;
        return _regenerator.default.wrap(function _callee14$(_context14) {
          while (1) {
            switch (_context14.prev = _context14.next) {
              case 0:
                if (_this12.canSubmitComment) {
                  _context14.next = 2;
                  break;
                }
                return _context14.abrupt("return");
              case 2:
                token = uni.getStorageSync('token');
                if (token) {
                  _context14.next = 6;
                  break;
                }
                uni.showModal({
                  title: '提示',
                  content: '请先登录后再评价',
                  confirmText: '去登录',
                  success: function success(res) {
                    if (res.confirm) {
                      uni.switchTab({
                        url: '/pages/user/index'
                      });
                    }
                  }
                });
                return _context14.abrupt("return");
              case 6:
                _this12.submittingComment = true;
                _context14.prev = 7;
                _context14.next = 10;
                return _http.default.post('/api/resource/comment', {
                  resource_id: _this12.resourceId,
                  rating: _this12.commentForm.rating,
                  content: _this12.commentForm.content.trim()
                });
              case 10:
                res = _context14.sent;
                if (res.code === 0) {
                  uni.showToast({
                    title: '评价成功',
                    icon: 'success'
                  });
                  _this12.commentForm.content = '';
                  _this12.commentForm.rating = 5;
                  _this12.loadComments();
                } else {
                  uni.showToast({
                    title: res.message || res.msg || '评价失败',
                    icon: 'none'
                  });
                }
                _context14.next = 17;
                break;
              case 14:
                _context14.prev = 14;
                _context14.t0 = _context14["catch"](7);
                uni.showToast({
                  title: '评价失败',
                  icon: 'none'
                });
              case 17:
                _context14.prev = 17;
                _this12.submittingComment = false;
                return _context14.finish(17);
              case 20:
              case "end":
                return _context14.stop();
            }
          }
        }, _callee14, null, [[7, 14, 17, 20]]);
      }))();
    },
    goRelated: function goRelated(item) {
      uni.navigateTo({
        url: '/pages/resource/detail?id=' + item.id
      });
    },
    formatSize: function formatSize(bytes) {
      if (!bytes) return '';
      if (bytes < 1024) return bytes + 'B';
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + 'KB';
      if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + 'MB';
      return (bytes / (1024 * 1024 * 1024)).toFixed(1) + 'GB';
    },
    formatCount: function formatCount(num) {
      if (!num) return '0';
      if (num >= 10000) return (num / 10000).toFixed(1) + 'w';
      if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
      return num + '';
    },
    fixUrl: function fixUrl(url) {
      if (!url) return '';
      return url.startsWith('http') ? url : _http.BASE_URL + url;
    },
    formatCommentTime: function formatCommentTime(time) {
      if (!time) return '';
      var d = new Date(time.replace(/-/g, '/'));
      var now = new Date();
      var diff = (now - d) / 1000;
      if (diff < 60) return '刚刚';
      if (diff < 3600) return Math.floor(diff / 60) + '分钟前';
      if (diff < 86400) return Math.floor(diff / 3600) + '小时前';
      if (diff < 604800) return Math.floor(diff / 86400) + '天前';
      return time.slice(0, 10);
    },
    getRatingPercent: function getRatingPercent(star) {
      // 模拟评分分布
      var dist = {
        5: 62,
        4: 24,
        3: 9,
        2: 3,
        1: 2
      };
      return dist[star] || 0;
    },
    copyCode: function copyCode() {
      if (this.resource.preview_code) {
        uni.setClipboardData({
          data: this.resource.preview_code,
          success: function success() {
            uni.showToast({
              title: '已复制代码',
              icon: 'success'
            });
          }
        });
      }
    },
    likeComment: function likeComment(item) {
      uni.showToast({
        title: '已点赞',
        icon: 'none'
      });
    },
    replyComment: function replyComment(item) {
      this.commentForm.content = '@' + item.nickname + ' ';
    },
    goMore: function goMore() {
      uni.switchTab({
        url: '/pages/category/list'
      });
    }
  },
  onShareAppMessage: function onShareAppMessage() {
    return {
      title: this.resource.title || '发现优质资源',
      path: "/pages/resource/detail?id=".concat(this.resourceId),
      imageUrl: this.resource.cover_url || ''
    };
  }
};
exports.default = _default;
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/index.js */ 2)["default"]))

/***/ }),

/***/ 82:
/*!******************************************************************************************************************!*\
  !*** E:/ziliaoku/miniprogram/pages/resource/detail.vue?vue&type=style&index=0&id=1c436dee&scoped=true&lang=css& ***!
  \******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_style_index_0_id_1c436dee_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/mini-css-extract-plugin/dist/loader.js??ref--6-oneOf-1-0!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/css-loader/dist/cjs.js??ref--6-oneOf-1-1!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/stylePostLoader.js!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--6-oneOf-1-2!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/postcss-loader/src??ref--6-oneOf-1-3!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../HBuilderX.5.24.2026081301/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./detail.vue?vue&type=style&index=0&id=1c436dee&scoped=true&lang=css& */ 83);
/* harmony import */ var _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_style_index_0_id_1c436dee_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_style_index_0_id_1c436dee_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_style_index_0_id_1c436dee_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_style_index_0_id_1c436dee_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_5_24_2026081301_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_detail_vue_vue_type_style_index_0_id_1c436dee_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ 83:
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/mini-css-extract-plugin/dist/loader.js??ref--6-oneOf-1-0!./node_modules/css-loader/dist/cjs.js??ref--6-oneOf-1-1!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--6-oneOf-1-2!./node_modules/postcss-loader/src??ref--6-oneOf-1-3!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!E:/ziliaoku/miniprogram/pages/resource/detail.vue?vue&type=style&index=0&id=1c436dee&scoped=true&lang=css& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin
    if(false) { var cssReload; }
  

/***/ })

},[[76,"common/runtime","common/vendor"]]]);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/resource/detail.js.map