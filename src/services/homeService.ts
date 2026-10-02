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
