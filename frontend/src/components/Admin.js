import React, { useState, useEffect } from 'react';
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
import { Line, Bar, Pie, Doughnut } from 'react-chartjs-2';
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
  const [overview, setOverview] = useState(null);
  const [topMajors, setTopMajors] = useState([]);
  const [questionsByIntent, setQuestionsByIntent] = useState([]);
  const [questionsByDay, setQuestionsByDay] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      setError(null);

      const [overviewRes, majorsRes, intentRes, dayRes] = await Promise.all([
        axios.get(`${API_URL}/dashboard/overview`),
        axios.get(`${API_URL}/dashboard/top-majors?limit=10`),
        axios.get(`${API_URL}/dashboard/questions-by-intent`),
        axios.get(`${API_URL}/dashboard/questions-by-day`),
      ]);

      setOverview(overviewRes.data);
      setTopMajors(majorsRes.data || []);
      setQuestionsByIntent(intentRes.data || []);
      setQuestionsByDay(dayRes.data || []);
    } catch (err) {
      console.error('Lỗi khi tải dữ liệu dashboard:', err);
      setError(err.message || 'Không thể tải dữ liệu');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="admin-page">
        <div className="admin-header">
          <h1>📊 Admin Dashboard</h1>
          <button className="refresh-btn" onClick={fetchDashboardData}>
            ↻ Làm mới
          </button>
        </div>
        <div className="loading">Đang tải dữ liệu...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="admin-page">
        <div className="admin-header">
          <h1>📊 Admin Dashboard</h1>
          <button className="refresh-btn" onClick={fetchDashboardData}>
            ↻ Làm mới
          </button>
        </div>
        <div className="error-message">Lỗi: {error}</div>
      </div>
    );
  }

  // Chart 1: Overview Stats
  const overviewChartData = {
    labels: ['Tổng câu hỏi', 'Hôm nay', 'Phiên hoạt động', 'Thời gian TB'],
    datasets: [
      {
        label: 'Giá trị',
        data: [
          overview?.total_questions || 0,
          overview?.today_questions || 0,
          overview?.active_sessions || 0,
          (overview?.average_response_time || 0) * 100, // Scale for visibility
        ],
        backgroundColor: ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd'],
        borderRadius: 8,
      },
    ],
  };

  // Chart 2: Top Majors
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

  // Chart 3: Questions by Intent (Pie)
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

  // Chart 4: Questions by Day (Line)
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
      <div className="admin-header">
        <h1>📊 Admin Dashboard</h1>
        <button className="refresh-btn" onClick={fetchDashboardData}>
          ↻ Làm mới
        </button>
      </div>

      {overview && (
        <div className="stats-overview">
          <div className="stat-card">
            <div className="stat-icon">📝</div>
            <div className="stat-content">
              <div className="stat-label">Tổng câu hỏi</div>
              <div className="stat-value">{overview.total_questions || 0}</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">📅</div>
            <div className="stat-content">
              <div className="stat-label">Hôm nay</div>
              <div className="stat-value">{overview.today_questions || 0}</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">👥</div>
            <div className="stat-content">
              <div className="stat-label">Phiên hoạt động</div>
              <div className="stat-value">{overview.active_sessions || 0}</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">⏱️</div>
            <div className="stat-content">
              <div className="stat-label">Thời gian TB (s)</div>
              <div className="stat-value">{(overview.average_response_time || 0).toFixed(2)}</div>
            </div>
          </div>
        </div>
      )}

      <div className="charts-grid">
        <div className="chart-card">
          <h3>📊 Thống kê Overview</h3>
          <Bar data={overviewChartData} options={{ maintainAspectRatio: true }} />
        </div>

        <div className="chart-card">
          <h3>🎓 Top Ngành (10 nhiều nhất)</h3>
          <Bar
            data={topMajorsData}
            options={{
              indexAxis: 'y',
              maintainAspectRatio: true,
            }}
          />
        </div>

        <div className="chart-card">
          <h3>❓ Phân bố Intent</h3>
          <Pie data={intentData} options={{ maintainAspectRatio: true }} />
        </div>

        <div className="chart-card">
          <h3>📈 Câu hỏi theo ngày</h3>
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
