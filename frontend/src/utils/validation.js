// Validation rules — MNTHC-57

export const rules = {
  fullName: (v) => {
    if (!v || v.trim().length < 2) return 'Full name must be at least 2 characters'
    if (v.trim().length > 100)     return 'Full name must be under 100 characters'
    return null
  },

  email: (v) => {
    if (!v) return 'Email is required'
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Enter a valid email address'
    return null
  },

  password: (v) => {
    if (!v)          return 'Password is required'
    if (v.length < 8) return 'Password must be at least 8 characters'
    if (!/[A-Z]/.test(v)) return 'Password must contain at least one uppercase letter'
    if (!/[0-9]/.test(v)) return 'Password must contain at least one number'
    return null
  },

  phone: (v) => {
    if (!v) return null // optional
    if (!/^09[0-9]{8}$/.test(v)) return 'Phone must be in format 09xxxxxxxx'
    return null
  },

  date: (v) => {
    if (!v) return 'Date is required'
    if (new Date(v) < new Date().setHours(0, 0, 0, 0)) return 'Date cannot be in the past'
    return null
  },

  time: (v) => {
    if (!v) return 'Time is required'
    const [h] = v.split(':').map(Number)
    if (h < 8 || h >= 18) return 'Appointment time must be between 08:00 and 18:00'
    return null
  },

  required: (v, label = 'This field') => {
    if (!v || v.toString().trim() === '') return `${label} is required`
    return null
  },
}

/**
 * Validate a form object against a schema.
 * schema: { fieldName: [ruleFn, ...] }
 * Returns { isValid, errors }
 */
export function validate(data, schema) {
  const errors = {}
  for (const [field, fieldRules] of Object.entries(schema)) {
    for (const rule of fieldRules) {
      const error = rule(data[field])
      if (error) {
        errors[field] = error
        break
      }
    }
  }
  return { isValid: Object.keys(errors).length === 0, errors }
}
