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
  pairHue: () => api<{ configured: boolean }>('/api/integrations/hue/pair', { method: 'POST' }),
  disconnectHue: () => fetch('/api/integrations/hue', { method: 'DELETE' }),
}

export const integrationApi = {
  weather: () => api<Record<string, unknown>>('/api/weather'),
  spotify: () => api<{ configured: boolean; playback: Record<string, unknown> | null }>('/api/integrations/spotify'),
  spotifyCommand: (command: 'play' | 'pause' | 'next' | 'previous') =>
    fetch(`/api/integrations/spotify/player/${command}`, { method: 'POST' }),
}

export interface IntegrationStatus {
  configured: boolean
  local: boolean
  status?: string
}

export const settingsApi = {
  integrations: () => api<Record<'hue' | 'tahoma' | 'netatmo' | 'spotify' | 'weather', IntegrationStatus>>('/api/integrations'),
  connectSpotifyUrl: '/api/integrations/spotify/connect',
  connectNetatmoUrl: '/api/integrations/netatmo/connect',
  pairHue: () => realHomeApi.pairHue(),
  disconnectHue: () => realHomeApi.disconnectHue(),
}
