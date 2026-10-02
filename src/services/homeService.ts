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

export interface ClimateSensor {
  id: string
  name: string
  kind: 'indoor' | 'outdoor' | 'wind' | 'rain'
  moduleType: string
  reachable: boolean
  lastSeen: number | null
  batteryPercent: number | null
  temperature: number | null
  humidity: number | null
  co2: number | null
  noise: number | null
  pressure: number | null
  temperatureTrend: string | null
  pressureTrend: string | null
  minTemperature: number | null
  maxTemperature: number | null
  windStrength: number | null
  windAngle: number | null
  gustStrength: number | null
  gustAngle: number | null
  rain: number | null
  rain24h: number | null
}

interface DashboardApiResponse {
  weather: { available: boolean; temperature?: number; apparentTemperature?: number; min?: number; max?: number; rainProbability?: number; hourly?: Array<{ time: string; temperature: number; rainProbability: number }> }
  sensors: { available: boolean; items?: ClimateSensor[] }
  spotify: { available: boolean; isPlaying?: boolean; device?: { name: string; type: string; volumePercent: number | null } | null; track?: { title: string; artist: string; album: string; coverUrl: string; progressMs: number; durationMs: number } | null }
}

async function api<T>(path: string, options?: RequestInit): Promise<T> {
  const response = await fetch(path, { ...options, headers: { 'Content-Type': 'application/json', ...options?.headers } })
  if (!response.ok) throw new Error(`API ${response.status}: ${await response.text()}`)
  if (response.status === 204) return undefined as T
  return response.json() as Promise<T>
}

function mapDashboard(raw: DashboardApiResponse): DashboardData {
  const indoorSensors = raw.sensors.available ? raw.sensors.items?.filter((sensor) => sensor.kind === 'indoor' && sensor.temperature !== null) ?? [] : []
  const outdoorSensor = raw.sensors.available ? raw.sensors.items?.find((sensor) => sensor.kind === 'outdoor' && sensor.temperature !== null) : undefined
  const mainIndoor = indoorSensors.find((sensor) => sensor.name.toLowerCase() === 'salon') ?? indoorSensors[0]
  const floorSensor = indoorSensors.find((sensor) => sensor.name.toLowerCase().includes('etage') || sensor.name.toLowerCase().includes('étage')) ?? indoorSensors.find((sensor) => sensor.id !== mainIndoor?.id)

  return {
    indoor: mainIndoor ? { room: mainIndoor.name, temperature: mainIndoor.temperature, humidity: mainIndoor.humidity ?? indoor.humidity, co2: mainIndoor.co2 ?? undefined, pressure: mainIndoor.pressure ?? undefined, status: 'good' } : structuredClone(indoor),
    upstairs: floorSensor ? { room: floorSensor.name, temperature: floorSensor.temperature, humidity: floorSensor.humidity ?? upstairs.humidity, status: 'good' } : structuredClone(upstairs),
    outdoor: outdoorSensor ? { room: outdoorSensor.name, temperature: outdoorSensor.temperature, humidity: outdoorSensor.humidity ?? outdoor.humidity, status: 'good' } : structuredClone(outdoor),
    weather: raw.weather.available ? {
      temperature: raw.weather.temperature ?? weather.temperature,
      apparentTemperature: raw.weather.apparentTemperature ?? weather.apparentTemperature,
      label: 'Prévisions locales',
      min: raw.weather.min ?? weather.min,
      max: raw.weather.max ?? weather.max,
      rainProbability: raw.weather.rainProbability ?? weather.rainProbability,
      hourly: (raw.weather.hourly ?? []).map((hour) => ({ time: hour.time, temperature: hour.temperature, condition: hour.rainProbability > 50 ? 'rain' : 'cloudy' as const })),
    } : structuredClone(weather),
    spotify: raw.spotify.available && raw.spotify.track
      ? { connected: true, isPlaying: raw.spotify.isPlaying ?? false, deviceName: raw.spotify.device?.name, deviceType: raw.spotify.device?.type, volumePercent: raw.spotify.device?.volumePercent, ...raw.spotify.track }
      : { ...structuredClone(spotify), connected: raw.spotify.available, isPlaying: false },
    rooms: structuredClone(rooms),
  }
}

export const homeService = {
  async getDashboard(): Promise<DashboardData> {
    try { return mapDashboard(await api<DashboardApiResponse>('/api/dashboard')) }
    catch { return structuredClone({ indoor, outdoor, upstairs, weather, spotify, rooms }) }
  },
  async getClimate(): Promise<{ available: boolean; items: ClimateSensor[] }> {
    try {
      const raw = await api<DashboardApiResponse>('/api/dashboard')
      return { available: raw.sensors.available, items: raw.sensors.items ?? [] }
    } catch {
      return { available: false, items: [] }
    }
  },
  async getRooms(): Promise<Room[]> {
    try { return await api<Room[]>('/api/rooms') }
    catch { return structuredClone(rooms) }
  },
}

export interface ApiLight {
  id: string
  name: string
  on: boolean
  brightness: number
  status: 'online' | 'offline' | 'updating'
  capabilities?: { dimming: boolean; colorTemperature: boolean; color: boolean; gradient: boolean }
  colorTemperature?: number | null
  colorTemperatureMin?: number | null
  colorTemperatureMax?: number | null
  color?: { x: number; y: number } | null
}

export interface HueScene {
  id: string
  name: string
  roomId: string | null
  status: string
}

export const realHomeApi = {
  lights: () => api<ApiLight[]>('/api/lights'),
  rooms: () => api<Room[]>('/api/rooms'),
  scenes: () => api<HueScene[]>('/api/integrations/hue/scenes'),
  recallScene: (id: string) => api<void>(`/api/integrations/hue/scenes/${id}/recall`, { method: 'POST' }),
  updateLight: (id: string, payload: { on?: boolean; brightness?: number; colorTemperature?: number; color?: { x: number; y: number } }) => api<ApiLight>(`/api/lights/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
  hueStatus: () => api<{ provider: 'hue'; configured: boolean }>('/api/integrations/hue'),
  pairHue: () => api<{ configured: boolean }>('/api/integrations/hue/pair', { method: 'POST' }),
  configureHue: (applicationKey: string) => api<{ configured: boolean }>('/api/integrations/hue', { method: 'PUT', body: JSON.stringify({ applicationKey }) }),
  disconnectHue: () => api<void>('/api/integrations/hue', { method: 'DELETE' }),
}

export interface SpotifyTrackApi {
  name: string
  duration_ms: number
  artists?: Array<{ name: string }>
  album?: { name?: string; images?: Array<{ url: string }> }
}

export interface SpotifyPlayback {
  is_playing: boolean
  progress_ms: number
  device?: { id?: string; name?: string; type?: string; volume_percent?: number | null }
  item?: SpotifyTrackApi | null
}

export interface SpotifyQueue {
  currently_playing?: SpotifyTrackApi | null
  queue?: SpotifyTrackApi[]
}

export const integrationApi = {
  weather: () => api<Record<string, unknown>>('/api/weather'),
  spotify: () => api<{ configured: boolean; playback: SpotifyPlayback | null }>('/api/integrations/spotify'),
  spotifyQueue: () => api<SpotifyQueue>('/api/integrations/spotify/queue'),
  spotifyCommand: (command: 'play' | 'pause' | 'next' | 'previous') => api<void>(`/api/integrations/spotify/player/${command}`, { method: 'POST' }),
  spotifyDevices: () => api<{ devices: Array<{ id: string; name: string; type: string; is_active: boolean; volume_percent: number | null }> }>('/api/integrations/spotify/devices'),
  spotifyVolume: (volume: number) => api<void>('/api/integrations/spotify/volume', { method: 'PUT', body: JSON.stringify({ volume }) }),
  spotifyShuffle: (enabled: boolean) => api<void>('/api/integrations/spotify/shuffle', { method: 'PUT', body: JSON.stringify({ enabled }) }),
  spotifyRepeat: (state: 'off' | 'context' | 'track') => api<void>('/api/integrations/spotify/repeat', { method: 'PUT', body: JSON.stringify({ state }) }),
  spotifyTransfer: (deviceId: string) => api<void>('/api/integrations/spotify/device', { method: 'PUT', body: JSON.stringify({ deviceId }) }),
}

export interface IntegrationStatus { configured: boolean; local: boolean; status?: string }

export const settingsApi = {
  integrations: () => api<Record<'hue' | 'tahoma' | 'netatmo' | 'spotify' | 'weather', IntegrationStatus>>('/api/integrations'),
  connectSpotifyUrl: '/api/integrations/spotify/connect',
  connectNetatmoUrl: '/api/integrations/netatmo/connect',
  pairHue: () => realHomeApi.pairHue(),
  configureHue: (applicationKey: string) => realHomeApi.configureHue(applicationKey),
  disconnectHue: () => realHomeApi.disconnectHue(),
  disconnectSpotify: () => api<void>('/api/integrations/spotify', { method: 'DELETE' }),
  disconnectNetatmo: () => api<void>('/api/integrations/netatmo', { method: 'DELETE' }),
  configureTahoma: (token: string) => api<{ configured: boolean }>('/api/integrations/tahoma', { method: 'PUT', body: JSON.stringify({ token }) }),
  disconnectTahoma: () => api<void>('/api/integrations/tahoma', { method: 'DELETE' }),
}
