import React, { useCallback, useEffect, useState } from 'react';
import axios from 'axios';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
} from 'chart.js';
import { Line, Bar, Pie } from 'react-chartjs-2';
import { useAuth } from '../contexts/AuthContext';
import './Admin.css';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend
);

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';

const Admin = () => {
  const { authHeaders, isAuthenticated, login, logout, user } = useAuth();
  const [overview, setOverview] = useState(null);
  const [topMajors, setTopMajors] = useState([]);
  const [questionsByIntent, setQuestionsByIntent] = useState([]);
  const [questionsByDay, setQuestionsByDay] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [loginForm, setLoginForm] = useState({ email: '', password: '' });
  const [loginLoading, setLoginLoading] = useState(false);
  const [loginError, setLoginError] = useState('');

  const fetchDashboardData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      const [overviewRes, majorsRes, intentRes, dayRes] = await Promise.all([
        axios.get(`${API_URL}/dashboard/overview`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/top-majors?limit=10`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/questions-by-intent`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/questions-by-day`, { headers: authHeaders }),
      ]);

      setOverview(overviewRes.data);
      setTopMajors(majorsRes.data || []);
      setQuestionsByIntent(intentRes.data || []);
      setQuestionsByDay(dayRes.data || []);
    } catch (err) {
      console.error('Dashboard load failed:', err);

      if (err?.response?.status === 401) {
        setError('Token hết hạn hoặc không hợp lệ. Vui lòng đăng nhập lại.');
        await logout();
      } else {
        setError('Không thể tải dữ liệu dashboard. Kiểm tra kết nối API và thử lại.');
      }
    } finally {
      setLoading(false);
    }
  }, [authHeaders, logout]);

  useEffect(() => {
    if (isAuthenticated) {
      fetchDashboardData();
    }
  }, [fetchDashboardData, isAuthenticated]);

  const handleLogin = async (event) => {
    event.preventDefault();
    setLoginLoading(true);
    setLoginError('');

    try {
      await login(loginForm);
    } catch (err) {
      setLoginError(
        err?.response?.status === 422 || err?.response?.status === 401
          ? 'Email hoặc mật khẩu không đúng.'
          : 'Không thể kết nối API đăng nhập.'
      );
    } finally {
      setLoginLoading(false);
    }
  };

  const renderHeader = () => (
    <div className="admin-header">
      <h1>Admin Dashboard</h1>
      <div className="admin-actions">
        {user?.email && <span>{user.email}</span>}
        <button className="refresh-btn" type="button" onClick={fetchDashboardData}>
          Làm mới
        </button>
        <button className="secondary-btn" type="button" onClick={logout}>
          Đăng xuất
        </button>
      </div>
    </div>
  );

  if (!isAuthenticated) {
    return (
      <div className="admin-page">
        <form className="admin-login" onSubmit={handleLogin}>
          <h1>Đăng nhập Admin</h1>
          <label>
            Email
            <input
              type="email"
              value={loginForm.email}
              onChange={(event) =>
                setLoginForm((prev) => ({ ...prev, email: event.target.value }))
              }
              autoComplete="email"
              required
            />
          </label>
          <label>
            Mật khẩu
            <input
              type="password"
              value={loginForm.password}
              onChange={(event) =>
                setLoginForm((prev) => ({ ...prev, password: event.target.value }))
              }
              autoComplete="current-password"
              required
            />
          </label>
          {loginError && <div className="error-message compact">{loginError}</div>}
          <button className="refresh-btn" type="submit" disabled={loginLoading}>
            {loginLoading ? 'Đang đăng nhập...' : 'Đăng nhập'}
          </button>
        </form>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="admin-page">
        {renderHeader()}
        <div className="dashboard-skeleton">
          <span></span>
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="admin-page">
        {renderHeader()}
        <div className="error-message">{error}</div>
      </div>
    );
  }

  const overviewChartData = {
    labels: ['Tổng câu hỏi', 'Hôm nay', 'Phiên hoạt động', 'Thời gian TB'],
    datasets: [
      {
        label: 'Giá trị',
        data: [
          overview?.total_questions || 0,
          overview?.today_questions || 0,
          overview?.active_sessions || 0,
          (overview?.average_response_time || 0) * 100,
        ],
        backgroundColor: ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd'],
        borderRadius: 8,
      },
    ],
  };

  const topMajorsData = {
    labels: topMajors.map((m) => m.major_name || 'Không xác định'),
    datasets: [
      {
        label: 'Số câu hỏi',
        data: topMajors.map((m) => m.total || 0),
        backgroundColor: '#2563eb',
        borderColor: '#1d4ed8',
        borderWidth: 1,
      },
    ],
  };

  const intentData = {
    labels: questionsByIntent.map((q) => q.intent || 'Unknown'),
    datasets: [
      {
        data: questionsByIntent.map((q) => q.total || 0),
        backgroundColor: [
          '#2563eb',
          '#3b82f6',
          '#60a5fa',
          '#93c5fd',
          '#dbeafe',
          '#bfdbfe',
          '#7dd3fc',
          '#38bdf8',
          '#0ea5e9',
          '#0284c7',
        ],
        borderColor: '#ffffff',
        borderWidth: 2,
      },
    ],
  };

  const dayData = {
    labels: questionsByDay.map((d) => d.date || d.period || 'N/A'),
    datasets: [
      {
        label: 'Câu hỏi theo ngày',
        data: questionsByDay.map((d) => d.total || 0),
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37, 99, 235, 0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        pointRadius: 5,
        pointBackgroundColor: '#2563eb',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
      },
    ],
  };

  return (
    <div className="admin-page">
      {renderHeader()}

      {overview && (
        <div className="stats-overview">
          <div className="stat-card">
            <div className="stat-icon">Q</div>
            <div className="stat-content">
              <div className="stat-label">Tổng câu hỏi</div>
              <div className="stat-value">{overview.total_questions || 0}</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">D</div>
            <div className="stat-content">
              <div className="stat-label">Hôm nay</div>
              <div className="stat-value">{overview.today_questions || 0}</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">S</div>
            <div className="stat-content">
              <div className="stat-label">Phiên hoạt động</div>
              <div className="stat-value">{overview.active_sessions || 0}</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">T</div>
            <div className="stat-content">
              <div className="stat-label">Thời gian TB (s)</div>
              <div className="stat-value">{(overview.average_response_time || 0).toFixed(2)}</div>
            </div>
          </div>
        </div>
      )}

      <div className="charts-grid">
        <div className="chart-card">
          <h3>Thống kê tổng quan</h3>
          <Bar data={overviewChartData} options={{ maintainAspectRatio: true }} />
        </div>

        <div className="chart-card">
          <h3>Top ngành</h3>
          <Bar
            data={topMajorsData}
            options={{
              indexAxis: 'y',
              maintainAspectRatio: true,
            }}
          />
        </div>

        <div className="chart-card">
          <h3>Phân bổ intent</h3>
          <Pie data={intentData} options={{ maintainAspectRatio: true }} />
        </div>

        <div className="chart-card">
          <h3>Câu hỏi theo ngày</h3>
          <Line
            data={dayData}
            options={{
              maintainAspectRatio: true,
              scales: {
                y: {
                  beginAtZero: true,
                },
              },
            }}
          />
        </div>
      </div>
    </div>
  );
};

export default Admin;
