const KEY = 'impactrace-offline-queue-v1'

export function readOfflineQueue() {
  try {
    const raw = localStorage.getItem(KEY)
    return raw ? JSON.parse(raw) : []
  } catch (error) {
    return []
  }
}

export function writeOfflineQueue(queue) {
  localStorage.setItem(KEY, JSON.stringify(queue))
}

function toDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result)
    reader.onerror = () => reject(new Error('Unable to read file for offline queue.'))
    reader.readAsDataURL(file)
  })
}

export async function queueSubmission(payload) {
  const queue = readOfflineQueue()
  const files = []

  const appendFiles = async (key, value) => {
    if (!value) return
    if (Array.isArray(value)) {
      for (const item of value) {
        if (item) {
          files.push({
            key: `${key}[]`,
            name: item.name || `${key}.jpg`,
            type: item.type || 'image/jpeg',
            dataUrl: await toDataUrl(item),
          })
        }
      }
      return
    }

    files.push({
      key,
      name: value.name || `${key}.jpg`,
      type: value.type || 'image/jpeg',
      dataUrl: await toDataUrl(value),
    })
  }

  for (const [key, value] of Object.entries(payload.files || {})) {
    await appendFiles(key, value)
  }

  queue.push({
    id: crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
    createdAt: new Date().toISOString(),
    payload: { ...payload, files: undefined },
    files,
  })

  writeOfflineQueue(queue)
  return queue.length
}

export function availableOfflineQueue() {
  return readOfflineQueue().length > 0
}

function dataURLToBlob(dataUrl) {
  const [meta, body] = dataUrl.split(',')
  const mime = meta.match(/data:(.*?);base64/)?.[1] || 'application/octet-stream'
  const binary = atob(body)
  const array = new Uint8Array(binary.length)
  for (let i = 0; i < binary.length; i++) {
    array[i] = binary.charCodeAt(i)
  }
  return new Blob([array], { type: mime })
}

export function rebuildFormDataFromQueue(item) {
  const formData = new FormData()

  Object.entries(item.payload || {}).forEach(([key, value]) => {
    if (value === undefined || value === null) return
    if (typeof value === 'object' && !(value instanceof Date)) {
      if (Array.isArray(value)) {
        value.forEach((entry) => formData.append(`${key}[]`, entry))
      } else {
        formData.append(key, JSON.stringify(value))
      }
      return
    }
    formData.append(key, value)
  })

  ;(item.files || []).forEach(({ key, name, dataUrl }) => {
    if (!dataUrl) return
    const blob = dataURLToBlob(dataUrl)
    const file = new File([blob], name, { type: blob.type || 'application/octet-stream' })
    formData.append(key, file, name)
  })

  return formData
}
