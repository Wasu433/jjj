import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import client from '../../api/client'
import Swal from 'sweetalert2'

export default function Register() {
  const [form, setForm] = useState({ name: '', email: '', password: '', confirm_password: '' })
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const navigate = useNavigate()

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await client.post('/register', form)
      await Swal.fire({ title: 'สำเร็จ!', text: 'ลงทะเบียนสำเร็จเรียบร้อย! สามารถเข้าสู่ระบบได้เลย', icon: 'success', confirmButtonColor: '#4f46e5' })
      navigate('/login')
    } catch (err) {
      setError(err.response?.data?.error || 'เกิดข้อผิดพลาด')
    } finally {
      setLoading(false)
    }
  }

  const set = (field) => (e) => setForm({ ...form, [field]: e.target.value })

  return (
    <div className="bg-gray-100 flex items-center justify-center min-h-screen"
      style={{ backgroundImage: 'radial-gradient(#d1d5db 1px, transparent 1px)', backgroundSize: '20px 20px' }}>

      <div className="bg-white w-full max-w-[440px] p-10 rounded-2xl shadow-2xl m-5">
        <div className="text-center mb-8">
          <i className="fas fa-user-plus text-5xl text-indigo-600 mb-4"></i>
          <h2 className="text-3xl font-semibold text-gray-800">ลงทะเบียน</h2>
          <p className="text-gray-500 text-sm mt-2">สร้างบัญชีนักศึกษาใหม่</p>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 text-sm">
            <i className="fas fa-exclamation-circle"></i>
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-gray-700 font-medium text-sm mb-2">ชื่อ-นามสกุล</label>
            <div className="relative">
              <input type="text" value={form.name} onChange={set('name')} required
                className="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                placeholder="ชื่อ-นามสกุล" autoFocus />
              <i className="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
          </div>

          <div>
            <label className="block text-gray-700 font-medium text-sm mb-2">อีเมลมหาวิทยาลัย</label>
            <div className="relative">
              <input type="email" value={form.email} onChange={set('email')} required
                className="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                placeholder="example@silpakorn.edu" />
              <i className="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            <p className="text-xs text-gray-400 mt-1">ต้องเป็นอีเมล @silpakorn.edu เท่านั้น</p>
          </div>

          <div>
            <label className="block text-gray-700 font-medium text-sm mb-2">รหัสผ่าน</label>
            <div className="relative">
              <input type="password" value={form.password} onChange={set('password')} required
                className="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                placeholder="อย่างน้อย 8 ตัวอักษร" />
              <i className="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
          </div>

          <div>
            <label className="block text-gray-700 font-medium text-sm mb-2">ยืนยันรหัสผ่าน</label>
            <div className="relative">
              <input type="password" value={form.confirm_password} onChange={set('confirm_password')} required
                className="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                placeholder="••••••••" />
              <i className="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
          </div>

          <button type="submit" disabled={loading}
            className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3.5 rounded-lg transition-all duration-200 active:scale-95 shadow-md hover:shadow-lg mt-2 flex justify-center items-center gap-2 disabled:opacity-60">
            {loading ? <i className="fas fa-spinner fa-spin"></i> : <i className="fas fa-user-plus"></i>}
            ลงทะเบียน
          </button>
        </form>

        <div className="text-center text-sm text-gray-500 mt-6">
          มีบัญชีแล้ว? <Link to="/login" className="text-indigo-600 font-semibold hover:underline">เข้าสู่ระบบ</Link>
        </div>
      </div>
    </div>
  )
}
