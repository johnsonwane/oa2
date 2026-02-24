const API_BASE = 'https://oac.hahahaxinli.com/api'

export function post(path, data = {}) {
  return new Promise((resolve, reject) => {
    uni.request({
      url: `${API_BASE}${path}`,
      method: 'POST',
      data,
      header: {
        'content-type': 'application/json'
      },
      success: (res) => {
        resolve(res)
      },
      fail: (err) => {
        reject(err)
      }
    })
  })
}
