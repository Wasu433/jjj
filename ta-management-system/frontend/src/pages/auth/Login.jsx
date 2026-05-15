import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'

export default function Login() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const { login } = useAuth()
  const navigate = useNavigate()

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const user = await login(email, password)
      if (user.role === 'admin') navigate('/admin/dashboard')
      else if (user.role === 'teacher') navigate('/teacher/dashboard')
      else navigate('/student/dashboard')
    } catch (err) {
      setError(err.response?.data?.error || 'เกิดข้อผิดพลาด')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="bg-gray-100 flex items-center justify-center min-h-screen text-gray-800"
      style={{ backgroundImage: 'radial-gradient(#d1d5db 1px, transparent 1px)', backgroundSize: '20px 20px' }}>

      <div className="bg-white w-full max-w-[420px] p-10 rounded-2xl shadow-2xl m-5 transition-transform duration-300 hover:-translate-y-1">
        <div className="text-center mb-8">
          <i className="fas fa-university text-5xl text-indigo-600 mb-4"></i>
          <h2 className="text-3xl font-semibold text-gray-800">เข้าสู่ระบบ</h2>
          <p className="text-gray-500 text-sm mt-2">ระบบจัดการผู้ช่วยสอน (TA Management)</p>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 text-sm">
            <i className="fas fa-exclamation-circle text-lg"></i>
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className="block text-gray-700 font-medium text-sm mb-2">อีเมล</label>
            <div className="relative">
              <input type="text" value={email} onChange={e => setEmail(e.target.value)}
                className="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                placeholder="กรอกอีเมลของคุณ" required autoFocus />
              <i className="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 peer-focus:text-indigo-600 transition-colors duration-300"></i>
            </div>
          </div>

          <div>
            <label className="block text-gray-700 font-medium text-sm mb-2">รหัสผ่าน</label>
            <div className="relative">
              <input type="password" value={password} onChange={e => setPassword(e.target.value)}
                className="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                placeholder="••••••••" required />
              <i className="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 peer-focus:text-indigo-600 transition-colors duration-300"></i>
            </div>
          </div>

          <button type="submit" disabled={loading}
            className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3.5 rounded-lg transition-all duration-200 active:scale-95 shadow-md hover:shadow-lg mt-2 flex justify-center items-center gap-2 disabled:opacity-60">
            {loading ? <i className="fas fa-spinner fa-spin"></i> : <i className="fas fa-sign-in-alt"></i>}
            เข้าสู่ระบบ
          </button>

          <div className="flex justify-between items-center text-sm mt-6">
            <span className="text-gray-500">ยังไม่มีบัญชี? <Link to="/register" className="text-indigo-600 font-semibold hover:text-indigo-800 hover:underline transition-colors">ลงทะเบียน</Link></span>
          </div>
        </form>

        <div className="text-center text-gray-400 text-xs mt-8 leading-relaxed">
          © 2568 ระบบจัดการผู้ช่วยสอน (TA System)<br />
          มหาวิทยาลัยศิลปากร
        </div>
      </div>
    </div>
  )
}
