import { useState, useEffect } from 'react'
import client from '../../api/client'
import Swal from 'sweetalert2'
import { useAuth } from '../../context/AuthContext'

export default function Profile() {
  const { user, setUser } = useAuth()
  const [form, setForm] = useState({ name: '', student_id: '' })
  const [resumeFile, setResumeFile] = useState(null)
  const [profile, setProfile] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)

  useEffect(() => { loadProfile() }, [])

  const loadProfile = async () => {
    setLoading(true)
    try {
      const res = await client.get('/student/profile')
      setProfile(res.data)
      setForm({ name: res.data.name || '', student_id: res.data.student_id || '' })
    } finally {
      setLoading(false)
    }
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    try {
      const fd = new FormData()
      fd.append('name', form.name)
      fd.append('student_id', form.student_id)
      if (resumeFile) fd.append('resume', resumeFile)

      const res = await client.put('/student/profile', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      Swal.fire({ title: 'บันทึกสำเร็จ!', icon: 'success', timer: 1500, showConfirmButton: false })
      if (res.data?.user) setUser(res.data.user)
      loadProfile()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'เกิดข้อผิดพลาด', icon: 'error', confirmButtonColor: '#4f46e5' })
    } finally {
      setSaving(false)
    }
  }

  if (loading) return (
    <div className="flex justify-center py-20"><i className="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>
  )

  return (
    <div className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">โปรไฟล์ของฉัน</h1>
        <p className="text-gray-500 text-sm mt-1">ข้อมูลส่วนตัวและการตั้งค่าบัญชี</p>
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div className="flex flex-col items-center mb-8">
          <div className="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-3xl font-bold mb-3">
            {profile?.name?.[0] || '?'}
          </div>
          <h2 className="text-xl font-bold text-gray-800">{profile?.name}</h2>
          <p className="text-gray-400 text-sm">{profile?.email}</p>
          <span className="mt-2 text-xs bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-medium">
            <i className="fas fa-user-graduate mr-1"></i>นักศึกษา
          </span>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
            <input value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} required
              className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
            <input value={profile?.email || ''} disabled
              className="w-full px-4 py-2.5 border border-gray-100 rounded-xl text-sm bg-gray-50 text-gray-400 cursor-not-allowed" />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">รหัสนักศึกษา</label>
            <input value={form.student_id} onChange={e => setForm({ ...form, student_id: e.target.value })}
              placeholder="เช่น 6701234567"
              className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">เรซูเม่ / Portfolio</label>
            {profile?.resume_file && (
              <div className="mb-2 flex items-center gap-2">
                <a href={`/uploads/${profile.resume_file.replace('uploads/', '')}`} target="_blank" rel="noopener noreferrer"
                  className="text-sm text-indigo-600 hover:underline flex items-center gap-1">
                  <i className="fas fa-file-pdf"></i>
                  <span>ไฟล์ปัจจุบัน</span>
                </a>
                <span className="text-xs text-gray-400">(อัปโหลดไฟล์ใหม่เพื่อแทนที่)</span>
              </div>
            )}
            <input type="file" accept=".pdf,.doc,.docx"
              onChange={e => setResumeFile(e.target.files[0])}
              className="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
            <p className="text-xs text-gray-400 mt-1">รองรับไฟล์ PDF, DOC, DOCX</p>
          </div>

          <div className="pt-2">
            <button type="submit" disabled={saving}
              className="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
              {saving ? <><i className="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...</> : 'บันทึกข้อมูล'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
