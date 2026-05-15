import { useState, useEffect } from 'react'
import client from '../../api/client'
import Swal from 'sweetalert2'

const emptyForm = { code: '', course_id: '', name: '', term: '1', year: '', teacher_id: null }

export default function ManageSubjects() {
  const [subjects, setSubjects] = useState([])
  const [teachers, setTeachers] = useState([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [editSubject, setEditSubject] = useState(null)
  const [form, setForm] = useState(emptyForm)
  const [search, setSearch] = useState('')
  const [syncing, setSyncing] = useState(false)

  useEffect(() => {
    loadSubjects()
    client.get('/admin/teachers').then(r => setTeachers(r.data)).catch(() => {})
  }, [])

  const loadSubjects = async () => {
    setLoading(true)
    try {
      const res = await client.get('/admin/subjects')
      setSubjects(res.data)
    } finally {
      setLoading(false)
    }
  }

  const openAdd = () => {
    setEditSubject(null)
    const currentYear = new Date().getFullYear() + 543
    setForm({ ...emptyForm, year: String(currentYear) })
    setShowModal(true)
  }

  const openEdit = (s) => {
    setEditSubject(s)
    const parts = s.semester?.split('/') || ['1', '']
    setForm({ code: s.code, course_id: s.course_id, name: s.name, term: parts[0] || '1', year: parts[1] || '', teacher_id: s.teacher_id || null })
    setShowModal(true)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    try {
      const data = { ...form, teacher_id: form.teacher_id ? parseInt(form.teacher_id) : null }
      if (editSubject) {
        await client.put(`/admin/subjects/${editSubject.id}`, data)
      } else {
        await client.post('/admin/subjects', data)
      }
      Swal.fire({ title: 'สำเร็จ!', icon: 'success', timer: 1500, showConfirmButton: false })
      setShowModal(false)
      loadSubjects()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'เกิดข้อผิดพลาด', icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const handleDelete = async (s) => {
    const result = await Swal.fire({
      title: 'ยืนยันการลบ?', text: `ลบรายวิชา "${s.name}"?`, icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
      confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก',
    })
    if (!result.isConfirmed) return
    try {
      await client.delete(`/admin/subjects/${s.id}`)
      Swal.fire({ title: 'ลบสำเร็จ!', timer: 1500, showConfirmButton: false, icon: 'success' })
      loadSubjects()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'ลบไม่สำเร็จ', icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const handleSync = async () => {
    setSyncing(true)
    try {
      const res = await client.post('/admin/sync-subjects')
      Swal.fire({ title: 'ซิงค์สำเร็จ!', text: res.data.message, icon: 'success', confirmButtonColor: '#4f46e5' })
      loadSubjects()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'ซิงค์ไม่สำเร็จ', icon: 'error', confirmButtonColor: '#4f46e5' })
    } finally {
      setSyncing(false)
    }
  }

  const filtered = subjects.filter(s => s.name.toLowerCase().includes(search.toLowerCase()) || s.code.toLowerCase().includes(search.toLowerCase()))

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">จัดการรายวิชา</h1>
          <p className="text-gray-500 text-sm mt-1">ทั้งหมด {subjects.length} วิชา</p>
        </div>
        <div className="flex gap-3 flex-wrap">
          <input type="text" placeholder="ค้นหาวิชา..." value={search} onChange={e => setSearch(e.target.value)}
            className="w-48 px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />
          <button onClick={handleSync} disabled={syncing}
            className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 transition-colors disabled:opacity-60">
            {syncing ? <i className="fas fa-spinner fa-spin"></i> : <i className="fas fa-sync"></i>} ซิงค์วิชา
          </button>
          <button onClick={openAdd}
            className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 transition-colors">
            <i className="fas fa-plus"></i> เพิ่มวิชา
          </button>
        </div>
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-20"><i className="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-100">
              <thead className="bg-gray-50">
                <tr>
                  {['รหัสวิชา', 'ชื่อวิชา', 'ภาคการศึกษา', 'อาจารย์ผู้สอน', 'จัดการ'].map(h => (
                    <th key={h} className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-50">
                {filtered.map(s => (
                  <tr key={s.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <div>
                        <span className="font-mono text-sm font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{s.code}</span>
                        {s.revisioncode && <span className="text-xs text-gray-400 ml-2">{s.revisioncode}</span>}
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm font-medium text-gray-800">{s.name}</td>
                    <td className="px-6 py-4 text-sm text-gray-500">{s.semester || '-'}</td>
                    <td className="px-6 py-4 text-sm text-gray-500">{s.teacher_name || '-'}</td>
                    <td className="px-6 py-4">
                      <div className="flex gap-2">
                        <button onClick={() => openEdit(s)} className="text-indigo-600 hover:text-indigo-800 p-1.5 hover:bg-indigo-50 rounded-lg transition-colors">
                          <i className="fas fa-edit"></i>
                        </button>
                        <button onClick={() => handleDelete(s)} className="text-red-500 hover:text-red-700 p-1.5 hover:bg-red-50 rounded-lg transition-colors">
                          <i className="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
                {filtered.length === 0 && (
                  <tr><td colSpan={5} className="text-center py-12 text-gray-400"><i className="fas fa-book text-3xl mb-2 block"></i>ไม่พบรายวิชา</td></tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="p-6 border-b border-gray-100">
              <h3 className="text-lg font-bold text-gray-800">{editSubject ? 'แก้ไขรายวิชา' : 'เพิ่มรายวิชาใหม่'}</h3>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">รหัสวิชา</label>
                  <input value={form.code} onChange={e => setForm({ ...form, code: e.target.value })} required
                    placeholder="CS001"
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 text-sm" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Course ID</label>
                  <input value={form.course_id} onChange={e => setForm({ ...form, course_id: e.target.value })}
                    placeholder="ไม่บังคับ"
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 text-sm" />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">ชื่อวิชา</label>
                <input value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} required
                  placeholder="ชื่อรายวิชา"
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 text-sm" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">ภาคการศึกษา</label>
                  <select value={form.term} onChange={e => setForm({ ...form, term: e.target.value })}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 text-sm bg-white">
                    <option value="1">ภาค 1</option>
                    <option value="2">ภาค 2</option>
                    <option value="3">ภาค 3</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">ปีการศึกษา (พ.ศ.)</label>
                  <input value={form.year} onChange={e => setForm({ ...form, year: e.target.value })} required
                    placeholder="2567"
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 text-sm" />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">อาจารย์ผู้สอน</label>
                <select value={form.teacher_id || ''} onChange={e => setForm({ ...form, teacher_id: e.target.value || null })}
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 text-sm bg-white">
                  <option value="">-- ไม่ระบุ --</option>
                  {teachers.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                </select>
              </div>
              <div className="flex gap-3 pt-2">
                <button type="button" onClick={() => setShowModal(false)}
                  className="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50 text-sm transition-colors">ยกเลิก</button>
                <button type="submit"
                  className="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
                  {editSubject ? 'บันทึก' : 'เพิ่มวิชา'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
