/* ============================================
   Admin Panel JS Utilities
   AJAX, Confirm Dialogs, Toast Notifications
   ============================================ */

var Admin = (function() {
  'use strict';

  // ---- Toast Notifications ----
  function toast(message, type) {
    type = type || 'info';
    var container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    var icons = {
      success: '✔',
      error: '✘',
      warning: '⚠',
      info: 'ℹ'
    };

    var el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML =
      '<span class="toast-icon">' + (icons[type] || icons.info) + '</span>' +
      '<span class="toast-msg">' + escapeHtml(message) + '</span>' +
      '<button class="toast-close" onclick="Admin.removeToast(this.parentElement)">&times;</button>';

    container.appendChild(el);

    setTimeout(function() {
      removeToast(el);
    }, 4000);
  }

  function removeToast(el) {
    if (!el || !el.parentElement) return;
    el.classList.add('removing');
    setTimeout(function() {
      if (el.parentElement) el.parentElement.removeChild(el);
    }, 300);
  }

  // ---- Confirm Dialog ----
  function confirm(message, onOk, title) {
    title = title || '确认';
    var overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.innerHTML =
      '<div class="confirm-box">' +
        '<div class="confirm-icon">⚠</div>' +
        '<h4>' + escapeHtml(title) + '</h4>' +
        '<p>' + escapeHtml(message) + '</p>' +
        '<div class="confirm-buttons">' +
          '<button class="btn btn-outline" data-action="cancel">取消</button>' +
          '<button class="btn btn-danger" data-action="ok">确认</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(overlay);

    overlay.querySelector('[data-action="cancel"]').onclick = function() {
      document.body.removeChild(overlay);
    };

    overlay.querySelector('[data-action="ok"]').onclick = function() {
      document.body.removeChild(overlay);
      if (typeof onOk === 'function') onOk();
    };

    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) document.body.removeChild(overlay);
    });
  }

  // ---- AJAX Helper ----
  function ajax(url, options) {
    options = options || {};
    var method = (options.method || 'POST').toUpperCase();
    var data = options.data || null;
    var onSuccess = options.onSuccess || null;
    var onError = options.onError || null;
    var dataType = options.dataType || 'json';

    var xhr = new XMLHttpRequest();
    xhr.open(method, url, true);

    var formData = null;
    if (data instanceof FormData) {
      formData = data;
    } else if (data && typeof data === 'object') {
      formData = new FormData();
      for (var key in data) {
        if (data.hasOwnProperty(key)) {
          formData.append(key, data[key]);
        }
      }
    } else if (typeof data === 'string') {
      formData = data;
    }

    if (formData && typeof formData === 'string') {
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr.status >= 200 && xhr.status < 300) {
        var resp;
        if (dataType === 'json') {
          try {
            resp = JSON.parse(xhr.responseText);
          } catch(e) {
            resp = { success: false, message: '无效的JSON响应' };
          }
        } else {
          resp = xhr.responseText;
        }
        if (typeof onSuccess === 'function') onSuccess(resp);
      } else {
        if (typeof onError === 'function') {
          onError(xhr.status, xhr.responseText);
        } else {
          toast('请求失败: HTTP ' + xhr.status, 'error');
        }
      }
    };

    xhr.send(formData);
  }

  // ---- GET helper for JSON API ----
  function getJSON(url, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          callback(null, JSON.parse(xhr.responseText));
        } catch(e) {
          callback(e, null);
        }
      } else {
        callback(new Error('HTTP ' + xhr.status), null);
      }
    };
    xhr.send();
  }

  // ---- CSRF Token ----
  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function appendCsrf(data) {
    var token = getCsrfToken();
    if (!token) return data;
    if (data instanceof FormData) {
      data.append('_token', token);
      return data;
    }
    if (typeof data === 'object' && data !== null) {
      data._token = token;
      return data;
    }
    return data + '&_token=' + encodeURIComponent(token);
  }

  // ---- Delete action ----
  function deleteItem(url, callback) {
    confirm('确定要删除吗？此操作不可撤销。', function() {
      ajax(url, {
        method: 'POST',
        data: { _token: getCsrfToken(), _method: 'DELETE' },
        onSuccess: function(resp) {
          if (resp.success) {
            toast(resp.message || '删除成功', 'success');
            if (typeof callback === 'function') callback(resp);
          } else {
            toast(resp.message || '删除失败', 'error');
          }
        },
        onError: function() {
          toast('请求失败', 'error');
        }
      });
    }, '确认删除');
  }

  // ---- Toggle Status ----
  function toggleStatus(url, callback) {
    ajax(url, {
      method: 'POST',
      data: { _token: getCsrfToken() },
      onSuccess: function(resp) {
        if (resp.success) {
          toast(resp.message || '状态已更新', 'success');
          if (typeof callback === 'function') callback(resp);
        } else {
          toast(resp.message || '更新失败', 'error');
        }
      }
    });
  }

  // ---- Escape HTML ----
  function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // ---- Format File Size ----
  function formatSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    var units = ['B', 'KB', 'MB', 'GB'];
    var i = 0;
    var size = parseFloat(bytes);
    while (size >= 1024 && i < units.length - 1) {
      size /= 1024;
      i++;
    }
    return size.toFixed(2) + ' ' + units[i];
  }

  // ---- File Upload Preview ----
  function initFileUpload(inputId, previewId) {
    var input = document.getElementById(inputId);
    var preview = document.getElementById(previewId);
    if (!input || !preview) return;

    input.addEventListener('change', function() {
      preview.innerHTML = '';
      var files = input.files;
      for (var i = 0; i < files.length; i++) {
        var file = files[i];
        var item = document.createElement('div');
        item.className = 'file-preview';

        if (file.type && file.type.startsWith('image/')) {
          var reader = new FileReader();
          reader.onload = (function(el) {
            return function(e) {
              var img = document.createElement('img');
              img.src = e.target.result;
              el.prepend(img);
            };
          })(item);
          reader.readAsDataURL(file);
        }

        var info = document.createElement('span');
        info.className = 'file-name';
        info.textContent = file.name + ' (' + formatSize(file.size) + ')';
        item.appendChild(info);

        preview.appendChild(item);
      }
    });
  }

  // ---- Mobile sidebar toggle ----
  function toggleSidebar() {
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    if (sidebar) sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('active');
  }

  // ---- Init common behaviors ----
  function init() {
    // Sidebar overlay click
    var overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
      overlay.addEventListener('click', toggleSidebar);
    }

    // Auto-dismiss alerts
    var alerts = document.querySelectorAll('.auto-dismiss');
    for (var i = 0; i < alerts.length; i++) {
      (function(el) {
        setTimeout(function() {
          el.style.transition = 'opacity .3s';
          el.style.opacity = '0';
          setTimeout(function() { el.remove(); }, 300);
        }, 3000);
      })(alerts[i]);
    }
  }

  // Auto-init on DOMContentLoaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  return {
    toast: toast,
    removeToast: removeToast,
    confirm: confirm,
    ajax: ajax,
    getJSON: getJSON,
    getCsrfToken: getCsrfToken,
    appendCsrf: appendCsrf,
    deleteItem: deleteItem,
    toggleStatus: toggleStatus,
    escapeHtml: escapeHtml,
    formatSize: formatSize,
    initFileUpload: initFileUpload,
    toggleSidebar: toggleSidebar
  };
})();
