import { useState, useEffect } from 'react'
import { NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import client from '../api/client'

export default function Navbar() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [userDropOpen, setUserDropOpen] = useState(false)
  const [notiDropOpen, setNotiDropOpen] = useState(false)
  const [notifications, setNotifications] = useState([])
  const [unreadCount, setUnreadCount] = useState(0)

  useEffect(() => {
    if (user) fetchNotifications()
    const interval = setInterval(() => { if (user) fetchNotifications() }, 30000)
    return () => clearInterval(interval)
  }, [user])

  const fetchNotifications = async () => {
    try {
      const res = await client.get('/notifications')
      setNotifications(res.data.notifications || [])
      setUnreadCount(res.data.unread_count || 0)
    } catch {}
  }

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const markRead = async (id) => {
    await client.put(`/notifications/${id}/read`)
    fetchNotifications()
  }

  const menus = {
    admin: [
      { path: '/admin/dashboard', label: 'หน้าหลัก', icon: 'fas fa-home' },
      { path: '/admin/users', label: 'จัดการผู้ใช้', icon: 'fas fa-users-cog' },
      { path: '/admin/recruitments', label: 'จัดการประกาศ', icon: 'fas fa-bullhorn' },
      { path: '/admin/subjects', label: 'เพิ่มรายวิชา', icon: 'fas fa-book-open' },
      { path: '/admin/reports', label: 'รายงานสถิติ', icon: 'fas fa-chart-line' },
    ],
    teacher: [
      { path: '/teacher/dashboard', label: 'หน้าหลัก', icon: 'fas fa-home' },
      { path: '/teacher/recruitments', label: 'ประกาศรับสมัคร', icon: 'fas fa-bullhorn' },
      { path: '/teacher/applicants', label: 'ผู้สมัคร', icon: 'fas fa-user-friends' },
      { path: '/teacher/tas', label: 'ผู้ช่วยสอนของฉัน', icon: 'fas fa-chalkboard-teacher' },
    ],
    student: [
      { path: '/student/dashboard', label: 'หน้าหลัก', icon: 'fas fa-home' },
      { path: '/student/recruitments', label: 'ประกาศทั้งหมด', icon: 'fas fa-newspaper' },
      { path: '/student/applications', label: 'ประวัติการสมัคร', icon: 'fas fa-history' },
      { path: '/student/profile', label: 'ข้อมูลส่วนตัว', icon: 'fas fa-user-edit' },
    ],
  }

  const currentMenus = menus[user?.role] || []

  const linkClass = ({ isActive }) =>
    isActive
      ? 'px-4 py-2 rounded-xl text-sm font-semibold text-indigo-700 bg-indigo-50 transition-all shadow-sm'
      : 'px-4 py-2 rounded-xl text-sm font-medium text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-all'

  return (
    <nav className="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">

          {/* Logo + Desktop Menu */}
          <div className="flex items-center gap-8">
            <NavLink to="/" className="flex-shrink-0 flex items-center gap-2 group">
              <div className="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-md">
                <i className="fas fa-graduation-cap"></i>
              </div>
              <span className="font-bold text-gray-800 text-lg tracking-tight">TA System</span>
            </NavLink>

            <div className="hidden md:flex md:space-x-2">
              {currentMenus.map(menu => (
                <NavLink key={menu.path} to={menu.path} className={linkClass}>
                  <i className={`${menu.icon} mr-1.5 opacity-70`}></i>
                  {menu.label}
                </NavLink>
              ))}
            </div>
          </div>

          {/* Right: Notifications + User */}
          <div className="hidden md:flex md:items-center gap-2">

            {/* Notification Bell */}
            <div className="relative">
              <button
                onClick={() => { setNotiDropOpen(!notiDropOpen); setUserDropOpen(false) }}
                className="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors focus:outline-none"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                {unreadCount > 0 && (
                  <span className="absolute top-1 right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold text-white bg-red-600 rounded-full border-2 border-white">
                    {unreadCount}
                  </span>
                )}
              </button>

              {notiDropOpen && (
                <div className="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl py-2 ring-1 ring-black ring-opacity-5 z-50">
                  <div className="px-4 py-3 border-b border-gray-50 flex justify-between items-center">
                    <span className="text-sm font-semibold text-gray-700">การแจ้งเตือน</span>
                    {unreadCount > 0 && (
                      <span className="bg-indigo-100 text-indigo-700 text-xs px-2 py-0.5 rounded-full">{unreadCount} ใหม่</span>
                    )}
                  </div>
                  <div className="max-h-80 overflow-y-auto">
                    {notifications.length > 0 ? notifications.map(n => (
                      <button key={n.id} onClick={() => markRead(n.id)}
                        className="w-full text-left block px-4 py-3 hover:bg-gray-50 transition border-b border-gray-50 last:border-0 relative">
                        {n.is_read === 0 && <span className="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500 rounded-l"></span>}
                        <p className={`text-sm font-semibold mb-0.5 ${n.is_read === 0 ? 'text-indigo-700' : 'text-gray-800'}`}>
                          {n.title || 'แจ้งเตือน'}
                        </p>
                        <p className="text-xs text-gray-500 line-clamp-2">{n.message}</p>
                        <p className="text-[10px] text-gray-400 mt-1 text-right">{n.created_at}</p>
                      </button>
                    )) : (
                      <div className="px-4 py-8 text-center">
                        <div className="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-300">
                          <i className="fas fa-bell-slash"></i>
                        </div>
                        <p className="text-sm text-gray-500">ไม่มีการแจ้งเตือนใหม่</p>
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* User Dropdown */}
            <div className="relative">
              <button
                onClick={() => { setUserDropOpen(!userDropOpen); setNotiDropOpen(false) }}
                className="flex items-center gap-3 bg-white rounded-full pl-1 pr-3 py-1 hover:bg-gray-50 border border-transparent hover:border-gray-200 transition-all focus:outline-none"
              >
                <div className="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-sm">
                  <i className="fas fa-user text-sm"></i>
                </div>
                <div className="flex flex-col items-start text-left">
                  <span className="text-sm font-semibold text-gray-700 leading-none">{user?.name}</span>
                  <span className="text-[10px] text-gray-400 font-medium uppercase tracking-wider mt-0.5">{user?.role}</span>
                </div>
                <i className={`fas fa-chevron-down text-gray-300 text-xs transition-transform duration-200 ${userDropOpen ? 'rotate-180' : ''}`}></i>
              </button>

              {userDropOpen && (
                <div className="origin-top-right absolute right-0 mt-2 w-56 rounded-2xl shadow-xl py-2 bg-white ring-1 ring-black ring-opacity-5 z-50">
                  <div className="px-5 py-3 border-b border-gray-50 mb-1">
                    <p className="text-xs text-gray-400">สถานะบัญชี</p>
                    <div className="flex items-center gap-2 mt-1">
                      <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                      <span className="text-sm font-medium text-green-600">ออนไลน์</span>
                    </div>
                  </div>
                  <NavLink to="/change-password" onClick={() => setUserDropOpen(false)}
                    className="flex items-center px-5 py-2.5 text-sm text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                    <i className="fas fa-key w-6 opacity-70"></i> เปลี่ยนรหัสผ่าน
                  </NavLink>
                  <div className="border-t border-gray-50 my-1"></div>
                  <button onClick={handleLogout}
                    className="w-full flex items-center px-5 py-2.5 text-sm text-red-500 hover:bg-red-50 hover:text-red-700 transition-colors rounded-b-2xl">
                    <i className="fas fa-sign-out-alt w-6 opacity-70"></i> ออกจากระบบ
                  </button>
                </div>
              )}
            </div>
          </div>

          {/* Mobile Menu Button */}
          <div className="-mr-2 flex items-center md:hidden">
            <button onClick={() => setMobileOpen(!mobileOpen)}
              className="p-2 rounded-xl text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 focus:outline-none">
              <i className="fas fa-bars text-xl"></i>
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Menu */}
      {mobileOpen && (
        <div className="md:hidden bg-white border-t border-gray-100">
          <div className="pt-3 pb-3 space-y-1 px-4">
            {currentMenus.map(menu => (
              <NavLink key={menu.path} to={menu.path}
                onClick={() => setMobileOpen(false)}
                className={({ isActive }) => isActive
                  ? 'block px-3 py-2 rounded-xl text-base font-medium text-indigo-700 bg-indigo-50 border-l-4 border-indigo-600'
                  : 'block px-3 py-2 rounded-xl text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50'
                }>
                <i className={`${menu.icon} mr-3 w-5 text-center`}></i>
                {menu.label}
              </NavLink>
            ))}
          </div>
          <div className="pt-4 pb-4 border-t border-gray-100 bg-gray-50/50">
            <div className="flex items-center px-5">
              <div className="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                <i className="fas fa-user"></i>
              </div>
              <div className="ml-3">
                <div className="text-base font-semibold text-gray-800">{user?.name}</div>
                <div className="text-sm text-gray-500 capitalize">{user?.role}</div>
              </div>
            </div>
            <div className="mt-4 space-y-1 px-2">
              <NavLink to="/change-password" onClick={() => setMobileOpen(false)}
                className="block px-3 py-2 rounded-xl text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-white">
                <i className="fas fa-key mr-2 opacity-70"></i> เปลี่ยนรหัสผ่าน
              </NavLink>
              <button onClick={handleLogout}
                className="w-full text-left block px-3 py-2 rounded-xl text-base font-medium text-red-500 hover:text-red-700 hover:bg-red-50">
                <i className="fas fa-sign-out-alt mr-2 opacity-70"></i> ออกจากระบบ
              </button>
            </div>
          </div>
        </div>
      )}
    </nav>
  )
}
