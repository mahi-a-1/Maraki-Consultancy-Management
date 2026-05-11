import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import api from '../api/axios'
import { BtnLoader } from '../components/Spinner'
import PasswordInput from '../components/PasswordInput'

export default function Register() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [form, setForm]   = useState({ name: '', email: '', password: '', phone: '' })
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async e => {
    e.preventDefault()
    setLoading(true)
    setError('')
    try {
      await api.post('/auth/register', form)
      navigate('/login')
    } catch (err) {
      setError(err.response?.data?.error || 'Registration failed')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-[70vh] flex items-center justify-center px-4">
      <div className="w-full max-w-md">
        <h1 className="text-3xl font-bold text-center mb-8">{t('auth.register_title')}</h1>
        {error && <div className="bg-red-50 text-red-600 p-3 rounded-lg mb-4">{error}</div>}
        <form onSubmit={handleSubmit} className="card p-8 space-y-5">
          <div>
            <label className="block text-sm font-medium mb-1">{t('auth.name')}</label>
            <input value={form.name} onChange={e => setForm({...form, name: e.target.value})} required className="input-field" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t('auth.email')}</label>
            <input type="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})} required className="input-field" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t('auth.phone')}</label>
            <input value={form.phone} onChange={e => setForm({...form, phone: e.target.value})} className="input-field" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t('auth.password')}</label>
            <PasswordInput
              value={form.password}
              onChange={e => setForm({...form, password: e.target.value})}
              required
            />
          </div>
          <button type="submit" disabled={loading} className="btn-primary w-full">
            {loading ? <BtnLoader label="Registering..." /> : t('auth.register_btn')}
          </button>
        </form>
        <p className="text-center mt-4 text-sm text-gray-600">
          {t('auth.have_account')} <Link to="/login" className="text-primary-600 hover:underline">{t('auth.login_btn')}</Link>
        </p>
      </div>
    </div>
  )
}
