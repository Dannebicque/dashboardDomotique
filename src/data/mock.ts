import type { Room, SensorSnapshot, SpotifySnapshot, WeatherSnapshot } from '../types/home'

export const indoor: SensorSnapshot = {
  room: 'Salon',
  temperature: 21.4,
  humidity: 48,
  co2: 612,
  pressure: 1018,
  status: 'excellent',
}

export const outdoor: SensorSnapshot = {
  room: 'Extérieur',
  temperature: 14.8,
  humidity: 76,
  status: 'good',
}

export const weather: WeatherSnapshot = {
  temperature: 17,
  apparentTemperature: 16,
  label: 'Partiellement nuageux',
  min: 12,
  max: 19,
  rainProbability: 30,
  hourly: [
    { time: '12h', temperature: 16, condition: 'cloudy' },
    { time: '15h', temperature: 18, condition: 'sunny' },
    { time: '18h', temperature: 17, condition: 'cloudy' },
    { time: '21h', temperature: 14, condition: 'rain' },
  ],
}

export const spotify: SpotifySnapshot = {
  isPlaying: true,
  title: 'The Adults Are Talking',
  artist: 'The Strokes',
  album: 'The New Abnormal',
  progressMs: 132000,
  durationMs: 309000,
  coverUrl: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=500&q=80',
}

export const rooms: Room[] = [
  {
    id: 'salon',
    name: 'Salon',
    lights: [
      { id: 'ceiling', name: 'Plafond', on: true, brightness: 65 },
      { id: 'floor', name: 'Lampadaire', on: true, brightness: 82 },
      { id: 'tv', name: 'TV', on: false, brightness: 0 },
    ],
    shutters: [
      { id: 'bay', name: 'Baie vitrée', position: 35 },
      { id: 'window', name: 'Fenêtre', position: 0 },
    ],
  },
  {
    id: 'cuisine',
    name: 'Cuisine',
    lights: [
      { id: 'spots', name: 'Spots', on: false, brightness: 70 },
      { id: 'worktop', name: 'Plan de travail', on: true, brightness: 55 },
    ],
    shutters: [{ id: 'kitchen-window', name: 'Fenêtre', position: 100 }],
  },
  {
    id: 'bureau',
    name: 'Bureau',
    lights: [
      { id: 'desk', name: 'Bureau', on: true, brightness: 45 },
      { id: 'ambient', name: 'Ambiance', on: false, brightness: 20 },
    ],
    shutters: [{ id: 'office-window', name: 'Fenêtre', position: 70 }],
  },
]
