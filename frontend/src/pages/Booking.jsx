import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useSearchParams } from 'react-router-dom'
import api from '../api/axios'
import { useAuth } from '../context/AuthContext'
import { BtnLoader } from '../components/Spinner'

export default function Booking() {
  const { t, i18n } = useTranslation()
  const lang = i18n.language
  const { user } = useAuth()
  const [searchParams] = useSearchParams()

  const [services, setServices] = useState([])
  const [form, setForm] = useState({
    full_name: user?.name || '',
    email: user?.email || '',
    phone: '',
    service_id: searchParams.get('service') || '',
    appointment_date: '',
    appointment_time: '',
    notes: ''
  })
  const [success, setSuccess] = useState(false)
  const [error, setError]     = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    api.get('/services').then(r => setServices(r.data)).catch(() => {})
  }, [])

  const handleChange = e => setForm({ ...form, [e.target.name]: e.target.value })

  const handleSubmit = async e => {
    e.preventDefault()
    setLoading(true)
    setError('')
    try {
      await api.post('/appointments', form)
      setSuccess(true)
    } catch (err) {
      setError(err.response?.data?.error || 'Something went wrong')
    } finally {
      setLoading(false)
    }
  }

  if (success) {
    return (
      <div className="max-w-lg mx-auto px-4 py-24 text-center">
        <div className="text-5xl mb-4">✅</div>
        <h2 className="text-2xl font-bold text-primary-700 mb-2">{t('booking.success')}</h2>
      </div>
    )
  }

  return (
    <div className="max-w-2xl mx-auto px-4 py-16">
      <h1 className="text-3xl font-bold mb-8 text-center">{t('booking.title')}</h1>
      {error && <div className="bg-red-50 text-red-600 p-3 rounded-lg mb-4">{error}</div>}
      <form onSubmit={handleSubmit} className="card p-8 space-y-5">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label className="block text-sm font-medium mb-1">{t('booking.full_name')} *</label>
            <input name="full_name" value={form.full_name} onChange={handleChange} required className="input-field" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t('booking.email')} *</label>
            <input name="email" type="email" value={form.email} onChange={handleChange} required className="input-field" />
          </div>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label className="block text-sm font-medium mb-1">{t('booking.phone')}</label>
            <input name="phone" value={form.phone} onChange={handleChange} className="input-field" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t('booking.service')} *</label>
            <select name="service_id" value={form.service_id} onChange={handleChange} required className="input-field">
              <option value="">{t('booking.select_service')}</option>
              {services.map(s => (
                <option key={s.id} value={s.id}>{lang === 'am' ? s.title_am : s.title_en}</option>
              ))}
            </select>
          </div>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label className="block text-sm font-medium mb-1">{t('booking.date')} *</label>
            <input name="appointment_date" type="date" value={form.appointment_date} onChange={handleChange} required
              min={new Date().toISOString().split('T')[0]} className="input-field" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t('booking.time')} *</label>
            <input name="appointment_time" type="time" value={form.appointment_time} onChange={handleChange} required className="input-field" />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t('booking.notes')}</label>
          <textarea name="notes" value={form.notes} onChange={handleChange} rows={3} className="input-field" />
        </div>
        <button type="submit" disabled={loading} className="btn-primary w-full">
          {loading ? <BtnLoader label="Booking..." /> : t('booking.submit')}
        </button>
      </form>
    </div>
  )
}
