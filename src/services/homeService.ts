import { indoor, outdoor, rooms, spotify, upstairs, weather } from '../data/mock'
import type { Room, SensorSnapshot, SpotifySnapshot, WeatherSnapshot } from '../types/home'

export interface DashboardData {
  indoor: SensorSnapshot
  outdoor: SensorSnapshot
  upstairs: SensorSnapshot
  weather: WeatherSnapshot
  spotify: SpotifySnapshot
  rooms: Room[]
}

const delay = (ms = 120) => new Promise((resolve) => window.setTimeout(resolve, ms))

export const homeService = {
  async getDashboard(): Promise<DashboardData> {
    await delay()
    return structuredClone({ indoor, outdoor, upstairs, weather, spotify, rooms })
  },

  async getRooms(): Promise<Room[]> {
    await delay()
    return structuredClone(rooms)
  },
}

export interface ApiLight {
  id: string
  name: string
  on: boolean
  brightness: number
  status: 'online' | 'offline' | 'updating'
}

async function api<T>(path: string, options?: RequestInit): Promise<T> {
  const response = await fetch(path, {
    ...options,
    headers: { 'Content-Type': 'application/json', ...options?.headers },
  })
  if (!response.ok) throw new Error(`API ${response.status}: ${await response.text()}`)
  return response.json() as Promise<T>
}

export const realHomeApi = {
  lights: () => api<ApiLight[]>('/api/lights'),
  updateLight: (id: string, payload: { on?: boolean; brightness?: number }) =>
    api<ApiLight>(`/api/lights/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
  hueStatus: () => api<{ provider: 'hue'; configured: boolean }>('/api/integrations/hue'),
  pairHue: () => api<{ applicationKey: string; message: string }>('/api/integrations/hue/pair', { method: 'POST' }),
}

export const integrationApi = {
  weather: () => api<Record<string, unknown>>('/api/weather'),
  spotify: () => api<{ configured: boolean; playback: Record<string, unknown> | null }>('/api/media/spotify'),
  spotifyCommand: (command: 'play' | 'pause' | 'next' | 'previous') =>
    fetch(`/api/media/spotify/${command}`, { method: 'POST' }),
}
