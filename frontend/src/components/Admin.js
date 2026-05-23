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
const chartColors = ['#2563eb', '#0ea5e9', '#14b8a6', '#22c55e', '#f59e0b', '#f97316', '#ef4444', '#8b5cf6'];

const Admin = () => {
  const { authHeaders, isAuthenticated, login, logout, user } = useAuth();
  const [overview, setOverview] = useState(null);
  const [realtime, setRealtime] = useState(null);
  const [topMajors, setTopMajors] = useState([]);
  const [hotMajors, setHotMajors] = useState([]);
  const [questionsByIntent, setQuestionsByIntent] = useState([]);
  const [questionsByPeriod, setQuestionsByPeriod] = useState([]);
  const [provinceHeatmap, setProvinceHeatmap] = useState([]);
  const [admissionMethods, setAdmissionMethods] = useState([]);
  const [platforms, setPlatforms] = useState([]);
  const [trends, setTrends] = useState(null);
  const [loading, setLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState(null);
  const [loginForm, setLoginForm] = useState({ email: '', password: '' });
  const [loginLoading, setLoginLoading] = useState(false);
  const [loginError, setLoginError] = useState('');
  const [filters, setFilters] = useState({ from: '', to: '', period: 'day' });

  const buildParams = useCallback((extra = {}) => {
    const params = new URLSearchParams();

    if (filters.from) params.set('from', filters.from);
    if (filters.to) params.set('to', filters.to);

    Object.entries(extra).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params.set(key, value);
      }
    });

    const query = params.toString();
    return query ? `?${query}` : '';
  }, [filters]);

  const fetchDashboardData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      const [
        overviewRes,
        majorsRes,
        hotMajorsRes,
        intentRes,
        periodRes,
        provinceRes,
        methodsRes,
        platformsRes,
        trendsRes,
        realtimeRes,
      ] = await Promise.all([
        axios.get(`${API_URL}/dashboard/overview${buildParams()}`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/top-majors${buildParams({ limit: 10 })}`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/hot-majors${buildParams({ limit: 10 })}`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/questions-by-intent${buildParams()}`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/questions-by-period${buildParams({ period: filters.period })}`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/province-heatmap${buildParams()}`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/admission-methods${buildParams()}`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/platforms${buildParams()}`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/trends${buildParams()}`, { headers: authHeaders }),
        axios.get(`${API_URL}/dashboard/realtime?minutes=60`, { headers: authHeaders }),
      ]);

      setOverview(overviewRes.data);
      setTopMajors(majorsRes.data || []);
      setHotMajors(hotMajorsRes.data || []);
      setQuestionsByIntent(intentRes.data || []);
      setQuestionsByPeriod(periodRes.data || []);
      setProvinceHeatmap(provinceRes.data || []);
      setAdmissionMethods(methodsRes.data || []);
      setPlatforms(platformsRes.data || []);
      setTrends(trendsRes.data || null);
      setRealtime(realtimeRes.data || null);
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
  }, [authHeaders, buildParams, filters.period, logout]);

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

  const handleExport = async () => {
    try {
      setExporting(true);
      setError(null);

      const response = await axios.get(`${API_URL}/dashboard/export${buildParams({ limit: 5000 })}`, {
        headers: authHeaders,
        responseType: 'blob',
      });
      const blobUrl = window.URL.createObjectURL(new Blob([response.data], { type: 'text/csv;charset=utf-8' }));
      const disposition = response.headers?.['content-disposition'] || '';
      const filenameMatch = disposition.match(/filename="?([^";]+)"?/i);
      const link = document.createElement('a');

      link.href = blobUrl;
      link.download = filenameMatch?.[1] || 'tuyen-sinh-report.csv';
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(blobUrl);
    } catch (err) {
      console.error('Export failed:', err);
      setError('Không thể xuất báo cáo CSV. Vui lòng thử lại.');
    } finally {
      setExporting(false);
    }
  };

  const updateFilter = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
  };

  const renderHeader = () => (
    <div className="admin-header">
      <h1>Admin Dashboard</h1>
      <div className="admin-actions">
        {user?.email && <span>{user.email}</span>}
        <button className="refresh-btn" type="button" onClick={fetchDashboardData}>
          Làm mới
        </button>
        <button className="secondary-btn" type="button" onClick={handleExport} disabled={exporting}>
          {exporting ? 'Đang xuất...' : 'Xuất CSV'}
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
              onChange={(event) => setLoginForm((prev) => ({ ...prev, email: event.target.value }))}
              autoComplete="email"
              required
            />
          </label>
          <label>
            Mật khẩu
            <input
              type="password"
              value={loginForm.password}
              onChange={(event) => setLoginForm((prev) => ({ ...prev, password: event.target.value }))}
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

  const overviewChartData = {
    labels: ['Tổng câu hỏi', 'Hôm nay', 'Phiên hoạt động', 'Realtime 60 phút'],
    datasets: [
      {
        label: 'Giá trị',
        data: [
          overview?.total_questions || 0,
          overview?.today_questions || 0,
          overview?.active_sessions || 0,
          realtime?.total_questions || 0,
        ],
        backgroundColor: chartColors.slice(0, 4),
        borderRadius: 8,
      },
    ],
  };

  const topMajorsData = {
    labels: topMajors.map((item) => item.major_name || 'Không xác định'),
    datasets: [
      {
        label: 'Số câu hỏi',
        data: topMajors.map((item) => item.total || 0),
        backgroundColor: '#2563eb',
        borderColor: '#1d4ed8',
        borderWidth: 1,
      },
    ],
  };

  const intentData = {
    labels: questionsByIntent.map((item) => item.intent || 'Unknown'),
    datasets: [
      {
        data: questionsByIntent.map((item) => item.total || 0),
        backgroundColor: chartColors,
        borderColor: '#ffffff',
        borderWidth: 2,
      },
    ],
  };

  const periodData = {
    labels: questionsByPeriod.map((item) => item.date || item.period || 'N/A'),
    datasets: [
      {
        label: 'Câu hỏi',
        data: questionsByPeriod.map((item) => item.total || 0),
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

  const hotMajorData = {
    labels: hotMajors.map((item) => item.major_name || 'Không xác định'),
    datasets: [
      {
        label: 'Tổng câu hỏi',
        data: hotMajors.map((item) => item.total || 0),
        backgroundColor: '#f97316',
        borderRadius: 8,
      },
      {
        label: 'Quan tâm điểm chuẩn',
        data: hotMajors.map((item) => item.score_interest || 0),
        backgroundColor: '#2563eb',
        borderRadius: 8,
      },
      {
        label: 'Tư vấn ngành',
        data: hotMajors.map((item) => item.consulting_interest || 0),
        backgroundColor: '#22c55e',
        borderRadius: 8,
      },
    ],
  };

  const methodData = {
    labels: admissionMethods.map((item) => item.admission_method || 'Khác'),
    datasets: [
      {
        data: admissionMethods.map((item) => item.total || 0),
        backgroundColor: chartColors,
        borderColor: '#ffffff',
        borderWidth: 2,
      },
    ],
  };

  const platformData = {
    labels: platforms.map((item) => item.platform || 'unknown'),
    datasets: [
      {
        label: 'Câu hỏi',
        data: platforms.map((item) => item.total || 0),
        backgroundColor: '#0f766e',
        borderRadius: 8,
      },
      {
        label: 'Phiên',
        data: platforms.map((item) => item.sessions || 0),
        backgroundColor: '#38bdf8',
        borderRadius: 8,
      },
    ],
  };

  const provinceData = {
    labels: provinceHeatmap.slice(0, 10).map((item) => item.province || 'Không xác định'),
    datasets: [
      {
        label: 'Câu hỏi',
        data: provinceHeatmap.slice(0, 10).map((item) => item.total || 0),
        backgroundColor: '#16a34a',
        borderColor: '#15803d',
        borderWidth: 1,
      },
    ],
  };

  return (
    <div className="admin-page">
      {renderHeader()}

      <div className="dashboard-filters">
        <label>
          Từ ngày
          <input type="date" value={filters.from} onChange={(event) => updateFilter('from', event.target.value)} />
        </label>
        <label>
          Đến ngày
          <input type="date" value={filters.to} onChange={(event) => updateFilter('to', event.target.value)} />
        </label>
        <label>
          Nhóm thời gian
          <select value={filters.period} onChange={(event) => updateFilter('period', event.target.value)}>
            <option value="day">Ngày</option>
            <option value="week">Tuần</option>
            <option value="month">Tháng</option>
          </select>
        </label>
        <button className="secondary-btn" type="button" onClick={() => setFilters({ from: '', to: '', period: 'day' })}>
          Xóa lọc
        </button>
      </div>

      {error && <div className="error-message">{error}</div>}

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
          <div className="stat-card">
            <div className="stat-icon">R</div>
            <div className="stat-content">
              <div className="stat-label">Realtime 60 phút</div>
              <div className="stat-value">{realtime?.total_questions || 0}</div>
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
          <Bar data={topMajorsData} options={{ indexAxis: 'y', maintainAspectRatio: true }} />
        </div>

        <div className="chart-card">
          <h3>Phân bổ intent</h3>
          <Pie data={intentData} options={{ maintainAspectRatio: true }} />
        </div>

        <div className="chart-card">
          <h3>Câu hỏi theo thời gian</h3>
          <Line data={periodData} options={{ maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }} />
        </div>

        <div className="chart-card">
          <h3>Ngành đang được quan tâm</h3>
          <Bar data={hotMajorData} options={{ indexAxis: 'y', maintainAspectRatio: true, scales: { x: { beginAtZero: true } } }} />
        </div>

        <div className="chart-card">
          <h3>Phương thức xét tuyển</h3>
          <Pie data={methodData} options={{ maintainAspectRatio: true }} />
        </div>

        <div className="chart-card">
          <h3>Nền tảng truy cập</h3>
          <Bar data={platformData} options={{ maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }} />
        </div>

        <div className="chart-card">
          <h3>Tỉnh/thành quan tâm</h3>
          <Bar data={provinceData} options={{ indexAxis: 'y', maintainAspectRatio: true, scales: { x: { beginAtZero: true } } }} />
        </div>
      </div>

      <div className="dashboard-detail-grid">
        <div className="detail-card">
          <h3>Hoạt động realtime</h3>
          <div className="realtime-summary">
            <span>{realtime?.active_sessions || 0} phiên hoạt động</span>
            <span>Cập nhật: {realtime?.updated_at ? new Date(realtime.updated_at).toLocaleTimeString('vi-VN') : 'N/A'}</span>
          </div>
          <div className="data-table-wrap">
            <table className="data-table">
              <thead>
                <tr>
                  <th>Thời gian</th>
                  <th>Nền tảng</th>
                  <th>Intent</th>
                  <th>Ngành</th>
                </tr>
              </thead>
              <tbody>
                {(realtime?.latest_logs || []).map((log) => (
                  <tr key={log.id}>
                    <td>{log.created_at ? new Date(log.created_at).toLocaleString('vi-VN') : 'N/A'}</td>
                    <td>{log.platform || 'N/A'}</td>
                    <td>{log.intent || 'N/A'}</td>
                    <td>{log.major_name || 'N/A'}</td>
                  </tr>
                ))}
                {(!realtime?.latest_logs || realtime.latest_logs.length === 0) && (
                  <tr>
                    <td colSpan="4">Chưa có dữ liệu realtime.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        <div className="detail-card">
          <h3>Tổng hợp xu hướng</h3>
          <div className="trend-list">
            <div>
              <span>Khoảng thời gian</span>
              <strong>{trends?.period?.from || filters.from || 'N/A'} - {trends?.period?.to || filters.to || 'N/A'}</strong>
            </div>
            <div>
              <span>Intent nổi bật</span>
              <strong>{trends?.intents?.[0]?.intent || overview?.top_intent?.intent || 'N/A'}</strong>
            </div>
            <div>
              <span>Ngành nổi bật</span>
              <strong>{trends?.hot_majors?.[0]?.major_name || topMajors?.[0]?.major_name || 'N/A'}</strong>
            </div>
            <div>
              <span>Phương thức nổi bật</span>
              <strong>{trends?.admission_methods?.[0]?.admission_method || admissionMethods?.[0]?.admission_method || 'N/A'}</strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Admin;
