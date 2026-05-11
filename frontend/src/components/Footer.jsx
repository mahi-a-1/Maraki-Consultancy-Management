import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

export default function Footer() {
  const { t } = useTranslation()
  return (
    <footer className="bg-primary-900 text-white mt-16">
      <div className="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div>
          <img src="/images/logo.png" alt="Maraki Logo" className="h-16 w-auto mb-3" />
          <p className="text-secondary-300 text-sm mb-4">{t('footer.tagline')}</p>
        </div>
        <div>
          <h4 className="font-semibold mb-3 text-secondary-400">Quick Links</h4>
          <ul className="space-y-2 text-primary-200 text-sm">
            <li><Link to="/"        className="hover:text-secondary-300 transition-colors">{t('nav.home')}</Link></li>
            <li><Link to="/booking" className="hover:text-secondary-300 transition-colors">{t('nav.booking')}</Link></li>
          </ul>
        </div>
        <div>
          <h4 className="font-semibold mb-3 text-secondary-400">Contact</h4>
          <p className="text-primary-200 text-sm">info@maraki.com</p>
          <p className="text-primary-200 text-sm mt-1">Addis Ababa, Ethiopia</p>
        </div>
      </div>
      <div className="border-t border-primary-800 text-center py-4 text-primary-400 text-sm">
        © {new Date().getFullYear()} Maraki. {t('footer.rights')}.
      </div>
    </footer>
  )
}
