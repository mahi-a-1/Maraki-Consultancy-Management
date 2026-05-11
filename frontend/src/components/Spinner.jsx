export default function Spinner({ size = 'sm', color = 'white' }) {
  const sizes = { sm: 'w-4 h-4', md: 'w-6 h-6', lg: 'w-8 h-8' }
  const colors = {
    white:     'border-white/30 border-t-white',
    primary:   'border-primary-200 border-t-primary-600',
    secondary: 'border-secondary-200 border-t-secondary-500',
  }
  return (
    <span
      className={`inline-block rounded-full border-2 animate-spin ${sizes[size]} ${colors[color]}`}
      role="status"
      aria-label="Loading"
    />
  )
}

export function BtnLoader({ label }) {
  return (
    <span className="flex items-center justify-center gap-2">
      <Spinner size="sm" color="white" />
      <span>{label}</span>
    </span>
  )
}
