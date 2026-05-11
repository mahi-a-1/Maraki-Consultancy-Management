// Session management — MNTHC-36
import { createContext, useContext, useState, useEffect } from 'react'

const AuthContext = createContext(null)

// Token expiry duration: 24 hours in ms
const SESSION_DURATION = 24 * 60 * 60 * 1000

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => JSON.parse(localStorage.getItem('user') || 'null'))

  // On mount, check if session has expired
  useEffect(() => {
    const loginTime = localStorage.getItem('login_time')
    if (loginTime && Date.now() - parseInt(loginTime) > SESSION_DURATION) {
      logout()
    }
  }, [])

  const login = (token, userData) => {
    localStorage.setItem('token', token)
    localStorage.setItem('user', JSON.stringify(userData))
    localStorage.setItem('login_time', Date.now().toString())
    setUser(userData)
  }

  const logout = () => {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('login_time')
    setUser(null)
  }

  // Check if user is authenticated
  const isAuthenticated = () => !!localStorage.getItem('token')

  return (
    <AuthContext.Provider value={{ user, login, logout, isAuthenticated }}>
      {children}
    </AuthContext.Provider>
  )
}

export const useAuth = () => useContext(AuthContext)
