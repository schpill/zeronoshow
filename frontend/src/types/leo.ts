export type LeoChannelType = 'telegram' | 'whatsapp' | 'sms' | 'slack' | 'discord'

export interface LeoChannelRecord {
  id: string
  business_id: string
  channel: LeoChannelType
  bot_name: string
  is_active: boolean
  external_identifier_masked: string
  created_at?: string | null
  updated_at?: string | null
}

export interface LeoAddonStatus {
  active: boolean
  stripe_item_id: string | null
}

export interface LeoMessageActivity {
  id: string
  direction: 'inbound' | 'outbound'
  intent: string | null
  response_preview: string | null
  created_at: string | null
}
