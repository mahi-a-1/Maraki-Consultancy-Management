import { Link, useNavigate, useLocation } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useAuth } from '../context/AuthContext'
import { useState } from 'react'
import i18n from '../i18n/i18n'

const navLinkClass = (isActive) =>
  `relative text-sm font-medium px-1 py-1 transition-colors duration-200 after:absolute after:bottom-0 after:left-0 after:h-0.5 after:w-full after:scale-x-0 after:bg-primary-900 after:transition-transform after:duration-200 hover:text-primary-900 hover:after:scale-x-100 ${
    isActive ? 'text-primary-900 after:scale-x-100' : 'text-primary-900'
  }`

export default function Navbar() {
  const { t } = useTranslation()
  const { user, logout } = useAuth()
  const navigate  = useNavigate()
  const location  = useLocation()
  const [menuOpen, setMenuOpen] = useState(false)

  const toggleLang = () => {
    const next = i18n.language === 'en' ? 'am' : 'en'
    i18n.changeLanguage(next)
    localStorage.setItem('lang', next)
  }

  const handleLogout = () => { logout(); navigate('/') }
  const isActive = (path) => path === '/' ? location.pathname === '/' : location.pathname.startsWith(path)

  return (
    <nav className="fixed top-3 left-4 right-4 z-50 md:left-8 md:right-8 lg:left-16 lg:right-16">
      <div className="bg-white/20 backdrop-blur-md rounded-2xl shadow-lg px-4 sm:px-6 border border-white/30">
        <div className="flex items-center justify-between h-14 gap-6">

          {/* Logo */}
          <Link to="/" className="flex items-center gap-2 flex-shrink-0">
            <img src="/images/logo.png" alt="Maraki" className="h-11 w-auto" />
            <div className="hidden sm:block">
              <span className="text-primary-900 font-bold text-lg block leading-tight">Maraki</span>
              <span className="text-primary-800 text-xs leading-tight">Nutritional Therapy</span>
            </div>
          </Link>

          {/* Center nav links */}
          <div className="hidden md:flex items-center gap-7 flex-grow justify-center">
            <Link to="/"        className={navLinkClass(isActive('/'))}>{t('nav.home')}</Link>
            <Link to="/booking" className={navLinkClass(isActive('/booking'))}>{t('nav.booking')}</Link>
          </div>

          {/* Right actions */}
          <div className="hidden md:flex items-center gap-3 flex-shrink-0">
            <button
              onClick={toggleLang}
              className="text-xs font-semibold border border-primary-900/40 rounded-lg px-3 py-1.5 text-primary-900 hover:bg-primary-900/10 transition-all duration-200">
              {i18n.language === 'en' ? 'አማ' : 'EN'}
            </button>
            {user ? (
              <button onClick={handleLogout}
                className="text-sm border border-primary-900 text-primary-900 font-medium px-4 py-1.5 rounded-lg hover:bg-primary-900 hover:text-white transition-all duration-200">
                {t('nav.logout')}
              </button>
            ) : (
              <>
                <Link to="/login"
                  className="text-sm font-medium text-primary-900 hover:text-primary-700 px-3 py-1.5 rounded-lg hover:bg-primary-900/10 transition-all">
                  {t('nav.login')}
                </Link>
                <Link to="/booking"
                  className="text-sm font-semibold bg-primary-700 text-white px-5 py-2 rounded-lg hover:bg-primary-900 shadow-sm transition-all duration-200">
                  {t('nav.booking')}
                </Link>
              </>
            )}
          </div>

          {/* Mobile hamburger */}
          <button className="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors" onClick={() => setMenuOpen(!menuOpen)}>
            <svg className="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                d={menuOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'} />
            </svg>
          </button>
        </div>
      </div>

      {/* Mobile menu */}
      {menuOpen && (
        <div className="md:hidden border-t border-gray-100 px-4 pb-4 pt-3 flex flex-col gap-1 rounded-b-2xl">
          {[
            { to: '/',        label: t('nav.home') },
            { to: '/booking', label: t('nav.booking') },
            { to: '/login',   label: t('nav.login') },
          ].map(item => (
            <Link key={item.to} to={item.to} onClick={() => setMenuOpen(false)}
              className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                isActive(item.to) ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-primary-600'
              }`}>
              {item.label}
            </Link>
          ))}
        </div>
      )}
    </nav>
  )
}
