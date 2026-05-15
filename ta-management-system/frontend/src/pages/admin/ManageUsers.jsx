import { useState, useEffect } from 'react'
import client from '../../api/client'
import Swal from 'sweetalert2'

const emptyForm = { name: '', email: '', role: 'student', password: '' }

export default function ManageUsers() {
  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [editUser, setEditUser] = useState(null)
  const [form, setForm] = useState(emptyForm)
  const [search, setSearch] = useState('')

  useEffect(() => { loadUsers() }, [])

  const loadUsers = async () => {
    setLoading(true)
    try {
      const res = await client.get('/admin/users')
      setUsers(res.data)
    } finally {
      setLoading(false)
    }
  }

  const openAdd = () => { setEditUser(null); setForm(emptyForm); setShowModal(true) }
  const openEdit = (u) => { setEditUser(u); setForm({ name: u.name, email: u.email, role: u.role, password: '' }); setShowModal(true) }

  const handleSubmit = async (e) => {
    e.preventDefault()
    try {
      if (editUser) {
        await client.put(`/admin/users/${editUser.id}`, form)
        Swal.fire({ title: 'สำเร็จ!', text: 'แก้ไขผู้ใช้เรียบร้อย', icon: 'success', timer: 1500, showConfirmButton: false })
      } else {
        await client.post('/admin/users', form)
        Swal.fire({ title: 'สำเร็จ!', text: 'เพิ่มผู้ใช้เรียบร้อย', icon: 'success', timer: 1500, showConfirmButton: false })
      }
      setShowModal(false)
      loadUsers()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'เกิดข้อผิดพลาด', icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const handleDelete = async (user) => {
    const result = await Swal.fire({
      title: 'ยืนยันการลบ?',
      text: `ลบผู้ใช้ "${user.name}" ออกจากระบบ?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'ลบ',
      cancelButtonText: 'ยกเลิก',
    })
    if (!result.isConfirmed) return
    try {
      await client.delete(`/admin/users/${user.id}`)
      Swal.fire({ title: 'ลบสำเร็จ!', timer: 1500, showConfirmButton: false, icon: 'success' })
      loadUsers()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'ลบไม่สำเร็จ', icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const roleColors = { admin: 'bg-red-100 text-red-700', teacher: 'bg-purple-100 text-purple-700', student: 'bg-blue-100 text-blue-700' }
  const filtered = users.filter(u => u.name.toLowerCase().includes(search.toLowerCase()) || u.email.toLowerCase().includes(search.toLowerCase()))

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">จัดการผู้ใช้งาน</h1>
          <p className="text-gray-500 text-sm mt-1">ทั้งหมด {users.length} คน</p>
        </div>
        <div className="flex gap-3 w-full sm:w-auto">
          <input type="text" placeholder="ค้นหาชื่อ/อีเมล..." value={search} onChange={e => setSearch(e.target.value)}
            className="flex-1 sm:w-64 px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />
          <button onClick={openAdd}
            className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 transition-colors">
            <i className="fas fa-plus"></i> เพิ่มผู้ใช้
          </button>
        </div>
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-20">
            <i className="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-100">
              <thead className="bg-gray-50">
                <tr>
                  {['#', 'ชื่อ', 'อีเมล', 'บทบาท', 'รหัสนักศึกษา', 'วันที่สร้าง', 'จัดการ'].map(h => (
                    <th key={h} className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-50">
                {filtered.map((u, i) => (
                  <tr key={u.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4 text-sm text-gray-400">{i + 1}</td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs">
                          <i className="fas fa-user"></i>
                        </div>
                        <span className="font-medium text-gray-800 text-sm">{u.name}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">{u.email}</td>
                    <td className="px-6 py-4">
                      <span className={`text-xs font-semibold px-2 py-1 rounded-full ${roleColors[u.role] || 'bg-gray-100 text-gray-600'}`}>{u.role}</span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">{u.student_id || '-'}</td>
                    <td className="px-6 py-4 text-sm text-gray-400">{new Date(u.created_at).toLocaleDateString('th-TH')}</td>
                    <td className="px-6 py-4">
                      <div className="flex gap-2">
                        <button onClick={() => openEdit(u)} className="text-indigo-600 hover:text-indigo-800 p-1.5 hover:bg-indigo-50 rounded-lg transition-colors">
                          <i className="fas fa-edit"></i>
                        </button>
                        <button onClick={() => handleDelete(u)} className="text-red-500 hover:text-red-700 p-1.5 hover:bg-red-50 rounded-lg transition-colors">
                          <i className="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
                {filtered.length === 0 && (
                  <tr><td colSpan={7} className="text-center py-12 text-gray-400"><i className="fas fa-users text-3xl mb-2 block"></i>ไม่พบผู้ใช้</td></tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Modal */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="p-6 border-b border-gray-100">
              <h3 className="text-lg font-bold text-gray-800">{editUser ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่'}</h3>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                <input type="text" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} required
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                <input type="email" value={form.email} onChange={e => setForm({ ...form, email: e.target.value })} required
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">บทบาท</label>
                <select value={form.role} onChange={e => setForm({ ...form, role: e.target.value })}
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm bg-white">
                  <option value="student">นักศึกษา</option>
                  <option value="teacher">อาจารย์</option>
                  <option value="admin">ผู้ดูแลระบบ</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  รหัสผ่าน {editUser && <span className="text-gray-400 font-normal">(เว้นว่างถ้าไม่ต้องการเปลี่ยน)</span>}
                </label>
                <input type="password" value={form.password} onChange={e => setForm({ ...form, password: e.target.value })}
                  required={!editUser}
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm" />
              </div>
              <div className="flex gap-3 pt-2">
                <button type="button" onClick={() => setShowModal(false)}
                  className="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50 text-sm transition-colors">
                  ยกเลิก
                </button>
                <button type="submit"
                  className="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
                  {editUser ? 'บันทึก' : 'เพิ่มผู้ใช้'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
