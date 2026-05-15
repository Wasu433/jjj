import { useState } from 'react'
import client from '../../api/client'
import Swal from 'sweetalert2'

export default function ChangePassword() {
  const [form, setForm] = useState({ current_password: '', new_password: '', confirm_password: '' })
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    try {
      const res = await client.post('/change-password', form)
      Swal.fire({ title: 'สำเร็จ!', text: res.data.message, icon: 'success', confirmButtonColor: '#4f46e5' })
      setForm({ current_password: '', new_password: '', confirm_password: '' })
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'เกิดข้อผิดพลาด', icon: 'error', confirmButtonColor: '#4f46e5' })
    } finally {
      setLoading(false)
    }
  }

  const set = (f) => (e) => setForm({ ...form, [f]: e.target.value })

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="max-w-lg mx-auto">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
          <div className="flex items-center gap-3 mb-6">
            <div className="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center text-xl">
              <i className="fas fa-key"></i>
            </div>
            <div>
              <h1 className="text-xl font-bold text-gray-800">เปลี่ยนรหัสผ่าน</h1>
              <p className="text-gray-500 text-sm">อัปเดตรหัสผ่านของบัญชีคุณ</p>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="space-y-5">
            {[
              { label: 'รหัสผ่านปัจจุบัน', field: 'current_password', placeholder: 'กรอกรหัสผ่านปัจจุบัน' },
              { label: 'รหัสผ่านใหม่', field: 'new_password', placeholder: 'อย่างน้อย 8 ตัวอักษร' },
              { label: 'ยืนยันรหัสผ่านใหม่', field: 'confirm_password', placeholder: 'กรอกรหัสผ่านใหม่อีกครั้ง' },
            ].map(({ label, field, placeholder }) => (
              <div key={field}>
                <label className="block text-sm font-medium text-gray-700 mb-2">{label}</label>
                <div className="relative">
                  <input type="password" value={form[field]} onChange={set(field)} required
                    placeholder={placeholder}
                    className="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all" />
                  <i className="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
              </div>
            ))}

            <button type="submit" disabled={loading}
              className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3.5 rounded-lg transition-all duration-200 flex justify-center items-center gap-2 disabled:opacity-60">
              {loading ? <i className="fas fa-spinner fa-spin"></i> : <i className="fas fa-save"></i>}
              บันทึกรหัสผ่านใหม่
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
