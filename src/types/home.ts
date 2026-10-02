export type SensorStatus = 'excellent' | 'good' | 'warning'

export interface SensorSnapshot {
  room: string
  temperature: number
  humidity: number
  co2?: number
  pressure?: number
  status: SensorStatus
}

export interface WeatherHour {
  time: string
  temperature: number
  condition: 'sunny' | 'cloudy' | 'rain'
}

export interface WeatherSnapshot {
  temperature: number
  apparentTemperature: number
  label: string
  min: number
  max: number
  rainProbability: number
  hourly: WeatherHour[]
}

export interface SpotifySnapshot {
  isPlaying: boolean
  title: string
  artist: string
  album: string
  progressMs: number
  durationMs: number
  coverUrl: string
  connected?: boolean
  deviceName?: string
  deviceType?: string
  volumePercent?: number | null
}

export type DeviceStatus = 'online' | 'offline' | 'updating'

export interface Light {
  id: string
  name: string
  on: boolean
  brightness: number
  status?: DeviceStatus
  capabilities?: {
    dimming: boolean
    colorTemperature: boolean
    color: boolean
    gradient: boolean
  }
  colorTemperature?: number | null
  colorTemperatureMin?: number | null
  colorTemperatureMax?: number | null
  color?: { x: number; y: number } | null
}

export interface Shutter {
  id: string
  name: string
  position: number
  status?: DeviceStatus
}

export interface Room {
  id: string
  name: string
  lights: Light[]
  shutters: Shutter[]
}
