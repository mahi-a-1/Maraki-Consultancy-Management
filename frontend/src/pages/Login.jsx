import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useAuth } from '../context/AuthContext'
import api from '../api/axios'
import { BtnLoader } from '../components/Spinner'
import PasswordInput from '../components/PasswordInput'

export default function Login() {
  const { t } = useTranslation()
  const { login } = useAuth()
  const navigate  = useNavigate()

  const [form, setForm]   = useState({ email: '', password: '' })
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleLogin = async e => {
    e.preventDefault()
    setLoading(true); setError('')
    try {
      const res = await api.post('/auth/login', form)
      login(res.data.token, res.data.user)
      navigate('/')
    } catch (err) {
      setError(err.response?.data?.error || 'Login failed')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-[70vh] flex items-center justify-center px-4">
      <div className="w-full max-w-md">
        <h1 className="text-3xl font-bold text-center mb-2">{t('auth.login_title')}</h1>
        <p className="text-center text-gray-500 text-sm mb-8">Sign in to your account</p>
        {error && <div className="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm">{error}</div>}
        <form onSubmit={handleLogin} className="card p-8 space-y-5">
          <div>
            <label className="block text-sm font-medium mb-1">{t('auth.email')}</label>
            <input type="email" value={form.email}
              onChange={e => setForm({...form, email: e.target.value})}
              required className="input-field w-full" />
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
            {loading ? <BtnLoader label="Signing in..." /> : t('auth.login_btn')}
          </button>
        </form>
        <p className="text-center mt-4 text-sm text-gray-600">
          {t('auth.no_account')}{' '}
          <Link to="/register" className="text-primary-600 hover:underline">{t('auth.register_btn')}</Link>
        </p>
      </div>
    </div>
  )
}
