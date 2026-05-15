import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from './context/AuthContext'
import Navbar from './components/Navbar'
import ProtectedRoute from './components/ProtectedRoute'

import Login from './pages/auth/Login'
import Register from './pages/auth/Register'
import ChangePassword from './pages/auth/ChangePassword'

import AdminDashboard from './pages/admin/AdminDashboard'
import ManageUsers from './pages/admin/ManageUsers'
import ManageSubjects from './pages/admin/ManageSubjects'
import ManageRecruitments from './pages/admin/ManageRecruitments'
import Reports from './pages/admin/Reports'

import TeacherDashboard from './pages/teacher/TeacherDashboard'
import MyRecruitments from './pages/teacher/MyRecruitments'
import ViewApplicants from './pages/teacher/ViewApplicants'
import MyTAs from './pages/teacher/MyTAs'

import StudentDashboard from './pages/student/StudentDashboard'
import RecruitmentList from './pages/student/RecruitmentList'
import MyApplications from './pages/student/MyApplications'
import Profile from './pages/student/Profile'

function Layout({ children }) {
  return (
    <div className="min-h-screen bg-gray-50 font-sans text-gray-800">
      <Navbar />
      <main>{children}</main>
    </div>
  )
}

function HomeRedirect() {
  const { user, loading } = useAuth()
  if (loading) return null
  if (!user) return <Navigate to="/login" replace />
  if (user.role === 'admin') return <Navigate to="/admin/dashboard" replace />
  if (user.role === 'teacher') return <Navigate to="/teacher/dashboard" replace />
  return <Navigate to="/student/dashboard" replace />
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomeRedirect />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />

        <Route path="/change-password" element={
          <ProtectedRoute>
            <Layout><ChangePassword /></Layout>
          </ProtectedRoute>
        } />

        {/* Admin */}
        <Route path="/admin/dashboard" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <Layout><AdminDashboard /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/admin/users" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <Layout><ManageUsers /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/admin/subjects" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <Layout><ManageSubjects /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/admin/recruitments" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <Layout><ManageRecruitments /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/admin/reports" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <Layout><Reports /></Layout>
          </ProtectedRoute>
        } />

        {/* Teacher */}
        <Route path="/teacher/dashboard" element={
          <ProtectedRoute allowedRoles={['teacher']}>
            <Layout><TeacherDashboard /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/teacher/recruitments" element={
          <ProtectedRoute allowedRoles={['teacher']}>
            <Layout><MyRecruitments /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/teacher/applicants" element={
          <ProtectedRoute allowedRoles={['teacher']}>
            <Layout><ViewApplicants /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/teacher/tas" element={
          <ProtectedRoute allowedRoles={['teacher']}>
            <Layout><MyTAs /></Layout>
          </ProtectedRoute>
        } />

        {/* Student */}
        <Route path="/student/dashboard" element={
          <ProtectedRoute allowedRoles={['student']}>
            <Layout><StudentDashboard /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/student/recruitments" element={
          <ProtectedRoute allowedRoles={['student']}>
            <Layout><RecruitmentList /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/student/applications" element={
          <ProtectedRoute allowedRoles={['student']}>
            <Layout><MyApplications /></Layout>
          </ProtectedRoute>
        } />
        <Route path="/student/profile" element={
          <ProtectedRoute allowedRoles={['student']}>
            <Layout><Profile /></Layout>
          </ProtectedRoute>
        } />

        <Route path="*" element={
          <div className="flex items-center justify-center min-h-screen bg-gray-50">
            <div className="text-center">
              <h1 className="text-6xl font-bold text-indigo-600 mb-4">404</h1>
              <p className="text-gray-500 mb-6">ไม่พบหน้าที่คุณต้องการ</p>
              <a href="/" className="bg-indigo-600 text-white px-6 py-3 rounded-xl hover:bg-indigo-700 transition-colors">
                กลับหน้าหลัก
              </a>
            </div>
          </div>
        } />
      </Routes>
    </BrowserRouter>
  )
}
