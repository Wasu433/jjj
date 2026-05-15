import { useState, useEffect } from 'react'
import client from '../../api/client'
import Swal from 'sweetalert2'

const emptySection = { name: '', schedule_time: '', quota: 1 }
const emptyForm = { teacher_id: '', subject_id: '', title: '', description: '', status: 'open', grade_req: '', sections: [{ ...emptySection }] }

export default function ManageRecruitments() {
  const [recruitments, setRecruitments] = useState([])
  const [teachers, setTeachers] = useState([])
  const [subjects, setSubjects] = useState([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [editItem, setEditItem] = useState(null)
  const [form, setForm] = useState(emptyForm)
  const [search, setSearch] = useState('')

  useEffect(() => {
    loadAll()
  }, [])

  const loadAll = async () => {
    setLoading(true)
    const [recRes, teacherRes, subjectRes] = await Promise.all([
      client.get('/admin/recruitments').catch(() => ({ data: [] })),
      client.get('/admin/teachers').catch(() => ({ data: [] })),
      client.get('/admin/subjects').catch(() => ({ data: [] })),
    ])
    setRecruitments(recRes.data)
    setTeachers(teacherRes.data)
    setSubjects(subjectRes.data)
    setLoading(false)
  }

  const openAdd = () => {
    setEditItem(null)
    setForm({ ...emptyForm, sections: [{ ...emptySection }] })
    setShowModal(true)
  }

  const openEdit = (r) => {
    setEditItem(r)
    const sections = (r.sections || []).length > 0
      ? r.sections.map(s => ({ name: s.name, schedule_time: s.schedule_time, quota: parseInt(s.quota) || 1 }))
      : [{ ...emptySection }]
    setForm({
      teacher_id: r.teacher_id, subject_id: r.subject_id,
      title: r.title, description: r.description, status: r.status,
      grade_req: r.grade_requirement, sections,
    })
    setShowModal(true)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    const data = { ...form, teacher_id: parseInt(form.teacher_id), subject_id: parseInt(form.subject_id), sections: form.sections.filter(s => s.name.trim()) }
    try {
      if (editItem) {
        await client.put(`/admin/recruitments/${editItem.id}`, data)
      } else {
        await client.post('/admin/recruitments', data)
      }
      Swal.fire({ title: 'สำเร็จ!', icon: 'success', timer: 1500, showConfirmButton: false })
      setShowModal(false)
      loadAll()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'เกิดข้อผิดพลาด', icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const handleDelete = async (r) => {
    const result = await Swal.fire({
      title: 'ยืนยันการลบ?', text: `ลบประกาศ "${r.title}"?`, icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
      confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก',
    })
    if (!result.isConfirmed) return
    try {
      await client.delete(`/admin/recruitments/${r.id}`)
      Swal.fire({ title: 'ลบสำเร็จ!', timer: 1500, showConfirmButton: false, icon: 'success' })
      loadAll()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'ลบไม่สำเร็จ', icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const addSection = () => setForm({ ...form, sections: [...form.sections, { ...emptySection }] })
  const removeSection = (i) => setForm({ ...form, sections: form.sections.filter((_, idx) => idx !== i) })
  const updateSection = (i, field, value) => {
    const sections = [...form.sections]
    sections[i] = { ...sections[i], [field]: value }
    setForm({ ...form, sections })
  }

  const statusColor = { open: 'bg-green-100 text-green-700', closed: 'bg-gray-100 text-gray-600' }
  const filtered = recruitments.filter(r => r.title?.toLowerCase().includes(search.toLowerCase()) || r.subject_name?.toLowerCase().includes(search.toLowerCase()))

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">จัดการประกาศรับสมัคร</h1>
          <p className="text-gray-500 text-sm mt-1">ทั้งหมด {recruitments.length} ประกาศ</p>
        </div>
        <div className="flex gap-3">
          <input type="text" placeholder="ค้นหา..." value={search} onChange={e => setSearch(e.target.value)}
            className="w-48 px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
          <button onClick={openAdd}
            className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 transition-colors">
            <i className="fas fa-plus"></i> เพิ่มประกาศ
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
                  {['หัวข้อ', 'วิชา', 'อาจารย์', 'โควตา', 'สถานะ', 'วันที่', 'จัดการ'].map(h => (
                    <th key={h} className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-50">
                {filtered.map(r => (
                  <tr key={r.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-5 py-4">
                      <p className="font-medium text-gray-800 text-sm">{r.title}</p>
                      {r.grade_requirement && <p className="text-xs text-gray-400">เกรด: {r.grade_requirement}</p>}
                    </td>
                    <td className="px-5 py-4">
                      <p className="text-sm font-medium text-indigo-700">{r.subject_code}</p>
                      <p className="text-xs text-gray-400">{r.subject_name}</p>
                    </td>
                    <td className="px-5 py-4 text-sm text-gray-500">{r.teacher_name}</td>
                    <td className="px-5 py-4 text-sm text-gray-800 font-semibold">{r.quota}</td>
                    <td className="px-5 py-4">
                      <span className={`text-xs font-semibold px-2 py-1 rounded-full ${statusColor[r.status] || 'bg-gray-100 text-gray-500'}`}>
                        {r.status === 'open' ? 'เปิดรับ' : 'ปิดแล้ว'}
                      </span>
                    </td>
                    <td className="px-5 py-4 text-xs text-gray-400">{new Date(r.created_at).toLocaleDateString('th-TH')}</td>
                    <td className="px-5 py-4">
                      <div className="flex gap-2">
                        <button onClick={() => openEdit(r)} className="text-indigo-600 hover:text-indigo-800 p-1.5 hover:bg-indigo-50 rounded-lg transition-colors">
                          <i className="fas fa-edit"></i>
                        </button>
                        <button onClick={() => handleDelete(r)} className="text-red-500 hover:text-red-700 p-1.5 hover:bg-red-50 rounded-lg transition-colors">
                          <i className="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
                {filtered.length === 0 && (
                  <tr><td colSpan={7} className="text-center py-12 text-gray-400"><i className="fas fa-bullhorn text-3xl mb-2 block"></i>ไม่พบประกาศ</td></tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-4">
            <div className="p-6 border-b border-gray-100 sticky top-0 bg-white rounded-t-2xl">
              <h3 className="text-lg font-bold text-gray-800">{editItem ? 'แก้ไขประกาศ' : 'สร้างประกาศใหม่'}</h3>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">อาจารย์</label>
                <select value={form.teacher_id} onChange={e => setForm({ ...form, teacher_id: e.target.value })} required
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
                  <option value="">-- เลือกอาจารย์ --</option>
                  {teachers.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">วิชา</label>
                <select value={form.subject_id} onChange={e => setForm({ ...form, subject_id: e.target.value })} required
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
                  <option value="">-- เลือกวิชา --</option>
                  {subjects.map(s => <option key={s.id} value={s.id}>{s.code} - {s.name}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">หัวข้อประกาศ</label>
                <input value={form.title} onChange={e => setForm({ ...form, title: e.target.value })} required
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                <textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} rows={3}
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 resize-none" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                  <select value={form.status} onChange={e => setForm({ ...form, status: e.target.value })}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
                    <option value="open">เปิดรับสมัคร</option>
                    <option value="closed">ปิดรับสมัคร</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">เกรดขั้นต่ำ</label>
                  <input value={form.grade_req} onChange={e => setForm({ ...form, grade_req: e.target.value })}
                    placeholder="เช่น B, B+"
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
                </div>
              </div>

              {/* Sections */}
              <div>
                <div className="flex justify-between items-center mb-2">
                  <label className="text-sm font-medium text-gray-700">กลุ่มเรียน (Sections)</label>
                  <button type="button" onClick={addSection}
                    className="text-xs bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1 rounded-lg transition-colors">
                    <i className="fas fa-plus mr-1"></i> เพิ่มกลุ่ม
                  </button>
                </div>
                <div className="space-y-2">
                  {form.sections.map((sec, i) => (
                    <div key={i} className="flex gap-2 items-center p-3 bg-gray-50 rounded-xl">
                      <input value={sec.name} onChange={e => updateSection(i, 'name', e.target.value)}
                        placeholder="ชื่อกลุ่ม" className="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500" />
                      <input value={sec.schedule_time} onChange={e => updateSection(i, 'schedule_time', e.target.value)}
                        placeholder="เวลา เช่น พุธ 10:00-12:00" className="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500" />
                      <input type="number" value={sec.quota} onChange={e => updateSection(i, 'quota', parseInt(e.target.value) || 1)}
                        min={1} placeholder="โควตา" className="w-20 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500" />
                      {form.sections.length > 1 && (
                        <button type="button" onClick={() => removeSection(i)} className="text-red-400 hover:text-red-600 p-1">
                          <i className="fas fa-times"></i>
                        </button>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              <div className="flex gap-3 pt-2">
                <button type="button" onClick={() => setShowModal(false)}
                  className="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50 text-sm transition-colors">ยกเลิก</button>
                <button type="submit"
                  className="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
                  {editItem ? 'บันทึก' : 'สร้างประกาศ'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
