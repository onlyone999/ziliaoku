/**
 * 应用配置文件
 * 统一管理API地址、超时时间等配置项
 */

// API基础地址
const BASE_URL = 'http://127.0.0.1:9901'

// 请求超时时间（毫秒）
const REQUEST_TIMEOUT = 15000

// 文件下载基础地址
const FILE_DOWNLOAD_URL = 'http://127.0.0.1:9901'

// 文件上传地址
const FILE_UPLOAD_URL = 'http://127.0.0.1:9901/api/upload'

// 分页默认每页条数
const PAGE_SIZE = 20

// 应用版本号
const APP_VERSION = '1.0.0'

// VIP等级配置
const VIP_LEVELS = [
  { level: 0, name: '普通用户', color: '#999999' },
  { level: 1, name: 'VIP会员', color: '#FF6B00' },
  { level: 2, name: 'SVIP会员', color: '#E74C3C' },
  { level: 3, name: '年费会员', color: '#8E44AD' }
]

// 资源类型配置
const RESOURCE_TYPES = [
  { id: 1, name: '文档', icon: 'doc' },
  { id: 2, name: '图片', icon: 'img' },
  { id: 3, name: '视频', icon: 'video' },
  { id: 4, name: '音频', icon: 'audio' },
  { id: 5, name: '软件', icon: 'soft' },
  { id: 6, name: '其他', icon: 'other' }
]

export default {
  BASE_URL,
  REQUEST_TIMEOUT,
  FILE_DOWNLOAD_URL,
  FILE_UPLOAD_URL,
  PAGE_SIZE,
  APP_VERSION,
  VIP_LEVELS,
  RESOURCE_TYPES
}
